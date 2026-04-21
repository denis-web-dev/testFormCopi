# ITFreelance — Платформа для фриланс-исполнителей

## Обзор проекта

ITFreelance — это веб-приложение для регистрации и управления профилями фриланс-исполнителей в сфере IT. Проект построен на классическом стеке PHP + MySQL + Nginx с использованием Docker для контейнеризации.

### Основные технологии

- **Backend**: PHP 8.2 (FPM), PDO, MySQL 8.0
- **Frontend**: HTML5, SCSS, JavaScript (vanilla)
- **Веб-сервер**: Nginx (Alpine)
- **Контейнеризация**: Docker + Docker Compose
- **Архитектура**: MVC-подобная (контроллеры, модели, представления)

## Структура проекта

```
.
├── config/
│   └── database.php          # Подключение к БД (PDO)
├── includes/
│   ├── functions.php         # Утилиты: e(), old(), redirect(), flash-сообщения
│   ├── auth.php              # Проверка авторизации, getCurrentUser()
│   └── captcha.php           # (зарезервировано)
├── src/
│   ├── controllers/          # PHP-контроллеры
│   │   ├── LoginController.php
│   │   ├── RegisterController.php
│   │   └── ProfileController.php
│   └── models/
│       └── User.php          # Модель пользователя
├── public/                   # Точка входа (document root)
│   ├── index.php             # Главная страница
│   ├── login.php             # Форма входа
│   ├── registration.php      # Форма регистрации
│   ├── profile.php           # Личный кабинет
│   ├── logout.php            # Выход из аккаунта
│   ├── uploads/              # Загруженные файлы
│   └── assets/
│       ├── css/              # Скомпилированные стили
│       ├── scss/             # Исходники SCSS
│       ├── js/               # JavaScript
│       ├── fonts/            # Шрифты (Unbounded, Arial)
│       └── images/           # Изображения и иконки
├── templates/                # (зарезервировано)
├── docker-compose.yml        # Docker-конфигурация
├── Dockerfile                # Сборка PHP-контейнера
├── nginx.conf                # Конфиг Nginx
└── .env.example              # Пример переменных окружения
```

## Сборка и запуск

### Требования

- Docker Desktop или Docker Engine + Docker Compose
- Порты: 8080 (веб), 3307 (MySQL)

### Команды запуска

```bash
# Клонирование и запуск
docker-compose up -d

# Просмотр логов
docker-compose logs -f

# Остановка
docker-compose down

# Пересборка после изменений
docker-compose up -d --build
```

### Доступ после запуска

- **Веб-приложение**: http://localhost:8080
- **База данных**: localhost:3307 (user: root, password: root, database: itfreelance)

### Сервисы Docker

| Сервис | Контейнер         | Порт      | Назначение  |
| ------ | ----------------- | --------- | ----------- |
| php    | itfreelance_php   | 9000      | PHP-FPM 8.2 |
| nginx  | itfreelance_nginx | 8080:80   | Веб-сервер  |
| db     | itfreelance_mysql | 3307:3306 | MySQL 8.0   |

## Архитектура приложения

### Поток запроса

1. Nginx принимает запрос → перенаправляет PHP-файлы в PHP-FPM
2. PHP-скрипт подключает `config/database.php` (возвращает PDO)
3. При POST-запросе вызывается соответствующий Controller
4. Controller обрабатывает данные, использует Model, редиректит при успехе
5. При GET-запросе рендерится View (PHP-файл с HTML)

### Контроллеры

| Файл                     | Назначение                                   |
| ------------------------ | -------------------------------------------- |
| `LoginController.php`    | Авторизация, проверка CSRF, создание сессии  |
| `RegisterController.php` | Регистрация с валидацией, хеширование пароля |
| `ProfileController.php`  | _(пустой, требует реализации)_               |

### Модели

**User.php** — основная модель пользователя:

- `emailExists(string $email): bool`
- `phoneExists(string $phone): bool`
- `register(string $name, string $phone, string $email, string $password): bool`
- `findByLogin(string $login): ?array`

### Вспомогательные функции (`includes/functions.php`)

| Функция                                          | Описание                   |
| ------------------------------------------------ | -------------------------- |
| `e(string $value): string`                       | Экранирование HTML         |
| `old(string $key, $default = ''): string`        | Получение значения из POST |
| `redirect(string $url): void`                    | HTTP-редирект              |
| `set_flash(string $type, string $message): void` | Установка flash-сообщения  |
| `get_flash(): void`                              | Вывод flash-сообщения      |

