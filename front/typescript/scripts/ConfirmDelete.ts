/**
 * 削除ボタンの確認ダイアログ
 */
document
  .querySelectorAll<HTMLButtonElement>("[data-confirm-delete]")
  .forEach((button) => {
    button.addEventListener("click", (event) => {
      const message = button.dataset.confirmDelete ?? "本当に削除しますか？";

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

export {};
