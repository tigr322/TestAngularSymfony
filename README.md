# Product Import/Export Test Project

Fullstack test assignment built with Symfony, Doctrine ORM, PostgreSQL, Angular, Vite-powered Angular tooling, TypeScript, Docker Compose, PHPUnit, Playwright, PhpSpreadsheet, and Nelmio OpenAPI.

The app imports products from an `.xlsx` file, upserts them by `external_code`, stores additional `Доп. поле` columns as attributes, downloads product images locally, and exposes a small frontend for importing and browsing products.

## Stack

- Backend: Symfony 8, Doctrine ORM, Doctrine Migrations, PostgreSQL, PhpSpreadsheet, Symfony HttpClient, PHPUnit, NelmioApiDocBundle
- Frontend: Angular 21, TypeScript, Angular Router, Angular HttpClient, Angular CLI Vite-based dev/build pipeline, Playwright
- Infrastructure: Docker Compose with `backend`, `nginx`, `postgres`, and `frontend` services

## Setup

```bash
make setup
```

This builds the PHP image, starts Docker Compose, installs backend/frontend dependencies, and runs migrations.

Useful commands:

```bash
make up
make down
make migrate
make fixtures
make test-backend
make test-frontend
make test-e2e
```

Local URLs:

- Frontend: `http://localhost:4200`
- Backend API through nginx: `http://localhost:8080/api`
- OpenAPI JSON: `http://localhost:8080/api/doc.json`

## API

- `POST /api/import/products` accepts multipart form field `file` with an `.xlsx` document and returns import statistics.
- `GET /api/products` returns list data for the products page.
- `GET /api/products/{id}` returns core fields, attributes, and images for a product card.

## XLSX Import Format

Reference file: `docs/import example (2).xlsx`

Expected sheet shape:

- first sheet is used
- row `1` contains headers
- product rows start at row `2`

Core field mapping:

| Product field | XLSX column |
|---|---|
| `externalCode` | `Внешний код` |
| `name` | `Наименование` |
| `description` | `Описание` |
| `price` | `Цена: Цена продажи` |
| `purchasePrice` | `Закупочная цена` |
| `discountPercent` | calculated |

Decimal values support comma notation such as `1320,00`.

Discount formula:

```text
((price - purchase_price) / purchase_price) * 100
```

The value is rounded to two decimals. If purchase price is missing or zero, `discountPercent` is stored as `null`.

## Attributes And Images

Every column starting with `Доп. поле:` is synchronized into `product_attributes`.

Image URLs are extracted from:

- `Доп. поле: Ссылка на упаковку`
- `Доп. поле: Ссылки на фото`, split by comma

Images are downloaded to `backend/public/uploads/products/...`. The database stores both the original `sourceUrl` and the web-facing `localPath`.

Repeated imports are deterministic:

- products are upserted by `externalCode`
- attributes are updated, created, or removed to match the current row
- images are synchronized by source URL and are not duplicated
- image download errors are returned as row-level import errors without rolling back the product row

## Architecture Notes

- Controllers only parse requests and return JSON responses.
- Import logic is split into parser, mapper, validator-style row mapping, product sync, attribute sync, image sync, image download, and discount calculation services.
- Doctrine repositories only contain query logic.
- Frontend pages are feature-based and consume the backend only through `ProductApiService`.
- Frontend state uses Angular signals so async API responses update reliably in Angular's zoneless runtime.

## Tests

Backend:

```bash
cd backend
php bin/phpunit
```

Coverage includes discount calculation, XLSX mapping/parsing, image sync with fakes, import endpoint behavior, repeated import upsert behavior, product list/details, and not-found responses.

Frontend:

```bash
cd frontend
npm test -- --watch=false
npm run e2e
```

Playwright tests mock the API, upload the provided sample `.xlsx`, verify import statistics, open the products page, and navigate to product details.

If Playwright browsers are not installed locally:

```bash
cd frontend
npx playwright install chromium
```

## Non-Goals

Authentication, roles, background queues, Kafka/RabbitMQ, pagination, advanced search, and export are intentionally out of scope for this assignment.
