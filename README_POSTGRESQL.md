# ITFreelance — Настройка с PostgreSQL

## 🚀 Быстрый старт

### Шаг 1: Остановка старых контейнеров

```powershell
docker-compose down
```

### Шаг 2: Очистка старых данных (если нужно)

```powershell
# Удалить старые тома MySQL
docker volume rm itfreelance_mysql_data 2>$null

# Или очистить все тома
docker volume prune -f
```

### Шаг 3: Запуск новых контейнеров с PostgreSQL

```powershell
docker-compose up -d --build
```

### Шаг 4: Проверка статуса контейнеров

```powershell
docker-compose ps
```

Должны быть запущены 4 контейнера:

- `itfreelance_php` — PHP-FPM 8.2
- `itfreelance_nginx` — Nginx (порт **8081**)
- `itfreelance_postgres` — PostgreSQL 15 (порт **5432**)
- `itfreelance_pgadmin` — pgAdmin 4 (порт **5050**)

---

## 📊 Доступ к сервисам

| Сервис     | URL                   | Логин                 | Пароль   |
| ---------- | --------------------- | --------------------- | -------- |
| Веб-сайт   | http://localhost:8081 | —                     | —        |
| pgAdmin    | http://localhost:5060 | admin@itfreelance.com | admin    |
| PostgreSQL | localhost:5432        | postgres              | postgres |

---

## 🗄️ Настройка базы данных

### Шаг 1: Вход в pgAdmin

1. Откройте http://localhost:5060
2. Войдите: `admin@itfreelance.com` / `admin`
3. Добавьте новое подключение:
   - **Name**: ITFreelance
   - **Host**: db (имя сервиса в Docker)
   - **Port**: 5432
   - **Database**: itfreelance
   - **Username**: postgres
   - **Password**: postgres

### Шаг 2: Выполнение миграций

**Вариант A: Через pgAdmin**

1. Откройте Tools → Query Tool
2. Скопируйте содержимое `database/migrations_postgresql.sql`
3. Выполните (F5 или кнопка Execute)

**Вариант B: Через терминал**

```powershell
Get-Content database/migrations_postgresql.sql | docker exec -i itfreelance_postgres psql -U postgres -d itfreelance
```

**Вариант C: Внутри контейнера**

```powershell
docker exec -it itfreelance_postgres psql -U postgres -d itfreelance -f /var/lib/postgresql/data/migrations.sql
```

### Шаг 3: Проверка таблиц

В pgAdmin или через терминал:

```sql
\dt

-- Должны отобразиться:
-- users
-- user_skills
-- user_tools
-- portfolio_items
-- skills_catalog
-- tools_catalog
```

---

## 🌐 Доступ к странице профиля

1. Откройте http://localhost:8081/registration.php
2. Зарегистрируйте нового пользователя
3. После регистрации попадёте на http://localhost:8081/profile.php

Или войдите если уже зарегистрированы:

1. http://localhost:8081/login.php
2. Введите email/телефон и пароль

---

## 🔍 Просмотр данных в БД

### Через терминал (Docker)

**Подключиться к PostgreSQL:**

```powershell
docker exec -it itfreelance_postgres psql -U postgres -d itfreelance
```

**Просмотреть пользователей:**

```sql
SELECT id, name, last_name, email, phone, region, sphere, created_at FROM users;
```

**Просмотреть навыки:**

```sql
SELECT u.name, STRING_AGG(s.skill_key, ', ') as skills
FROM users u
LEFT JOIN user_skills s ON u.id = s.user_id
GROUP BY u.id;
```

**Просмотреть портфолио:**

```sql
SELECT u.name, p.image_path, p.created_at
FROM portfolio_items p
JOIN users u ON p.user_id = u.id
ORDER BY p.created_at DESC;
```

**Выйти из psql:**

```sql
\q
```

### Через pgAdmin (веб-интерфейс)

