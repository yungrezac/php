// js/app.js
import { supabase, getOrCreateUser, updateUserRole, loadArticles, loadTemplates, loadCustomerRequests, loadDeveloperRequests, loadNotifications, loadHistory, sendChatMessage, loadChatMessages } from './supabase.js';
import { showToast, showLoading, hideLoading, renderUserProfile, renderArticles, renderTemplates, renderCustomerRequests, renderChat } from './ui.js';

// Глобальное состояние приложения
window.app = {
  state: {
    currentTab: 'requests',
    currentRole: 'customer',
    currentUser: null,
    articles: [],
    articlesCache: [],
    templates: [],
    requests: [],
    historyItems: [],
    chatMessages: [],
    currentRequestId: null,
    chatSubscription: null,
    historyPage: 1,
    historyPerPage: 5
  },
  // Функция для открытия статьи (пример)
  openArticle: async function(articleId) {
    showLoading('Загрузка статьи...');
    try {
      const { data, error } = await supabase
        .from('articles')
        .select('*')
        .eq('id', articleId)
        .single();
      if (error) throw error;
      if (data) {
        showToast(`Открыта статья: ${data.title}`, 'info');
        // Здесь можно реализовать отображение полного текста статьи
      }
    } catch (err) {
      console.error(err);
      showToast('Ошибка загрузки статьи', 'error');
    } finally {
      hideLoading();
    }
  },
  // Функция для открытия чата заявки
  openRequestChat: async function(requestId) {
    showLoading('Загрузка чата...');
    try {
      const { data: requestData, error } = await supabase
        .from('requests')
        .select('*, customer:users(*), developer:users(*)')
        .eq('id', requestId)
        .single();
      if (error) throw error;
      if (!requestData) throw new Error('Заявка не найдена');
      // Проверяем доступ пользователя
      if (window.app.state.currentUser.id !== requestData.customer_id &&
          window.app.state.currentUser.id !== requestData.developer_id) {
        throw new Error('Нет доступа к этому чату');
      }
      window.app.state.currentRequestId = requestId;
      const messages = await loadChatMessages(requestId);
      window.app.state.chatMessages = messages || [];
      renderChat(requestData, window.app.state.chatMessages);
      // Подписка на новые сообщения (пример; в реальном приложении реализуйте подписку через Realtime API)
    } catch (err) {
      console.error(err);
      showToast(`Ошибка: ${err.message}`, 'error');
    } finally {
      hideLoading();
    }
  },
  // Функция отправки сообщения в чат
  sendMessage: async function() {
    const chatInput = document.getElementById('chatInput');
    const text = chatInput.value.trim();
    if (!text || !window.app.state.currentRequestId || !window.app.state.currentUser) return;
    showLoading('Отправка сообщения...');
    try {
      await sendChatMessage(window.app.state.currentRequestId, window.app.state.currentUser.id, text);
      chatInput.value = '';
      // После отправки можно заново загрузить сообщения или добавить новое в UI
    } catch (err) {
      console.error(err);
      showToast('Ошибка отправки сообщения', 'error');
    } finally {
      hideLoading();
    }
  },
  // Функция создания заявки
  createRequest: async function(requestData) {
    showLoading('Создание заявки...');
    try {
      const { data, error } = await supabase
        .from('requests')
        .insert([{
          customer_id: window.app.state.currentUser.id,
          ...requestData,
          status: 'open',
          created_at: new Date().toISOString()
        }])
        .select();
      if (error) throw error;
      showToast('Заявка успешно создана', 'success');
      // Перезагрузка списка заявок
      await loadAndRenderRequests();
    } catch (err) {
      console.error(err);
      showToast('Ошибка создания заявки', 'error');
    } finally {
      hideLoading();
    }
  }
};

/**
 * Загрузка заявок в зависимости от роли пользователя
 */
async function loadAndRenderRequests() {
  try {
    let requests;
    if (window.app.state.currentRole === 'customer') {
      requests = await loadCustomerRequests(window.app.state.currentUser.id);
      window.app.state.requests = requests;
      renderCustomerRequests(requests);
    } else {
      requests = await loadDeveloperRequests();
      window.app.state.requests = requests;
      // Здесь можно реализовать рендеринг заявок для разработчика
    }
  } catch (err) {
    console.error(err);
    showToast('Ошибка загрузки заявок', 'error');
  }
}

/**
 * Переключение вкладок
 */
function switchTab(tab) {
  if (tab === window.app.state.currentTab) return;
  // Скрываем текущую вкладку
  document.querySelectorAll('.tabContent').forEach(el => el.classList.add('hidden'));
  // Показываем выбранную
  document.getElementById(`tabContent-${tab}`).classList.remove('hidden');
  window.app.state.currentTab = tab;
  // Обновляем заголовок верхней панели
  const titles = { articles: 'Статьи', market: 'Маркет', requests: 'Заявки', profile: 'Профиль' };
  document.getElementById('topBarTitle').textContent = titles[tab] || '';
  // Если вкладка "Статьи" и данные не загружены – загружаем их
  if (tab === 'articles' && !window.app.state.articlesCache.length) {
    loadAndRenderArticles();
  } else if (tab === 'requests') {
    loadAndRenderRequests();
  }
}

/**
 * Загрузка статей с кешированием и рендеринг
 */
