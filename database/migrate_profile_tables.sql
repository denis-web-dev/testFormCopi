-- Миграции для таблиц профиля исполнителя
-- Создание таблиц, если они не существуют

-- ======================
-- 1. Таблица навыков пользователя (user_skills)
-- ======================
CREATE TABLE IF NOT EXISTS `user_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `skill_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индекс для быстрого поиска навыков пользователя
CREATE INDEX `idx_user_skills_user_id` ON `user_skills`(`user_id`);

-- ======================
-- 2. Таблица инструментов пользователя (user_tools)
-- ======================
CREATE TABLE IF NOT EXISTS `user_tools` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tool_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индекс для быстрого поиска инструментов пользователя
CREATE INDEX `idx_user_tools_user_id` ON `user_tools`(`user_id`);

-- ======================
-- 3. Таблица портфолио (portfolio_items)
-- ======================
CREATE TABLE IF NOT EXISTS `portfolio_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `title` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индексы для портфолио
CREATE INDEX `idx_portfolio_items_user_id` ON `portfolio_items`(`user_id`);
CREATE INDEX `idx_portfolio_items_created_at` ON `portfolio_items`(`created_at`);

-- ======================
-- 4. Справочник навыков (skills_catalog)
-- ======================
CREATE TABLE IF NOT EXISTS `skills_catalog` (
    `skill_key` VARCHAR(50) PRIMARY KEY,
    `skill_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- 5. Справочник инструментов (tools_catalog)
-- ======================
CREATE TABLE IF NOT EXISTS `tools_catalog` (
    `tool_key` VARCHAR(50) PRIMARY KEY,
    `tool_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- 6. Наполнение справочников
-- ======================
INSERT INTO `skills_catalog` (`skill_key`, `skill_name`, `category`) VALUES
('verstka', 'Верстка', 'design'),
('adaptive', 'Адаптив', 'design'),
('animation', 'Анимация', 'design'),
('mobile', 'Мобильная разработка', 'development'),
('uxui', 'UX/UI', 'design'),
('webapps', 'Web-приложения', 'development'),
('landing', 'Лендинги', 'design'),
('multipage', 'Многостраничные сайты', 'development'),
('branding', 'Брендинг', 'design'),
('logos', 'Логотипы', 'design')
ON DUPLICATE KEY UPDATE `skill_name` = VALUES(`skill_name`), `category` = VALUES(`category`);

INSERT INTO `tools_catalog` (`tool_key`, `tool_name`, `category`) VALUES
('figma', 'Figma', 'design'),
('illustrator', 'Adobe Illustrator', 'design'),
('photoshop', 'Adobe Photoshop', 'design'),
('html', 'HTML', 'development'),
('css', 'CSS', 'development'),
('js', 'JS', 'development'),
('react', 'React', 'development'),
('vite', 'Vite', 'development'),
('postgresql', 'PostgreSQL', 'database'),
('cms', 'CMS', 'cms'),
('python', 'Python', 'development'),
('java', 'Java', 'development'),
('cpp', 'C++', 'development'),
('csharp', 'C#', 'development')
ON DUPLICATE KEY UPDATE `tool_name` = VALUES(`tool_name`), `category` = VALUES(`category`);

-- ======================
-- 7. Обновление существующих записей (если нужно)
-- ======================
-- Комментарий: таблицы созданы, справочники заполнены