## Стили и дизайн

### Переменные SCSS (`public/assets/scss/base/_vars.scss`)

```scss
--text-color-primary: #1a1a1a;
--text-color-secondary: #7b7b7b;
--text-hover: #b7b7b7;
--bg-btn-active: #1a1a1a;
--bg-btn-normal: #51d62c;
--ui-secondery-pink: #eca4d7;
--ui-secondery-green: #51d62c;
--font-primary: 'Unbounded', sans-serif;
--font-secondery: 'Arial', sans-serif;
```

### Шрифты

- **Заголовки**: Unbounded (Google Fonts)
- **Текст**: Arial, Helvetica

### Компиляция SCSS

```bash
# Если установлен sass глобально
sass public/assets/scss/main.scss public/assets/css/main.css

# С отслеживанием изменений
sass --watch public/assets/scss:public/assets/css
```

## Валидация и безопасность

### CSRF-защита

- Токен генерируется в сессии: `$_SESSION['csrf_token']`
- Проверяется в контроллерах перед обработкой POST

### Валидация регистрации

| Поле          | Правила                                         |
| ------------- | ----------------------------------------------- |
| Имя           | 2-50 символов, только буквы, пробелы, дефисы    |
| Телефон       | +7 или 8 и 10 цифр                              |
| Email         | Валидный формат + домен (.com, .ru, .net и др.) |
| Пароль        | Минимум 8 символов, буквы + цифры               |
| Подтверждение | Должно совпадать с паролем                      |

### JavaScript валидация

Файл `public/assets/js/login-registration.js` содержит:

- Валидацию в реальном времени (blur-события)
- Показ/скрытие пароля (SVG-иконки)
- Динамическое отображение ошибок

## База данных

### Таблица `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Подключение

Конфигурация в `config/database.php` использует переменные окружения:

- `DB_HOST` (по умолчанию: `db` — имя сервиса Docker)
- `DB_DATABASE` (по умолчанию: `itfreelance`)
- `DB_USERNAME` (по умолчанию: `root`)
- `DB_PASSWORD` (по умолчанию: `root`)

## Правила разработки

### Кодирование

- Все PHP-файлы начинаются с `declare(strict_types=1);`
- Используйте `require_once` для подключений
- Экранируйте вывод через `e()`
- Проверяйте CSRF-токены в POST-обработчиках

### Сессии

- Старт сессии: `session_start()` в начале скрипта
- Хранение user_id и user_name в `$_SESSION`

### Обработка ошибок

- Ошибки формы сохраняйте в `$_SESSION['form_errors']`
- Старые значения — в `$_SESSION['form_old']`
- Flash-сообщения через `set_flash()` / `get_flash()`

### Работа с БД

- Используйте подготовленные запросы (PDO::prepare)
- Проверяйте уникальность email/phone перед INSERT
- Хешируйте пароли: `password_hash($password, PASSWORD_DEFAULT)`

## Текущий статус и TODO

### Реализовано

- [x] Регистрация с валидацией
- [x] Авторизация (email/телефон + пароль)
- [x] Сессии и выход из аккаунта
- [x] Базовый профиль пользователя
- [x] Docker-окружение
- [x] SCSS-стили для входа/регистрации

### Требует реализации

- [ ] Полноценный ProfileController
- [ ] Расширенный профиль исполнителя (навыки, инструменты, портфолио)
- [ ] Загрузка аватара и изображений портфолио
- [ ] Чекбоксы навыков и инструментов
- [ ] Адаптивная вёрстка профиля
- [ ] API для AJAX-обновлений

### Макеты

В проекте есть макеты для страницы профиля:

- `Main.png` — основная информация
- `About.png` — блок "О себе"
- `Портфолио.png` — галерея работ
- `Заполнение профиля Исполнитель_1920.jpg` — полный макет

Стили для профиля описаны в `public/assets/images/стили профиля.txt`.

## Полезные ссылки

- **Главная**: http://localhost:8080/
- **Вход**: http://localhost:8080/login.php
- **Регистрация**: http://localhost:8080/registration.php
- **Профиль**: http://localhost:8080/profile.php (требует авторизации)
