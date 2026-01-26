# Check-DB — Database Health Checker (Laravel + React + Docker)

Веб-інтерфейс для завантаження SQLite БД (`.db` / `.sqlite`), запуску асинхронного аналізу та перегляду результатів у вигляді дашборда з деталізацією проблем.

---

## 0) Вимоги

Переконайся, що встановлено:

- **Git**
- **Docker Desktop** (або Docker Engine) + **Docker Compose v2**
- (Опційно) `make` — якщо в репозиторії є `Makefile` і ти хочеш запускати команди через `make`.

Перевірка:
```bash
docker --version
docker compose version
git --version
```

---

## 1) Клонування проекту

```bash
git clone <YOUR_REPO_URL>
cd <PROJECT_DIR>
```

---

## 2) Старт контейнерів

> У docker-compose є сервіси: `nginx`, `app`, `queue`, `node`, `mysql`, `redis` , `scheduler`.

Запуск:
```bash
docker compose up -d
```

Перевірити статус:
```bash
docker compose ps
```

Логи (за потреби):
```bash
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f nginx
docker compose logs -f node
docker compose logs -f mysql
```

---

## 3) Backend (Laravel): залежності + env

### 3.1 Composer install
```bash
docker compose exec app composer install
```

### 3.2 .env
Якщо `.env` ще немає — створи з прикладу:
```bash
docker compose exec app bash -lc "cp -n .env.example .env || true"
```

Згенеруй ключ:
```bash
docker compose exec app php artisan key:generate
```

> ⚠️ У цьому проекті база піднята в Docker як **mysql** (service name `mysql`).
> Переконайся, що в `.env` вказано:
> - `DB_HOST=mysql`
> - `DB_PORT=3306`
> - `DB_DATABASE=check_db`
> - `DB_USERNAME=check_adm`
> - `DB_PASSWORD=...` (пароль з docker-compose)

---

## 4) Міграції + базова ініціалізація

```bash
docker compose exec app php artisan migrate
```

Якщо є seeders:
```bash
docker compose exec app php artisan db:seed
```

---

## 5) Frontend (Vite + React): dev server

У проекті `node` сервіс вже виконує:
`npm install && npm run dev -- --host 0.0.0.0 --port 5173`

Перевірити логи:
```bash
docker compose logs -f node
```

> ⚠️ Якщо бачиш помилку:
> `bind: address already in use 5173`
> — порт зайнятий. Варіанти:
>
> 1) Зупини інший процес/контейнер на 5173:
> ```bash
> lsof -i :5173
> ```
> 2) Або зміни порт у `docker-compose.yml` для `node` (наприклад на 5174):
> - ports: `"5174:5173"`
> - або зміни `--port 5173` на `--port 5174` і пробрось `"5174:5174"`.

---

## 6) Scheduler (очистка тимчасових файлів)

Проект використовує команду:
- `analyses:cleanup` — очищення storage (наприклад `storage/app/analyses/...`)

### 6.1 Перевірити, що команда існує
```bash
docker compose exec app php artisan list | grep analyses
```

### 6.2 Запустити вручну
```bash
docker compose exec app php artisan analyses:cleanup
```

### 6.3 Автозапуск по schedule (Laravel 11)
Scheduling налаштовується в `routes/console.php`.

Приклад:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('analyses:cleanup')->dailyAt('03:10');
```

Контейнер `scheduler` (якщо є) має запускати:
```bash
php artisan schedule:work
```

Подивитися логи scheduler:
```bash
docker compose logs -f scheduler
```

---

## 7) Queue worker (аналіз асинхронно)

Worker піднятий як сервіс `queue`:
- `php artisan queue:work --sleep=1 --tries=1 --timeout=0`

Логи:
```bash
docker compose logs -f queue
```

Перезапуск:
```bash
docker compose restart queue
```

---

## 8) Доступи / URL

- **Backend через nginx**: http://localhost:8085
- **Vite dev server**: http://localhost:5173
- **MySQL (з хоста)**: `127.0.0.1:3307` (порт прокинутий назовні)
- **Redis (з хоста)**: `127.0.0.1:6380`

---

## 9) Перевірка API

### 9.1 Створення аналізу (upload)
API очікує multipart/form-data:
- `file` — файл БД (наприклад `.db` або `.zip`)
- `profile` — профіль перевірок (наприклад `basic`)

Приклад через curl:
```bash
curl -X POST http://localhost:8085/api/analyses \
  -F "file=@/path/to/test.db" \
  -F "profile=basic"
```

### 9.2 Перевірка статусу
```bash
curl http://localhost:8085/api/analyses/<ID>
```

---

## 10) Типові проблеми

### 10.1 “service db is not running”
У docker-compose сервіс називається **mysql**, не `db`.
Правильно:
```bash
docker compose exec mysql mysql -ucheck_adm -p"<PASSWORD_FROM_COMPOSE>" -D check_db -e "SHOW TABLES;"
```

### 10.2 502/503
Дивись логи:
```bash
docker compose logs -f nginx
docker compose logs -f app
```

---

## 11) Корисні команди

Очистити кеші Laravel:
```bash
docker compose exec app php artisan optimize:clear
```

Зайти в контейнер:
```bash
docker compose exec app bash
```

Перебудувати образи:
```bash
docker compose build --no-cache
docker compose up -d
```

Зупинити все:
```bash
docker compose down
```

Зупинити та видалити volumes (УВАГА: видалить дані MySQL):
```bash
docker compose down -v
```

---

## 12) Ready-check (швидкий чек-лист)

1) `docker compose up -d` ✅
2) `docker compose exec app composer install` ✅
3) `docker compose exec app php artisan key:generate` ✅
4) `docker compose exec app php artisan migrate` ✅
5) Відкривається http://localhost:8085 ✅
6) Відкривається http://localhost:5173 ✅
7) `queue` сервіс працює (`docker compose logs -f queue` без FAIL) ✅
