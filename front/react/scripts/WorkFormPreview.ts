const initWorkFormPreview = (form: HTMLFormElement) => {
  const modal = document.querySelector<HTMLElement>(
    "[data-work-preview-modal]",
  );
  const openButton = form.querySelector<HTMLButtonElement>(
    "[data-work-preview-open]",
  );
  const closeButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-work-preview-close]",
  );
  const tabButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-work-preview-tab]",
  );
  const frame = document.querySelector<HTMLIFrameElement>(
    "[data-work-preview-frame]",
  );
  const previewBody = document.querySelector<HTMLElement>(
    "[data-work-preview-body]",
  );
  const thumbnailInput = form.querySelector<HTMLInputElement>(
    "[data-work-thumbnail]",
  );
  const thumbnailPlaceholder = form.querySelector<HTMLElement>(
    "[data-work-thumbnail-placeholder]",
  );

  if (!modal || !openButton || !frame || !previewBody) {
    return;
  }

  let requestController: AbortController | null = null;
  let thumbnailUrl: string | null = null;
  const initialThumbnailMarkup = thumbnailPlaceholder?.innerHTML ?? "";

  const updateThumbnail = () => {
    if (!thumbnailInput || !thumbnailPlaceholder) {
      return;
    }

    if (thumbnailUrl) {
      URL.revokeObjectURL(thumbnailUrl);
      thumbnailUrl = null;
    }

    const file = thumbnailInput.files?.[0];

    if (!file) {
      thumbnailPlaceholder.innerHTML = initialThumbnailMarkup;
      return;
    }

    thumbnailUrl = URL.createObjectURL(file);
    const image = document.createElement("img");
    image.src = thumbnailUrl;
    image.alt = "選択中のサムネイル";
    thumbnailPlaceholder.replaceChildren(image);
  };

  const insertLocalThumbnail = (view: "list" | "detail") => {
    if (!thumbnailUrl || !frame.contentDocument) {
      return;
    }

    const documentInFrame = frame.contentDocument;
    const selector = view === "list"
      ? "[data-work-preview-detail] img, [data-work-preview-detail] .work-thumbnail"
      : ".work-detail-image img, .work-detail-image .work-thumbnail";
    const target = documentInFrame.querySelector<HTMLElement>(selector);

    if (!target) {
      return;
    }

    if (target.tagName === "IMG") {
      (target as HTMLImageElement).src = thumbnailUrl;
      return;
    }

    const image = documentInFrame.createElement("img");
    image.src = thumbnailUrl;
    image.alt = "入力中の実績サムネイル";
    target.replaceWith(image);
  };

  const showPreviewError = () => {
    frame.srcdoc = '<!DOCTYPE html><html lang="ja"><body><p>プレビューを表示できませんでした。画面を再読み込みしてお試しください。</p></body></html>';
    previewBody.classList.add("is-loaded");
  };

  const setActiveTab = (view: "list" | "detail") => {
    tabButtons.forEach((button) => {
      const active = button.dataset.workPreviewTab === view;
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
      const response = await fetch(`/admin/works/preview/${view}`, {
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
        insertLocalThumbnail(view);

        const documentInFrame = frame.contentDocument;
        documentInFrame?.addEventListener("click", (event) => {
          const target = event.target as Element | null;
          const link = target?.closest?.("a");

          if (!link) {
            return;
          }

          event.preventDefault();
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
      const view = button.dataset.workPreviewTab;

      if (view === "list" || view === "detail") {
        void loadPreview(view);
      }
    });
  });
  thumbnailInput?.addEventListener("change", updateThumbnail);
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) {
      closePreview();
    }
  });
};

document
  .querySelectorAll<HTMLFormElement>("[data-work-create-form]")
  .forEach(initWorkFormPreview);
