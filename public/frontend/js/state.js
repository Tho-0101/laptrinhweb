window.BMState = {
  token: localStorage.getItem("bm_token") || "",
  baseUrl: localStorage.getItem("bm_base_url") || "http://localhost:8888/bike-shop1/public/index.php/api",
};

window.BMElements = {
  alertBox: null,
  baseUrlInput: null,
  loginForm: null,
  registerForm: null,
  uploadForm: null,
  uploadedUrl: null,
  uploadedPreview: null,
  uploadedPreviewWrap: null,
  listingForm: null,
  listingFilterForm: null,
  listingsTableWrap: null,
  homeProductList: null,
  btnReloadListings: null,
  btnLoadMe: null,
  btnLogout: null,
  createConversationForm: null,
  conversationsWrap: null,
  messageForm: null,
  btnReloadConversations: null,
  btnLoadMessages: null,
  messagesWrap: null,
  profileForm: null,
  passwordForm: null,
  navBanXe: null,
  btnLoginNav: null,
  btnLogoutNav: null,
};

// Initialize elements when DOM is ready
function initializeElements() {
  window.BMElements = {
    alertBox: document.getElementById("alertBox"),
    baseUrlInput: document.getElementById("baseUrlInput"),
    loginForm: document.getElementById("loginForm"),
    registerForm: document.getElementById("registerForm"),
    uploadForm: document.getElementById("uploadForm"),
    uploadedUrl: document.getElementById("uploadedUrl"),
    uploadedPreview: document.getElementById("uploadedPreview"),
    uploadedPreviewWrap: document.getElementById("uploadedPreviewWrap"),
    listingForm: document.getElementById("listingForm"),
    listingFilterForm: document.getElementById("listingFilterForm"),
    listingsTableWrap: document.getElementById("listingsTableWrap"),
    homeProductList: document.getElementById("homeProductList"),
    btnReloadListings: document.getElementById("btnReloadListings"),
    btnLoadMe: document.getElementById("btnLoadMe"),
    btnLogout: document.getElementById("btnLogout"),
    createConversationForm: document.getElementById("createConversationForm"),
    conversationsWrap: document.getElementById("conversationsWrap"),
    messageForm: document.getElementById("messageForm"),
    btnReloadConversations: document.getElementById("btnReloadConversations"),
    btnLoadMessages: document.getElementById("btnLoadMessages"),
    messagesWrap: document.getElementById("messagesWrap"),
    profileForm: document.getElementById("profileForm"),
    passwordForm: document.getElementById("passwordForm"),
    navBanXe: document.getElementById("navBanXe"),
    btnLoginNav: document.getElementById("btnLoginNav"),
    btnLogoutNav: document.getElementById("btnLogoutNav"),
  };
}

// Initialize immediately so scripts loaded at end of body can use BMElements right away.
initializeElements();

// Re-initialize on DOMContentLoaded to keep compatibility with pages that load scripts earlier.
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeElements);
}
