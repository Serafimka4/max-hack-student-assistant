# Развёртывание и обслуживание

## Локально

```bash
docker compose up -d --build
```

Открывается на http://localhost:8088. Демонстрационный режим включён по умолчанию, `.env` не нужен. Переменные и рабочие настройки — в [../README.md](../README.md) и [../.env.example](../.env.example).

## Стенд

| Параметр | Значение |
|---|---|
| Домен | `max-hack.ai-weblab.ru`, сертификат Let's Encrypt с автопродлением certbot |
| Сервер | `62.113.105.182`, Ubuntu 24.04, Docker + nginx |
| Код | `/opt/max-hack`, ветка `main`, отдельный ключ Gitea только на чтение |
| Настройки | `/opt/max-hack/.env`, права 600, в репозиторий не попадают |
| Приложение | Docker слушает `127.0.0.1:8090`, наружу порт не публикуется |
| nginx | `/etc/nginx/sites-enabled/max-hack.conf`: HTTPS-прокси, HTTP → HTTPS |
| Бот | Вебхук `https://max-hack.ai-weblab.ru/max/webhook`, события `bot_started`, `message_created` |

Заголовок `X-Frame-Options` намеренно не выставляется: веб-версия MAX открывает мини-приложение во встроенном окне. Cookie сессии заданы как `SameSite=None; Secure; Partitioned`.

### Первое развёртывание на новом сервере

1. Установить Docker с плагином Compose и nginx.
2. Создать ключ для доступа к репозиторию и добавить его в Gitea как deploy key на чтение:
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/max_hack_deploy -N ''
   ```
3. Склонировать репозиторий в `/opt/max-hack`.
4. Создать `/opt/max-hack/.env`: `APP_KEY` (пусто — сгенерируется), `APP_URL`, `APP_PORT=127.0.0.1:8090`, свой `DB_PASSWORD`, `MAX_BOT_TOKEN`, `MAX_BOT_NAME`, `MAX_WEBHOOK_SECRET`.
5. Поднять стек: `docker compose up -d --build`.
6. Настроить nginx на `127.0.0.1:8090`, выпустить сертификат:
   ```bash
   certbot certonly --webroot -w /var/www/letsencrypt -d <домен>
   ```
7. Подписать бота на события: `docker compose exec app php artisan max:webhook subscribe`.
8. В настройках бота в MAX указать адрес мини-приложения `https://<домен>/app/start`.

## CI/CD

Workflow [.gitea/workflows/deploy.yml](../.gitea/workflows/deploy.yml) запускается при push в `main` и вручную. Шаги: обновить код, собрать тестовый образ и прогнать тесты, пересобрать и перезапустить стек, дождаться ответа `/up`. Если тесты падают, развёртывание не выполняется.

Задания выполняет `act_runner` на том же сервере:

| Параметр | Значение |
|---|---|
| Служба | `act_runner.service`, автозапуск включён |
| Конфигурация | `/etc/act_runner/config.yaml`, ярлык `max-hack:host` |
| Состояние регистрации | `/var/lib/act_runner/.runner` |

Ярлык `host` означает, что шаги выполняются прямо на сервере: заданию нужны `docker compose` и каталог `/opt/max-hack`. В workflow нет шагов `uses:`, поэтому Node на сервере не требуется.

```bash
systemctl status act_runner
journalctl -u act_runner -n 50
```

Повторная регистрация раннера (токен берётся в Gitea: репозиторий → Settings → Actions → Runners):

```bash
cd /var/lib/act_runner && act_runner register --no-interactive \
  --instance https://gitea.gipno.tech --token <токен> \
  --name max-hack --labels max-hack:host --config /etc/act_runner/config.yaml
```

Ярлыки берутся из `config.yaml`: значение в нём перекрывает указанное при регистрации.

## Ручное развёртывание

```bash
ssh root@62.113.105.182 'cd /opt/max-hack && git pull && docker compose up -d --build'
```

Миграции выполняются автоматически при старте контейнера `app`. Демо-данные загружаются только в пустую базу и только при `SEED_DEMO=1`.

## Обслуживание

```bash
cd /opt/max-hack && docker compose ps
```

```bash
docker compose logs -f app
```

```bash
docker compose exec app php artisan max:webhook show
```

Резервная копия базы:

```bash
docker compose exec -T db pg_dump -U max_hack max_hack | gzip > /root/max-hack-$(date +%F).sql.gz
```

Восстановление:

```bash
gunzip -c /root/max-hack-2026-09-30.sql.gz | docker compose exec -T db psql -U max_hack max_hack
```

## Что проверить при переносе в рабочую среду

- `APP_ENV=production`, `APP_DEBUG=false`, `SEED_DEMO=0`, `MINIAPP_DEMO_LOGIN=false` — иначе демо-вход и демо-учётные записи доступны всем.
- Свой `DB_PASSWORD` до первого запуска: пароль PostgreSQL задаётся при создании тома.
- `MINIAPP_ENROLL_ORGANIZATION` — пусто, если студентов добавляют по списку участников пилота, а не автоматически.
- Сертификат Минцифры в образе (`platform/docker/certs/`) — без него запросы к API MAX не проходят проверку TLS.
- Домен вебхука должен отвечать по HTTPS на порту 443 сертификатом доверенного центра: самоподписанные MAX не принимает.
