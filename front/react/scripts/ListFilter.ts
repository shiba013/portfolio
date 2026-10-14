/**
 * 管理画面の検索・絞り込み
 */

const normalize = (value: string) => {
  return value.normalize("NFKC").toLocaleLowerCase("ja-JP").trim();
};

const initListFilter = (controls: HTMLElement) => {
  const targetSelector = controls.dataset.target;

  if (!targetSelector) {
    return;
  }

  const target = document.querySelector<HTMLElement>(targetSelector);

  if (!target) {
    return;
  }

  const searchInput =
    controls.querySelector<HTMLInputElement>("[data-list-search]");

  const statusInputs =
    controls.querySelectorAll<HTMLInputElement>("[data-list-status]");

  const sortSelect =
    controls.querySelector<HTMLSelectElement>("[data-list-sort]");

  const body =
    target.querySelector<HTMLTableSectionElement>("[data-list-body]");

  const emptyMessage = target.querySelector<HTMLElement>("[data-list-empty]");

  if (!body) {
    return;
  }

  let searchTimer: number | undefined;

  const applyFilter = () => {
    const keyword = normalize(searchInput?.value ?? "");

    const selectedStatus =
      controls.querySelector<HTMLInputElement>("[data-list-status]:checked")
        ?.value ?? "all";

    const rows = Array.from(
      body.querySelectorAll<HTMLTableRowElement>("[data-list-row]"),
    );

    /*
     * 並び替え
     */
    const selectedSort = sortSelect?.selectedOptions[0];

    const sortKey = selectedSort?.dataset.sortKey;

    const sortType = selectedSort?.dataset.sortType ?? "text";

    const sortDirection = selectedSort?.dataset.sortDirection ?? "asc";

    if (sortKey) {
      rows.sort((firstRow, secondRow) => {
        const firstValue = firstRow.dataset[sortKey] ?? "";

        const secondValue = secondRow.dataset[sortKey] ?? "";

        let result: number;

        if (sortType === "number") {
          result = Number(firstValue) - Number(secondValue);
        } else {
          result = firstValue.localeCompare(secondValue, "ja");
        }

        return sortDirection === "desc" ? -result : result;
      });

      rows.forEach((row) => {
        body.insertBefore(row, emptyMessage);
      });
    }

    /*
     * 検索・公開状態フィルター
     */
    let visibleCount = 0;

    rows.forEach((row) => {
      const searchableText = normalize(row.dataset.search ?? "");

      const rowStatus = row.dataset.status ?? "";

      const matchesKeyword = keyword === "" || searchableText.includes(keyword);

      const matchesStatus =
        selectedStatus === "all" || rowStatus === selectedStatus;

      const visible = matchesKeyword && matchesStatus;

      row.hidden = !visible;

      if (visible) {
        visibleCount++;
      }
    });

    if (emptyMessage) {
      emptyMessage.hidden = visibleCount !== 0;
    }
  };

  /*
   * キーワードは入力停止から300ms後
   */
  searchInput?.addEventListener("input", () => {
    window.clearTimeout(searchTimer);

    searchTimer = window.setTimeout(() => {
      applyFilter();
    }, 300);
  });

  /*
   * 公開状態は即時反映
   */
  statusInputs.forEach((input) => {
    input.addEventListener("change", () => {
      window.clearTimeout(searchTimer);
      applyFilter();
    });
  });

  /*
   * 並び替えは即時反映
   */
  sortSelect?.addEventListener("change", () => {
    window.clearTimeout(searchTimer);
    applyFilter();
  });

  applyFilter();
};

document
  .querySelectorAll<HTMLElement>("[data-list-controls]")
  .forEach(initListFilter);
