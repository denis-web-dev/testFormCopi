<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/models/User.php';

$pdo = require __DIR__ . '/../config/database.php';
$userModel = new User($pdo);

requireAuth();
$user = getCurrentUser($pdo);

// Получаем ошибки и старые значения из сессии
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

// Получить полные данные пользователя, навыки, инструменты и портфолио
$userFull = $userModel->findById($_SESSION['user_id'] ?? 0);
$userSkills = $userModel->getSkills($_SESSION['user_id'] ?? 0);
$userTools = $userModel->getTools($_SESSION['user_id'] ?? 0);
$portfolioItems = $userModel->getPortfolio($_SESSION['user_id'] ?? 0);

// Объединяем данные пользователя
$user = array_merge($user, $userFull ?? []);

// Формируем полное имя
$user['full_name'] = trim(($user['name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITFREELANCE — Профиль исполнителя</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body class="page-profile-edit">

<!-- Шапка -->
<header class="profile-header">
    <div class="container">
        <div class="profile-header__inner">
            <a href="/" class="profile-header__logo">
                <span>IT</span>Freelance
            </a>
            <nav class="profile-header__nav">
                <a href="/catalog.php" class="profile-header__link">Исполнители</a>
                <a href="/orders.php" class="profile-header__link">Заказы</a>
            </nav>
            <div class="profile-header__actions">
                <button class="profile-header__btn profile-header__btn--notification">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C13.1 22 14 21.1 14 20H10C10 21.1 10.9 22 12 22ZM18 16V11C18 7.93 16.37 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 10.5 4V4.68C7.63 5.36 6 7.92 6 11V16L4 18V19H20V18L18 16Z" fill="#1A1A1A"/>
                    </svg>
                </button>
                <div class="profile-header__user">
                    <img src="<?= e($user['avatar'] ?? '/assets/images/default-avatar.png') ?>" alt="Аватар" class="profile-header__avatar">
                    <span class="profile-header__name"><?= e($user['name'] ?? 'Исполнитель') ?></span>
                </div>
                <a href="/logout.php" class="profile-header__logout">Выйти</a>
            </div>
        </div>
    </div>
</header>

<!-- Основной контент -->
<main class="profile-main">
    <div class="container">

        <?php get_flash(); ?>

        <form method="POST" action="/profile.php" enctype="multipart/form-data" class="profile-form" id="profileForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Основная информация -->
            <section class="profile-section profile-section--main">
                <div class="profile-main__grid">

                    <!-- Левая колонка: Аватар -->
                    <div class="profile-avatar-block">
                        <div class="profile-avatar__wrapper" id="avatarDropZone">
                            <img src="<?= e($user['avatar'] ?? '/assets/images/default-avatar.png') ?>"
                                 alt="Аватар"
                                 class="profile-avatar__image"
                                 id="avatarPreview">
                            <div class="profile-avatar__overlay">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 16V4M12 4L8 8M12 4L16 8M4 17V19C4 19.5304 4.21071 20.0391 4.58579 20.4142C4.96086 20.7893 5.46957 21 6 21H18C18.5304 21 19.0391 20.7893 19.4142 20.4142C19.7893 20.0391 20 19.5304 20 19V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Загрузить фото</span>
                            </div>
                            <input type="file"
                                   name="avatar"
                                   id="avatarInput"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="profile-avatar__input">
                        </div>
                        <p class="profile-avatar__hint">300×300 px, JPG или PNG</p>
                        <?php if (isset($errors['avatar'])): ?>
                            <span class="error-text"><?= e($errors['avatar']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Правая колонка: Поля -->
                    <div class="profile-fields">
                        <h1 class="profile-section__title">Основная информация</h1>

                        <div class="profile-fields__grid">
                            <!-- Имя Фамилия -->
                            <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                                <label class="form-label" for="full_name">Имя Фамилия <span class="required">*</span></label>
                                <input type="text"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= old('full_name', $user['full_name'] ?? $user['name'] ?? '') ?>"
                                       class="form-input"
                                       placeholder="Иван Иванов"
                                       required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <span class="error-text"><?= e($errors['full_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Регион -->
                            <div class="form-group <?= isset($errors['region']) ? 'has-error' : '' ?>">
                                <label class="form-label" for="region">Регион <span class="required">*</span></label>
                                <select id="region" name="region" class="form-select" required>
                                    <option value="">Выберите регион</option>
                                    <option value="moscow" <?= old('region', $user['region'] ?? '') === 'moscow' ? 'selected' : '' ?>>Москва</option>
                                    <option value="spb" <?= old('region', $user['region'] ?? '') === 'spb' ? 'selected' : '' ?>>Санкт-Петербург</option>
                                    <option value="novosibirsk" <?= old('region', $user['region'] ?? '') === 'novosibirsk' ? 'selected' : '' ?>>Новосибирск</option>
                                    <option value="ekaterinburg" <?= old('region', $user['region'] ?? '') === 'ekaterinburg' ? 'selected' : '' ?>>Екатеринбург</option>
                                    <option value="kazan" <?= old('region', $user['region'] ?? '') === 'kazan' ? 'selected' : '' ?>>Казань</option>
                                    <option value="other" <?= old('region', $user['region'] ?? '') === 'other' ? 'selected' : '' ?>>Другой</option>
                                </select>
                                <?php if (isset($errors['region'])): ?>
                                    <span class="error-text"><?= e($errors['region']) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Опыт работы -->
                            <div class="form-group <?= isset($errors['experience']) ? 'has-error' : '' ?>">
                                <label class="form-label" for="experience">Опыт работы <span class="required">*</span></label>
                                <select id="experience" name="experience" class="form-select" required>
                                    <option value="">Выберите опыт</option>
                                    <option value="0-1" <?= old('experience', $user['experience'] ?? '') === '0-1' ? 'selected' : '' ?>>Менее 1 года</option>
                                    <option value="1-3" <?= old('experience', $user['experience'] ?? '') === '1-3' ? 'selected' : '' ?>>1–3 года</option>
                                    <option value="3-5" <?= old('experience', $user['experience'] ?? '') === '3-5' ? 'selected' : '' ?>>3–5 лет</option>
                                    <option value="5-10" <?= old('experience', $user['experience'] ?? '') === '5-10' ? 'selected' : '' ?>>5–10 лет</option>
                                    <option value="10+" <?= old('experience', $user['experience'] ?? '') === '10+' ? 'selected' : '' ?>>Более 10 лет</option>
                                </select>
                                <?php if (isset($errors['experience'])): ?>
                                    <span class="error-text"><?= e($errors['experience']) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Ставка -->
                            <div class="form-group <?= isset($errors['rate']) ? 'has-error' : '' ?>">
                                <label class="form-label" for="rate">Ставка, ₽/час</label>
                                <input type="number"
                                       id="rate"
                                       name="rate"
                                       value="<?= old('rate', $user['rate'] ?? '') ?>"
                                       class="form-input"
                                       placeholder="2500"
                                       min="0"
                                       step="100">
                                <?php if (isset($errors['rate'])): ?>
                                    <span class="error-text"><?= e($errors['rate']) ?></span>
                                <?php endif; ?>
                            </div>

<!-- Сфера деятельности -->
                            <div class="form-group form-group--full <?= isset($errors['sphere']) ? 'has-error' : '' ?>">
                                <label class="form-label" for="sphere">Сфера деятельности <span class="required">*</span></label>
                                <input type="text"
                                       id="sphere"
                                       name="sphere"
                                       value="<?= old('sphere', $user['sphere'] ?? '') ?>"
                                       class="form-input"
                                       placeholder="Например: Веб-разработка, Дизайн, Мобильная разработка"
                                       required>
                                <?php if (isset($errors['sphere'])): ?>
                                    <span class="error-text"><?= e($errors['sphere']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Контакты -->
            <section class="profile-section profile-section--contacts">
                <h2 class="profile-section__title">Контакты</h2>
                <div class="profile-fields__grid profile-fields__grid--3">
                    <!-- Телефон (readonly) -->
                    <div class="form-group form-group--readonly">
                        <label class="form-label" for="phone">Телефон</label>
                        <input type="tel"
                               id="phone"
                               value="<?= e($user['phone'] ?? '') ?>"
                               class="form-input"
                               readonly>
                    </div>

                    <!-- Email (readonly) -->
                    <div class="form-group form-group--readonly">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email"
                               id="email"
                               value="<?= e($user['email'] ?? '') ?>"
                               class="form-input"
                               readonly>
                    </div>

                    <!-- Сайт -->
                    <div class="form-group <?= isset($errors['website']) ? 'has-error' : '' ?>">
                        <label class="form-label" for="website">Сайт</label>
                        <input type="url"
                               id="website"
                               name="website"
                               value="<?= old('website', $user['website'] ?? '') ?>"
                               class="form-input"
                               placeholder="https://example.com">
                        <?php if (isset($errors['website'])): ?>
                            <span class="error-text"><?= e($errors['website']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Телеграм -->
                    <div class="form-group <?= isset($errors['telegram']) ? 'has-error' : '' ?>">
                        <label class="form-label" for="telegram">Телеграм</label>
                        <input type="text"
                               id="telegram"
                               name="telegram"
                               value="<?= old('telegram', $user['telegram'] ?? '') ?>"
                               class="form-input"
                               placeholder="@username">
                        <?php if (isset($errors['telegram'])): ?>
                            <span class="error-text"><?= e($errors['telegram']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Вконтакте -->
                    <div class="form-group <?= isset($errors['vk']) ? 'has-error' : '' ?>">
                        <label class="form-label" for="vk">Вконтакте</label>
                        <input type="text"
                               id="vk"
                               name="vk"
                               value="<?= old('vk', $user['vk'] ?? '') ?>"
                               class="form-input"
                               placeholder="vk.com/username">
                        <?php if (isset($errors['vk'])): ?>
                            <span class="error-text"><?= e($errors['vk']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- О себе -->
            <section class="profile-section profile-section--about">
                <h2 class="profile-section__title">О себе</h2>
                <div class="form-group <?= isset($errors['about']) ? 'has-error' : '' ?>">
                    <textarea id="about"
                              name="about"
                              class="form-textarea"
                              placeholder="Расскажите о себе, своём опыте и навыках..."
                              rows="6"><?= old('about', $user['about'] ?? '') ?></textarea>
                    <div class="form-textarea__counter">
                        <span id="aboutCounter">0</span> / 1000
                    </div>
                    <?php if (isset($errors['about'])): ?>
                        <span class="error-text"><?= e($errors['about']) ?></span>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Навыки -->
            <section class="profile-section profile-section--skills">
                <h2 class="profile-section__title">Навыки</h2>
                <div class="checkbox-grid">
                    <?php
                    $skills = [
                        'verstka' => 'Верстка',
                        'adaptive' => 'Адаптив',
                        'animation' => 'Анимация',
                        'mobile' => 'Мобильная разработка',
                        'uxui' => 'UX/UI',
                        'webapps' => 'Web-приложения',
                        'landing' => 'Лендинги',
                        'multipage' => 'Многостраничные сайты',
                        'branding' => 'Брендинг',
                        'logos' => 'Логотипы'
                    ];
                    foreach ($skills as $key => $label):
                    ?>
                    <label class="checkbox-item">
                        <input type="checkbox"
                               name="skills[]"
                               value="<?= $key ?>"
                               class="checkbox-input"
                               <?= in_array($key, $userSkills) ? 'checked' : '' ?>>
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-label"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Инструменты -->
            <section class="profile-section profile-section--tools">
                <h2 class="profile-section__title">Инструменты</h2>
                <div class="checkbox-grid">
                    <?php
                    $tools = [
                        'figma' => 'Figma',
                        'illustrator' => 'Adobe Illustrator',
                        'photoshop' => 'Adobe Photoshop',
                        'html' => 'HTML',
                        'css' => 'CSS',
                        'js' => 'JS',
                        'react' => 'React',
                        'vite' => 'Vite',
                        'postgresql' => 'PostgreSQL',
                        'cms' => 'CMS',
                        'python' => 'Python',
                        'java' => 'Java',
                        'cpp' => 'C++',
                        'csharp' => 'C#'
                    ];
                    foreach ($tools as $key => $label):
                    ?>
                    <label class="checkbox-item">
                        <input type="checkbox"
                               name="tools[]"
                               value="<?= $key ?>"
                               class="checkbox-input"
                               <?= in_array($key, $userTools) ? 'checked' : '' ?>>
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-label"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Портфолио -->
            <section class="profile-section profile-section--portfolio">
                <h2 class="profile-section__title">Портфолио</h2>
                <div class="portfolio-grid" id="portfolioGrid">
                    <!-- Кнопка добавления -->
                    <div class="portfolio-item portfolio-item--add" id="portfolioAddBtn">
                        <div class="portfolio-item__plus">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5V19M5 12H19" stroke="#1A1A1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="portfolio-item__text">Добавить работу</span>
                        <input type="file"
                               name="portfolio[]"
                               id="portfolioInput"
                               accept="image/jpeg,image/png,image/webp"
                               multiple
                               class="portfolio-item__input">
                    </div>

                    <!-- Существующие работы -->
                    <?php foreach ($portfolioItems as $item): ?>
                    <div class="portfolio-item" data-id="<?= $item['id'] ?>">
                        <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['title'] ?? 'Работа') ?>" class="portfolio-item__image">
                        <button type="button" class="portfolio-item__delete" title="Удалить">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="portfolio-hint">Перетащите изображения сюда или нажмите на кнопку +. Максимум 20 работ.</p>
            </section>

            <!-- Кнопка сохранения -->
            <div class="profile-actions">
                <button type="submit" class="profile-btn profile-btn--primary">
                    Сохранить изменения
                </button>
                <a href="/profile.php" class="profile-btn profile-btn--secondary">Отмена</a>
            </div>

        </form>
    </div>
</main>

<script src="/assets/js/profile.js"></script>
</body>
</html>

docker ps
