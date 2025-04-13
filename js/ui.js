// js/ui.js

/**
 * Вывод toast-уведомлений
 */
export function showToast(message, type = 'info') {
  const colors = { 
    info: 'bg-blue-500', 
    success: 'bg-green-500', 
    error: 'bg-red-500', 
    warning: 'bg-yellow-500' 
  };
  const icons = {
    info: 'information-circle',
    success: 'checkmark-circle',
    error: 'alert-circle',
    warning: 'warning'
  };
  const toast = document.createElement('div');
  toast.className = `fixed bottom-16 left-1/2 transform -translate-x-1/2 text-white px-4 py-2 rounded-lg shadow-lg ${colors[type]} fade-in flex items-center`;
  toast.innerHTML = `<ion-icon name="${icons[type]}" class="mr-2"></ion-icon><span>${message}</span>`;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

/**
 * Показ и скрытие загрузочного оверлея
 */
export function showLoading(message = 'Загрузка...') {
  const loadingMessage = document.getElementById('loadingMessage');
  loadingMessage.textContent = message;
  document.getElementById('loadingOverlay').classList.remove('hidden');
}

export function hideLoading() {
  document.getElementById('loadingOverlay').classList.add('hidden');
}

/**
 * Рендеринг профиля пользователя
 */
export function renderUserProfile(profile) {
  const profileNameEl = document.getElementById('profileName');
  const profileUsernameEl = document.getElementById('profileUsername');
  const profileAvatarEl = document.getElementById('profileAvatar');
  if (profile.first_name) {
    let name = profile.first_name + (profile.last_name ? ' ' + profile.last_name : '');
    if (profileNameEl) profileNameEl.textContent = name;
    if (profileAvatarEl) {
      if (profile.avatar_url) {
        profileAvatarEl.style.backgroundImage = `url(${profile.avatar_url})`;
        profileAvatarEl.style.backgroundSize = 'cover';
        profileAvatarEl.style.backgroundPosition = 'center';
        profileAvatarEl.textContent = '';
      } else {
        profileAvatarEl.textContent = profile.first_name.charAt(0).toUpperCase();
      }
    }
  }
  if (profile.username && profileUsernameEl) {
    profileUsernameEl.textContent = '@' + profile.username;
  }
}

/**
 * Рендеринг списка статей
 */
export function renderArticles(articles) {
  const list = document.getElementById('articlesList');
  list.innerHTML = '';
  if (!articles.length) {
    list.innerHTML = `<div class="text-center py-8 text-gray-500">
      <ion-icon name="document-text-outline" class="text-3xl mb-2"></ion-icon>
      <p>Статьи не найдены</p>
    </div>`;
    return;
  }
  articles.forEach(article => {
    const item = document.createElement('div');
    item.className = 'flex justify-between items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 smooth-transition';
    item.onclick = () => window.app.openArticle(article.id);
    item.innerHTML = `
      <div class="flex items-start">
        <div class="bg-blue-100 p-2 rounded-lg mr-3">
          <ion-icon name="document-text-outline" class="text-blue-600 text-xl"></ion-icon>
        </div>
        <div>
          <div class="font-medium">${article.title}</div>
          <div class="text-xs text-gray-500 mt-1">${article.description}</div>
          <div class="flex items-center mt-2 text-xs text-gray-400">
            <ion-icon name="time-outline" class="mr-1"></ion-icon>
            <span>${article.reading_time || 5} мин чтения</span>
          </div>
        </div>
      </div>
      <ion-icon name="chevron-forward-outline" class="text-gray-400 text-xl"></ion-icon>`;
    list.appendChild(item);
  });
}

/**
 * Рендеринг списка шаблонов
 */
export function renderTemplates(templates) {
  const list = document.getElementById('templatesList');
  list.innerHTML = '';
  if (!templates.length) {
    document.getElementById('noTemplatesMessage').classList.remove('hidden');
    return;
  }
  document.getElementById('noTemplatesMessage').classList.add('hidden');
  templates.forEach(template => {
    const item = document.createElement('div');
    item.className = 'relative border rounded-lg p-4 hover:shadow-md smooth-transition';
    item.innerHTML = `
      <div class="flex items-start">
        <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
          <ion-icon name="cube-outline" class="text-purple-600 text-2xl"></ion-icon>
        </div>
        <div class="flex-1">
          <div class="font-medium">${template.title}</div>
          <div class="text-sm text-gray-500 mt-1">${template.description}</div>
          ${template.rating > 0 ? `
          <div class="flex items-center mt-2">
            <div class="flex text-yellow-400 text-sm">
              ${renderStarsHTML(template.rating)}
            </div>
            <span class="text-xs text-gray-500 ml-1">${template.rating.toFixed(1)} (${template.reviews_count})</span>
          </div>
          ` : ''}
        </div>
      </div>
      <div class="flex justify-between items-center mt-3 pt-3 border-t">
        <div>
          <div class="text-sm font-semibold text-gray-700">${template.price} TON</div>
          <div class="text-xs text-gray-500">${template.sales_count} продаж</div>
        </div>
        <button class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-lg smooth-transition">Купить</button>
      </div>`;
    list.appendChild(item);
  });
}

function renderStarsHTML(rating) {
  let stars = '';
  const fullStars = Math.floor(rating);
  const halfStar = rating % 1 >= 0.5;
  for (let i = 0; i < fullStars; i++) {
    stars += '<ion-icon name="star"></ion-icon>';
  }
  if (halfStar) {
    stars += '<ion-icon name="star-half"></ion-icon>';
  }
  const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
  for (let i = 0; i < emptyStars; i++) {
    stars += '<ion-icon name="star-outline"></ion-icon>';
  }
  return stars;
}

/**
 * Рендеринг списка заявок для заказчика
 */
export function renderCustomerRequests(requests) {
  const list = document.getElementById('customerRequestsList');
  list.innerHTML = '';
  if (!requests.length) {
    document.getElementById('noCustomerRequests').classList.remove('hidden');
    return;
  }
  document.getElementById('noCustomerRequests').classList.add('hidden');
  requests.forEach(req => {
    const item = document.createElement('div');
    item.className = 'border rounded-lg p-4 hover:shadow-md smooth-transition cursor-pointer';
    item.onclick = () => window.app.openRequestChat(req.id);
    let statusBadge = '';
    if (req.status === 'open') statusBadge = 'bg-orange-100 text-orange-800';
    else if (req.status === 'in_progress') statusBadge = 'bg-blue-100 text-blue-800';
    else if (req.status === 'completed') statusBadge = 'bg-green-100 text-green-800';
    else if (req.status === 'cancelled') statusBadge = 'bg-gray-100 text-gray-800';
    item.innerHTML = `
      <div class="flex justify-between items-start">
        <div>
          <div class="font-medium">${req.title}</div>
          <div class="text-sm text-gray-500 mt-1">Бюджет: ${req.budget} TON</div>
        </div>
        <div class="${statusBadge} text-xs px-2 py-1 rounded-full">${getStatusText(req.status)}</div>
      </div>
      <div class="flex items-center mt-2 text-sm text-gray-500">
        <ion-icon name="time-outline" class="mr-1"></ion-icon>
        <span>Обновлено ${formatDate(req.updated_at)}</span>
      </div>`;
    list.appendChild(item);
  });
}

function getStatusText(status) {
  const texts = { open: 'Открыта', in_progress: 'В работе', completed: 'Завершена', cancelled: 'Отменена' };
  return texts[status] || status;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return 'сегодня';
  else if (diffDays === 1) return 'вчера';
  else if (diffDays < 7) return diffDays + ' дня назад';
  else return date.toLocaleDateString('ru-RU');
}

/**
 * Рендеринг чата заявки с сообщениями
 */
export function renderChat(request, messages) {
  const chatTitle = document.getElementById('chatRequestTitle');
  const chatMessages = document.getElementById('chatMessages');
  chatTitle.textContent = request.title;
  chatMessages.innerHTML = '';
  messages.forEach(msg => {
    appendChatMessage(msg);
  });
  // Прокрутка вниз
  setTimeout(() => {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }, 10);
}

/**
 * Добавление нового сообщения в чат (UI)
 */
export function appendChatMessage(msg) {
  const chatMessages = document.getElementById('chatMessages');
  const isCurrentUser = msg.sender_id === window.app.state.currentUser.id;
  const bubble = document.createElement('div');
  bubble.className = 'flex ' + (isCurrentUser ? 'justify-end' : 'justify-start');
  const bubbleInner = document.createElement('div');
  bubbleInner.className = 'max-w-xs lg:max-w-md px-4 py-2 rounded-2xl ' +
    (isCurrentUser ? 'bg-blue-500 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none');
  if (!isCurrentUser) {
    const senderName = document.createElement('div');
    senderName.className = 'text-xs font-medium mb-1';
    senderName.textContent = msg.sender?.first_name || 'Аноним';
    bubbleInner.appendChild(senderName);
  }
  const messageContent = document.createElement('div');
  messageContent.textContent = msg.message;
  bubbleInner.appendChild(messageContent);
  const timestamp = document.createElement('div');
  timestamp.className = 'text-xs mt-1 text-right ' + (isCurrentUser ? 'text-blue-100' : 'text-gray-500');
  timestamp.textContent = formatTime(msg.created_at);
  bubbleInner.appendChild(timestamp);
  bubble.appendChild(bubbleInner);
  chatMessages.appendChild(bubble);
}

function formatTime(dateString) {
  const date = new Date(dateString);
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}
