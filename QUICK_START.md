# 🚀 Быстрый старт ITFreelance (PostgreSQL)

## ✅ Проект запущен!

### 📊 Доступ к сервисам

| Сервис         | URL                   | Логин                 | Пароль   |
| -------------- | --------------------- | --------------------- | -------- |
| **Веб-сайт**   | http://127.0.0.1:8081 | —                     | —        |
| **pgAdmin 4**  | http://127.0.0.1:5070 | admin@itfreelance.com | admin    |
| **PostgreSQL** | localhost:5432        | postgres              | postgres |

---

## 🎯 Первые шаги

### 1. Регистрация пользователя

1. Откройте http://127.0.0.1:8081/registration.php
2. Заполните форму:
   - Имя: `Тест`
   - Телефон: `+79991234567`
   - Email: `test@example.com`
   - Пароль: `test123456`
3. Нажмите "Зарегистрироваться"

### 2. Заполнение профиля

После регистрации вы автоматически попадёте на страницу профиля:

- http://127.0.0.1:8081/profile.php

Заполните:

- Имя Фамилия
- Регион
- Опыт работы
- Ставку
- Сферу деятельности
- Навыки и инструменты
- Загрузите аватар
- Добавьте работы в портфолио

### 3. Просмотр базы данных

**Вариант A: Через pgAdmin (веб-интерфейс)**

1. Откройте http://127.0.0.1:5070
2. Войдите: `admin@itfreelance.com` / `admin`
3. Подключитесь к серверу "ITFreelance" (или добавьте вручную):
   - Host: `db`
   - Database: `itfreelance`
   - Username: `postgres`
   - Password: `postgres`
4. Разверните: Databases → itfreelance → public → Tables
5. Правой кнопкой на таблице → View/Edit Data → All Rows

**Вариант B: Через терминал**

```powershell
# Подключиться к БД
docker exec -it itfreelance_postgres psql -U postgres -d itfreelance

# Просмотреть пользователей
SELECT id, name, email, sphere FROM users;

# Просмотреть навыки
SELECT u.name, STRING_AGG(s.skill_key, ', ') as skills
FROM users u
LEFT JOIN user_skills s ON u.id = s.user_id
GROUP BY u.id;

# Выйти
\q
```

---

## 📁 Куда сохраняются данные

### База данных PostgreSQL

- **Хост**: localhost:5432
- **База**: itfreelance
- **Пользователь**: postgres
- **Пароль**: postgres

**Таблицы:**

- `users` — пользователи
- `user_skills` — навыки пользователей
- `user_tools` — инструменты пользователей
- `portfolio_items` — работы портфолио
- `skills_catalog` — справочник навыков
- `tools_catalog` — справочник инструментов

### Файлы загрузок

```
public/uploads/avatars/     — аватары пользователей
public/uploads/portfolio/   — работы портфолио
```

---

## 🔧 Управление контейнерами

```powershell
# Просмотр статуса
docker-compose ps

# Остановка
docker-compose down

# Запуск
docker-compose up -d

# Перезапуск
docker-compose restart

# Просмотр логов
docker-compose logs -f

# Пересборка
docker-compose up -d --build
```

---

## 🐛 Решение проблем

### pgAdmin не запускается

```powershell
docker-compose restart pgadmin
```

### Ошибка подключения к БД

```powershell
docker-compose restart db
```

### Сайт не открывается

1. Проверьте, что контейнеры запущены: `docker-compose ps`
2. Проверьте логи: `docker-compose logs nginx`
3. Убедитесь, что порт 8081 не занят

### Сброс базы данных (ВСЕ ДАННЫЕ БУДУТ УДАЛЕНЫ!)

```powershell
docker exec itfreelance_postgres psql -U postgres -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
Get-Content database/migrations_postgresql.sql | docker exec -i itfreelance_postgres psql -U postgres -d itfreelance
```

---

## 📝 Тестовые данные

Для быстрого тестирования можно создать пользователя через SQL:

```sql
-- Пароль: test123456 (хешированный)
INSERT INTO users (name, last_name, phone, email, password, region, experience, sphere)
VALUES ('Иван', 'Иванов', '+79991234567', 'ivan@test.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moscow', '3-5', 'Веб-разработка');
```

---

## 🎉 Всё готово!

Теперь вы можете:

- ✅ Регистрировать новых пользователей
- ✅ Заполнять профили исполнителей
- ✅ Загружать аватары и работы портфолио
- ✅ Просматривать данные в pgAdmin
- ✅ Тестировать функционал платформы

**Основной сайт**: http://127.0.0.1:8081
**База данных**: http://127.0.0.1:5070
