const topButton = document.querySelector<HTMLButtonElement>(".top-button");
const showOffset = 320;

if (topButton) {
  const toggleTopButton = () => {
    topButton.classList.toggle("is-visible", window.scrollY > showOffset);
  };

  toggleTopButton();

  window.addEventListener("scroll", toggleTopButton, { passive: true });

  topButton.addEventListener("click", () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });
}
