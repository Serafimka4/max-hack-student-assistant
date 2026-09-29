# API платформы

Через API вуз или внешняя система ведёт тот же контент, что и в админ-панели: шаблоны документов, тесты навыков и их версии. API и интерфейсы вызывают один слой логики, поэтому правила и права совпадают.

- Интерактивная документация: https://max-hack.ai-weblab.ru/docs/api
- Спецификация OpenAPI 3.1: https://max-hack.ai-weblab.ru/docs/api.json
- Обязательные проверки, роли и ожидаемые ответы: [../DATA-API.yaml](../DATA-API.yaml)

Базовый адрес: `https://max-hack.ai-weblab.ru/api`. Все ответы — JSON в кодировке UTF-8.

## Аутентификация

Токен передаётся заголовком `Authorization: Bearer <token>`. Получить его можно двумя способами.

**Токен интеграции** выдаёт администратор платформы на сервере:

```bash
docker compose exec app php artisan api:token editor@demo.test --name=integration --days=90
```

**Токен мини-приложения** выдаётся по данным запуска MAX: сервер проверяет подпись токеном бота и возвращает токен на сутки.

```bash
curl -X POST https://max-hack.ai-weblab.ru/api/v1/auth/max \
  -H 'Content-Type: application/json' \
  -d '{"init_data": "<строка window.WebApp.initData>"}'
```

```json
{
  "token": "3|xxxxxxxx",
  "user": {"id": 7, "name": "Анна Тестова"},
  "start_param": "offer_1"
}
```

Ошибки: `401` — подпись не совпала или данные устарели (срок 1 час), `422` — не передан `init_data`, `429` — превышен лимит (20 запросов в минуту с адреса).

## Права

| Действие | Кто может |
|---|---|
| Читать опубликованные шаблоны организации | Любой пользователь с токеном |
| Читать черновики и версии файлов, менять шаблоны | Администратор и редактор организации |
| Читать тесты и версии | Участник организации |
| Создавать и менять тесты, публиковать версии | Администратор и редактор организации |

Запись из чужой организации недоступна по адресу этой организации: ответ `404`, а не `403` — так не раскрывается факт существования записи. Недостаточные права — `403`. Нарушение предметного правила (например, правка опубликованной версии) — `422` с текстом в поле `message`.

## Методы

Организации и справочники:

| Метод | Адрес | Назначение |
|---|---|---|
| `GET` | `/v1/me` | Текущий пользователь и его организации |
| `GET` | `/v1/organizations` | Организации платформы |
| `GET` | `/v1/organizations/{slug}` | Одна организация |
| `GET` | `/v1/organizations/{slug}/skills` | Навыки: общий справочник и собственные |

Шаблоны документов:

| Метод | Адрес | Назначение |
|---|---|---|
| `GET` | `/v1/organizations/{slug}/document-templates` | Список: участники видят все, остальные — опубликованные |
| `POST` | `/v1/organizations/{slug}/document-templates` | Создать шаблон |
| `GET` | `/v1/organizations/{slug}/document-templates/{id}` | Карточка с актуальной версией файла |
| `PATCH` | `/v1/organizations/{slug}/document-templates/{id}` | Изменить карточку |
| `DELETE` | `/v1/organizations/{slug}/document-templates/{id}` | Удалить шаблон |
| `GET` | `/v1/organizations/{slug}/document-templates/{id}/versions` | История версий файла |
| `POST` | `/v1/organizations/{slug}/document-templates/{id}/versions` | Загрузить новую версию (multipart, поле `file`) |

Тесты навыков:

| Метод | Адрес | Назначение |
|---|---|---|
| `GET` | `/v1/organizations/{slug}/assessments` | Тесты организации |
| `POST` | `/v1/organizations/{slug}/assessments` | Создать тест |
| `GET` `PATCH` `DELETE` | `/v1/organizations/{slug}/assessments/{id}` | Карточка теста |
| `GET` | `/v1/organizations/{slug}/assessments/{id}/versions` | Версии |
| `POST` | `/v1/organizations/{slug}/assessments/{id}/versions` | Создать черновую версию |
| `GET` | `/v1/organizations/{slug}/assessments/{id}/versions/{version}` | Версия с вопросами |
| `PUT` | `/v1/organizations/{slug}/assessments/{id}/versions/{version}` | Заменить вопросы черновика |
| `POST` | `/v1/organizations/{slug}/assessments/{id}/versions/{version}/publish` | Опубликовать версию |

## Примеры

Загрузка новой версии файла шаблона:

```bash
curl -X POST \
  https://max-hack.ai-weblab.ru/api/v1/organizations/demo-university/document-templates/1/versions \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' \
  -F 'file=@zayavlenie.pdf'
```

Ответ содержит номер версии и временную ссылку на скачивание (30 минут):

```json
{
  "data": {
    "id": 2, "version": 2, "original_name": "zayavlenie.pdf",
    "mime_type": "application/pdf", "size": 28170,
    "download_url": "https://max-hack.ai-weblab.ru/files/templates/2?expires=...&signature=..."
  }
}
```

Создание версии теста с вопросами, правилами оценки и практическим заданием:

```bash
curl -X POST \
  https://max-hack.ai-weblab.ru/api/v1/organizations/demo-employer/assessments/1/versions \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "notes": "Добавлен вопрос про идемпотентность",
    "basic_threshold": 60,
    "min_questions_per_skill": 2,
    "applied_threshold": 70,
    "confident_threshold": 90,
    "practical_task": "Свёрстайте страницу списка вакансий и загрузите данные из /api/vacancies.",
    "practical_rubric": [
      {"skill_id": 1, "criterion": "Адаптивная вёрстка 375–1440 px", "max_points": 4}
    ],
    "questions": [
      {
        "skill_id": 3, "type": "single", "points": 1,
        "prompt": "Какой код ответа означает, что ресурс создан?",
        "options": [{"key": "A", "text": "200"}, {"key": "B", "text": "201"}],
        "correct_keys": ["B"]
      }
    ]
  }'
```

Если `questions` не передать, версия создаётся с вопросами предыдущей — так правится опубликованный тест.

Публикация версии:

```bash
curl -X POST \
  https://max-hack.ai-weblab.ru/api/v1/organizations/demo-employer/assessments/1/versions/2/publish \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

## Правила, которые проверяет сервер

- Правильные ответы (`correct_keys`) возвращаются только редакторам организации; студенту и стороннему участнику — нет.
- Опубликованную версию изменить нельзя: `422`, «Опубликованная версия теста не изменяется. Создайте новую версию».
- Для типа «один ответ» нужен ровно один правильный вариант; правильные ключи должны совпадать с вариантами.
- Навык должен быть из общего справочника или принадлежать организации.
- Версию без вопросов опубликовать нельзя.
- `confident_threshold` не может быть меньше `applied_threshold`.
- Файл шаблона: PDF, DOC(X), ODT, XLS(X), до 20 МБ.

## Ограничения запросов

120 запросов в минуту на пользователя (`/v1/*`), 20 в минуту на адрес для `/v1/auth/max`. При превышении — `429`.
