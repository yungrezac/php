<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MiniApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <style>
    .fade-in {
      animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .slide-up {
      animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .pulse {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
      70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
      100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    .smooth-transition {
      transition: all 0.3s ease;
    }
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
    ::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
    * {
      scrollbar-width: thin;
      scrollbar-color: #888 #f1f1f1;
    }
    .toggle {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    .toggle input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    .toggle input:checked + .toggle-slider {
      background-color: #3b82f6;
    }
    .toggle input:checked + .toggle-slider:before {
      transform: translateX(26px);
    }
  </style>
</head>
<body class="bg-white text-black font-sans antialiased">

<!-- Top Bar -->
<div id="topBar" class="fixed top-0 left-0 w-full h-12 flex items-center justify-center border-b bg-white text-base font-semibold text-gray-800 relative z-10 shadow-sm">
  <span id="topBarTitle">Заявки</span>
  <div id="notifIconWrapper" class="absolute right-4 cursor-pointer">
    <ion-icon name="notifications-outline" class="text-2xl text-gray-600 hover:text-blue-500 smooth-transition"></ion-icon>
    <span id="notifDot" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full hidden"></span>
  </div>
  <div id="notifPanel" class="hidden absolute right-2 top-full mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-xl p-3 text-sm text-gray-700 z-20">
    <div class="flex justify-between items-center mb-2 pb-2 border-b">
      <h4 class="font-medium">Уведомления</h4>
      <button id="markAllAsReadBtn" class="text-blue-500 text-xs">Прочитать все</button>
    </div>
    <div class="space-y-3 max-h-80 overflow-y-auto" id="notificationsList">
      <!-- Notifications will be loaded here -->
    </div>
  </div>
</div>

<!-- Content Sections -->
<div id="tabContent-articles" class="tabContent px-4 mt-12 pb-16 hidden">
  <div class="relative mb-4">
    <input type="text" id="articleSearch" placeholder="Поиск статей..." 
           class="w-full border border-gray-300 rounded-full px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent smooth-transition">
    <ion-icon name="search-outline" class="absolute left-3 top-3 text-gray-400 text-lg"></ion-icon>
  </div>
  
  <div class="space-y-3" id="articlesList">
    <!-- Articles will be loaded here -->
  </div>
  
  <div id="articlesLoading" class="flex justify-center py-4">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
  </div>
</div>

<div id="tabContent-market" class="tabContent px-4 mt-12 pb-16 hidden">
  <div id="addTemplateCard" class="hidden border-2 border-dashed border-gray-400 rounded-lg p-4 flex items-center justify-center text-gray-600 mb-4 cursor-pointer hover:bg-gray-50 smooth-transition">
    <ion-icon name="add-circle-outline" class="text-2xl mr-2"></ion-icon>
    <span>Добавить шаблон</span>
  </div>
  
  <div class="flex mb-4 gap-2">
    <div class="relative flex-1">
      <input type="text" placeholder="Поиск шаблонов..." id="marketSearch"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent smooth-transition">
      <ion-icon name="search-outline" class="absolute left-3 top-3 text-gray-400 text-lg"></ion-icon>
    </div>
    <button id="filterTemplatesBtn" class="bg-gray-100 p-2 rounded-lg hover:bg-gray-200 smooth-transition">
      <ion-icon name="filter-outline" class="text-gray-600 text-lg"></ion-icon>
    </button>
  </div>
  
  <div id="marketFilters" class="hidden mb-4 p-3 bg-gray-50 rounded-lg">
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
        <select id="templateCategoryFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="all">Все</option>
          <option value="UI">UI шаблоны</option>
          <option value="functional">Функциональные</option>
          <option value="games">Игры</option>
          <option value="other">Другое</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
        <select id="templatePriceFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="all">Любая</option>
          <option value="0-10">0-10 TON</option>
          <option value="10-50">10-50 TON</option>
          <option value="50+">50+ TON</option>
        </select>
      </div>
    </div>
    <div class="mt-3 flex justify-between">
      <button id="resetFiltersBtn" class="text-blue-500 text-sm hover:text-blue-700 smooth-transition">Сбросить</button>
      <button id="applyFiltersBtn" class="bg-blue-500 text-white text-sm px-3 py-1 rounded-lg hover:bg-blue-600 smooth-transition">Применить</button>
    </div>
  </div>
  
  <div class="space-y-3" id="templatesList">
    <!-- Templates will be loaded here -->
  </div>
  
  <div id="templatesLoading" class="flex justify-center py-4">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
  </div>
  
  <div id="noTemplatesMessage" class="hidden text-center py-8 text-gray-500">
    <ion-icon name="search-outline" class="text-3xl mb-2"></ion-icon>
    <p>Шаблонов не найдено</p>
    <button id="resetFiltersBtn2" class="text-blue-500 text-sm mt-2 hover:text-blue-700 smooth-transition">Сбросить фильтры</button>
  </div>
</div>

<div id="tabContent-requests" class="tabContent px-4 mt-12 pb-16">
  <div id="customerRequests">
    <button id="newRequestBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg mb-4 w-full flex items-center justify-center smooth-transition pulse">
      <ion-icon name="add-outline" class="text-xl mr-2"></ion-icon>
      <span>Новая заявка</span>
    </button>
    
    <div class="space-y-3" id="customerRequestsList">
      <!-- Customer requests will be loaded here -->
    </div>
    
    <div id="noCustomerRequests" class="hidden text-center py-8 text-gray-500">
      <ion-icon name="clipboard-outline" class="text-3xl mb-2"></ion-icon>
      <p>У вас пока нет заявок</p>
      <button id="createRequestBtn" class="text-blue-500 text-sm mt-2 hover:text-blue-700 smooth-transition">Создать заявку</button>
    </div>
  </div>
  
  <div id="developerRequests" class="hidden">
    <div class="flex mb-4 gap-2">
      <div class="relative flex-1">
        <input type="text" placeholder="Поиск заявок..." id="requestSearch"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent smooth-transition">
        <ion-icon name="search-outline" class="absolute left-3 top-3 text-gray-400 text-lg"></ion-icon>
      </div>
      <button id="filterRequestsBtn" class="bg-gray-100 p-2 rounded-lg hover:bg-gray-200 smooth-transition">
        <ion-icon name="filter-outline" class="text-gray-600 text-lg"></ion-icon>
      </button>
    </div>
    
    <div id="requestFilters" class="hidden mb-4 p-3 bg-gray-50 rounded-lg">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Бюджет</label>
          <select id="requestBudgetFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="all">Любой</option>
            <option value="0-50">0-50 TON</option>
            <option value="50-100">50-100 TON</option>
            <option value="100+">100+ TON</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Срок</label>
          <select id="requestDeadlineFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="all">Любой</option>
            <option value="1-3">1-3 дня</option>
            <option value="3-7">3-7 дней</option>
            <option value="7+">7+ дней</option>
          </select>
        </div>
      </div>
      <div class="mt-3 flex justify-between">
        <button id="resetRequestFiltersBtn" class="text-blue-500 text-sm hover:text-blue-700 smooth-transition">Сбросить</button>
        <button id="applyRequestFiltersBtn" class="bg-blue-500 text-white text-sm px-3 py-1 rounded-lg hover:bg-blue-600 smooth-transition">Применить</button>
      </div>
    </div>
    
    <div class="space-y-3" id="developerRequestsList">
      <!-- Developer requests will be loaded here -->
    </div>
    
    <div id="noDeveloperRequests" class="hidden text-center py-8 text-gray-500">
      <ion-icon name="clipboard-outline" class="text-3xl mb-2"></ion-icon>
      <p>Нет доступных заявок</p>
      <button id="refreshRequestsBtn" class="text-blue-500 text-sm mt-2 hover:text-blue-700 smooth-transition">Обновить</button>
    </div>
  </div>
</div>

<div id="tabContent-profile" class="tabContent px-4 mt-12 pb-16 hidden">
  <div class="bg-gray-100 rounded-lg p-4 mb-4 flex items-center">
    <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4" id="profileAvatar">
      П
    </div>
    <div>
      <div id="profileName" class="text-lg font-semibold">Пользователь</div>
      <div id="profileUsername" class="text-sm text-gray-600"></div>
      <div id="profileRating" class="flex items-center mt-1">
        <div class="flex text-yellow-400 text-sm" id="ratingStars">
          <ion-icon name="star"></ion-icon>
          <ion-icon name="star"></ion-icon>
          <ion-icon name="star"></ion-icon>
          <ion-icon name="star"></ion-icon>
          <ion-icon name="star-half"></ion-icon>
        </div>
        <span class="text-xs text-gray-500 ml-1" id="ratingText">4.8 (12 отзывов)</span>
      </div>
    </div>
  </div>
  
  <div id="walletSection" class="mb-4">
    <button id="connectWalletBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg w-full flex items-center justify-center smooth-transition">
      <ion-icon name="wallet-outline" class="text-xl mr-2"></ion-icon>
      <span>Подключить TON кошелек</span>
    </button>
    <div id="walletInfo" class="hidden p-4 bg-gray-100 rounded-lg mt-2">
      <div class="flex justify-between items-center mb-2">
        <div class="text-sm font-medium text-gray-700">TON Кошелек</div>
        <button id="disconnectWalletBtn" class="text-blue-500 text-sm hover:text-blue-700 smooth-transition">Отключить</button>
      </div>
      <div id="walletAddress" class="text-xs text-gray-500 break-all bg-white p-2 rounded mb-3"></div>
      <div class="flex justify-between items-center">
        <div class="text-sm font-medium text-gray-700">Баланс</div>
        <div id="walletBalance" class="font-semibold">0 TON</div>
      </div>
    </div>
  </div>
  
  <div class="flex justify-center mb-4">
    <div id="roleToggle" class="flex bg-gray-200 rounded-full p-1">
      <button id="roleCustBtn" class="px-4 py-1 text-sm font-medium rounded-full bg-blue-500 text-white smooth-transition">Заказчик</button>
      <button id="roleDevBtn" class="px-4 py-1 text-sm font-medium text-blue-500 hover:text-blue-700 smooth-transition">Разработчик</button>
    </div>
  </div>
  
  <div class="grid grid-cols-2 gap-3 mb-4">
    <div class="bg-blue-50 p-3 rounded-lg">
      <div class="text-xs text-blue-600 mb-1">Завершено заказов</div>
      <div class="text-xl font-semibold" id="completedOrders">0</div>
    </div>
    <div class="bg-green-50 p-3 rounded-lg">
      <div class="text-xs text-green-600 mb-1">Заработано</div>
      <div class="text-xl font-semibold" id="earnedAmount">0 TON</div>
    </div>
    <div class="bg-purple-50 p-3 rounded-lg">
      <div class="text-xs text-purple-600 mb-1">Шаблонов продано</div>
      <div class="text-xl font-semibold" id="templatesSold">0</div>
    </div>
    <div class="bg-yellow-50 p-3 rounded-lg">
      <div class="text-xs text-yellow-600 mb-1">Рейтинг</div>
      <div class="text-xl font-semibold" id="userRating">0</div>
    </div>
  </div>
  
  <div class="space-y-2 mb-4">
    <button id="settingsBtn" class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 smooth-transition">
      <div class="flex items-center">
        <ion-icon name="settings-outline" class="text-gray-600 mr-3"></ion-icon>
        <span>Настройки</span>
      </div>
      <ion-icon name="chevron-forward-outline" class="text-gray-400"></ion-icon>
    </button>
    <button id="helpBtn" class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 smooth-transition">
      <div class="flex items-center">
        <ion-icon name="help-circle-outline" class="text-gray-600 mr-3"></ion-icon>
        <span>Помощь</span>
      </div>
      <ion-icon name="chevron-forward-outline" class="text-gray-400"></ion-icon>
    </button>
    <button id="aboutBtn" class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 smooth-transition">
      <div class="flex items-center">
        <ion-icon name="information-circle-outline" class="text-gray-600 mr-3"></ion-icon>
        <span>О приложении</span>
      </div>
      <ion-icon name="chevron-forward-outline" class="text-gray-400"></ion-icon>
    </button>
  </div>
  
  <div>
    <h3 class="font-medium mb-2">История операций</h3>
    <ul class="text-sm text-gray-700 space-y-3" id="historyList">
      <!-- History items will be loaded here -->
    </ul>
    <button id="loadMoreHistory" class="w-full text-center text-blue-500 text-sm py-2 hover:text-blue-700 smooth-transition hidden">
      Показать еще
    </button>
  </div>
</div>

<!-- Bottom Nav Bar -->
<div id="bottomNav" class="fixed bottom-0 left-0 w-full h-14 border-t bg-white flex justify-around items-center z-10 shadow-sm">
  <button id="tab-articles" class="flex flex-col items-center justify-center flex-1 h-full smooth-transition">
    <ion-icon id="tab-icon-articles" name="document-text-outline" class="text-2xl text-gray-600 mb-1"></ion-icon>
    <span class="text-xs text-gray-600">Статьи</span>
  </button>
  <button id="tab-market" class="flex flex-col items-center justify-center flex-1 h-full smooth-transition">
    <ion-icon id="tab-icon-market" name="pricetag-outline" class="text-2xl text-gray-600 mb-1"></ion-icon>
    <span class="text-xs text-gray-600">Маркет</span>
  </button>
  <button id="tab-requests" class="flex flex-col items-center justify-center flex-1 h-full smooth-transition">
    <ion-icon id="tab-icon-requests" name="clipboard-outline" class="text-2xl text-blue-600 mb-1"></ion-icon>
    <span class="text-xs text-blue-600">Заявки</span>
  </button>
  <button id="tab-profile" class="flex flex-col items-center justify-center flex-1 h-full smooth-transition">
    <ion-icon id="tab-icon-profile" name="person-outline" class="text-2xl text-gray-600 mb-1"></ion-icon>
    <span class="text-xs text-gray-600">Профиль</span>
  </button>
</div>

<!-- Chat Overlay -->
<div id="chatOverlay" class="hidden fixed inset-0 z-50 flex flex-col bg-white">
  <div class="h-12 flex items-center border-b px-4 bg-white sticky top-0 z-10">
    <button id="closeChatBtn" class="mr-2">
      <ion-icon name="chevron-back-outline" class="text-2xl text-blue-600"></ion-icon>
    </button>
    <div class="flex-1 font-medium truncate" id="chatRequestTitle">Заявка</div>
    <button id="chatRequestActionBtn" class="text-blue-600 text-sm font-medium hover:text-blue-800 smooth-transition" id="chatRequestAction"></button>
  </div>
  <div id="chatMessages" class="flex-1 overflow-y-auto px-4 py-2 space-y-3">
    <!-- Chat messages will be loaded here -->
  </div>
  <div class="border-t p-3 flex items-center bg-white sticky bottom-0">
    <div class="relative">
      <button id="attachFileBtn" class="text-gray-500 hover:text-gray-700 mr-2 smooth-transition">
        <ion-icon name="attach-outline" class="text-xl"></ion-icon>
      </button>
      <div id="attachmentOptions" class="hidden absolute bottom-full left-0 mb-2 w-48 bg-white rounded-lg shadow-lg p-2 z-20">
        <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-lg smooth-transition">
          <ion-icon name="image-outline" class="mr-2"></ion-icon> Фото
        </button>
        <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-lg smooth-transition">
          <ion-icon name="document-outline" class="mr-2"></ion-icon> Файл
        </button>
        <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-lg smooth-transition">
          <ion-icon name="location-outline" class="mr-2"></ion-icon> Местоположение
        </button>
      </div>
    </div>
    <input id="chatInput" type="text" class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent smooth-transition" placeholder="Написать сообщение..." />
    <button id="chatSendBtn" class="ml-2 text-blue-600 font-semibold text-sm hover:text-blue-800 smooth-transition">Отправить</button>
  </div>
</div>

<!-- Add Request Modal -->
<div id="addRequestModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-md p-4 slide-up">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Новая заявка</h3>
      <button id="closeRequestModalBtn" class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
      </button>
    </div>
    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название заявки <span class="text-red-500">*</span></label>
        <input type="text" id="requestTitle" placeholder="Что нужно сделать?" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <div id="requestTitleError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите название заявки</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Описание <span class="text-red-500">*</span></label>
        <textarea id="requestDescription" rows="3" placeholder="Опишите задачу подробнее..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
        <div id="requestDescError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите описание заявки</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Бюджет (TON) <span class="text-red-500">*</span></label>
        <input type="number" id="requestBudget" placeholder="50" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <div id="requestBudgetError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите корректный бюджет</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Срок выполнения</label>
        <select id="requestDeadline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="3">3 дня</option>
          <option value="7" selected>1 неделя</option>
          <option value="14">2 недели</option>
          <option value="30">1 месяц</option>
          <option value="custom">Другое</option>
        </select>
      </div>
      <div id="customDeadlineContainer" class="hidden">
        <label class="block text-sm font-medium text-gray-700 mb-1">Укажите срок (дней)</label>
        <input type="number" id="customDeadline" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <div id="customDeadlineError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите корректный срок</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Прикрепить файлы (до 3)</label>
        <div id="fileUploadArea" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 smooth-transition">
          <ion-icon name="cloud-upload-outline" class="text-2xl text-gray-400 mb-1"></ion-icon>
          <div class="text-sm text-gray-500">Нажмите для загрузки файлов</div>
          <div id="requestFilesNames" class="text-xs text-gray-400 mt-1 hidden"></div>
          <input type="file" id="requestFiles" class="hidden" multiple>
        </div>
        <div id="requestFilesError" class="text-xs text-red-500 mt-1 hidden">Можно загрузить не более 3 файлов</div>
      </div>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button id="cancelRequestBtn" class="px-4 py-2 text-sm text-gray-600 rounded-lg border hover:bg-gray-100 smooth-transition">Отмена</button>
      <button id="submitRequestBtn" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 smooth-transition">Опубликовать</button>
    </div>
  </div>
</div>

<!-- Add Template Modal -->
<div id="addTemplateModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-md p-4 slide-up">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Добавить шаблон</h3>
      <button id="closeTemplateModalBtn" class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
      </button>
    </div>
    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название шаблона <span class="text-red-500">*</span></label>
        <input type="text" id="templateTitle" placeholder="SuperBot UI" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <div id="templateTitleError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите название шаблона</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Описание <span class="text-red-500">*</span></label>
        <textarea id="templateDescription" rows="3" placeholder="Опишите функционал шаблона..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
        <div id="templateDescError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите описание шаблона</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Цена (TON) <span class="text-red-500">*</span></label>
        <input type="number" id="templatePrice" placeholder="10" min="0" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <div id="templatePriceError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, укажите корректную цену</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Категория <span class="text-red-500">*</span></label>
        <select id="templateCategory" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="UI">UI шаблоны</option>
          <option value="functional">Функциональные</option>
          <option value="games">Игры</option>
          <option value="other">Другое</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Файл шаблона (ZIP) <span class="text-red-500">*</span></label>
        <div id="templateFileUpload" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 smooth-transition">
          <ion-icon name="cloud-upload-outline" class="text-2xl text-gray-400 mb-1"></ion-icon>
          <div class="text-sm text-gray-500">Нажмите для загрузки файла</div>
          <div id="templateFileName" class="text-xs text-gray-400 mt-1 hidden"></div>
          <input type="file" id="templateFile" class="hidden" accept=".zip">
        </div>
        <div id="templateFileError" class="text-xs text-red-500 mt-1 hidden">Пожалуйста, загрузите файл шаблона</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Превью (изображение)</label>
        <div id="templatePreviewUpload" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 smooth-transition">
          <ion-icon name="image-outline" class="text-2xl text-gray-400 mb-1"></ion-icon>
          <div class="text-sm text-gray-500">Нажмите для загрузки изображения</div>
          <div id="templatePreviewName" class="text-xs text-gray-400 mt-1 hidden"></div>
          <input type="file" id="templatePreview" class="hidden" accept="image/*">
        </div>
      </div>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button id="cancelTemplateBtn" class="px-4 py-2 text-sm text-gray-600 rounded-lg border hover:bg-gray-100 smooth-transition">Отмена</button>
      <button id="submitTemplateBtn" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 smooth-transition">Отправить</button>
    </div>
  </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-md p-4 slide-up">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Настройки</h3>
      <button id="closeSettingsBtn" class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
      </button>
    </div>
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Тема</label>
        <div class="flex space-x-2">
          <button id="themeLight" class="flex-1 border rounded-lg p-2 flex items-center justify-center">
            <ion-icon name="sunny-outline" class="mr-2"></ion-icon> Светлая
          </button>
          <button id="themeDark" class="flex-1 border rounded-lg p-2 flex items-center justify-center">
            <ion-icon name="moon-outline" class="mr-2"></ion-icon> Темная
          </button>
          <button id="themeSystem" class="flex-1 border rounded-lg p-2 flex items-center justify-center">
            <ion-icon name="desktop-outline" class="mr-2"></ion-icon> Системная
          </button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Уведомления</label>
        <div class="space-y-2">
          <label class="flex items-center justify-between">
            <span>Новые сообщения</span>
            <label class="toggle">
              <input type="checkbox" id="notifMessages" checked>
              <span class="toggle-slider"></span>
            </label>
          </label>
          <label class="flex items-center justify-between">
            <span>Обновления заявок</span>
            <label class="toggle">
              <input type="checkbox" id="notifRequests" checked>
              <span class="toggle-slider"></span>
            </label>
          </label>
          <label class="flex items-center justify-between">
            <span>Новости и обновления</span>
            <label class="toggle">
              <input type="checkbox" id="notifNews">
              <span class="toggle-slider"></span>
            </label>
          </label>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Язык</label>
        <select id="languageSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          <option value="ru">Русский</option>
          <option value="en">English</option>
          <option value="uk">Українська</option>
        </select>
      </div>
    </div>
    <div class="mt-6 flex justify-end">
      <button id="saveSettingsBtn" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 smooth-transition">Сохранить</button>
    </div>
  </div>
</div>

<!-- Help Modal -->
<div id="helpModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-md p-4 slide-up">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Помощь</h3>
      <button id="closeHelpBtn" class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
      </button>
    </div>
    <div class="space-y-3 text-sm text-gray-700">
      <div class="p-3 bg-blue-50 rounded-lg">
        <h4 class="font-medium text-blue-700 mb-1">Как создать заявку?</h4>
        <p>Перейдите в раздел "Заявки" и нажмите кнопку "Новая заявка". Заполните все необходимые поля и нажмите "Опубликовать".</p>
      </div>
      <div class="p-3 bg-green-50 rounded-lg">
        <h4 class="font-medium text-green-700 mb-1">Как подключить кошелек?</h4>
        <p>В разделе "Профиль" нажмите кнопку "Подключить TON кошелек" и следуйте инструкциям.</p>
      </div>
      <div class="p-3 bg-purple-50 rounded-lg">
        <h4 class="font-medium text-purple-700 mb-1">Как добавить шаблон?</h4>
        <p>Переключитесь в режим разработчика в профиле, затем в разделе "Маркет" нажмите "Добавить шаблон".</p>
      </div>
    </div>
    <div class="mt-4">
      <button class="w-full flex items-center justify-center text-blue-500 text-sm py-2 hover:text-blue-700 smooth-transition">
        <ion-icon name="chatbubble-ellipses-outline" class="mr-2"></ion-icon>
        <span>Связаться с поддержкой</span>
      </button>
    </div>
  </div>
</div>

<!-- About Modal -->
<div id="aboutModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-md p-4 slide-up">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">О приложении</h3>
      <button id="closeAboutBtn" class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
      </button>
    </div>
    <div class="text-center mb-4">
      <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">
        M
      </div>
      <h4 class="font-medium">MiniApp</h4>
      <div class="text-sm text-gray-500">Версия 1.0.0</div>
    </div>
    <div class="text-sm text-gray-700 space-y-2">
      <p>MiniApp - это платформа для заказа и выполнения разработки Telegram ботов и других проектов.</p>
      <p>Используйте TON кошелек для безопасных платежей между заказчиками и разработчиками.</p>
    </div>
    <div class="mt-6 flex justify-center space-x-4">
      <button class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="logo-telegram" class="text-xl"></ion-icon>
      </button>
      <button class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="logo-github" class="text-xl"></ion-icon>
      </button>
      <button class="text-gray-500 hover:text-gray-700 smooth-transition">
        <ion-icon name="mail-outline" class="text-xl"></ion-icon>
      </button>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 z-50 bg-black bg-opacity-30 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 shadow-xl flex flex-col items-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
    <p id="loadingMessage">Загрузка...</p>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg w-full max-w-sm p-4 slide-up">
    <div class="text-center mb-4">
      <ion-icon name="help-circle-outline" class="text-4xl text-yellow-500 mb-2"></ion-icon>
      <h3 id="confirmationTitle" class="text-lg font-semibold">Подтвердите действие</h3>
      <p id="confirmationMessage" class="text-sm text-gray-600 mt-1">Вы уверены, что хотите выполнить это действие?</p>
    </div>
    <div class="flex justify-center space-x-3">
      <button id="cancelConfirmBtn" class="px-4 py-2 text-sm text-gray-600 rounded-lg border hover:bg-gray-100 smooth-transition">Отмена</button>
      <button id="confirmActionBtn" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 smooth-transition">Подтвердить</button>
    </div>
  </div>
</div>

<script>
  // Supabase initialization
  const supabaseUrl = 'https://kwwszbvcwchujbyvswqp.supabase.co';
  const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt3d3N6YnZjd2NodWpieXZzd3FwIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDMyMTIyNDcsImV4cCI6MjA1ODc4ODI0N30.3HBMf8zoj-8fzjZOD-R3Oh86jTtGg807Oj7Be1VjFEw';
  const supabase = supabase.createClient(supabaseUrl, supabaseKey);

  // Tab titles and icons
  const tabTitles = {
    'articles': 'Статьи',
    'market': 'Маркет',
    'requests': 'Заявки',
    'profile': 'Профиль'
  };

  const tabIcons = {
    'articles': { outline: 'document-text-outline', filled: 'document-text' },
    'market': { outline: 'pricetag-outline', filled: 'pricetag' },
    'requests': { outline: 'clipboard-outline', filled: 'clipboard' },
    'profile': { outline: 'person-outline', filled: 'person' }
  };

  // App state
  const state = {
    currentTab: 'requests',
    currentRole: 'customer',
    currentUser: null,
    walletConnected: false,
    currentChatRequestId: null,
    chatMessages: [],
    theme: 'light',
    notifications: {
      messages: true,
      requests: true,
      news: false
    },
    language: 'ru',
    historyItems: [],
    articles: [],
    templates: [],
    requests: [],
    isLoading: false,
    currentArticleId: null,
    currentTemplateId: null,
    currentRequestId: null,
    historyPage: 1,
    historyPerPage: 5,
    pendingAction: null,
    pendingActionData: null,
    selectedFiles: [],
    selectedTemplateFile: null,
    selectedTemplatePreview: null
  };

  // Initialize the app
  document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners first to ensure they're available
    setupEventListeners();
    
    // Then initialize the app
    initApp();
  });

  async function initApp() {
    try {
      await checkAuth();
      switchTab(state.currentTab);
      await loadInitialData();
      initTheme();
      initLanguage();
      initNotifications();
    } catch (error) {
      console.error('App initialization failed:', error);
      showToast('Не удалось загрузить приложение', 'error');
    }
  }

  // Check authentication status
  async function checkAuth() {
    try {
      const { data: { user }, error } = await supabase.auth.getUser();
      if (error) throw error;
      
      if (user) {
        state.currentUser = user;
        await loadUserProfile(user.id);
      } else {
        console.log('User not authenticated');
      }
    } catch (error) {
      console.error('Error checking auth:', error);
      showToast('Ошибка проверки авторизации', 'error');
    }
  }

  // Load user profile from database
  async function loadUserProfile(userId) {
    try {
      const { data, error } = await supabase
        .from('users')
        .select('*')
        .eq('id', userId)
        .single();
      
      if (error) throw error;
      
      if (data) {
        state.currentUser.profile = data;
        updateProfileUI(data);
        await loadUserWallet(userId);
        
        if (data.role) {
          state.currentRole = data.role;
          updateRoleUI();
        }
        
        await loadUserStats(userId);
      }
    } catch (error) {
      console.error('Error loading user profile:', error);
      showToast('Ошибка загрузки профиля', 'error');
    }
  }

  // Load user wallet
  async function loadUserWallet(userId) {
    try {
      const { data, error } = await supabase
        .from('wallets')
        .select('*')
        .eq('user_id', userId)
        .single();
      
      if (error) throw error;
      
      if (data) {
        state.walletConnected = true;
        updateWalletUI(data);
      }
    } catch (error) {
      console.error('Error loading wallet:', error);
      showToast('Ошибка загрузки кошелька', 'error');
    }
  }

  // Load user statistics
  async function loadUserStats(userId) {
    try {
      const { data, error } = await supabase
        .from('user_stats')
        .select('*')
        .eq('user_id', userId)
        .single();
      
      if (error) throw error;
      
      if (data) {
        updateUserStatsUI(data);
      }
    } catch (error) {
      console.error('Error loading user stats:', error);
    }
  }

  // Update profile UI
  function updateProfileUI(profile) {
    if (profile.first_name) {
      const name = `${profile.first_name}${profile.last_name ? ' ' + profile.last_name : ''}`;
      document.getElementById('profileName').textContent = name;
      document.getElementById('profileAvatar').textContent = profile.first_name[0].toUpperCase();
    }
    
    if (profile.username) {
      document.getElementById('profileUsername').textContent = `@${profile.username}`;
    }
    
    if (profile.rating) {
      updateRatingUI(profile.rating);
    }
  }

  // Update user stats UI
  function updateUserStatsUI(stats) {
    document.getElementById('completedOrders').textContent = stats.completed_orders || 0;
    document.getElementById('earnedAmount').textContent = `${stats.earned_amount || 0} TON`;
    document.getElementById('templatesSold').textContent = stats.templates_sold || 0;
    document.getElementById('userRating').textContent = stats.rating ? stats.rating.toFixed(1) : 0;
  }

  // Update rating UI
  function updateRatingUI(rating) {
    const starsContainer = document.getElementById('ratingStars');
    starsContainer.innerHTML = '';
    
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    for (let i = 0; i < fullStars; i++) {
      const star = document.createElement('ion-icon');
      star.setAttribute('name', 'star');
      starsContainer.appendChild(star);
    }
    
    if (hasHalfStar) {
      const halfStar = document.createElement('ion-icon');
      halfStar.setAttribute('name', 'star-half');
      starsContainer.appendChild(halfStar);
    }
    
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < emptyStars; i++) {
      const emptyStar = document.createElement('ion-icon');
      emptyStar.setAttribute('name', 'star-outline');
      starsContainer.appendChild(emptyStar);
    }
    
    document.getElementById('ratingText').textContent = `${rating.toFixed(1)} (${state.currentUser.profile.reviews_count || 0} отзывов)`;
  }

  // Update wallet UI
  function updateWalletUI(wallet) {
    document.getElementById('walletAddress').textContent = wallet.address;
    document.getElementById('walletBalance').textContent = `${wallet.balance} ${wallet.currency}`;
    document.getElementById('connectWalletBtn').classList.add('hidden');
    document.getElementById('walletInfo').classList.remove('hidden');
  }

  // Update role UI
  function updateRoleUI() {
    if (state.currentRole === 'customer') {
      document.getElementById('roleCustBtn').classList.add('bg-blue-500', 'text-white');
      document.getElementById('roleCustBtn').classList.remove('text-blue-500');
      document.getElementById('roleDevBtn').classList.add('text-blue-500');
      document.getElementById('roleDevBtn').classList.remove('bg-blue-500', 'text-white');
    } else {
      document.getElementById('roleDevBtn').classList.add('bg-blue-500', 'text-white');
      document.getElementById('roleDevBtn').classList.remove('text-blue-500');
      document.getElementById('roleCustBtn').classList.add('text-blue-500');
      document.getElementById('roleCustBtn').classList.remove('bg-blue-500', 'text-white');
    }
    
    document.getElementById('addTemplateCard').classList.toggle('hidden', state.currentRole !== 'developer');
    document.getElementById('customerRequests').classList.toggle('hidden', state.currentRole !== 'customer');
    document.getElementById('developerRequests').classList.toggle('hidden', state.currentRole === 'customer');
  }

  // Load initial data
  async function loadInitialData() {
    showLoading('Загрузка данных...');
    
    try {
      await Promise.all([
        loadArticles(),
        loadTemplates(),
        loadRequests(),
        loadNotifications(),
        loadHistory()
      ]);
    } catch (error) {
      console.error('Error loading initial data:', error);
      showToast('Ошибка загрузки данных', 'error');
    } finally {
      hideLoading();
    }
  }

  // Load articles
  async function loadArticles() {
    try {
      const { data, error } = await supabase
        .from('articles')
        .select('*')
        .order('created_at', { ascending: false });
      
      if (error) throw error;
      
      if (data) {
        state.articles = data;
        renderArticles();
      }
    } catch (error) {
      console.error('Error loading articles:', error);
      showToast('Ошибка загрузки статей', 'error');
    }
  }

  // Render articles
  function renderArticles() {
    const articlesList = document.getElementById('articlesList');
    articlesList.innerHTML = '';
    
    if (state.articles.length === 0) {
      articlesList.innerHTML = `
        <div class="text-center py-8 text-gray-500">
          <ion-icon name="document-text-outline" class="text-3xl mb-2"></ion-icon>
          <p>Статьи не найдены</p>
        </div>
      `;
      return;
    }
    
    state.articles.forEach(article => {
      const articleEl = document.createElement('div');
      articleEl.className = 'flex justify-between items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 smooth-transition';
      articleEl.onclick = () => openArticle(article.id);
      
      articleEl.innerHTML = `
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
        <ion-icon name="chevron-forward-outline" class="text-gray-400 text-xl"></ion-icon>
      `;
      
      articlesList.appendChild(articleEl);
    });
  }

  // Load templates with filters
  async function loadTemplates(filters = {}) {
    try {
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
      
      if (data) {
        state.templates = data;
        renderTemplates();
      }
    } catch (error) {
      console.error('Error loading templates:', error);
      showToast('Ошибка загрузки шаблонов', 'error');
    }
  }

  // Render templates
  function renderTemplates() {
    const templatesList = document.getElementById('templatesList');
    templatesList.innerHTML = '';
    
    if (state.templates.length === 0) {
      document.getElementById('noTemplatesMessage').classList.remove('hidden');
      return;
    }
    
    document.getElementById('noTemplatesMessage').classList.add('hidden');
    
    state.templates.forEach(template => {
      const templateEl = document.createElement('div');
      templateEl.className = 'relative border rounded-lg p-4 hover:shadow-md smooth-transition';
      
      templateEl.innerHTML = `
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
          <button class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-lg smooth-transition">
            Купить
          </button>
        </div>
      `;
      
      templatesList.appendChild(templateEl);
    });
  }

  // Render stars HTML
  function renderStarsHTML(rating) {
    let stars = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    for (let i = 0; i < fullStars; i++) {
      stars += '<ion-icon name="star"></ion-icon>';
    }
    
    if (hasHalfStar) {
      stars += '<ion-icon name="star-half"></ion-icon>';
    }
    
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < emptyStars; i++) {
      stars += '<ion-icon name="star-outline"></ion-icon>';
    }
    
    return stars;
  }

  // Load requests with filters
  async function loadRequests(filters = {}) {
    if (state.currentRole === 'customer') {
      await loadCustomerRequests(filters);
    } else {
      await loadDeveloperRequests(filters);
    }
  }

  // Load customer requests
  async function loadCustomerRequests(filters = {}) {
    if (!state.currentUser) return;
    
    try {
      let query = supabase
        .from('requests')
        .select('*')
        .eq('customer_id', state.currentUser.id)
        .order('created_at', { ascending: false });
      
      if (filters.status && filters.status !== 'all') {
        query = query.eq('status', filters.status);
      }
      
      const { data, error } = await query;
      
      if (error) throw error;
      
      if (data) {
        state.requests = data;
        renderCustomerRequests();
      }
    } catch (error) {
      console.error('Error loading customer requests:', error);
      showToast('Ошибка загрузки заявок', 'error');
    }
  }

  // Render customer requests
  function renderCustomerRequests() {
    const requestsList = document.getElementById('customerRequestsList');
    requestsList.innerHTML = '';
    
    if (state.requests.length === 0) {
      document.getElementById('noCustomerRequests').classList.remove('hidden');
      return;
    }
    
    document.getElementById('noCustomerRequests').classList.add('hidden');
    
    state.requests.forEach(request => {
      const requestEl = document.createElement('div');
      requestEl.className = 'border rounded-lg p-4 hover:shadow-md smooth-transition cursor-pointer';
      requestEl.onclick = () => openRequestChat(request.id);
      
      let statusBadge = '';
      if (request.status === 'open') {
        statusBadge = 'bg-orange-100 text-orange-800';
      } else if (request.status === 'in_progress') {
        statusBadge = 'bg-blue-100 text-blue-800';
      } else if (request.status === 'completed') {
        statusBadge = 'bg-green-100 text-green-800';
      } else if (request.status === 'cancelled') {
        statusBadge = 'bg-gray-100 text-gray-800';
      }
      
      requestEl.innerHTML = `
        <div class="flex justify-between items-start">
          <div>
            <div class="font-medium">${request.title}</div>
            <div class="text-sm text-gray-500 mt-1">Бюджет: ${request.budget} TON</div>
          </div>
          <div class="${statusBadge} text-xs px-2 py-1 rounded-full">${getStatusText(request.status)}</div>
        </div>
        ${request.developer_id ? `
          <div class="flex items-center mt-3 pt-3 border-t">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-2">
              <ion-icon name="person-outline" class="text-gray-600"></ion-icon>
            </div>
            <div class="text-sm text-gray-700">Исполнитель: ${request.developer_id}</div>
          </div>
        ` : `
          <div class="flex items-center mt-3 pt-3 border-t">
            <div class="text-sm text-gray-500">${request.responses_count || 0} откликов</div>
          </div>
        `}
        <div class="flex items-center mt-2 text-sm text-gray-500">
          <ion-icon name="time-outline" class="mr-1"></ion-icon>
          <span>Обновлено ${formatDate(request.updated_at)}</span>
        </div>
      `;
      
      requestsList.appendChild(requestEl);
    });
  }

  // Load developer requests with filters
  async function loadDeveloperRequests(filters = {}) {
    try {
      let query = supabase
        .from('requests')
        .select('*, customers:users(*)')
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
      
      if (data) {
        state.requests = data;
        renderDeveloperRequests();
      }
    } catch (error) {
      console.error('Error loading developer requests:', error);
      showToast('Ошибка загрузки заявок', 'error');
    }
  }

  // Render developer requests
  function renderDeveloperRequests() {
    const requestsList = document.getElementById('developerRequestsList');
    requestsList.innerHTML = '';
    
    if (state.requests.length === 0) {
      document.getElementById('noDeveloperRequests').classList.remove('hidden');
      return;
    }
    
    document.getElementById('noDeveloperRequests').classList.add('hidden');
    
    state.requests.forEach(request => {
      const requestEl = document.createElement('div');
      requestEl.className = 'border rounded-lg p-4 hover:shadow-md smooth-transition cursor-pointer';
      requestEl.onclick = () => openRequestChat(request.id);
      
      const customer = request.customers;
      
      requestEl.innerHTML = `
        <div class="flex justify-between items-start">
          <div>
            <div class="font-medium">${request.title}</div>
            <div class="text-sm text-gray-500 mt-1">Бюджет: ${request.budget} TON</div>
          </div>
          <div class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">${getStatusText(request.status)}</div>
        </div>
        <div class="flex items-center mt-3 pt-3 border-t">
          <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-2">
            <ion-icon name="person-outline" class="text-gray-600"></ion-icon>
          </div>
          <div class="text-sm text-gray-700">${customer.first_name} ${customer.last_name || ''}</div>
        </div>
        <div class="flex items-center mt-2 text-sm text-gray-500">
          <ion-icon name="time-outline" class="mr-1"></ion-icon>
          <span>Опубликовано ${formatDate(request.created_at)}</span>
        </div>
        <button class="mt-3 w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg smooth-transition">
          Откликнуться
        </button>
      `;
      
      requestsList.appendChild(requestEl);
    });
  }

  // Get status text
  function getStatusText(status) {
    const statusTexts = {
      'open': 'Открыта',
      'in_progress': 'В работе',
      'completed': 'Завершена',
      'cancelled': 'Отменена'
    };
    return statusTexts[status] || status;
  }

  // Format date
  function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const diffDays = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
      return 'сегодня';
    } else if (diffDays === 1) {
      return 'вчера';
    } else if (diffDays < 7) {
      return `${diffDays} дня назад`;
    } else {
      return date.toLocaleDateString('ru-RU');
    }
  }

  // Load notifications
  async function loadNotifications() {
    if (!state.currentUser) return;
    
    try {
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', state.currentUser.id)
        .eq('is_read', false)
        .order('created_at', { ascending: false });
      
      if (error) throw error;
      
      if (data && data.length > 0) {
        document.getElementById('notifDot').classList.remove('hidden');
        renderNotifications(data);
      }
    } catch (error) {
      console.error('Error loading notifications:', error);
    }
  }

  // Render notifications
  function renderNotifications(notifications) {
    const notificationsList = document.getElementById('notificationsList');
    notificationsList.innerHTML = '';
    
    notifications.forEach(notification => {
      const notificationEl = document.createElement('div');
      notificationEl.className = 'flex items-start p-2 rounded-lg hover:bg-gray-50 smooth-transition';
      
      let iconName = 'information-circle-outline';
      let iconColor = 'blue';
      
      if (notification.type === 'request') {
        iconName = 'chatbubble-ellipses-outline';
        iconColor = 'blue';
      } else if (notification.type === 'template') {
        iconName = 'checkmark-circle-outline';
        iconColor = 'green';
      } else if (notification.type === 'payment') {
        iconName = 'cash-outline';
        iconColor = 'green';
      }
      
      notificationEl.innerHTML = `
        <ion-icon name="${iconName}" class="text-${iconColor}-500 text-lg mr-2 mt-0.5 flex-shrink-0"></ion-icon>
        <div class="text-sm">${notification.message}</div>
      `;
      
      notificationsList.appendChild(notificationEl);
    });
  }

  // Load history
  async function loadHistory() {
    if (!state.currentUser) return;
    
    try {
      const { data, error } = await supabase
        .from('transactions')
        .select('*')
        .eq('user_id', state.currentUser.id)
        .order('created_at', { ascending: false })
        .range(0, state.historyPerPage - 1);
      
      if (error) throw error;
      
      if (data) {
        state.historyItems = data;
        renderHistory();
      }
    } catch (error) {
      console.error('Error loading history:', error);
      showToast('Ошибка загрузки истории', 'error');
    }
  }

  // Load more history
  async function loadMoreHistory() {
    if (!state.currentUser) return;
    
    showLoading('Загрузка дополнительных записей...');
    
    try {
      const nextPage = state.historyPage + 1;
      const start = (nextPage - 1) * state.historyPerPage;
      const end = nextPage * state.historyPerPage - 1;
      
      const { data, error } = await supabase
        .from('transactions')
        .select('*')
        .eq('user_id', state.currentUser.id)
        .order('created_at', { ascending: false })
        .range(start, end);
      
      if (error) throw error;
      
      if (data && data.length > 0) {
        state.historyItems = [...state.historyItems, ...data];
        state.historyPage = nextPage;
        renderHistory();
      } else {
        document.getElementById('loadMoreHistory').classList.add('hidden');
      }
    } catch (error) {
      console.error('Error loading more history:', error);
      showToast('Ошибка загрузки истории', 'error');
    } finally {
      hideLoading();
    }
  }

  // Render history
  function renderHistory() {
    const historyList = document.getElementById('historyList');
    historyList.innerHTML = '';
    
    state.historyItems.forEach(item => {
      const historyItem = document.createElement('li');
      historyItem.className = 'flex justify-between items-start';
      
      let iconName = 'cash-outline';
      let iconColor = 'blue';
      
      if (item.type === 'purchase') {
        iconName = 'pricetag-outline';
        iconColor = 'purple';
      } else if (item.type === 'sale') {
        iconName = 'cash-outline';
        iconColor = 'green';
      } else if (item.type === 'transfer') {
        iconName = 'swap-horizontal-outline';
        iconColor = 'blue';
      } else if (item.type === 'withdrawal') {
        iconName = 'arrow-down-outline';
        iconColor = 'red';
      } else if (item.type === 'deposit') {
        iconName = 'arrow-up-outline';
        iconColor = 'green';
      }
      
      const amountEl = item.amount > 0 ? 
        `<div class="text-green-500 font-medium">+${item.amount} ${item.currency}</div>` : 
        `<div class="text-red-500 font-medium">${item.amount} ${item.currency}</div>`;
      
      historyItem.innerHTML = `
        <div class="flex items-start">
          <div class="bg-${iconColor}-100 p-1 rounded mr-2">
            <ion-icon name="${iconName}" class="text-${iconColor}-600"></ion-icon>
          </div>
          <div>
            <div>${item.description}</div>
            <div class="text-xs text-gray-400">${formatDate(item.created_at)}</div>
          </div>
        </div>
        ${amountEl}
      `;
      
      historyList.appendChild(historyItem);
    });
    
    if (state.historyItems.length >= state.historyPerPage) {
      document.getElementById('loadMoreHistory').classList.remove('hidden');
    }
  }

  // Open article
  async function openArticle(articleId) {
    showLoading('Загрузка статьи...');
    
    try {
      const { data, error } = await supabase
        .from('articles')
        .select('*')
        .eq('id', articleId)
        .single();
      
      if (error) throw error;
      
      if (data) {
        state.currentArticleId = articleId;
        showToast(`Открыта статья: ${data.title}`, 'info');
      }
    } catch (error) {
      console.error('Error opening article:', error);
      showToast('Ошибка загрузки статьи', 'error');
    } finally {
      hideLoading();
    }
  }

  // Open request chat
  async function openRequestChat(requestId) {
    showLoading('Загрузка чата...');
    
    try {
      const { data: requestData, error: requestError } = await supabase
        .from('requests')
        .select('*, customers:users(*), developers:users(*)')
        .eq('id', requestId)
        .single();
      
      if (requestError) throw requestError;
      
      if (requestData) {
        state.currentRequestId = requestId;
        document.getElementById('chatRequestTitle').textContent = requestData.title;
        
        const actionBtn = document.getElementById('chatRequestActionBtn');
        if (state.currentRole === 'developer') {
          actionBtn.textContent = 'Предложить услуги';
          actionBtn.onclick = () => respondToRequest(requestId);
        } else {
          if (requestData.status === 'open') {
            actionBtn.textContent = 'Выбрать исполнителя';
            actionBtn.onclick = () => showToast('Функция выбора исполнителя в разработке', 'info');
          } else if (requestData.status === 'in_progress') {
            actionBtn.textContent = 'Завершить заказ';
            actionBtn.onclick = () => completeRequest(requestId);
          } else {
            actionBtn.textContent = 'Оставить отзыв';
            actionBtn.onclick = () => showToast('Функция отзыва в разработке', 'info');
          }
        }
        
        const { data: messagesData, error: messagesError } = await supabase
          .from('chat_messages')
          .select('*, sender:users(*)')
          .eq('request_id', requestId)
          .order('created_at', { ascending: true });
        
        if (messagesError) throw messagesError;
        
        if (messagesData) {
          state.chatMessages = messagesData;
          renderChatMessages();
        }
        
        document.getElementById('chatOverlay').classList.remove('hidden');
      }
    } catch (error) {
      console.error('Error opening chat:', error);
      showToast('Ошибка загрузки чата', 'error');
    } finally {
      hideLoading();
    }
  }

  // Respond to request (developer)
  async function respondToRequest(requestId) {
    showLoading('Отправка отклика...');
    
    try {
      const { data, error } = await supabase
        .from('request_responses')
        .insert([
          {
            request_id: requestId,
            developer_id: state.currentUser.id,
            message: 'Я готов выполнить эту работу',
            price: state.requests.find(r => r.id === requestId).budget
          }
        ]);
      
      if (error) throw error;
      
      if (data) {
        showToast('Ваш отклик отправлен', 'success');
        await loadRequests();
      }
    } catch (error) {
      console.error('Error responding to request:', error);
      showToast('Ошибка отправки отклика', 'error');
    } finally {
      hideLoading();
    }
  }

  // Complete request (customer)
  async function completeRequest(requestId) {
    showConfirmation(
      'Завершение заказа',
      'Вы уверены, что хотите завершить этот заказ? После подтверждения средства будут переведены разработчику.',
      async () => {
        showLoading('Завершение заказа...');
        
        try {
          const { data, error } = await supabase
            .from('requests')
            .update({ status: 'completed' })
            .eq('id', requestId);
          
          if (error) throw error;
          
          if (data) {
            showToast('Заказ успешно завершен', 'success');
            await loadRequests();
            closeChat();
          }
        } catch (error) {
          console.error('Error completing request:', error);
          showToast('Ошибка завершения заказа', 'error');
        } finally {
          hideLoading();
        }
      }
    );
  }

  // Render chat messages
  function renderChatMessages() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML = '';
    
    state.chatMessages.forEach(msg => {
      const isCurrentUser = msg.sender_id === state.currentUser.id;
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
    });
    
    setTimeout(() => {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 10);
  }

  // Format time
  function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
  }

  // Close chat
  function closeChat() {
    document.getElementById('chatOverlay').classList.add('hidden');
    state.currentRequestId = null;
    state.chatMessages = [];
  }

  // Send message
  async function sendMessage() {
    const text = document.getElementById('chatInput').value.trim();
    if (!text || !state.currentRequestId || !state.currentUser) return;
    
    showLoading('Отправка сообщения...');
    
    try {
      const { data, error } = await supabase
        .from('chat_messages')
        .insert([
          {
            request_id: state.currentRequestId,
            sender_id: state.currentUser.id,
            message: text
          }
        ]);
      
      if (error) throw error;
      
      if (data) {
        document.getElementById('chatInput').value = '';
        await openRequestChat(state.currentRequestId);
      }
    } catch (error) {
      console.error('Error sending message:', error);
      showToast('Ошибка отправки сообщения', 'error');
    } finally {
      hideLoading();
    }
  }

  // Connect wallet
  async function connectWallet() {
    if (!state.currentUser) return;
    
    showConfirmation(
      'Подключение кошелька',
      'Вы хотите подключить TON кошелек к вашему аккаунту?',
      async () => {
        showLoading('Подключение кошелька...');
        
        try {
          const dummyAddress = 'EQC' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
          
          const { data, error } = await supabase
            .from('wallets')
            .upsert([
              {
                user_id: state.currentUser.id,
                address: dummyAddress,
                balance: 100,
                currency: 'TON'
              }
            ], { onConflict: 'user_id' });
          
          if (error) throw error;
          
          if (data) {
            state.walletConnected = true;
            updateWalletUI({
              address: dummyAddress,
              balance: 100,
              currency: 'TON'
            });
            
            await supabase
              .from('transactions')
              .insert([
                {
                  user_id: state.currentUser.id,
                  amount: 100,
                  currency: 'TON',
                  type: 'deposit',
                  description: 'Пополнение баланса'
                }
              ]);
            
            showToast('TON кошелек успешно подключен', 'success');
          }
        } catch (error) {
          console.error('Error connecting wallet:', error);
          showToast('Ошибка подключения кошелька', 'error');
        } finally {
          hideLoading();
        }
      }
    );
  }

  // Disconnect wallet
  async function disconnectWallet() {
    showConfirmation(
      'Отключение кошелька',
      'Вы уверены, что хотите отключить TON кошелек?',
      async () => {
        showLoading('Отключение кошелька...');
        
        try {
          document.getElementById('connectWalletBtn').classList.remove('hidden');
          document.getElementById('walletInfo').classList.add('hidden');
          state.walletConnected = false;
          
          showToast('TON кошелек отключен', 'info');
        } catch (error) {
          console.error('Error disconnecting wallet:', error);
          showToast('Ошибка отключения кошелька', 'error');
        } finally {
          hideLoading();
        }
      }
    );
  }

  // Show add request modal
  function showAddRequestModal() {
    document.getElementById('requestTitle').value = '';
    document.getElementById('requestDescription').value = '';
    document.getElementById('requestBudget').value = '';
    document.getElementById('requestDeadline').value = '7';
    document.getElementById('customDeadline').value = '';
    document.getElementById('customDeadlineContainer').classList.add('hidden');
    document.getElementById('requestFiles').value = '';
    document.getElementById('requestFilesNames').classList.add('hidden');
    document.getElementById('requestFilesNames').textContent = '';
    state.selectedFiles = [];
    
    document.getElementById('requestTitleError').classList.add('hidden');
    document.getElementById('requestDescError').classList.add('hidden');
    document.getElementById('requestBudgetError').classList.add('hidden');
    document.getElementById('customDeadlineError').classList.add('hidden');
    document.getElementById('requestFilesError').classList.add('hidden');
    
    document.getElementById('addRequestModal').classList.remove('hidden');
  }

  // Close add request modal
  function closeAddRequestModal() {
    document.getElementById('addRequestModal').classList.add('hidden');
  }

  // Handle file upload for request
  function handleRequestFileUpload(event) {
    const files = event.target.files;
    if (files.length > 3) {
      document.getElementById('requestFilesError').classList.remove('hidden');
      document.getElementById('requestFilesError').textContent = 'Можно загрузить не более 3 файлов';
      return;
    }
    
    state.selectedFiles = Array.from(files);
    const filesNames = state.selectedFiles.map(file => file.name).join(', ');
    
    if (filesNames) {
      document.getElementById('requestFilesNames').textContent = filesNames;
      document.getElementById('requestFilesNames').classList.remove('hidden');
      document.getElementById('requestFilesError').classList.add('hidden');
    } else {
      document.getElementById('requestFilesNames').classList.add('hidden');
    }
  }

  // Submit new request
  async function submitNewRequest() {
    const title = document.getElementById('requestTitle').value.trim();
    const description = document.getElementById('requestDescription').value.trim();
    const budget = parseFloat(document.getElementById('requestBudget').value) || 0;
    let deadline = document.getElementById('requestDeadline').value;
    
    if (deadline === 'custom') {
      deadline = document.getElementById('customDeadline').value;
    }
    
    let isValid = true;
    
    if (!title) {
      document.getElementById('requestTitleError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('requestTitleError').classList.add('hidden');
    }
    
    if (!description) {
      document.getElementById('requestDescError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('requestDescError').classList.add('hidden');
    }
    
    if (!budget || budget <= 0) {
      document.getElementById('requestBudgetError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('requestBudgetError').classList.add('hidden');
    }
    
    if (deadline === 'custom') {
      const customDeadline = document.getElementById('customDeadline').value;
      if (!customDeadline || customDeadline <= 0) {
        document.getElementById('customDeadlineError').classList.remove('hidden');
        isValid = false;
      } else {
        document.getElementById('customDeadlineError').classList.add('hidden');
      }
    }
    
    if (!isValid) {
      showToast('Пожалуйста, заполните все обязательные поля', 'error');
      return;
    }
    
    showLoading('Создание заявки...');
    
    try {
      const { data, error } = await supabase
        .from('requests')
        .insert([
          {
            customer_id: state.currentUser.id,
            title: title,
            description: description,
            budget: budget,
            deadline: deadline,
            status: 'open'
          }
        ])
        .select();
      
      if (error) throw error;
      
      if (data) {
        showToast('Заявка успешно создана', 'success');
        closeAddRequestModal();
        await loadRequests();
      }
    } catch (error) {
      console.error('Error creating request:', error);
      showToast('Ошибка при создании заявки', 'error');
    } finally {
      hideLoading();
    }
  }

  // Show add template modal
  function showAddTemplateModal() {
    document.getElementById('templateTitle').value = '';
    document.getElementById('templateDescription').value = '';
    document.getElementById('templatePrice').value = '';
    document.getElementById('templateCategory').value = 'UI';
    document.getElementById('templateFile').value = '';
    document.getElementById('templateFileName').classList.add('hidden');
    document.getElementById('templateFileName').textContent = '';
    document.getElementById('templatePreview').value = '';
    document.getElementById('templatePreviewName').classList.add('hidden');
    document.getElementById('templatePreviewName').textContent = '';
    state.selectedTemplateFile = null;
    state.selectedTemplatePreview = null;
    
    document.getElementById('templateTitleError').classList.add('hidden');
    document.getElementById('templateDescError').classList.add('hidden');
    document.getElementById('templatePriceError').classList.add('hidden');
    document.getElementById('templateFileError').classList.add('hidden');
    
    document.getElementById('addTemplateModal').classList.remove('hidden');
  }

  // Close add template modal
  function closeAddTemplateModal() {
    document.getElementById('addTemplateModal').classList.add('hidden');
  }

  // Handle template file upload
  function handleTemplateFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.name.endsWith('.zip')) {
      document.getElementById('templateFileError').classList.remove('hidden');
      document.getElementById('templateFileError').textContent = 'Файл должен быть в формате ZIP';
      return;
    }
    
    state.selectedTemplateFile = file;
    document.getElementById('templateFileName').textContent = file.name;
    document.getElementById('templateFileName').classList.remove('hidden');
    document.getElementById('templateFileError').classList.add('hidden');
  }

  // Handle template preview upload
  function handleTemplatePreviewUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
      showToast('Пожалуйста, загрузите изображение', 'error');
      return;
    }
    
    state.selectedTemplatePreview = file;
    document.getElementById('templatePreviewName').textContent = file.name;
    document.getElementById('templatePreviewName').classList.remove('hidden');
  }

  // Submit new template
  async function submitNewTemplate() {
    const title = document.getElementById('templateTitle').value.trim();
    const description = document.getElementById('templateDescription').value.trim();
    const price = parseFloat(document.getElementById('templatePrice').value) || 0;
    const category = document.getElementById('templateCategory').value;
    
    let isValid = true;
    
    if (!title) {
      document.getElementById('templateTitleError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('templateTitleError').classList.add('hidden');
    }
    
    if (!description) {
      document.getElementById('templateDescError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('templateDescError').classList.add('hidden');
    }
    
    if (!price || price <= 0) {
      document.getElementById('templatePriceError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('templatePriceError').classList.add('hidden');
    }
    
    if (!state.selectedTemplateFile) {
      document.getElementById('templateFileError').classList.remove('hidden');
      isValid = false;
    } else {
      document.getElementById('templateFileError').classList.add('hidden');
    }
    
    if (!isValid) {
      showToast('Пожалуйста, заполните все обязательные поля', 'error');
      return;
    }
    
    showLoading('Отправка шаблона...');
    
    try {
      const { data, error } = await supabase
        .from('templates')
        .insert([
          {
            developer_id: state.currentUser.id,
            title: title,
            description: description,
            price: price,
            category: category,
            status: 'pending'
          }
        ])
        .select();
      
      if (error) throw error;
      
      if (data) {
        showToast('Шаблон успешно отправлен на модерацию', 'success');
        closeAddTemplateModal();
        await loadTemplates();
      }
    } catch (error) {
      console.error('Error submitting template:', error);
      showToast('Ошибка при отправке шаблона', 'error');
    } finally {
      hideLoading();
    }
  }

  // Switch tab
  function switchTab(tab) {
    if (tab === state.currentTab) return;
    
    document.getElementById(`tabContent-${state.currentTab}`).classList.add('hidden');
    
    const currentTabIcon = document.getElementById(`tab-icon-${state.currentTab}`);
    const currentTabLabel = document.querySelector(`#tab-${state.currentTab} span`);
    
    if (currentTabIcon && currentTabLabel) {
      currentTabIcon.setAttribute('name', tabIcons[state.currentTab].outline);
      currentTabIcon.classList.remove('text-blue-600');
      currentTabIcon.classList.add('text-gray-600');
      currentTabLabel.classList.remove('text-blue-600');
      currentTabLabel.classList.add('text-gray-600');
    }
    
    document.getElementById(`tabContent-${tab}`).classList.remove('hidden');
    state.currentTab = tab;
    document.getElementById('topBarTitle').textContent = tabTitles[tab] || '';
    
    const newTabIcon = document.getElementById(`tab-icon-${tab}`);
    const newTabLabel = document.querySelector(`#tab-${tab} span`);
    
    if (newTabIcon && newTabLabel) {
      newTabIcon.setAttribute('name', tabIcons[tab].filled);
      newTabIcon.classList.remove('text-gray-600');
      newTabIcon.classList.add('text-blue-600');
      newTabLabel.classList.remove('text-gray-600');
      newTabLabel.classList.add('text-blue-600');
    }
    
    if (tab === 'articles' && state.articles.length === 0) {
      loadArticles();
    } else if (tab === 'market' && state.templates.length === 0) {
      loadTemplates();
    } else if (tab === 'requests') {
      loadRequests();
    } else if (tab === 'profile') {
      loadHistory();
    }
  }

  // Switch role
  async function switchRole(role) {
    if (role === state.currentRole) return;
    
    showConfirmation(
      'Смена роли',
      `Вы хотите переключиться в режим ${role === 'developer' ? 'разработчика' : 'заказчика'}?`,
      async () => {
        showLoading('Смена роли...');
        
        try {
          const { error } = await supabase
            .from('users')
            .update({ role: role })
            .eq('id', state.currentUser.id);
          
          if (error) throw error;
          
          state.currentRole = role;
          updateRoleUI();
          await loadRequests();
          
          showToast(`Режим ${role === 'developer' ? 'разработчика' : 'заказчика'} активирован`, 'success');
        } catch (error) {
          console.error('Error switching role:', error);
          showToast('Ошибка при смене роли', 'error');
        } finally {
          hideLoading();
        }
      }
    );
  }

  // Toggle notifications panel
  function toggleNotifications() {
    document.getElementById('notifPanel').classList.toggle('hidden');
    
    if (!document.getElementById('notifPanel').classList.contains('hidden')) {
      document.getElementById('notifDot').classList.add('hidden');
      
      if (state.currentUser) {
        supabase
          .from('notifications')
          .update({ is_read: true })
          .eq('user_id', state.currentUser.id)
          .eq('is_read', false);
      }
    }
  }

  // Mark all notifications as read
  async function markAllAsRead() {
    if (!state.currentUser) return;
    
    showLoading('Обновление уведомлений...');
    
    try {
      const { error } = await supabase
        .from('notifications')
        .update({ is_read: true })
        .eq('user_id', state.currentUser.id)
        .eq('is_read', false);
      
      if (error) throw error;
      
      showToast('Все уведомления помечены как прочитанные', 'success');
      document.getElementById('notifPanel').classList.add('hidden');
      document.getElementById('notifDot').classList.add('hidden');
    } catch (error) {
      console.error('Error marking notifications as read:', error);
      showToast('Ошибка при обновлении уведомлений', 'error');
    } finally {
      hideLoading();
    }
  }

  // Initialize theme
  function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      state.theme = savedTheme;
    } else {
      state.theme = 'system';
    }
    
    setTheme(state.theme);
  }

  // Set theme
  function setTheme(theme) {
    state.theme = theme;
    localStorage.setItem('theme', theme);
    
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else if (theme === 'light') {
      document.documentElement.classList.remove('dark');
    } else {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    }
    
    document.getElementById('themeLight').classList.toggle('border-blue-500', theme === 'light');
    document.getElementById('themeLight').classList.toggle('bg-blue-50', theme === 'light');
    document.getElementById('themeDark').classList.toggle('border-blue-500', theme === 'dark');
    document.getElementById('themeDark').classList.toggle('bg-blue-50', theme === 'dark');
    document.getElementById('themeSystem').classList.toggle('border-blue-500', theme === 'system');
    document.getElementById('themeSystem').classList.toggle('bg-blue-50', theme === 'system');
  }

  // Initialize language
  function initLanguage() {
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
      state.language = savedLanguage;
      document.getElementById('languageSelect').value = savedLanguage;
    }
  }

  // Initialize notifications settings
  function initNotifications() {
    const savedNotifications = localStorage.getItem('notifications');
    if (savedNotifications) {
      state.notifications = JSON.parse(savedNotifications);
      document.getElementById('notifMessages').checked = state.notifications.messages;
      document.getElementById('notifRequests').checked = state.notifications.requests;
      document.getElementById('notifNews').checked = state.notifications.news;
    }
  }

  // Show settings modal
  function showSettingsModal() {
    document.getElementById('settingsModal').classList.remove('hidden');
  }

  // Close settings modal
  function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
  }

  // Save settings
  async function saveSettings() {
    const language = document.getElementById('languageSelect').value;
    const notifications = {
      messages: document.getElementById('notifMessages').checked,
      requests: document.getElementById('notifRequests').checked,
      news: document.getElementById('notifNews').checked
    };
    
    state.language = language;
    state.notifications = notifications;
    
    localStorage.setItem('language', language);
    localStorage.setItem('notifications', JSON.stringify(notifications));
    
    showToast('Настройки сохранены', 'success');
    closeSettingsModal();
  }

  // Show help modal
  function showHelpModal() {
    document.getElementById('helpModal').classList.remove('hidden');
  }

  // Close help modal
  function closeHelpModal() {
    document.getElementById('helpModal').classList.add('hidden');
  }

  // Show about modal
  function showAboutModal() {
    document.getElementById('aboutModal').classList.remove('hidden');
  }

  // Close about modal
  function closeAboutModal() {
    document.getElementById('aboutModal').classList.add('hidden');
  }

  // Show confirmation dialog
  function showConfirmation(title, message, confirmCallback) {
    document.getElementById('confirmationTitle').textContent = title;
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmationModal').classList.remove('hidden');
    
    const confirmBtn = document.getElementById('confirmActionBtn');
    confirmBtn.onclick = () => {
      document.getElementById('confirmationModal').classList.add('hidden');
      if (confirmCallback) confirmCallback();
    };
  }

  // Toggle filters panel for templates
  function toggleTemplateFilters() {
    document.getElementById('marketFilters').classList.toggle('hidden');
  }

  // Apply template filters
  function applyTemplateFilters() {
    const category = document.getElementById('templateCategoryFilter').value;
    const price = document.getElementById('templatePriceFilter').value;
    
    const filters = {};
    if (category !== 'all') filters.category = category;
    if (price !== 'all') filters.price = price;
    
    loadTemplates(filters);
    document.getElementById('marketFilters').classList.add('hidden');
  }

  // Reset template filters
  function resetTemplateFilters() {
    document.getElementById('templateCategoryFilter').value = 'all';
    document.getElementById('templatePriceFilter').value = 'all';
    loadTemplates();
    document.getElementById('marketFilters').classList.add('hidden');
  }

  // Toggle filters panel for requests
  function toggleRequestFilters() {
    document.getElementById('requestFilters').classList.toggle('hidden');
  }

  // Apply request filters
  function applyRequestFilters() {
    const budget = document.getElementById('requestBudgetFilter').value;
    const deadline = document.getElementById('requestDeadlineFilter').value;
    
    const filters = {};
    if (budget !== 'all') filters.budget = budget;
    if (deadline !== 'all') filters.deadline = deadline;
    
    loadRequests(filters);
    document.getElementById('requestFilters').classList.add('hidden');
  }

  // Reset request filters
  function resetRequestFilters() {
    document.getElementById('requestBudgetFilter').value = 'all';
    document.getElementById('requestDeadlineFilter').value = 'all';
    loadRequests();
    document.getElementById('requestFilters').classList.add('hidden');
  }

  // Show loading overlay
  function showLoading(message = 'Загрузка...') {
    document.getElementById('loadingMessage').textContent = message;
    document.getElementById('loadingOverlay').classList.remove('hidden');
    state.isLoading = true;
  }

  // Hide loading overlay
  function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
    state.isLoading = false;
  }

  // Show toast notification
  function showToast(message, type = 'info') {
    const colors = {
      'info': 'bg-blue-500',
      'success': 'bg-green-500',
      'error': 'bg-red-500',
      'warning': 'bg-yellow-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed bottom-16 left-1/2 transform -translate-x-1/2 text-white px-4 py-2 rounded-lg shadow-lg ${colors[type]} fade-in flex items-center`;
    toast.innerHTML = `
      <ion-icon name="${type === 'success' ? 'checkmark-circle' : 
                      type === 'error' ? 'alert-circle' : 
                      type === 'warning' ? 'warning' : 'information-circle'}" 
                class="mr-2"></ion-icon>
      <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Setup event listeners
  function setupEventListeners() {
    // Tab buttons
    document.getElementById('tab-articles').addEventListener('click', () => switchTab('articles'));
    document.getElementById('tab-market').addEventListener('click', () => switchTab('market'));
    document.getElementById('tab-requests').addEventListener('click', () => switchTab('requests'));
    document.getElementById('tab-profile').addEventListener('click', () => switchTab('profile'));
    
    // Role toggle
    document.getElementById('roleCustBtn').addEventListener('click', () => switchRole('customer'));
    document.getElementById('roleDevBtn').addEventListener('click', () => switchRole('developer'));
    
    // Wallet
    document.getElementById('connectWalletBtn').addEventListener('click', connectWallet);
    document.getElementById('disconnectWalletBtn').addEventListener('click', disconnectWallet);
    
    // Notifications
    document.getElementById('notifIconWrapper').addEventListener('click', toggleNotifications);
    document.getElementById('markAllAsReadBtn').addEventListener('click', markAllAsRead);
    
    // Requests
    document.getElementById('newRequestBtn').addEventListener('click', showAddRequestModal);
    document.getElementById('createRequestBtn').addEventListener('click', showAddRequestModal);
    document.getElementById('refreshRequestsBtn').addEventListener('click', () => loadRequests());
    
    // Request modal
    document.getElementById('closeRequestModalBtn').addEventListener('click', closeAddRequestModal);
    document.getElementById('cancelRequestBtn').addEventListener('click', closeAddRequestModal);
    document.getElementById('submitRequestBtn').addEventListener('click', submitNewRequest);
    document.getElementById('requestDeadline').addEventListener('change', function() {
      document.getElementById('customDeadlineContainer').classList.toggle('hidden', this.value !== 'custom');
    });
    document.getElementById('fileUploadArea').addEventListener('click', () => {
      document.getElementById('requestFiles').click();
    });
    document.getElementById('requestFiles').addEventListener('change', handleRequestFileUpload);
    
    // Templates
    document.getElementById('addTemplateCard').addEventListener('click', showAddTemplateModal);
    document.getElementById('filterTemplatesBtn').addEventListener('click', toggleTemplateFilters);
    document.getElementById('applyFiltersBtn').addEventListener('click', applyTemplateFilters);
    document.getElementById('resetFiltersBtn').addEventListener('click', resetTemplateFilters);
    document.getElementById('resetFiltersBtn2').addEventListener('click', resetTemplateFilters);
    
    // Template modal
    document.getElementById('closeTemplateModalBtn').addEventListener('click', closeAddTemplateModal);
    document.getElementById('cancelTemplateBtn').addEventListener('click', closeAddTemplateModal);
    document.getElementById('submitTemplateBtn').addEventListener('click', submitNewTemplate);
    document.getElementById('templateFileUpload').addEventListener('click', () => {
      document.getElementById('templateFile').click();
    });
    document.getElementById('templateFile').addEventListener('change', handleTemplateFileUpload);
    document.getElementById('templatePreviewUpload').addEventListener('click', () => {
      document.getElementById('templatePreview').click();
    });
    document.getElementById('templatePreview').addEventListener('change', handleTemplatePreviewUpload);
    
    // Requests filters
    document.getElementById('filterRequestsBtn').addEventListener('click', toggleRequestFilters);
    document.getElementById('applyRequestFiltersBtn').addEventListener('click', applyRequestFilters);
    document.getElementById('resetRequestFiltersBtn').addEventListener('click', resetRequestFilters);
    
    // Chat
    document.getElementById('closeChatBtn').addEventListener('click', closeChat);
    document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });
    document.getElementById('attachFileBtn').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('attachmentOptions').classList.toggle('hidden');
    });
    
    // Close attachment options when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('#attachmentOptions') && !e.target.closest('#attachFileBtn')) {
        document.getElementById('attachmentOptions').classList.add('hidden');
      }
    });
    
    // Profile buttons
    document.getElementById('settingsBtn').addEventListener('click', showSettingsModal);
    document.getElementById('helpBtn').addEventListener('click', showHelpModal);
    document.getElementById('aboutBtn').addEventListener('click', showAboutModal);
    document.getElementById('loadMoreHistory').addEventListener('click', loadMoreHistory);
    
    // Settings modal
    document.getElementById('closeSettingsBtn').addEventListener('click', closeSettingsModal);
    document.getElementById('saveSettingsBtn').addEventListener('click', saveSettings);
    
    // Theme buttons
    document.getElementById('themeLight').addEventListener('click', () => setTheme('light'));
    document.getElementById('themeDark').addEventListener('click', () => setTheme('dark'));
    document.getElementById('themeSystem').addEventListener('click', () => setTheme('system'));
    
    // Help modal
    document.getElementById('closeHelpBtn').addEventListener('click', closeHelpModal);
    
    // About modal
    document.getElementById('closeAboutBtn').addEventListener('click', closeAboutModal);
    
    // Confirmation modal
    document.getElementById('cancelConfirmBtn').addEventListener('click', () => {
      document.getElementById('confirmationModal').classList.add('hidden');
    });
    
    // Close modals when clicking outside
    const modals = ['addRequestModal', 'addTemplateModal', 'settingsModal', 'helpModal', 'aboutModal', 'confirmationModal'];
    modals.forEach(modalId => {
      document.getElementById(modalId).addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.add('hidden');
        }
      });
    });
  }
</script>

</body>
</html>
