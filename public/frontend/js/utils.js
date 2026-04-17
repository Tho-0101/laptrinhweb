window.BMUtils = {
  showAlert(message, type = "info") {
    let { alertBox } = window.BMElements;
    if (!alertBox) {
      alertBox = document.getElementById("alertBox");
    }
    if (!alertBox) {
      console.warn("alertBox element not found");
      return;
    }
    alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => {
      alertBox.innerHTML = "";
    }, 3000);
  },

  saveBaseUrl() {
    const state = window.BMState;
    const value = window.BMElements.baseUrlInput.value.trim().replace(/\/$/, "");
    state.baseUrl = value;
    localStorage.setItem("bm_base_url", value);
  },

  setToken(token) {
    window.BMState.token = token;
    localStorage.setItem("bm_token", token);
  },

  clearToken() {
    window.BMState.token = "";
    localStorage.removeItem("bm_token");
  },
};
