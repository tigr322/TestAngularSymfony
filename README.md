# Импорт и просмотр товаров

Это тестовый fullstack-проект для импорта товаров из Excel-файла. Backend написан на Symfony, данные хранятся в PostgreSQL через Doctrine ORM, frontend сделан на Angular. Все запускается через Docker Compose.

Главная идея простая: пользователь загружает `.xlsx` файл, backend читает строки, создает или обновляет товары по `external_code`, сохраняет дополнительные поля как атрибуты, скачивает изображения и отдает данные на frontend.

## Стек

- Backend: Symfony 8, Doctrine ORM, Doctrine Migrations, PostgreSQL, PhpSpreadsheet, Symfony HttpClient, PHPUnit, NelmioApiDocBundle.
- Frontend: Angular 21, TypeScript, Angular Router, Angular HttpClient, Angular CLI с Vite-based build/dev pipeline, Playwright.
- Инфраструктура: Docker Compose c сервисами `backend`, `nginx`, `postgres`, `frontend` и `rabbitmq`.

## Быстрый старт

Для первого запуска:

```bash
make setup
```

Эта команда собирает PHP-образ, поднимает Docker Compose, ставит зависимости backend/frontend и запускает миграции.

Полезные команды:

```bash
make up
make down
make migrate
make fixtures
make test-backend
make test-frontend
make test-e2e
make queues
```

Локальные адреса:

- Frontend: `http://localhost:4200`
- Backend API через nginx: `http://localhost:8080/api`
- Swagger UI: `http://localhost:8080/api/doc`
- OpenAPI JSON: `http://localhost:8080/api/doc.json`
- RabbitMQ Management UI: `http://localhost:15672`, логин и пароль по умолчанию: `app` / `app`

## API

В проекте есть импорт и полноценный CRUD для товаров:

- `POST /api/import/products` - принимает multipart form field `file` с `.xlsx` файлом и возвращает статистику импорта.
- `GET /api/products` - возвращает список товаров для страницы каталога.
- `GET /api/products/{id}` - возвращает карточку товара: основные поля, атрибуты и изображения.
- `POST /api/products` - создает товар вручную.
- `PUT /api/products/{id}` - полностью обновляет товар.
- `DELETE /api/products/{id}` - удаляет товар.

Тело запроса для создания и обновления товара передается как JSON:

```json
{
  "externalCode": "manual-1",
  "name": "Товар из API",
  "description": "Описание товара",
  "price": "1200.00",
  "purchasePrice": "800.00",
  "attributes": {
    "Бренд": "MINIMI",
    "Размер": "M"
  }
}
```

Обязательные поля: `externalCode`, `name`, `price`. Поле `purchasePrice` можно не передавать или передать как `null`. `discountPercent` руками не передается: backend считает его сам по той же формуле, что и при импорте.

Если `externalCode` уже занят другим товаром, API вернет `409 Conflict`. Ошибки валидации возвращаются как `400 Bad Request`.

Документация API доступна в браузере через Swagger UI:

```text
http://localhost:8080/api/doc
```

Сырой OpenAPI JSON остается доступен отдельно:

```text
http://localhost:8080/api/doc.json
```

## Формат XLSX

Файл-ориентир лежит здесь:

```text
docs/import example (2).xlsx
```

Как читается файл:

- используется первый лист;
- первая строка считается строкой заголовков;
- товары начинаются со второй строки.

Основные поля товара берутся так:

| Поле товара | Колонка в XLSX |
|---|---|
| `externalCode` | `Внешний код` |
| `name` | `Наименование` |
| `description` | `Описание` |
| `price` | `Цена: Цена продажи` |
| `purchasePrice` | `Закупочная цена` |
| `discountPercent` | рассчитывается автоматически |

Числа можно писать с запятой, например `1320,00`.

Формула наценки:

```text
((price - purchase_price) / purchase_price) * 100
```

Результат округляется до двух знаков после запятой. Если закупочная цена пустая или равна нулю, `discountPercent` сохраняется как `null`.

## Атрибуты и изображения

Все колонки, которые начинаются с `Доп. поле:`, сохраняются в таблицу `product_attributes`.

Ссылки на изображения берутся из двух колонок:

- `Доп. поле: Ссылка на упаковку` - одна ссылка;
- `Доп. поле: Ссылки на фото` - несколько ссылок через запятую.

Картинки скачиваются в:

```text
backend/public/uploads/products/...
```

В базе сохраняются две вещи:

- исходная ссылка `sourceUrl`;
- локальный путь для браузера `localPath`.

Повторный импорт работает предсказуемо:

- товар ищется и обновляется по `externalCode`;
- атрибуты синхронизируются с текущей строкой файла;
- старые атрибуты, которых больше нет в строке, удаляются;
- изображения синхронизируются по исходной ссылке и не дублируются;
- если картинка не скачалась, ошибка попадает в результат импорта, но сам товар не откатывается.

## Как устроен проект

Backend сделан API-first. Контроллеры тонкие: они принимают запрос, вызывают сервис и возвращают JSON.

Основная бизнес-логика разложена по небольшим сервисам:

- чтение XLSX;
- определение заголовков;
- маппинг строки в данные товара;
- расчет наценки;
- создание или обновление товара;
- синхронизация атрибутов;
- синхронизация изображений;
- скачивание изображений.

Doctrine repositories используются только для запросов к базе. Они не управляют бизнес-процессами.

Frontend разделен по feature-страницам и общается с backend только через `ProductApiService`. Состояние страниц хранится просто, через Angular signals: этого достаточно для загрузки, ошибок, пустых состояний и отображения результата импорта.

## Очереди

В Docker Compose добавлен RabbitMQ. Сейчас он нужен как инфраструктурный слой на будущее: импорт по-прежнему работает синхронно, без фоновых задач. Так проще отлаживать проект и легче проверять результат.

RabbitMQ доступен так:

- AMQP: `localhost:5672`
- Management UI: `http://localhost:15672`

Логин и пароль по умолчанию задаются в `.env.example`:

```dotenv
RABBITMQ_DEFAULT_USER=app
RABBITMQ_DEFAULT_PASS=app
```

Запустить весь проект:

```bash
make up
```

Запустить только очередь:

```bash
make queues
```

Kafka специально не добавлен в основной Compose-файл. Для текущего объема RabbitMQ закрывает требование по queue layer без лишней сложности и без неиспользуемой интеграции в коде.

## Тесты

Backend-тесты:

```bash
make test-backend
```

Или напрямую из папки backend:

```bash
cd backend
php bin/phpunit
```

Покрыты важные части: расчет наценки, парсинг и маппинг XLSX, синхронизация изображений с fake downloader, endpoint импорта, повторный импорт, список товаров, карточка товара и not-found ответ.

Frontend-тесты:

```bash
make test-frontend
make test-e2e
```

Или напрямую:

```bash
cd frontend
npm test -- --watch=false
npm run e2e
```

Playwright-тесты мокают API, загружают пример `.xlsx`, проверяют статистику импорта, открывают список товаров и переходят в карточку товара.

Если браузер для Playwright еще не установлен:

```bash
cd frontend
npx playwright install chromium
```

## Что специально не сделано

В проекте намеренно нет авторизации, ролей, глубокой интеграции очередей, Kafka, пагинации, продвинутого поиска и экспорта.

Это сделано осознанно: задача проекта - показать чистый импорт, понятный API, рабочий frontend, документацию и минимальную инфраструктуру без лишнего усложнения.
