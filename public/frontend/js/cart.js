window.BMCart = {
  items: JSON.parse(localStorage.getItem("bm_cart") || "[]"),

  init() {
    this.setupEventListeners();
    this.render();
  },

  setupEventListeners() {
    const navCartBtn = document.getElementById("navCartBtn");
    const closeCartBtn = document.getElementById("closeCartDropdown");

    if (navCartBtn) {
      navCartBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const dropdown = document.getElementById("cartDropdown");
        dropdown.classList.toggle("show");
        navCartBtn.setAttribute(
          "aria-expanded",
          dropdown.classList.contains("show"),
        );
      });
    }

    if (closeCartBtn) {
      closeCartBtn.addEventListener("click", () => {
        const dropdown = document.getElementById("cartDropdown");
        dropdown.classList.remove("show");
        navCartBtn && navCartBtn.setAttribute("aria-expanded", "false");
      });
    }

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      const container = document.querySelector(".nav-cart-container");
      const dropdown = document.getElementById("cartDropdown");
      if (container && !container.contains(e.target) && dropdown) {
        dropdown.classList.remove("show");
        navCartBtn && navCartBtn.setAttribute("aria-expanded", "false");
      }
    });
  },

  addItem(listing) {
    // Check if item already exists
    const existingItem = this.items.find((item) => item.id === listing.id);
    if (existingItem) {
      existingItem.qty = (existingItem.qty || 1) + 1;
    } else {
      this.items.push({
        id: listing.id,
        title: listing.title || listing.name,
        price: listing.price || 0,
        image: listing.primary_image || listing.image_url || listing.thumbnail,
        qty: 1,
      });
    }
    this.save();
    this.render();
    window.BMUtils.showAlert("Đã thêm vào giỏ hàng", "success");
  },

  removeItem(itemId) {
    this.items = this.items.filter((item) => item.id !== itemId);
    this.save();
    this.render();
  },

  updateQty(itemId, qty) {
    const item = this.items.find((item) => item.id === itemId);
    if (item) {
      item.qty = Math.max(1, qty);
      this.save();
      this.render();
    }
  },

  clear() {
    this.items = [];
    this.save();
    this.render();
  },

  getTotal() {
    return this.items.reduce(
      (total, item) => total + item.price * (item.qty || 1),
      0,
    );
  },

  getCount() {
    return this.items.reduce((count, item) => count + (item.qty || 1), 0);
  },

  save() {
    localStorage.setItem("bm_cart", JSON.stringify(this.items));
  },

  render() {
    this.updateBadge();
    this.updateDropdown();
  },

  updateBadge() {
    const badge = document.getElementById("cartBadge");
    const count = this.getCount();
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? "inline-block" : "none";
    }
  },

  updateDropdown() {
    const itemsContainer = document.getElementById("cartItems");
    const totalElement = document.getElementById("cartTotal");

    if (!itemsContainer) return;

    if (this.items.length === 0) {
      itemsContainer.innerHTML = `
        <div class="empty-cart-message">
          <i class="bi bi-cart-x"></i>
          <p>Giỏ hàng trống</p>
        </div>
      `;
      if (totalElement) {
        totalElement.textContent = "0 ₫";
      }
      return;
    }

    itemsContainer.innerHTML = this.items
      .map(
        (item) => `
      <div class="cart-item" data-item-id="${item.id}">
        <img
          src="${item.image || "https://via.placeholder.com/60?text=No+Image"}"
          alt="${item.title}"
          class="cart-item-image"
          onerror="this.src='https://via.placeholder.com/60?text=No+Image'"
        />
        <div class="cart-item-content">
          <div class="cart-item-title">${item.title}</div>
          <div class="cart-item-price">${Number(item.price || 0).toLocaleString("vi-VN")} ₫</div>
          <div class="cart-item-qty">SL: ${item.qty || 1}</div>
        </div>
        <button class="cart-item-remove btn-remove-item" data-item-id="${item.id}" title="Xóa">
          ×
        </button>
      </div>
    `,
      )
      .join("");

    // Add event listeners to remove buttons
    document.querySelectorAll(".btn-remove-item").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const itemId = parseInt(e.currentTarget.getAttribute("data-item-id"));
        this.removeItem(itemId);
      });
    });

    // Update total
    if (totalElement) {
      const total = this.getTotal();
      totalElement.textContent = Number(total).toLocaleString("vi-VN") + " ₫";
    }
  },
};

// Initialize cart when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    window.BMCart.init();
  });
} else {
  window.BMCart.init();
}
