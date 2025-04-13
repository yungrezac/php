// js/app.js
import { 
  supabase, getOrCreateUser, updateUserRole, updateUserBalance, loadArticles, loadTemplates, 
  loadCustomerRequests, loadDeveloperRequests, loadNotifications, loadHistory, 
  sendChatMessage, loadChatMessages, subscribeChatMessages, getBotInfo 
} from "./supabase.js";
import { 
  showToast, showLoading, hideLoading, renderUserProfile, renderArticles, 
  renderTemplates, renderCustomerRequests, renderChat, appendChatMessage 
} from "./ui.js";

// Глобальное состояние приложения
window.app = {
  state: {
    currentTab: "requests",
    currentRole: "customer", // по умолчанию заказчик
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
   * Открытие полной статьи.
   */
  openArticle: async function(articleId) {
    showLoading("Загрузка статьи...");
    try {
      const { data, error } = await supabase
        .from("articles")
        .select("*")
        .eq("id", articleId)
        .single();
      if (error) throw error;
      if (data) {
        showToast(`Открыта статья: ${data.title}`, "info");
      }
    } catch (err) {
      console.error(err);
      showToast("Ошибка загрузки статьи", "error");
    } finally {
      hideLoading();
    }
  },
  /**
   * Открытие чата заявки.
   */
  openRequestChat: async function(requestId) {
    showLoading("Загрузка чата...");
    try {
      const { data: requestData, error } = await supabase
        .from("requests")
        .select("*, customer:users(*), developer:users(*)")
        .eq("id", requestId)
        .single();
      if (error) throw error;
      if (!requestData) throw new Error("Заявка не найдена");
      if (
        window.app.state.currentUser.id !== requestData.customer_id &&
        window.app.state.currentUser.id !== requestData.developer_id
      ) {
        throw new Error("Нет доступа к этому чату");
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
        const chatMessages = document.getElementById("chatMessages");
        chatMessages.scrollTop = chatMessages.scrollHeight;
      });
      document.getElementById("chatOverlay").classList.remove("hidden");
    } catch (err) {
      console.error(err);
      showToast(`Ошибка: ${err.message}`, "error");
    } finally {
      hideLoading();
    }
  },
  /**
   * Отправка сообщения в чат.
   */
  sendMessage: async function() {
    const chatInput = document.getElementById("chatInput");
    const text = chatInput.value.trim();
    if (!text || !window.app.state.currentRequestId || !window.app.state.currentUser) return;
    showLoading("Отправка сообщения...");
    try {
      await sendChatMessage(window.app.state.currentRequestId, window.app.state.currentUser.id, text);
      chatInput.value = "";
    } catch (err) {
      console.error(err);
      showToast("Ошибка отправки сообщения", "error");
    } finally {
      hideLoading();
    }
  },
  /**
   * Создание новой заявки.
   * Заказчик должен списать с баланса сумму (бюджет + 5%).
   */
  createRequest: async function(requestData) {
    const totalCost = requestData.budget * 1.05;
    const currentBalance = window.app.state.currentUser.balance || 0;
    if (currentBalance < totalCost) {
      showToast(`Недостаточно средств. Пополните баланс на ${(totalCost - currentBalance).toFixed(2)} TON`, "error");
      return;
    }
    showLoading("Создание заявки...");
    try {
      const newBalance = currentBalance - totalCost;
      await updateUserBalance(window.app.state.currentUser.id, newBalance);
      window.app.state.currentUser.balance = newBalance;
      document.getElementById("userBalance").textContent = newBalance + " TON";
      
      const { data, error } = await supabase
        .from("requests")
        .insert([{
          customer_id: window.app.state.currentUser.id,
          ...requestData,
          status: "open",
          paid_amount: totalCost,
          created_at: new Date().toISOString()
        }])
        .select();
      if (error) throw error;
      showToast("Заявка успешно создана", "success");
      await loadAndRenderRequests();
    } catch (err) {
      console.error(err);
      showToast("Ошибка создания заявки", "error");
    } finally {
      hideLoading();
    }
  },
  /**
   * Завершение заявки (для разработчика).
   * Разработчику начисляется 85% от бюджета заявки.
   */
  completeRequest: async function(requestId) {
    showLoading("Завершение заявки...");
    try {
      const { data: requestData, error } = await supabase
        .from("requests")
        .select("*")
        .eq("id", requestId)
        .single();
      if (error) throw error;
      if (!requestData) throw new Error("Заявка не найдена");
      const earning = requestData.budget * 0.85;
      const { error: updateError } = await supabase
        .from("requests")
        .update({ status: "completed" })
        .eq("id", requestId);
      if (updateError) throw updateError;
      const { data: devData } = await supabase
        .from("users")
        .select("balance")
        .eq("id", window.app.state.currentUser.id)
        .single();
      const newBalance = (devData.balance || 0) + earning;
      await updateUserBalance(window.app.state.currentUser.id, newBalance);
      showToast("Заявка завершена. Заработано: " + earning.toFixed(2) + " TON", "success");
      await loadAndRenderRequests();
      closeChat();
    } catch (err) {
      console.error(err);
      showToast("Ошибка завершения заявки", "error");
    } finally {
      hideLoading();
    }
  },
  /**
   * Создание нового шаблона.
   * При размещении цена для покупателя увеличивается на 5% (показывается увеличенная стоимость), но разработчику начисляется исходная цена.
   */
  createTemplate: async function(templateData) {
    // Рассчитываем цену для покупателя (базовая цена * 1.05)
    const displayedPrice = templateData.price * 1.05;
    showLoading("Отправка шаблона...");
    try {
      const { data, error } = await supabase
        .from("templates")
        .insert([{
          developer_id: window.app.state.currentUser.id,
          ...templateData,
          price: displayedPrice,
          status: "pending",
          created_at: new Date().toISOString()
        }])
        .select();
      if (error) throw error;
      showToast("Шаблон успешно отправлен на модерацию", "success");
    } catch (err) {
      console.error(err);
      showToast("Ошибка отправки шаблона", "error");
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
    if (window.app.state.currentRole === "customer") {
      const requests = await loadCustomerRequests(window.app.state.currentUser.id);
      window.app.state.requests = requests;
      renderCustomerRequests(requests);
    } else {
      const requests = await loadDeveloperRequests();
      window.app.state.requests = requests;
      renderCustomerRequests(requests); // Функция рендера проверяет роль для отображения цены с -15%
    }
  } catch (err) {
    console.error(err);
    showToast("Ошибка загрузки заявок", "error");
  }
}

/**
 * Загрузка и рендеринг статей.
 */
async function loadAndRenderArticles() {
  showLoading("Загрузка статей...");
  try {
    const articles = await loadArticles();
    window.app.state.articlesCache = articles;
    window.app.state.articles = [...articles];
    renderArticles(articles);
  } catch (err) {
    console.error(err);
    showToast("Ошибка загрузки статей", "error");
  } finally {
    hideLoading();
  }
}

/**
 * Переключение вкладок.
 */
function switchTab(tab) {
  if (tab === window.app.state.currentTab) return;
  document.querySelectorAll(".tabContent").forEach(el => el.classList.add("hidden"));
  document.getElementById(`tabContent-${tab}`).classList.remove("hidden");
  window.app.state.currentTab = tab;
  const titles = { articles: "Статьи", market: "Маркет", requests: "Заявки", profile: "Профиль" };
  document.getElementById("topBarTitle").textContent = titles[tab] || "";
  if (tab === "articles" && !window.app.state.articlesCache.length) {
    loadAndRenderArticles();
  } else if (tab === "requests") {
    loadAndRenderRequests();
  }
}

/**
 * Открытие модального окна создания заявки.
 */
function openCreateRequestModal() {
  document.getElementById("addRequestModal").classList.remove("hidden");
}

/**
 * Закрытие модального окна создания заявки.
 */
function closeCreateRequestModal() {
  document.getElementById("addRequestModal").classList.add("hidden");
}

/**
 * Обработка создания заявки.
 */
async function submitNewRequest() {
  const title = document.getElementById("requestTitle").value.trim();
  const description = document.getElementById("requestDescription").value.trim();
  const budget = parseFloat(document.getElementById("requestBudget").value);
  let deadline = document.getElementById("requestDeadline").value;
  if (deadline === "custom") {
    deadline = document.getElementById("customDeadline").value;
  }
  if (!title || !description || !budget || budget <= 0 || !deadline) {
    showToast("Пожалуйста, заполните все обязательные поля", "error");
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
  document.getElementById("chatOverlay").classList.add("hidden");
  window.app.state.currentRequestId = null;
  window.app.state.chatMessages = [];
}

/**
 * Переключение уведомлений (показ/скрытие панели).
 */
function toggleNotifications() {
  const panel = document.getElementById("notifPanel");
  panel.classList.toggle("hidden");
  if (!panel.classList.contains("hidden")) {
    document.getElementById("notifDot").classList.add("hidden");
    supabase.from("notifications")
      .update({ is_read: true })
      .eq("user_id", window.app.state.currentUser.id)
      .eq("is_read", false);
  }
}

/**
 * Пометка всех уведомлений как прочитанные.
 */
async function markAllAsRead() {
  showLoading("Обновление уведомлений...");
  try {
    const { error } = await supabase
      .from("notifications")
      .update({ is_read: true })
      .eq("user_id", window.app.state.currentUser.id)
      .eq("is_read", false);
    if (error) throw error;
    showToast("Все уведомления помечены как прочитанные", "success");
    document.getElementById("notifPanel").classList.add("hidden");
    document.getElementById("notifDot").classList.add("hidden");
  } catch (err) {
    console.error(err);
    showToast("Ошибка обновления уведомлений", "error");
  } finally {
    hideLoading();
  }
}

/**
 * Интеграция с Telegram WebApp API.
 */
function integrateWithTelegram() {
  if (window.Telegram && window.Telegram.WebApp) {
    window.Telegram.WebApp.expand();
    window.Telegram.WebApp.ready();
    window.Telegram.WebApp.setHeaderColor("#3b82f6");
    const tgUser = window.Telegram.WebApp.initDataUnsafe?.user;
    if (tgUser) {
      window.app.state.currentUser = {
        id: tgUser.id.toString(),
        first_name: tgUser.first_name || "Пользователь",
        last_name: tgUser.last_name || "",
        username: tgUser.username || "",
        avatar_url: tgUser.photo_url || "",
        balance: 0
      };
      renderUserProfile(window.app.state.currentUser);
    }
    // Демонстрация вызова Telegram Bot API (небезопасно для продакшена!)
    const TELEGRAM_BOT_TOKEN = "7320783045:AAEROEaHuhJAp1-i3Ji_iGokgV2UB_YLyeE";
    fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getMe`)
      .then(response => response.json())
      .then(data => {
        console.log("Telegram Bot Info:", data);
      })
      .catch(err => console.error("Ошибка получения данных о боте:", err));
      
    const closeBtn = document.createElement("button");
    closeBtn.textContent = "Закрыть приложение";
    closeBtn.className = "fixed top-2 right-2 bg-red-500 text-white px-3 py-1 rounded";
    closeBtn.addEventListener("click", () => {
      window.Telegram.WebApp.close();
    });
    document.body.appendChild(closeBtn);
  }
}

/**
 * Функция пополнения баланса через API Cryptobot (демонстрация).
 */
async function topUpBalance() {
  const amountStr = prompt("Введите сумму пополнения (TON):");
  const amount = parseFloat(amountStr);
  if (isNaN(amount) || amount <= 0) {
    showToast("Неверная сумма", "error");
    return;
  }
  showLoading("Пополнение баланса...");
  try {
    // Демонстрационный запрос – замените на реальную интеграцию с Cryptobot API
    await fetch("https://api.cryptobot.example.com/topup", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userId: window.app.state.currentUser.id, amount })
    });
    const currentBalance = window.app.state.currentUser.balance || 0;
    const newBalance = currentBalance + amount;
    await updateUserBalance(window.app.state.currentUser.id, newBalance);
    window.app.state.currentUser.balance = newBalance;
    document.getElementById("userBalance").textContent = newBalance + " TON";
    showToast("Баланс успешно пополнен", "success");
  } catch (err) {
    console.error(err);
    showToast("Ошибка пополнения баланса", "error");
  } finally {
    hideLoading();
  }
}

/**
 * Переключение роли между заказчиком и разработчиком.
 */
async function switchRole() {
  const newRole = window.app.state.currentRole === "customer" ? "developer" : "customer";
  showLoading("Переключение роли...");
  try {
    await updateUserRole(window.app.state.currentUser.id, newRole);
    window.app.state.currentRole = newRole;
    showToast("Роль переключена: " + (newRole === "customer" ? "Заказчик" : "Разработчик"), "success");
    switchTab("requests");
  } catch (err) {
    console.error(err);
    showToast("Ошибка переключения роли", "error");
  } finally {
    hideLoading();
  }
}

/**
 * Обработка создания шаблона.
 */
async function submitTemplate() {
  const title = document.getElementById("templateTitle").value.trim();
  const description = document.getElementById("templateDescription").value.trim();
  const priceInput = parseFloat(document.getElementById("templatePrice").value);
  const category = document.getElementById("templateCategory").value;
  if (!title || !description || isNaN(priceInput) || priceInput <= 0 || !category) {
    showToast("Пожалуйста, заполните все обязательные поля", "error");
    return;
  }
  const templateData = {
    title,
    description,
    price: priceInput, // исходная цена, для разработчика
    category
  };
  await window.app.createTemplate(templateData);
  document.getElementById("addTemplateModal").classList.add("hidden");
}

/**
 * Функция для разработчика: завершение заявки.
 */
async function completeRequest(requestId) {
  showLoading("Завершение заявки...");
  try {
    const { data: requestData, error } = await supabase
      .from("requests")
      .select("*")
      .eq("id", requestId)
      .single();
    if (error) throw error;
    if (!requestData) throw new Error("Заявка не найдена");
    const earning = requestData.budget * 0.85;
    const { error: updateError } = await supabase
      .from("requests")
      .update({ status: "completed" })
      .eq("id", requestId);
    if (updateError) throw updateError;
    const { data: devData } = await supabase
      .from("users")
      .select("balance")
      .eq("id", window.app.state.currentUser.id)
      .single();
    const newBalance = (devData.balance || 0) + earning;
    await updateUserBalance(window.app.state.currentUser.id, newBalance);
    showToast("Заявка завершена. Заработано: " + earning.toFixed(2) + " TON", "success");
    await loadAndRenderRequests();
    closeChat();
  } catch (err) {
    console.error(err);
    showToast("Ошибка завершения заявки", "error");
  } finally {
    hideLoading();
  }
}

/**
 * Установка обработчиков событий.
 */
function setupEventListeners() {
  // Переключение вкладок
  document.getElementById("tab-articles").addEventListener("click", () => switchTab("articles"));
  document.getElementById("tab-market").addEventListener("click", () => switchTab("market"));
  document.getElementById("tab-requests").addEventListener("click", () => switchTab("requests"));
  document.getElementById("tab-profile").addEventListener("click", () => switchTab("profile"));
  
  // Уведомления
  document.getElementById("notifIconWrapper").addEventListener("click", toggleNotifications);
  document.getElementById("markAllAsReadBtn").addEventListener("click", markAllAsRead);
  
  // Заявки
  document.getElementById("newRequestBtn").addEventListener("click", openCreateRequestModal);
  document.getElementById("cancelRequestBtn").addEventListener("click", closeCreateRequestModal);
  document.getElementById("submitRequestBtn").addEventListener("click", submitNewRequest);
  document.getElementById("requestDeadline").addEventListener("change", function() {
    document.getElementById("customDeadlineContainer").classList.toggle("hidden", this.value !== "custom");
  });
  
  // Чат
  document.getElementById("closeChatBtn").addEventListener("click", closeChat);
  document.getElementById("chatSendBtn").addEventListener("click", window.app.sendMessage);
  document.getElementById("chatInput").addEventListener("keypress", (e) => {
    if (e.key === "Enter") window.app.sendMessage();
  });
  document.getElementById("attachFileBtn").addEventListener("click", (e) => {
    e.stopPropagation();
    document.getElementById("attachmentOptions").classList.toggle("hidden");
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest("#attachmentOptions") && !e.target.closest("#attachFileBtn")) {
      document.getElementById("attachmentOptions").classList.add("hidden");
    }
  });
  
  // Переключение ролей
  document.getElementById("switchRoleBtn").addEventListener("click", switchRole);
  
  // Пополнение баланса
  document.getElementById("topUpBalanceBtn").addEventListener("click", topUpBalance);
  
  // Создание шаблона
  document.getElementById("submitTemplateBtn").addEventListener("click", submitTemplate);
}

/**
 * Инициализация приложения.
 */
async function init() {
  showLoading("Инициализация приложения...");
  try {
    integrateWithTelegram();
    const user = await getOrCreateUser();
    if (!window.app.state.currentUser) {
      window.app.state.currentUser = user;
    }
    renderUserProfile(window.app.state.currentUser);
    await loadAndRenderRequests();
    if (window.app.state.currentTab === "articles") {
      await loadAndRenderArticles();
    }
  } catch (err) {
    console.error(err);
    showToast(`Ошибка инициализации: ${err.message}`, "error");
  } finally {
    hideLoading();
  }
}

document.addEventListener("DOMContentLoaded", () => {
  setupEventListeners();
  init();
});
