// js/supabase.js

const SUPABASE_URL = 'https://kwwszbvcwchujbyvswqp.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt3d3N6YnZjd2NodWpieXZzd3FwIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDMyMTIyNDcsImV4cCI6MjA1ODc4ODI0N30.3HBMf8zoj-8fzjZOD-R3Oh86jTtGg807Oj7Be1VjFEw'; // Замените на ваш реальный публичный ключ
export const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

/**
 * Получение id пользователя.
 * Если приложение запущено внутри Telegram – используется id пользователя Telegram.
 * Иначе берётся значение из localStorage или генерируется новый UUID.
 */
export async function getOrCreateUser() {
  let userId = null;
  
  if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initDataUnsafe && window.Telegram.WebApp.initDataUnsafe.user) {
    const tgUser = window.Telegram.WebApp.initDataUnsafe.user;
    userId = tgUser.id.toString();
    localStorage.setItem('userId', userId);
  } else {
    userId = localStorage.getItem('userId');
    if (!userId) {
      userId = crypto.randomUUID();
      localStorage.setItem('userId', userId);
    }
  }
  
  let { data, error } = await supabase
    .from('users')
    .select('*')
    .eq('id', userId)
    .single();
  if (error && error.code !== 'PGRST116') {
    throw error;
  }
  if (!data) {
    const tgData = window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initDataUnsafe?.user;
    const newUser = {
      id: userId,
      first_name: tgData?.first_name || 'Пользователь',
      last_name: tgData?.last_name || '',
      username: tgData?.username || '',
      role: 'customer',
      balance: 0, // начальный баланс
      created_at: new Date().toISOString()
    };
    let { error: insertError } = await supabase
      .from('users')
      .insert([newUser]);
    if (insertError) throw insertError;
    return newUser;
  }
  return data;
}

/**
 * Обновление роли пользователя.
 */
export async function updateUserRole(userId, role) {
  const { error } = await supabase
    .from('users')
    .update({ role })
    .eq('id', userId);
  if (error) throw error;
  return true;
}

/**
 * Обновление баланса пользователя.
 */
export async function updateUserBalance(userId, newBalance) {
  const { error } = await supabase
    .from('users')
    .update({ balance: newBalance })
    .eq('id', userId);
  if (error) throw error;
  return true;
}

/**
 * Загрузка статей (ожидается, что статьи содержат поле content с полным текстом).
 */
export async function loadArticles() {
  const { data, error } = await supabase
    .from('articles')
    .select('*')
    .order('created_at', { ascending: false });
  if (error) throw error;
  return data;
}

/**
 * Загрузка шаблонов (одобренных).
 * filters: { category, price }.
 */
export async function loadTemplates(filters = {}) {
  let query = supabase
    .from('templates')
    .select('*')
    .eq('status', 'approved')
    .order('created_at', { ascending: false });
  if (filters.category && filters.category !== 'all') {
    query = query.eq('category', filters.category);
  }
  if (filters.price && filters.price !== 'all') {
    const [min, max] = filters.price.split('-');
    if (max) {
      query = query.gte('price', parseFloat(min)).lte('price', parseFloat(max));
    } else {
      query = query.gte('price', parseFloat(min.replace('+', '')));
    }
  }
  const { data, error } = await query;
  if (error) throw error;
  return data;
}

/**
 * Загрузка заявок для заказчика.
 */
export async function loadCustomerRequests(userId, filters = {}) {
  let query = supabase
    .from('requests')
    .select('*')
    .eq('customer_id', userId)
    .order('created_at', { ascending: false });
  if (filters.status && filters.status !== 'all') {
    query = query.eq('status', filters.status);
  }
  const { data, error } = await query;
  if (error) throw error;
  return data;
}

/**
 * Загрузка заявок для разработчика.
 */
export async function loadDeveloperRequests(filters = {}) {
  let query = supabase
    .from('requests')
    .select('*, customer:users(*)')
    .eq('status', 'open')
    .order('created_at', { ascending: false });
  if (filters.budget && filters.budget !== 'all') {
    const [min, max] = filters.budget.split('-');
    if (max) {
      query = query.gte('budget', parseFloat(min)).lte('budget', parseFloat(max));
    } else {
      query = query.gte('budget', parseFloat(min.replace('+', '')));
    }
  }
  if (filters.deadline && filters.deadline !== 'all') {
    const [min, max] = filters.deadline.split('-');
    if (max) {
      query = query.gte('deadline', parseInt(min)).lte('deadline', parseInt(max));
    } else {
      query = query.gte('deadline', parseInt(min.replace('+', '')));
    }
  }
  const { data, error } = await query;
  if (error) throw error;
  return data;
}

/**
 * Загрузка уведомлений (непрочитанных) для пользователя.
 */
export async function loadNotifications(userId) {
  const { data, error } = await supabase
    .from('notifications')
    .select('*')
    .eq('user_id', userId)
    .eq('is_read', false)
    .order('created_at', { ascending: false });
  if (error) throw error;
  return data;
}

/**
 * Загрузка истории операций.
 */
export async function loadHistory(userId, range = { start: 0, end: 4 }) {
  const { data, error } = await supabase
    .from('transactions')
    .select('*')
    .eq('user_id', userId)
    .order('created_at', { ascending: false })
    .range(range.start, range.end);
  if (error) throw error;
  return data;
}

/**
 * Отправка сообщения в чат.
 */
export async function sendChatMessage(requestId, senderId, message) {
  const { error } = await supabase
    .from('chat_messages')
    .insert([{ request_id: requestId, sender_id: senderId, message }]);
  if (error) throw error;
  return true;
}

/**
 * Загрузка сообщений чата по заявке.
 */
export async function loadChatMessages(requestId) {
  const { data, error } = await supabase
    .from('chat_messages')
    .select('*, sender:users(*)')
    .eq('request_id', requestId)
    .order('created_at', { ascending: true });
  if (error) throw error;
  return data;
}

/**
 * Подписка на новые сообщения чата для заявки.
 */
export function subscribeChatMessages(requestId, callback) {
  const subscription = supabase
    .from(`chat_messages:request_id=eq.${requestId}`)
    .on('INSERT', payload => {
      callback(payload.new);
    })
    .subscribe();
  return subscription;
}

/**
 * Демонстрационный вызов метода getMe через Telegram Bot API.
 * (Не используйте токен бота в клиентском коде в продакшене!)
 */
export async function getBotInfo() {
  const TELEGRAM_BOT_TOKEN = "7320783045:AAEROEaHuhJAp1-i3Ji_iGokgV2UB_YLyeE";
  try {
    const response = await fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getMe`);
    const data = await response.json();
    return data;
  } catch (err) {
    console.error("Ошибка получения информации о боте:", err);
    return null;
  }
}
