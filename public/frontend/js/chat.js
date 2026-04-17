window.BMChat = {
  async loadConversations() {
    const { conversationsWrap } = window.BMElements;
    try {
      const res = await window.BMApi.fetch("/conversations");
      const rows = res.data || [];
      if (!rows.length) {
        conversationsWrap.innerHTML = '<p class="text-muted">No conversations.</p>';
        return;
      }

      conversationsWrap.innerHTML = rows
        .map(
          (c) => `
        <div class="conversation-item">
          <div><strong>#${c.id}</strong> - ${c.listing_title || "No listing"}</div>
          <div class="small text-muted">buyer: ${c.buyer_name} | seller: ${c.seller_name}</div>
          <div class="small text-muted">unread: ${c.unread_count || 0}</div>
        </div>
      `,
        )
        .join("");
    } catch (err) {
      window.BMUtils.showAlert(err.message, "danger");
    }
  },

  async createConversationFromForm() {
    const form = new FormData(window.BMElements.createConversationForm);
    return window.BMApi.fetch("/conversations", {
      method: "POST",
      body: { listing_id: Number(form.get("listing_id")) },
    });
  },

  async sendMessageFromForm() {
    const form = new FormData(window.BMElements.messageForm);
    return window.BMApi.fetch(`/conversations/${form.get("conversation_id")}/messages`, {
      method: "POST",
      body: { content: form.get("content") },
    });
  },

  async loadMessages(conversationId) {
    const { messagesWrap } = window.BMElements;
    if (!messagesWrap) {
      return;
    }
    const id = Number(conversationId);
    if (!id) {
      messagesWrap.innerHTML = '<p class="text-muted">Please enter conversation_id.</p>';
      return;
    }
    try {
      const res = await window.BMApi.fetch(`/conversations/${id}/messages`);
      const rows = res.data || [];
      if (!rows.length) {
        messagesWrap.innerHTML = '<p class="text-muted">No messages yet.</p>';
        return;
      }
      messagesWrap.innerHTML = rows
        .map(
          (m) => `
        <div class="message-item">
          <div class="small text-muted">#${m.id} user ${m.sender_user_id} - ${m.sent_at}</div>
          <div>${m.content || ""}</div>
          ${m.image_url ? `<a href="${m.image_url}" target="_blank">${m.image_url}</a>` : ""}
        </div>
      `,
        )
        .join("");
    } catch (err) {
      window.BMUtils.showAlert(err.message, "danger");
    }
  },
};
