window.BMAuth = {
  async loginFromForm() {
    const form = new FormData(window.BMElements.loginForm);
    return window.BMApi.fetch("/login", {
      method: "POST",
      body: {
        email: form.get("email"),
        password: form.get("password"),
      },
    });
  },

  async registerFromForm() {
    const form = new FormData(window.BMElements.registerForm);
    return window.BMApi.fetch("/register", {
      method: "POST",
      body: {
        full_name: form.get("full_name"),
        email: form.get("email"),
        phone: form.get("phone"),
        password: form.get("password"),
        role: form.get("role") || "nguoi_mua",
      },
    });
  },

  async me() {
    return window.BMApi.fetch("/me");
  },

  async updateProfileFromForm() {
    const form = new FormData(window.BMElements.profileForm);
    return window.BMApi.fetch("/me", {
      method: "PUT",
      body: {
        full_name: form.get("full_name"),
        phone: form.get("phone"),
        avatar_url: form.get("avatar_url"),
        bio: form.get("bio"),
        province: form.get("province"),
        district: form.get("district"),
      },
    });
  },

  async changePasswordFromForm() {
    const form = new FormData(window.BMElements.passwordForm);
    return window.BMApi.fetch("/me/password", {
      method: "PUT",
      body: {
        current_password: form.get("current_password"),
        new_password: form.get("new_password"),
        confirm_password: form.get("confirm_password"),
      },
    });
  },
};
