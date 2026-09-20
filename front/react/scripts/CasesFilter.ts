/**
 * 事例絞り込み
 */
const initCasesFilter = () => {
  const filterButtons = document.querySelectorAll<HTMLButtonElement>(
    "[data-case-skill-filter]",
  );
  const caseCards = document.querySelectorAll<HTMLElement>("[data-case-card]");

  if (filterButtons.length === 0 || caseCards.length === 0) {
    return;
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const selectedSkillId = button.dataset.caseSkillFilter ?? "all";

      filterButtons.forEach((filterButton) => {
        filterButton.classList.toggle("is-active", filterButton === button);
      });

      caseCards.forEach((card) => {
        const skillIds = Array.from(
          card.querySelectorAll<HTMLElement>("[data-case-skill-id]"),
        ).map((skill) => skill.dataset.caseSkillId);
        const isVisible =
          selectedSkillId === "all" || skillIds.includes(selectedSkillId);

        card.hidden = !isVisible;
      });
    });
  });
};
initCasesFilter();
