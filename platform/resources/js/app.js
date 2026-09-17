// Интеграция с MAX Bridge (window.WebApp) — только когда приложение открыто внутри MAX.
const webApp = () => window.WebApp;

document.addEventListener('livewire:init', () => {
    webApp()?.ready?.();
});

// Кнопка «Назад» в шапке MAX: показывается на вложенных экранах (data-back-url на <main>).
document.addEventListener('livewire:navigated', () => {
    const app = webApp();
    const back = document.querySelector('[data-back-url]')?.dataset.backUrl;
    if (!app?.BackButton) return;

    app.BackButton.offClick?.(window.__maxBack);
    if (back) {
        window.__maxBack = () => window.Livewire.navigate(back);
        app.BackButton.onClick(window.__maxBack);
        app.BackButton.show();
    } else {
        app.BackButton.hide();
    }
});

// Скачивание файлов: в MAX обычная ссылка не работает, нужен WebApp.downloadFile.
window.downloadFile = (url, name) => {
    const app = webApp();
    if (app?.downloadFile && app.initData) {
        app.downloadFile(url, name);
    } else {
        window.location.href = url;
    }
};
