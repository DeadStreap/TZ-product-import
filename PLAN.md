# Implementation Plan — TZ-product-import

## P0 — Critical (must pass review)

### 1. Backend Tests (PHPUnit)

**What:** CRUD tests for repositories, unit tests for services, integration tests for controllers.

**Files to create:**
```
backend/tests/
├── Unit/
│   ├── Services/ImportServiceTest.php
│   ├── Services/AuthServiceTest.php
│   ├── DTO/ProductFilterTest.php
│   └── DTO/ImportResultTest.php
├── Integration/
│   ├── Repositories/ProductRepositoryTest.php
│   ├── Repositories/ProductAttributeRepositoryTest.php
│   ├── Repositories/ProductImageRepositoryTest.php
│   └── Controllers/ProductControllerTest.php
└── bootstrap.php (test DB setup)
```

**Approach:**
- Unit tests: mock EntityManager, test pure logic (discount calc, filter parsing, price parsing)
- Integration tests: use SQLite in-memory DB, create schema, test real queries
- Add `phpunit.xml` env vars for test DB
- Update `Makefile` `test` target

**Estimate:** ~2h

---

### 2. OpenAPI / Swagger

**What:** API documentation via OpenAPI spec.

**Approach (lazy):** Hand-written `openapi.yaml` in `docs/` — no new dependencies. Slim routes are simple enough.

**File:** `docs/openapi.yaml`

**Endpoints to document:**
- `POST /api/auth/login`
- `GET /api/health`
- `POST /api/import`
- `GET /api/import/{id}/status`
- `GET /api/products` (with query params)
- `GET /api/products/{id}`

**Estimate:** ~1h

---

### 3. GitHub Actions CI

**What:** CI workflow that runs tests and builds Docker image on push.

**File:** `.github/workflows/ci.yml`

**Steps:**
1. Checkout code
2. Setup PHP 8.4, install Composer deps
3. Run PHPStan
4. Run PHP CS Fixer (dry-run)
5. Run PHPUnit
6. Setup Node 20, install frontend deps
7. Build Angular (production)
8. Build Docker image (verify Dockerfile)

**Estimate:** ~1h

---

## P1 — Serious Problems

### 4. Doctrine Migrations (replace MigrateCommand)

**What:** Replace drop+recreate with versioned migrations.

**Files to modify:**
- `backend/bin/console` — add `migrations:migrate` command
- `backend/src/Migrations/Version20260901.php` — already exists, keep it
- `backend/src/Console/MigrateCommand.php` — delete or keep as `schema:create`

**New files:**
- `backend/config/migrations.php` — Doctrine Migrations config

**Approach:**
- Use `doctrine/migrations` package (already installed)
- Configure migrations path in `bin/console`
- Replace `make migrate` to use `migrations:migrate`
- Keep old `MigrateCommand` as `schema:drop-and-create` for dev

**Estimate:** ~1h

---

### 5. ImportTask in DB (replace temp files)

**What:** Track import tasks in `import_tasks` table instead of temp files.

**Files to modify:**
- `src/App/Controllers/ImportController.php` — create ImportTask entity, persist to DB
- `src/App/Messages/ImportProductsHandler.php` — update ImportTask status in DB
- `src/App/Repositories/ImportTaskRepository.php` — new repository
- `src/App/Config/Dependencies.php` — register ImportTaskRepository

**Flow:**
1. Controller creates `ImportTask` entity with status `pending`, persists, returns `task_id`
2. Messenger dispatches message with `task_id`
3. Handler: sets status `processing`, runs import, sets `completed`/`failed`, saves result
4. Status endpoint: queries `import_tasks` by ID

**Estimate:** ~1.5h

---

### 6. Async Import — Honest Implementation

**What:** Either real async (RabbitMQ) or honest sync with DB tasks.

**Decision:** Since RabbitMQ is already in docker-compose and Messenger is configured, make it actually async:
- The `messenger` consumer container already runs `messenger:consume async`
- Fix: ImportController persists ImportTask, dispatches message, returns 202
- Handler (in messenger container): picks up message, processes, updates DB
- Frontend polls `/api/import/{id}/status`

**What's broken now:**
- ImportController creates temp file + random task ID (not from DB)
- ImportProductsHandler reads/writes temp files, not DB
- No real connection between controller and handler

**Fix:** As described in #5 — same work covers both.

---

### 7. Tailwind: CDN → PostCSS

**What:** Replace CDN `<script>` with proper Tailwind CSS build.

**Files to modify:**
- `frontend/package.json` — add tailwindcss, postcss, autoprefixer
- `frontend/tailwind.config.js` — new
- `frontend/postcss.config.js` — new
- `frontend/src/styles.css` — new (Tailwind directives)
- `frontend/src/index.html` — remove CDN script, import styles.css
- `frontend/angular.json` — add styles entry

**Steps:**
```bash
cd frontend
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

**Estimate:** ~30min

---

## P2 — Improvements

### 8. Seeders / Fixtures

**What:** Test data for products, attributes, images.

**Files:**
- `backend/src/App/Console/SeedCommand.php` — new command
- `backend/bin/console` — register seed command

**Approach:** Insert 20-50 test products with attributes and images.

**Estimate:** ~45min

---

### 9. Users Table (replace hardcoded auth)

**What:** Real user entity instead of hardcoded credentials.

**Files:**
- `src/App/Entities/User.php` — new entity
- `src/App/Repositories/UserRepository.php` — new
- `src/App/Services/AuthService.php` — query DB for login
- `src/App/Console/SeedCommand.php` — create admin user
- `Migrations/Version20260902.php` — users table

**Approach:** Simple `users` table (id, email, password_hash, created_at). Use `password_hash()` / `password_verify()`.

**Estimate:** ~1h

---

### 10. Missing .gitkeep for tests dirs

**What:** Ensure `tests/Unit/` and `tests/Integration/` exist with `.gitkeep` so they're tracked.

---

## Execution Order

```
Phase 1 (P0): 1 → 2 → 3
Phase 2 (P1): 4 → 5+6 → 7
Phase 3 (P2): 8 → 9 → 10
```

Each phase is independently committable. Phase 1 = submission-ready. Phase 2 = production-ready. Phase 3 = polished.

## Dependency Notes

- #5+6 are the same work (ImportTask in DB = async via Messenger)
- #4 must come before #5 (migrations create the table, then code uses it)
- #7 (Tailwind) is independent, can be done in parallel
- #9 (Users) depends on #4 (migration for users table)
