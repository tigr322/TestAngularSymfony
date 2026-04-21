Product Import/Export Test Project

Overview

This repository contains a fullstack test assignment built with Symfony, Doctrine ORM, PostgreSQL, Angular, Vite, TypeScript, and Docker Compose.

The application imports products from an .xlsx file, persists them in PostgreSQL, downloads and stores product images locally, and provides a clean frontend for browsing imported products.

The project is designed as a practical, interview-quality solution with an API-first backend, a typed Angular frontend, clean architecture, deterministic tests, and a simple local developer experience.

⸻

Goals

The application must:

* import products from an .xlsx file
* create or update products by external_code
* extract all columns starting with Доп. поле into additional product attributes
* download product images from URLs and store them locally
* expose REST API endpoints for import, product listing, and product details
* provide a frontend with three pages:
    * import page
    * products list page
    * product details page

⸻

Tech Stack

Backend

* Symfony
* Doctrine ORM
* Doctrine Migrations
* PostgreSQL
* PHPUnit
* PhpSpreadsheet
* Symfony HttpClient or Guzzle
* Swagger / OpenAPI via NelmioApiDocBundle or swagger-php

Frontend

* Angular
* Vite
* TypeScript
* Angular Router
* Angular HttpClient
* Playwright for E2E tests

Infrastructure

* Docker Compose
* PHP-FPM / App container
* Nginx container
* PostgreSQL container
* Node / Frontend container

⸻

Functional Scope

1. Product Import Page

The import page must provide:

* .xlsx file selection
* import button
* request to backend API
* visible loading state
* import result summary
* validation and error display
* row-level error feedback when available

2. Products List Page

The products list page must:

* fetch all imported products from API
* display them as a list or cards
* make each product clickable
* navigate to the product details page
* support loading, empty, and error states

3. Product Details Page

The product details page must:

* fetch a product by id
* display all core product fields
* display additional attributes
* display all related images
* support loading, not-found, and error states

⸻

API Endpoints

Required

POST /api/import/products

Imports products from an uploaded .xlsx file.

Expected behavior:

* validate file type and contents
* parse rows
* create or update products by external_code
* synchronize attributes and images
* return import statistics and errors

GET /api/products

Returns a list of imported products.

Expected behavior:

* return predictable JSON
* return enough fields for list rendering
* remain simple and frontend-friendly

GET /api/products/{id}

Returns detailed information for a single product.

Expected behavior:

* return product core fields
* return additional attributes
* return related images
* return 404 if the product does not exist

Optional

GET /api/import/status/{id}

Optional endpoint if asynchronous import is introduced.

For this assignment, a synchronous import with statistics returned directly from POST /api/import/products is fully acceptable.

⸻

Database Schema

The project must contain three main tables.

products

Field	Type	Notes
id	integer / bigint	primary key
external_code	string	unique, required
name	string	required
description	text	nullable if source allows
price	decimal	current sale price
purchase_price	decimal	закупочная цена, nullable if missing in source
discount_percent	decimal	calculated field
created_at	datetime	set by application
updated_at	datetime	set by application

product_attributes

Field	Type	Notes
id	integer / bigint	primary key
product_id	FK	references products.id, cascade delete
attribute_key	string	attribute name
attribute_value	text	attribute value

product_images

Field	Type	Notes
id	integer / bigint	primary key
product_id	FK	references products.id, cascade delete
source_url	text	original image URL
local_path	string	stored file path

⸻

Import Rules

Business Key

* external_code is the business key for product upsert.
* Re-importing the same product must update the existing record instead of creating duplicates.

Attributes

* Every column whose header starts with Доп. поле must be stored in product_attributes.
* Attribute synchronization should be deterministic during repeated imports.

Images

* Image URLs must be detected from the source file format.
* Images must be downloaded locally.
* Both original URL and local file path must be stored.
* Failures while downloading images must be handled gracefully.

Idempotency

The import flow must be idempotent:

* same file import should not create duplicate products
* products must be updated by external_code
* stale attributes/images should be synchronized according to the chosen implementation strategy and documented in code/README

Error Handling

The import flow must handle:

