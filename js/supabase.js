// js/supabase.js
const SUPABASE_URL = 'https://kwwszbvcwchujbyvswqp.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt3d3N6YnZjd2NodWpieXZzd3FwIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDMyMTIyNDcsImV4cCI6MjA1ODc4ODI0N30.3HBMf8zoj-8fzjZOD-R3Oh86jTtGg807Oj7Be1VjFEw'; // Замените на ваш публичный ключ с ограниченными правами
export const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

/**
 * Получает идентификатор пользователя из localStorage или генерирует новый,
 * затем ищет пользователя в таблице 'users'. Если пользователь не найден, создаёт нового.
 */
export async function getOrCreateUser() {
  // Если браузер поддерживает crypto.randomUUID, используем его
  let userId = localStorage.getItem('userId');
  if (!userId) {
    // Генерируем стандартный UUID без префикса "user_"
    userId =  
    localStorage.setItem('userId', userId);
  }
  // Дальнейшая логика поиска или создания пользователя в базе не изменяется.
  let { data, error } = await supabase
    .from('users')
    .select('*')
    .eq('id', userId)
    .single();
  if (error && error.code !== 'PGRST116') {
    throw error;
  }
  if (!data) {
    const newUser = {
      id: userId,
      first_name: 'Пользователь',
      role: 'customer',
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
 * Функция загрузки статей.
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
 * Функция загрузки шаблонов (только одобренных).
 * Принимает объект filters: { category, price }.
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
 * Функция загрузки заявок для заказчика.
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
 * Функция загрузки заявок для разработчика.
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
 * Функция загрузки уведомлений (непрочитанных) для пользователя.
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
 * Функция загрузки истории операций.
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
 * Функция отправки сообщений в чат.
 */
export async function sendChatMessage(requestId, senderId, message) {
  const { error } = await supabase
    .from('chat_messages')
    .insert([{ request_id: requestId, sender_id: senderId, message }]);
  if (error) throw error;
  return true;
}

/**
 * Функция загрузки сообщений чата заявки.
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
