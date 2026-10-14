const initCaseFormPreview = (form: HTMLFormElement) => {
  const modal = document.querySelector<HTMLElement>("[data-case-preview-modal]");
  const openButton = form.querySelector<HTMLButtonElement>("[data-case-preview-open]");
  const closeButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-case-preview-close]",
  );
  const tabButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-case-preview-tab]",
  );
  const frame = document.querySelector<HTMLIFrameElement>("[data-case-preview-frame]");
  const previewBody = document.querySelector<HTMLElement>("[data-case-preview-body]");

  if (!modal || !openButton || !frame || !previewBody) {
    return;
  }

  let requestController: AbortController | null = null;

  const showPreviewError = () => {
    frame.srcdoc = '<!DOCTYPE html><html lang="ja"><body><p>プレビューを表示できませんでした。画面を再読み込みしてお試しください。</p></body></html>';
    previewBody.classList.add("is-loaded");
  };

  const setActiveTab = (view: "list" | "detail") => {
    tabButtons.forEach((button) => {
      const active = button.dataset.casePreviewTab === view;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-selected", String(active));
    });
  };

  const loadPreview = async (view: "list" | "detail") => {
    setActiveTab(view);
    previewBody.classList.remove("is-loaded");
    requestController?.abort();
    requestController = new AbortController();

    try {
      const response = await fetch(`/admin/cases/preview/${view}`, {
        method: "POST",
        body: new FormData(form),
        signal: requestController.signal,
      });
      const html = await response.text();

      if (!response.ok) {
        showPreviewError();
        return;
      }

      frame.onload = () => {
        frame.contentDocument?.addEventListener("click", (event) => {
          const target = event.target as Element | null;
          const link = target?.closest?.("a");

          if (link) {
            event.preventDefault();
          }
        });
        previewBody.classList.add("is-loaded");
      };
      frame.srcdoc = html;
    } catch (error) {
      if (error instanceof DOMException && error.name === "AbortError") {
        return;
      }

      showPreviewError();
    }
  };

  const openPreview = () => {
    modal.hidden = false;
    document.body.classList.add("is-preview-open");
    void loadPreview("list");
  };

  const closePreview = () => {
    requestController?.abort();
    modal.hidden = true;
    document.body.classList.remove("is-preview-open");
    openButton.focus();
  };

  openButton.addEventListener("click", openPreview);
  closeButtons.forEach((button) => {
    button.addEventListener("click", closePreview);
  });
  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const view = button.dataset.casePreviewTab;

      if (view === "list" || view === "detail") {
        void loadPreview(view);
      }
    });
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) {
      closePreview();
    }
  });
};

document
  .querySelectorAll<HTMLFormElement>("[data-case-create-form]")
  .forEach(initCaseFormPreview);

export {};
