/**
 * 実績絞り込み
 */
const initWorksFilter = () => {
  const filterButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-work-category-filter]",
  );
  const workCards = document.querySelectorAll<HTMLElement>("[data-work-card]");

  if (filterButtons.length === 0 || workCards.length === 0) {
    return;
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const selectedCategory = button.dataset.workCategoryFilter ?? "all";

      filterButtons.forEach((filterButton) => {
        filterButton.classList.toggle("is-active", filterButton === button);
      });

      workCards.forEach((card) => {
        const isVisible =
          selectedCategory === "all" ||
          card.dataset.workCategory === selectedCategory;

        card.hidden = !isVisible;
      });
    });
  });
};
initWorksFilter();
