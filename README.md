# Цифровой помощник студента в MAX

Мини-приложение MAX и платформа для вузов и работодателей: практики и заявки, тесты навыков, шаблоны документов, обращения. План проекта — [PLAN.md](PLAN.md).

> Статус: каркас. Готовы API, админ-панель организаций (шаблоны, тесты) и вход через MAX на уровне API. Мини-приложение на Livewire ещё не перенесено из прототипа `web/`.

## Структура

| Каталог | Назначение |
|---|---|
| `platform/` | Laravel 13: API `/api/v1`, админ-панель `/admin` (Filament 5), будущее мини-приложение (Livewire 4) |
| `web/` | Кликабельный дизайн-прототип мини-приложения (React + Vite), только для справки |
| `compose.yaml` | Запуск всех компонентов: приложение, очередь, планировщик, PostgreSQL |

## Запуск

```bash
cp .env.example .env   # при необходимости поменяйте пароль БД и укажите MAX_BOT_TOKEN
docker compose up -d --build
```

| Адрес | Что это |
|---|---|
| http://localhost:8088/admin | Админ-панель |
| http://localhost:8088/docs/api | Документация API (OpenAPI 3.1, JSON — `/docs/api.json`) |
| http://localhost:8088/up | Проверка работоспособности |

Остановка: `docker compose down` (данные сохраняются в томах `pgdata` и `storage`). Полный сброс: `docker compose down -v`.

### Демонстрационные данные

При `SEED_DEMO=1` и пустой базе загружаются вымышленные организации и учётные записи. Пароль у всех — `password`; только для локальной проверки.

| Почта | Роль |
|---|---|
| `admin@demo.test` | Администратор платформы, видит все организации |
| `editor@demo.test` | Редактор «Демо-вуза»: шаблоны документов |
| `hr@demo.test` | Администратор «Волга Софт»: тесты навыков |

## API

- Все данные организаций доступны по `/api/v1/organizations/{slug}/…`: шаблоны и версии файлов, тесты, версии и публикация, навыки.
- Токен для интеграции: `docker compose exec app php artisan api:token user@example.org --name=integration --days=90`, далее заголовок `Authorization: Bearer <token>`.
- Мини-приложение получает токен через `POST /api/v1/auth/max` со строкой `window.WebApp.initData`; подпись проверяется токеном бота.
- Опубликованная версия теста неизменяема; правильные ответы видят только редакторы организации.
- Файлы шаблонов отдаются по временной подписанной ссылке (подходит для `WebApp.downloadFile` в MAX).

## Разработка

```bash
cd platform
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve          # http://localhost:8000
php artisan test           # тесты (SQLite в памяти)
```

Для запросов к API MAX из Docker положите корневой сертификат Минцифры (`*.crt`) в `platform/docker/certs/` и пересоберите образ.