* invalid file type
* corrupted .xlsx file
* empty rows
* missing required fields
* invalid URLs
* unavailable image resources

The backend should report:

* created count
* updated count
* skipped count
* error count
* row-level errors when possible

⸻

Discount Calculation

discount_percent must be calculated in a single dedicated place in backend code.

Suggested formula:

((price - purchase_price) / purchase_price) * 100

Implementation requirements:

* keep the formula documented
* cover it with unit tests
* handle division by zero safely
* handle missing values safely

⸻

Architecture Principles

The project should follow these principles:

* DRY
* KISS
* SOLID
* API-first backend
* explicit validation
* simple and reviewable architecture
* no unnecessary abstractions

Backend Principles

* thin controllers
* business logic in services
* Doctrine repositories only for query logic
* DTOs only where they improve clarity
* centralized error handling
* migrations for every schema change

Frontend Principles

* feature-based structure
* typed API models
* API communication via Angular services
* small focused components
* explicit loading / error / empty states
* no unnecessary global state solution

⸻

Suggested Project Structure

.
├── AGENTS.md
├── README.md
├── docker-compose.yml
├── Makefile
├── .env.example
├── docs/
│   └── sample-import.xlsx
├── backend/
│   ├── AGENTS.md
│   ├── composer.json
│   ├── phpunit.xml.dist
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── src/
│   ├── tests/
│   └── var/
├── frontend/
│   ├── AGENTS.md
│   ├── package.json
│   ├── vite.config.ts
│   ├── playwright.config.ts
│   └── src/
└── docker/
    ├── nginx/
    ├── php/
    └── node/

⸻

Testing Requirements

Backend

The backend must include:

* unit tests for discount calculation
* unit tests for xlsx row mapping / parsing logic
* integration tests for import API
* integration tests for products list API
* integration tests for product details API
* tests for repeated import behavior
* tests for uniqueness and upsert via external_code
* tests for image download/storage logic using mocks or fakes

Frontend

The frontend must include Playwright E2E tests for:

* uploading an .xlsx file
* seeing imported products in the list
* opening a product details page

Testing Principles

* tests must be deterministic
* tests must be isolated
* external HTTP calls must be mocked/faked in automated tests
* important business paths must be covered first
* avoid flaky selectors and timing assumptions in E2E tests

⸻

Fixtures / Seed Data

The project should include fixtures or seed data for:

* products
* product_attributes
* product_images

Fixture data should be:

* small
* realistic
* readable
* useful for development and tests

⸻

Docker Requirements

The project must run via Docker Compose.

Expected services:

1. app / php-fpm
2. postgres
3. nginx
4. frontend / node

Optional infrastructure such as Kafka or RabbitMQ should not be added unless it becomes truly necessary for the assignment.

⸻

Documentation Requirements

The repository should include:

* setup instructions
* run commands
* test commands
* architecture notes
* API documentation via Swagger/OpenAPI
* implementation assumptions
* notes about the .xlsx import format

⸻

Assumptions

Unless the sample .xlsx requires otherwise, the following assumptions apply:

* external_code is the primary business key for upsert
* columns starting with Доп. поле belong to product_attributes
* image URLs come from dedicated image-related columns in the source file
* repeated imports update products instead of creating duplicates
* browser E2E tests are implemented with Playwright, not Laravel Dusk, because the project stack is Symfony + Angular

⸻

Non-Goals

The following are out of scope unless explicitly added later:

* authentication
* admin roles
* background queues
* Kafka / RabbitMQ integration
* advanced filtering
* search
* pagination
* export implementation
* advanced media processing beyond download and local storage

⸻

Recommended Delivery Flow

1. Define project structure
2. Set up Docker Compose
3. Initialize Symfony backend
4. Initialize Angular frontend
5. Create entities and migrations
6. Implement import parser and services
7. Implement API endpoints
8. Implement frontend pages
9. Add tests
10. Add Swagger/OpenAPI docs
11. Finalize README and cleanup

⸻

Quality Expectations

This project should be delivered as a clean, practical, interview-quality solution.

Expected qualities:

* readable code
* clear separation of responsibilities
* stable structure
* meaningful tests
* predictable API
* simple and pleasant UI
* easy local setup
* no overengineering