1. Откройте http://localhost:5060
2. Подключитесь к серверу "ITFreelance"
3. Разверните: Databases → itfreelance → public → Tables
4. Правой кнопкой на таблице → View/Edit Data → All Rows

---

## 📁 Загруженные файлы

**Аватары:**

```
public/uploads/avatars/
```

**Портфолио:**

```
public/uploads/portfolio/
```

**Проверить через терминал:**

```powershell
ls public/uploads/avatars/
ls public/uploads/portfolio/
```

---

## 🐛 Решение проблем

### Ошибка: "Port 8081 is already in use"

Измените порт в `docker-compose.yml`:

```yaml
ports:
  - '8082:80' # вместо 8081
```

Перезапустите:

```powershell
docker-compose down
docker-compose up -d
```

### Ошибка: "database does not exist"

Выполните миграции:

```powershell
Get-Content database/migrations_postgresql.sql | docker exec -i itfreelance_postgres psql -U postgres -d itfreelance
```

### Ошибка: "connection refused" к PostgreSQL

Проверьте, что контейнер запущен:

```powershell
docker ps | findstr postgres
```

Перезапустите:

```powershell
docker-compose restart db
```

### Ошибка: "permission denied" для загрузок

```powershell
docker exec itfreelance_php chmod -R 777 /var/www/html/public/uploads/
```

### Сброс базы данных

**Внимание! Это удалит все данные!**

```powershell
docker exec -it itfreelance_postgres psql -U postgres -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
```

Затем выполните миграции заново.

---

## 🚀 Подготовка к деплою на сервер

### 1. Обновите .env для production

```env
DB_HOST=db
DB_PORT=5432
DB_DATABASE=itfreelance
DB_USERNAME=postgres
DB_PASSWORD=ваш_надёжный_пароль

APP_URL=https://ваш-домен.ru
APP_ENV=production
```

### 2. docker-compose.prod.yml

Создайте файл для production:

```yaml
version: '3.8'

services:
  php:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - ./:/var/www/html
    expose:
      - '9000'
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    ports:
      - '80:80'
      - '443:443' # HTTPS
    volumes:
      - ./:/var/www/html
      - ./nginx.prod.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl # SSL сертификаты
    depends_on:
      - php

  db:
    image: postgres:15-alpine
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      POSTGRES_DB: itfreelance
    volumes:
      - postgres_data:/var/lib/postgresql/data
    # Не открывайте порт 5432 наружу в production!

volumes:
  postgres_data:
```

### 3. Настройка Nginx для production

Создайте `nginx.prod.conf` с SSL и кэшированием.

### 4. Бэкап базы данных

```powershell
# Создать бэкап
docker exec itfreelance_postgres pg_dump -U postgres itfreelance > backup.sql

# Восстановить из бэкапа
Get-Content backup.sql | docker exec -i itfreelance_postgres psql -U postgres -d itfreelance
```

### 5. Логи в production

```powershell
# Просмотр логов
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f db
```

---

## 📋 Команды для повседневной работы

```powershell
# Запуск
docker-compose up -d

# Остановка
docker-compose down

# Перезапуск
docker-compose restart

# Пересборка
docker-compose up -d --build

# Логи
docker-compose logs -f

# Войти в контейнер PHP
docker exec -it itfreelance_php bash

# Войти в контейнер PostgreSQL
docker exec -it itfreelance_postgres psql -U postgres -d itfreelance

# Очистка
docker-compose down -v  # удалить тома
docker system prune -f  # очистить систему
```

---

## 🎯 Чек-лист после установки

- [ ] Контейнеры запущены (`docker-compose ps`)
- [ ] Сайт доступен на http://localhost:8081
- [ ] pgAdmin доступен на http://localhost:5050
- [ ] Миграции выполнены (таблицы созданы)
- [ ] Регистрация работает
- [ ] Профиль открывается
- [ ] Загрузка аватара работает
- [ ] Портфолио загружается

Если все пункты выполнены — проект готов к разработке! ✅