async function loadAndRenderArticles() {
  showLoading('Загрузка статей...');
  try {
    const articles = await loadArticles();
    window.app.state.articlesCache = articles;
    window.app.state.articles = [...articles];
    renderArticles(articles);
  } catch (err) {
    console.error(err);
    showToast('Ошибка загрузки статей', 'error');
  } finally {
    hideLoading();
  }
}

/**
 * Открытие модального окна создания заявки
 */
function openCreateRequestModal() {
  document.getElementById('addRequestModal').classList.remove('hidden');
}

/**
 * Закрытие модального окна создания заявки
 */
function closeCreateRequestModal() {
  document.getElementById('addRequestModal').classList.add('hidden');
}

/**
 * Обработка отправки новой заявки
 */
async function submitNewRequest() {
  const title = document.getElementById('requestTitle').value.trim();
  const description = document.getElementById('requestDescription').value.trim();
  const budget = parseFloat(document.getElementById('requestBudget').value);
  let deadline = document.getElementById('requestDeadline').value;
  if (deadline === 'custom') {
    deadline = document.getElementById('customDeadline').value;
  }
  if (!title || !description || !budget || budget <= 0 || !deadline) {
    showToast('Пожалуйста, заполните все обязательные поля', 'error');
    return;
  }
  await window.app.createRequest({ title, description, budget, deadline });
  closeCreateRequestModal();
}

/**
 * Обработчики модальных окон для добавления шаблонов, настроек, помощи, "О приложении", подтверждения и прочее можно добавить аналогичным образом.
 */

/**
 * Установка обработчиков событий
 */
function setupEventListeners() {
  // Вкладки
  document.getElementById('tab-articles').addEventListener('click', () => switchTab('articles'));
  document.getElementById('tab-market').addEventListener('click', () => switchTab('market'));
  document.getElementById('tab-requests').addEventListener('click', () => switchTab('requests'));
  document.getElementById('tab-profile').addEventListener('click', () => switchTab('profile'));
  
  // Уведомления
  document.getElementById('notifIconWrapper').addEventListener('click', toggleNotifications);
  document.getElementById('markAllAsReadBtn').addEventListener('click', markAllAsRead);
  
  // Заявки
  document.getElementById('newRequestBtn').addEventListener('click', openCreateRequestModal);
  document.getElementById('cancelRequestBtn').addEventListener('click', closeCreateRequestModal);
  document.getElementById('submitRequestBtn').addEventListener('click', submitNewRequest);
  document.getElementById('requestDeadline').addEventListener('change', function() {
    document.getElementById('customDeadlineContainer').classList.toggle('hidden', this.value !== 'custom');
  });
  
  // Чат
  document.getElementById('closeChatBtn').addEventListener('click', closeChat);
  document.getElementById('chatSendBtn').addEventListener('click', window.app.sendMessage);
  document.getElementById('chatInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') window.app.sendMessage();
  });
  document.getElementById('attachFileBtn').addEventListener('click', (e) => {
    e.stopPropagation();
    document.getElementById('attachmentOptions').classList.toggle('hidden');
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#attachmentOptions') && !e.target.closest('#attachFileBtn')) {
      document.getElementById('attachmentOptions').classList.add('hidden');
    }
  });
  
  // Привяжите остальные обработчики для шаблонов, кошелька, настроек, помощи, "О приложении", истории и т.д.
}

/**
 * Функция переключения уведомлений (показ/скрытие панели)
 */
function toggleNotifications() {
  const panel = document.getElementById('notifPanel');
  panel.classList.toggle('hidden');
  if (!panel.classList.contains('hidden')) {
    document.getElementById('notifDot').classList.add('hidden');
    // Пометить уведомления как прочитанные
    supabase.from('notifications')
      .update({ is_read: true })
      .eq('user_id', window.app.state.currentUser.id)
      .eq('is_read', false);
  }
}

/**
 * Функция пометки всех уведомлений как прочитанные
 */
async function markAllAsRead() {
  showLoading('Обновление уведомлений...');
  try {
    const { error } = await supabase
      .from('notifications')
      .update({ is_read: true })
      .eq('user_id', window.app.state.currentUser.id)
      .eq('is_read', false);
    if (error) throw error;
    showToast('Все уведомления помечены как прочитанные', 'success');
    document.getElementById('notifPanel').classList.add('hidden');
    document.getElementById('notifDot').classList.add('hidden');
  } catch (err) {
    console.error(err);
    showToast('Ошибка обновления уведомлений', 'error');
  } finally {
    hideLoading();
  }
}

/**
 * Функция закрытия чата
 */
function closeChat() {
  if (window.app.state.chatSubscription) {
    supabase.removeSubscription(window.app.state.chatSubscription);
    window.app.state.chatSubscription = null;
  }
  document.getElementById('chatOverlay').classList.add('hidden');
  window.app.state.currentRequestId = null;
  window.app.state.chatMessages = [];
}

/**
 * Инициализация приложения: проверка Telegram WebApp, получение/создание пользователя и загрузка начальных данных
 */
async function init() {
  showLoading('Инициализация приложения...');
  try {
    if (window.Telegram && window.Telegram.WebApp) {
      window.Telegram.WebApp.expand();
    }
    const user = await getOrCreateUser();
    window.app.state.currentUser = user;
    renderUserProfile(user);
    // Загрузка данных для заявок, статей и пр.
    await loadAndRenderRequests();
    if (window.app.state.currentTab === 'articles') {
      await loadAndRenderArticles();
    }
  } catch (err) {
    console.error(err);
    showToast(`Ошибка инициализации: ${err.message}`, 'error');
  } finally {
    hideLoading();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  init();
});
