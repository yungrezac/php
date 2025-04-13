// js/app.js
import { 
  supabase, getOrCreateUser, updateUserRole, loadArticles, loadTemplates, 
  loadCustomerRequests, loadDeveloperRequests, loadNotifications, loadHistory, 
  sendChatMessage, loadChatMessages, subscribeChatMessages 
} from './supabase.js';
import { 
  showToast, showLoading, hideLoading, renderUserProfile, renderArticles, 
  renderTemplates, renderCustomerRequests, renderChat, appendChatMessage 
} from './ui.js';

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
  /**
   * Открытие статьи (пример).
   */
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
        // Реализуйте логику показа полной статьи (например, модальное окно)
      }
    } catch (err) {
      console.error(err);
      showToast('Ошибка загрузки статьи', 'error');
    } finally {
      hideLoading();
    }
  },
  /**
   * Открытие чата заявки и подписка на новые сообщения.
   */
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
      if (
        window.app.state.currentUser.id !== requestData.customer_id &&
        window.app.state.currentUser.id !== requestData.developer_id
      ) {
        throw new Error('Нет доступа к этому чату');
      }
      window.app.state.currentRequestId = requestId;
      const messages = await loadChatMessages(requestId);
      window.app.state.chatMessages = messages || [];
      renderChat(requestData, window.app.state.chatMessages);
      if (window.app.state.chatSubscription) {
        supabase.removeSubscription(window.app.state.chatSubscription);
      }
      window.app.state.chatSubscription = subscribeChatMessages(requestId, (newMsg) => {
        window.app.state.chatMessages.push(newMsg);
        appendChatMessage(newMsg);
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
      });
      document.getElementById('chatOverlay').classList.remove('hidden');
    } catch (err) {
      console.error(err);
      showToast(`Ошибка: ${err.message}`, 'error');
    } finally {
      hideLoading();
    }
  },
  /**
   * Отправка сообщения в чат.
   */
  sendMessage: async function() {
    const chatInput = document.getElementById('chatInput');
    const text = chatInput.value.trim();
    if (!text || !window.app.state.currentRequestId || !window.app.state.currentUser) return;
    showLoading('Отправка сообщения...');
    try {
      await sendChatMessage(window.app.state.currentRequestId, window.app.state.currentUser.id, text);
      chatInput.value = '';
    } catch (err) {
      console.error(err);
      showToast('Ошибка отправки сообщения', 'error');
    } finally {
      hideLoading();
    }
  },
  /**
   * Создание новой заявки.
   */
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
 * Загрузка и рендеринг заявок.
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
      // Реализуйте рендеринг для разработчика по необходимости
    }
  } catch (err) {
    console.error(err);
    showToast('Ошибка загрузки заявок', 'error');
  }
}

/**
 * Загрузка и рендеринг статей.
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
 * Переключение вкладок.
 */
function switchTab(tab) {
  if (tab === window.app.state.currentTab) return;
  document.querySelectorAll('.tabContent').forEach(el => el.classList.add('hidden'));
  document.getElementById(`tabContent-${tab}`).classList.remove('hidden');
  window.app.state.currentTab = tab;
  const titles = { articles: 'Статьи', market: 'Маркет', requests: 'Заявки', profile: 'Профиль' };
  document.getElementById('topBarTitle').textContent = titles[tab] || '';
  if (tab === 'articles' && !window.app.state.articlesCache.length) {
    loadAndRenderArticles();
  } else if (tab === 'requests') {
    loadAndRenderRequests();
  }
}

/**
 * Открытие модального окна создания заявки.
 */
function openCreateRequestModal() {
  document.getElementById('addRequestModal').classList.remove('hidden');
}

/**
 * Закрытие модального окна создания заявки.
 */
function closeCreateRequestModal() {
  document.getElementById('addRequestModal').classList.add('hidden');
}

/**
 * Обработка отправки новой заявки.
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
 * Закрытие окна чата.
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
 * Переключение уведомлений.
 */
function toggleNotifications() {
  const panel = document.getElementById('notifPanel');
  panel.classList.toggle('hidden');
  if (!panel.classList.contains('hidden')) {
    document.getElementById('notifDot').classList.add('hidden');
    supabase.from('notifications')
      .update({ is_read: true })
      .eq('user_id', window.app.state.currentUser.id)
      .eq('is_read', false);
  }
}

/**
 * Пометка всех уведомлений как прочитанные.
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
 * Интеграция с Telegram WebApp API.
 * Если приложение запущено внутри Telegram, расширяем окно, устанавливаем заголовок и добавляем кнопку закрытия.
 */
function integrateWithTelegram() {
  if (window.Telegram && window.Telegram.WebApp) {
    window.Telegram.WebApp.expand();
    window.Telegram.WebApp.ready();
    window.Telegram.WebApp.setHeaderColor('#3b82f6');
    const tgUser = window.Telegram.WebApp.initDataUnsafe?.user;
    if (tgUser) {
      window.app.state.currentUser = {
        id: tgUser.id.toString(),
        first_name: tgUser.first_name || 'Пользователь',
        last_name: tgUser.last_name || '',
        username: tgUser.username || '',
        avatar_url: tgUser.photo_url || ''
      };
      renderUserProfile(window.app.state.currentUser);
    }
    const closeBtn = document.createElement('button');
    closeBtn.textContent = 'Закрыть приложение';
    closeBtn.className = 'fixed top-2 right-2 bg-red-500 text-white px-3 py-1 rounded';
    closeBtn.addEventListener('click', () => {
      window.Telegram.WebApp.close();
    });
    document.body.appendChild(closeBtn);
  }
}

/**
 * Установка обработчиков событий.
 */
function setupEventListeners() {
  // Переключение вкладок
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
  // Дополнительные обработчики (шаблоны, кошелек, настройки, история и пр.) можно добавить здесь.
}

/**
 * Инициализация приложения: интеграция с Telegram, получение/создание пользователя и загрузка начальных данных.
 */
async function init() {
  showLoading('Инициализация приложения...');
  try {
    integrateWithTelegram();
    const user = await getOrCreateUser();
    if (!window.app.state.currentUser) {
      window.app.state.currentUser = user;
    }
    renderUserProfile(window.app.state.currentUser);
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
