(() => {
  const el = window.BMElements;
  const { showAlert, saveBaseUrl, setToken, clearToken } = window.BMUtils;
  const getCurrentToken = () => localStorage.getItem("bm_token") || "";
  const isLoggedIn = () => !!getCurrentToken();
  let currentUserRole = localStorage.getItem("bm_user_role") || "";

  const updateNavByAuth = async () => {
    console.log("updateNavByAuth called, isLoggedIn:", isLoggedIn(), "currentUserRole:", currentUserRole);
    window.BMState.token = getCurrentToken();
    
    if (isLoggedIn() && !currentUserRole) {
      // Fetch user info to get role
      try {
        console.log("Fetching user roles...");
        const userRes = await window.BMAuth.me();
        console.log("User response:", userRes);
        if (userRes?.data?.roles && Array.isArray(userRes.data.roles)) {
          // Get role codes from roles array
          const roleCodes = userRes.data.roles.map(r => r.code).join(",");
          currentUserRole = roleCodes;
          localStorage.setItem("bm_user_role", currentUserRole);
          console.log("User roles fetched:", roleCodes);
        }
      } catch (err) {
        console.error("Error fetching user role:", err);
      }
    }
    
    if (!isLoggedIn()) {
      currentUserRole = "";
      localStorage.removeItem("bm_user_role");
    }

    // Show "Bán xe" only for sellers/shop owners, not for buyers
    if (el.navBanXe) {
      // Check if user has seller or shop owner role
      const isSeller = currentUserRole.includes("seller") || currentUserRole.includes("shop_owner");
      el.navBanXe.style.display = isSeller ? "block" : "none";
      console.log("navBanXe check - Role:", currentUserRole, "isSeller:", isSeller, "Display:", el.navBanXe.style.display);
    }
    
    if (el.btnLoginNav) {
      if (isLoggedIn()) {
        el.btnLoginNav.href = "./profile.html";
        if (el.btnLoginNav.classList.contains("nav-icon-btn")) {
          el.btnLoginNav.innerHTML = '<i class="bi bi-person-check"></i>';
          el.btnLoginNav.setAttribute("aria-label", "Tài khoản");
          el.btnLoginNav.title = "Tài khoản";
        } else {
          el.btnLoginNav.innerHTML = '<i class="bi bi-person-check"></i> Tài khoản';
        }
      } else {
        el.btnLoginNav.href = "./login.html#login";
        if (el.btnLoginNav.classList.contains("nav-icon-btn")) {
          el.btnLoginNav.innerHTML = '<i class="bi bi-person-circle"></i>';
          el.btnLoginNav.setAttribute("aria-label", "Đăng nhập");
          el.btnLoginNav.title = "Đăng nhập";
        } else {
          el.btnLoginNav.innerHTML = '<i class="bi bi-person-circle"></i> Đăng nhập';
        }
      }
    }
    if (el.btnLogoutNav) {
      el.btnLogoutNav.style.display = isLoggedIn() ? "inline-block" : "none";
    }
  };

  window.addEventListener("pageshow", () => {
    console.log("pageshow event triggered");
    updateNavByAuth();
  });

  window.addEventListener("storage", (event) => {
    if (event.key === "bm_token") {
      window.BMState.token = localStorage.getItem("bm_token") || "";
      currentUserRole = localStorage.getItem("bm_user_role") || "";
      updateNavByAuth();
    }
  });

  if (el.baseUrlInput) {
    el.baseUrlInput.value = window.BMState.baseUrl;
    el.baseUrlInput.addEventListener("change", saveBaseUrl);
  }

  if (el.loginForm) {
    el.loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      saveBaseUrl();
      try {
        const res = await window.BMAuth.loginFromForm();
        setToken(res.data.token);
        // Fetch user role immediately after login
        try {
          const userRes = await window.BMAuth.me();
          if (userRes?.data?.roles && Array.isArray(userRes.data.roles)) {
            const roleCodes = userRes.data.roles.map(r => r.code).join(",");
            currentUserRole = roleCodes;
            localStorage.setItem("bm_user_role", currentUserRole);
            console.log("User roles after login:", roleCodes);
          }
        } catch (err) {
          console.error("Error fetching user role:", err);
        }
        showAlert("Đăng nhập thành công", "success");
        await updateNavByAuth();
        if (window.location.pathname.endsWith("/login.html")) {
          setTimeout(() => {
            window.location.href = "./index.html";
          }, 400);
          return;
        }
        if (el.listingsTableWrap && el.conversationsWrap) {
          await Promise.all([window.BMListings.load(), window.BMChat.loadConversations()]);
        }
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.registerForm) {
    el.registerForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      console.log("Register form submitted");
      saveBaseUrl();
      try {
        const res = await window.BMAuth.registerFromForm();
        if (res?.ok) {
          const token = res?.data?.token;
          if (token) {
            setToken(token);
            updateNavByAuth();
            showAlert("Đăng ký thành công, bạn đã được đăng nhập.", "success");
            if (window.location.pathname.endsWith("/login.html")) {
              setTimeout(() => {
                window.location.href = "./index.html";
              }, 400);
            }
          } else {
            showAlert("Đăng ký thành công, vui lòng đăng nhập.", "success");
            const loginTabBtn = document.getElementById("loginTabBtn");
            if (loginTabBtn && window.bootstrap?.Tab) {
              window.bootstrap.Tab.getOrCreateInstance(loginTabBtn).show();
            }
          }
        }
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.uploadForm) {
    el.uploadForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        const res = await window.BMUpload.uploadFromForm();
        const uploadedUrl = res?.data?.url || "";
        if (el.uploadedUrl) {
          el.uploadedUrl.value = uploadedUrl;
        }
        if (el.uploadedPreview && el.uploadedPreviewWrap) {
          if (uploadedUrl) {
            el.uploadedPreview.src = uploadedUrl;
            el.uploadedPreviewWrap.classList.remove("d-none");
          } else {
            el.uploadedPreview.removeAttribute("src");
            el.uploadedPreviewWrap.classList.add("d-none");
          }
        }
        showAlert("Upload success", "success");
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.listingForm) {
    el.listingForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        await window.BMListings.createFromForm();
        showAlert("Listing created", "success");
        el.listingForm.reset();
        await window.BMListings.load();
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.listingFilterForm) {
    window.BMListings.initFiltersFromUrl();
    el.listingFilterForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        window.BMListings.setFiltersFromForm();
        await window.BMListings.load();
        showAlert("Filter applied", "info");
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.listingsTableWrap) {
    el.listingsTableWrap.addEventListener("click", async (e) => {
      const target = e.target;
      if (!(target instanceof HTMLElement)) return;

      const editBtn = target.closest(".btn-edit-listing");
      if (editBtn instanceof HTMLElement) {
        const id = Number(editBtn.dataset.id);
        const title = window.prompt("New title:");
        if (!title) return;
        const priceRaw = window.prompt("New price:");
        if (!priceRaw) return;
        try {
          await window.BMListings.update(id, {
            title,
            price: Number(priceRaw),
          });
          showAlert("Listing updated", "success");
          await window.BMListings.load();
        } catch (err) {
          showAlert(err.message, "danger");
        }
      }

      const deleteBtn = target.closest(".btn-delete-listing");
      if (deleteBtn instanceof HTMLElement) {
        const id = Number(deleteBtn.dataset.id);
        if (!window.confirm(`Delete listing #${id}?`)) return;
        try {
          await window.BMListings.remove(id);
          showAlert("Listing deleted", "warning");
          await window.BMListings.load();
        } catch (err) {
          showAlert(err.message, "danger");
        }
      }
    });
  }

  if (el.createConversationForm) {
    el.createConversationForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        const res = await window.BMChat.createConversationFromForm();
        showAlert(`Conversation #${res.data.id} ready`, "success");
        await window.BMChat.loadConversations();
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.messageForm) {
    el.messageForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        await window.BMChat.sendMessageFromForm();
        showAlert("Message sent", "success");
        const conversationId = new FormData(el.messageForm).get("conversation_id");
        el.messageForm.reset();
        await window.BMChat.loadConversations();
        if (conversationId) {
          await window.BMChat.loadMessages(conversationId);
        }
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.btnReloadListings) {
    el.btnReloadListings.addEventListener("click", window.BMListings.load);
  }

  if (el.btnLoadMe) {
    el.btnLoadMe.addEventListener("click", async () => {
      try {
        const res = await window.BMAuth.me();
        const user = res.data.user;
        showAlert(`Logged in as: ${user.full_name} (${user.email})`, "info");
        if (el.profileForm) {
          const setValue = (name, value) => {
            const field = el.profileForm.elements.namedItem(name);
            if (field) field.value = value || "";
          };
          setValue("full_name", user.full_name);
          setValue("phone", user.phone);
          setValue("avatar_url", user.avatar_url);
          setValue("bio", user.bio);
          setValue("province", user.province);
          setValue("district", user.district);
        }
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.btnLogout) {
    el.btnLogout.addEventListener("click", () => {
      clearToken();
      showAlert("Đã đăng xuất", "warning");
      updateNavByAuth();
      setTimeout(() => {
        window.location.href = "./index.html";
      }, 150);
    });
  }
  if (el.btnLogoutNav) {
    el.btnLogoutNav.addEventListener("click", () => {
      clearToken();
      showAlert("Đã đăng xuất", "warning");
      updateNavByAuth();
      setTimeout(() => {
        window.location.href = "./index.html";
      }, 150);
    });
  }

  if (el.btnReloadConversations) {
    el.btnReloadConversations.addEventListener("click", window.BMChat.loadConversations);
  }

  if (el.btnLoadMessages) {
    el.btnLoadMessages.addEventListener("click", () => {
      const id = new FormData(el.messageForm).get("conversation_id");
      window.BMChat.loadMessages(id);
    });
  }

  if (el.profileForm) {
    el.profileForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        await window.BMAuth.updateProfileFromForm();
        showAlert("Profile updated", "success");
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.passwordForm) {
    el.passwordForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        await window.BMAuth.changePasswordFromForm();
        showAlert("Password changed", "success");
        el.passwordForm.reset();
      } catch (err) {
        showAlert(err.message, "danger");
      }
    });
  }

  if (el.listingsTableWrap) {
    window.BMListings.load();
  }
  if (el.homeProductList) {
    window.BMListings.loadHome();
  }
  if (el.conversationsWrap) {
    window.BMChat.loadConversations();
  }
  updateNavByAuth();
})();
