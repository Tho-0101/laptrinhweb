window.BMListings = {
  currentRows: [],
  currentFilters: {
    page: 1,
    limit: 20,
    status: "",
    sort: "newest",
  },
  fallbackImage: "https://xedapgiakho.com/wp-content/uploads/2025/01/hinh-anh-xe-dap-hinh-nen-xe-dap-dep-6.jpg",
  extractPrimaryImage(row) {
    return (
      row?.primary_image ||
      row?.image_url ||
      (Array.isArray(row?.images) && row.images.length ? row.images[0]?.url || row.images[0]?.image_url || row.images[0] : "")
    );
  },

  async load() {
    const { listingsTableWrap } = window.BMElements;
    try {
      const query = new URLSearchParams();
      Object.entries(this.currentFilters).forEach(([key, value]) => {
        if (value === null || value === undefined || value === "") return;
        query.set(key, String(value));
      });
      const res = await window.BMApi.fetch(`/listings?${query.toString()}`);
      const rows = res.data || [];
      this.currentRows = rows;
      if (!rows.length) {
        listingsTableWrap.innerHTML = `
          <div class="listing-empty-state">
            <div class="listing-empty-inner">
              <h4 class="mb-2 empty-title">Rất tiếc, không tìm thấy chiếc xe nào phù hợp.</h4>
              <p class="mb-3 empty-subtitle">Bạn có thể thử lại với bộ lọc khác hoặc xem toàn bộ tin đăng.</p>
              <button class="btn btn-outline-primary btn-sm" id="btnResetFilter">Xem tất cả xe</button>
            </div>
            <div class="listing-empty-gallery" aria-hidden="true">
              <img src="https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8YmlrZXxlbnwwfHwwfHx8MA%3D%3D" alt="" width="600" height="400" loading="lazy" decoding="async">
              <img src="https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8YmlrZXxlbnwwfHwwfHx8MA%3D%3D" alt="" width="600" height="400" loading="lazy" decoding="async">
              <img src="https://images.unsplash.com/photo-1593764592116-bfb2a97c642a?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTl8fGJpa2V8ZW58MHx8MHx8fDA%3D" alt="" width="600" height="400" loading="lazy" decoding="async">
            </div>
          </div>
        `;
        const btnResetFilter = document.getElementById("btnResetFilter");
        if (btnResetFilter) {
          btnResetFilter.addEventListener("click", async () => {
            this.resetFilters();
            this.syncFilterForm();
            await this.load();
          });
        }
        return;
      }

      listingsTableWrap.innerHTML = `
        <div class="listing-grid">
          ${rows
            .map(
              (r) => {
                const imageSrc = this.resolveImageUrl(this.extractPrimaryImage(r));
                const imageAlt = r.title || "Bike image";
                const conditionBadgeColor = {
                  'excellent': 'bg-success',
                  'good': 'bg-primary', 
                  'fair': 'bg-warning',
                  'poor': 'bg-danger'
                }[r.condition || 'fair'] || 'bg-secondary';
                
                const conditionLabel = {
                  'excellent': 'Xuất sắc',
                  'good': 'Tốt',
                  'fair': 'Bình thường',
                  'poor': 'Cần sửa chữa'
                }[r.condition || 'fair'] || r.condition_level || 'Đã qua sử dụng';
                
                return `
            <div class="listing-item bike-card card border-0 shadow-sm">
              <div class="position-relative overflow-hidden">
                <img
                  src="${imageSrc}"
                  alt="${imageAlt}"
                  class="card-img-top"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='${this.fallbackImage}';"
                >
                <span class="badge ${conditionBadgeColor} position-absolute top-0 start-0 m-3">
                  ${conditionLabel}
                </span>
              </div>
              <div class="card-body pb-3">
                <h6 class="card-title fw-600">${r.title}</h6>
                
                <div class="price">${Number(r.price || 0).toLocaleString('vi-VN')} ₫</div>
                
                <div class="bike-info small">
                  ${r.brand_name ? `<span><i class="bi bi-tag"></i> ${r.brand_name}</span>` : ''}
                  ${r.bike_type_name ? `<span><i class="bi bi-gear"></i> ${r.bike_type_name}</span>` : ''}
                  ${r.location_province ? `<span><i class="bi bi-geo-alt"></i> ${r.location_province}</span>` : ''}
                </div>
                
                <div class="location small text-muted">
                  ${r.location_district ? r.location_district : 'Địa điểm không xác định'}
                </div>
                
                <div class="d-flex gap-2 mt-3">
                  <button class="btn btn-success btn-sm flex-grow-1 btn-add-to-cart" data-id="${r.id}" data-listing='${JSON.stringify({
                    id: r.id,
                    title: r.title,
                    price: r.price,
                    primary_image: imageSrc,
                  })}'>
                    <i class="bi bi-cart-plus"></i> Giỏ hàng
                  </button>
                  <button class="btn btn-outline-primary btn-sm flex-grow-1 btn-edit-listing" data-id="${r.id}">
                    <i class="bi bi-pencil"></i> Chi tiết
                  </button>
                  <button class="btn btn-outline-danger btn-sm btn-delete-listing" data-id="${r.id}" title="Xóa">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          `;
              },
            )
            .join("")}
        </div>
      `;
      
      // Add event listeners for cart buttons
      listingsTableWrap.querySelectorAll(".btn-add-to-cart").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.preventDefault();
          try {
            const listing = JSON.parse(btn.getAttribute("data-listing"));
            window.BMCart.addItem(listing);
          } catch (err) {
            window.BMUtils.showAlert("Lỗi khi thêm vào giỏ hàng", "danger");
          }
        });
      });
    } catch (err) {
      window.BMUtils.showAlert(err.message, "danger");
    }
  },

  async createFromForm() {
    const form = new FormData(window.BMElements.listingForm);
    await window.BMApi.fetch("/listings", {
      method: "POST",
      body: {
        bike_model_id: Number(form.get("bike_model_id")),
        title: form.get("title"),
        description: form.get("description"),
        condition_level: form.get("condition_level"),
        price: Number(form.get("price")),
        location_province: form.get("location_province"),
        location_district: form.get("location_district"),
        image_url: form.get("image_url"),
      },
    });
  },

  setFiltersFromForm() {
    const form = new FormData(window.BMElements.listingFilterForm);
    this.currentFilters = {
      page: 1,
      limit: 20,
      status: form.get("status") || "",
      keyword: form.get("keyword") || "",
      brand_id: form.get("brand_id") || "",
      bike_type_id: form.get("bike_type_id") || "",
      min_price: form.get("min_price") || "",
      max_price: form.get("max_price") || "",
      province: form.get("province") || "",
      district: form.get("district") || "",
      sort: form.get("sort") || "newest",
    };
  },

  resetFilters() {
    this.currentFilters = {
      page: 1,
      limit: 20,
      status: "",
      keyword: "",
      brand_id: "",
      bike_type_id: "",
      min_price: "",
      max_price: "",
      province: "",
      district: "",
      sort: "newest",
    };
  },

  syncFilterForm() {
    const form = window.BMElements.listingFilterForm;
    if (!form) return;
    Object.entries(this.currentFilters).forEach(([key, value]) => {
      const field = form.elements.namedItem(key);
      if (!field) return;
      field.value = value ?? "";
    });
  },

  initFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const category = params.get("category");
    const categoryMap = { road: "1", mtb: "2", touring: "4" };

    this.currentFilters = {
      ...this.currentFilters,
      keyword: params.get("keyword") || "",
      bike_type_id: params.get("bike_type_id") || categoryMap[category] || "",
      status: params.get("status") ?? "",
    };
    this.syncFilterForm();
  },

  async update(id, payload) {
    return window.BMApi.fetch(`/listings/${id}`, {
      method: "PUT",
      body: payload,
    });
  },

  async remove(id) {
    return window.BMApi.fetch(`/listings/${id}`, {
      method: "DELETE",
    });
  },

  async loadHome() {
    const { homeProductList } = window.BMElements;
    if (!homeProductList) return;
    const homeProductListHot = document.getElementById("homeProductListHot");
    const homeProductListRace = document.getElementById("homeProductListRace");
    const homeProductListSport = document.getElementById("homeProductListSport");
    try {
      const res = await window.BMApi.fetch("/listings?page=1&limit=20&status=published&sort=newest");
      const rows = res.data || [];
      const renderRows = (items) =>
        items
          .map(
            (r) => `
        <div class="col">
          <a href="./listings.html?keyword=${encodeURIComponent(r.title || "")}" class="text-decoration-none text-dark">
            <div class="card h-100 border-0 bike-card overflow-hidden">
              <div class="position-relative">
                <img
                  src="${this.resolveImageUrl(this.extractPrimaryImage(r))}"
                  class="card-img-top"
                  style="height: 115px; object-fit: cover"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='${this.fallbackImage}';"
                >
              </div>
              <div class="card-body">
                <h5 class="card-title fw-bold text-truncate">${r.title}</h5>
                <h4 class="text-primary fw-bold mb-1">${Number(r.price).toLocaleString()}đ</h4>
              </div>
            </div>
          </a>
        </div>
      `,
          )
          .join("");

      if (!rows.length) {
        homeProductList.innerHTML = '<div class="col-12 text-center text-muted py-5">Hiện chưa có chiếc xe nào được đăng bán.</div>';
        if (homeProductListHot) homeProductListHot.innerHTML = "";
        if (homeProductListRace) homeProductListRace.innerHTML = "";
        if (homeProductListSport) homeProductListSport.innerHTML = "";
        return;
      }

      homeProductList.innerHTML = renderRows(rows.slice(0, 6));
      if (homeProductListHot) homeProductListHot.innerHTML = renderRows(rows.slice(6, 12));
      if (homeProductListRace) homeProductListRace.innerHTML = renderRows(rows.slice(12, 18));
      if (homeProductListSport) homeProductListSport.innerHTML = renderRows(rows.slice(18, 24));
    } catch (err) {
      homeProductList.innerHTML = '<div class="col-12 text-center text-danger py-5">Lỗi tải dữ liệu trang chủ.</div>';
      if (homeProductListHot) homeProductListHot.innerHTML = "";
      if (homeProductListRace) homeProductListRace.innerHTML = "";
      if (homeProductListSport) homeProductListSport.innerHTML = "";
    }
  },

  resolveImageUrl(rawUrl) {
    const fallback = this.fallbackImage;
    if (!rawUrl) return fallback;
    const value = String(rawUrl).trim().replace(/\\/g, "/");
    if (!value) return fallback;
    if (/^https?:\/\//i.test(value) || value.startsWith("data:")) return value;

    const apiBase = window.BMState?.baseUrl || "";
    const origin = apiBase ? apiBase.replace(/\/index\.php\/api$/i, "").replace(/\/api$/i, "") : window.location.origin;
    const normalizedPath = value.startsWith("/") ? value : `/${value}`;
    return `${origin}${normalizedPath}`;
  },
};
