window.BMApi = {
  async fetch(path, options = {}) {
    const state = window.BMState;
    const headers = { ...(options.headers || {}) };

    if (state.token) {
      headers.Authorization = `Bearer ${state.token}`;
    }

    if (!(options.body instanceof FormData)) {
      headers["Content-Type"] = "application/json";
    }

    const response = await window.fetch(`${state.baseUrl}${path}`, {
      method: options.method || "GET",
      headers,
      body:
        options.body instanceof FormData
          ? options.body
          : options.body
            ? JSON.stringify(options.body)
            : undefined,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.ok === false) {
      throw new Error(data.message || "Request failed");
    }
    return data;
  },
};
