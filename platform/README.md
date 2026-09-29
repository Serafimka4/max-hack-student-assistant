# platform

Laravel-приложение проекта: мини-приложение MAX, админ-панель, API и бот. Запуск, окружение и учётные записи — в [корневом README](../README.md), архитектура — в [docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md).

Устройство кода:

- `app/Actions` — предметная логика, общая для API, мини-приложения и админ-панели.
- `app/Policies` — права участников организаций.
- `app/Support` — учебный календарь, профиль навыков, проверка данных запуска MAX, клиент API бота.
- `app/Livewire/MiniApp` — экраны студента, шаблоны в `resources/views/livewire/miniapp`.
- `app/Filament` — кабинеты вуза, работодателя и проверяющего.
- `app/Http/Controllers/Api/V1`, `app/Http/Resources/V1` — REST API, документация собирается из кода.
- `app/Notifications` — уведомления через бота MAX.

Разработка без Docker:

```bash
composer install && npm install
```

```bash
cp .env.example .env && php artisan key:generate && php artisan migrate --seed
```

```bash
npm run build && php artisan serve
```

Тесты и стиль кода:

```bash
php artisan test
```

```bash
vendor/bin/pint
```
