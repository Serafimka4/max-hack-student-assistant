<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Цифровой помощник</title>
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    @vite(['resources/css/app.css'])
</head>
<body>
<main class="relative mx-auto flex min-h-dvh max-w-[440px] flex-col overflow-hidden bg-ink px-4 pb-8 text-white">
    <svg class="pointer-events-none absolute -right-16 top-24 size-72" viewBox="0 0 200 200" aria-hidden="true">
        <circle cx="120" cy="80" r="78" fill="var(--color-blue)" />
        @for ($i = 0; $i < 9; $i++)
            <line x1="0" y1="{{ 20 + $i * 16 }}" x2="200" y2="{{ 80 + $i * 16 }}" stroke="var(--color-lime)" stroke-opacity=".45" />
        @endfor
    </svg>

    <div class="relative py-3.5"><x-miniapp.logo /></div>

    <div class="relative mt-auto">
        <span class="badge border border-lime text-lime">Студентам · MAX</span>
        <h1 class="heading mt-4 text-[34px]">Учёба,<br>практика<br>и помощь</h1>

        <div id="state-loading" class="mt-6 flex items-center gap-3 text-muted-dark">
            <span class="size-4 animate-spin rounded-full border-2 border-lime border-t-transparent"></span>
            Входим через MAX…
        </div>

        <div id="state-message" class="mt-6 hidden rounded-card border border-ink-line bg-ink-2 p-4">
            <div id="message-title" class="font-extrabold"></div>
            <p id="message-text" class="mt-1 text-sm text-muted-dark"></p>
        </div>

        @if ($demoLogin)
            <button id="demo-login" type="button" class="btn-lime mt-4 hidden w-full">Войти как демо-студент</button>
            <p id="demo-hint" class="mt-2 hidden text-2xs text-muted-dark">Локальная проверка без MAX. В рабочей среде кнопка отключена.</p>
        @endif
    </div>
</main>

<script>
    (() => {
        const reason = @json($reason);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const $ = (id) => document.getElementById(id);

        const show = (title, text) => {
            $('state-loading').classList.add('hidden');
            $('state-message').classList.remove('hidden');
            $('message-title').textContent = title;
            $('message-text').textContent = text;
            $('demo-login')?.classList.remove('hidden');
            $('demo-hint')?.classList.remove('hidden');
        };

        const post = async (url, body = {}) => {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            // 419 — сессионная cookie не дошла до сервера (например, заблокирована во встроенном окне).
            if (response.status === 419) {
                throw new Error('Браузер не сохранил cookie сессии. Откройте приложение в мобильном MAX или обновите страницу.');
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Не удалось войти. Попробуйте позже.');
            return data;
        };

        $('demo-login')?.addEventListener('click', async () => {
            try {
                location.replace((await post(@json(route('miniapp.demo-login')))).redirect);
            } catch (e) {
                show('Не получилось войти', e.message);
            }
        });

        if (reason === 'no-access') {
            return show('Нет доступа', 'Приложение доступно студентам вуза-участника пилота. Обратитесь к координатору, чтобы вас добавили в список.');
        }

        const initData = window.WebApp?.initData;
        if (!initData) {
            return show('Откройте приложение в MAX', 'Вход выполняется автоматически через мессенджер: найдите бота и нажмите «Открыть».');
        }

        window.WebApp.ready?.();
        post(@json(route('miniapp.auth')), { init_data: initData })
            .then((data) => location.replace(data.redirect))
            .catch((e) => show('Не получилось войти', e.message));
    })();
</script>
</body>
</html>
