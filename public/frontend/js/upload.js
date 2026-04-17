window.BMUpload = {
  async uploadFromForm() {
    const formData = new FormData(window.BMElements.uploadForm);
    return window.BMApi.fetch("/upload/image", { method: "POST", body: formData });
  },
};
