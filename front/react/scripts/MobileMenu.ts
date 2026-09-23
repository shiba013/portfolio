/**
 * ヘッダーのハンバーガーメニュー
 */
const menuToggle = document.querySelector<HTMLButtonElement>(
  ".mobile-menu-toggle",
);
const mobileMenu = document.querySelector<HTMLElement>(".mobile-menu");

if (menuToggle && mobileMenu) {
  const setMenuOpen = (isOpen: boolean) => {
    menuToggle.classList.toggle("is-open", isOpen);
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    mobileMenu.classList.toggle("is-open", isOpen);
    mobileMenu.setAttribute("aria-hidden", String(!isOpen));
    document.body.classList.toggle("is-mobile-menu-open", isOpen);
  };

  menuToggle.addEventListener("click", () => {
    setMenuOpen(!mobileMenu.classList.contains("is-open"));
  });

  mobileMenu
    .querySelector("[data-mobile-menu-close]")
    ?.addEventListener("click", () => {
      setMenuOpen(false);
    });

  mobileMenu.querySelectorAll<HTMLAnchorElement>("a").forEach((link) => {
    link.addEventListener("click", () => setMenuOpen(false));
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setMenuOpen(false);
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 600) {
      setMenuOpen(false);
    }
  });
}
