-- Миграции для проекта ITFreelance (упрощённая версия)
-- Создание таблиц для профиля исполнителя

-- ======================
-- 1. Расширение таблицы users (без IF NOT EXISTS для MySQL 8.0)
-- ======================
-- Проверяем и добавляем колонки по одной
-- last_name
ALTER TABLE `users` 
ADD COLUMN `last_name` VARCHAR(50) NULL AFTER `name`;

-- avatar
ALTER TABLE `users` 
ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `phone`;

-- region
ALTER TABLE `users` 
ADD COLUMN `region` VARCHAR(100) NULL AFTER `email`;

-- experience
ALTER TABLE `users` 
ADD COLUMN `experience` VARCHAR(50) NULL AFTER `region`;

-- rate
ALTER TABLE `users` 
ADD COLUMN `rate` INT NULL AFTER `experience`;

-- specialization
ALTER TABLE `users` 
ADD COLUMN `specialization` VARCHAR(150) NULL AFTER `rate`;

-- website
ALTER TABLE `users` 
ADD COLUMN `website` VARCHAR(255) NULL AFTER `specialization`;

-- telegram
ALTER TABLE `users` 
ADD COLUMN `telegram` VARCHAR(100) NULL AFTER `website`;

-- vk
ALTER TABLE `users` 
ADD COLUMN `vk` VARCHAR(100) NULL AFTER `telegram`;

-- about
ALTER TABLE `users` 
ADD COLUMN `about` TEXT NULL AFTER `vk`;

-- updated_at
ALTER TABLE `users` 
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;

-- ======================
-- 2. Таблица навыков пользователя (user_skills)
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
-- 3. Таблица инструментов пользователя (user_tools)
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
-- 4. Таблица портфолио (portfolio_items)
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
-- 5. Начальные данные для справочников
-- ======================
-- Таблица справочник навыков (опционально)
CREATE TABLE IF NOT EXISTS `skills_catalog` (
    `skill_key` VARCHAR(50) PRIMARY KEY,
    `skill_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица справочник инструментов (опционально)
CREATE TABLE IF NOT EXISTS `tools_catalog` (
    `tool_key` VARCHAR(50) PRIMARY KEY,
    `tool_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- 6. Наполнение справочников
-- ======================
INSERT IGNORE INTO `skills_catalog` (`skill_key`, `skill_name`, `category`) VALUES
('verstka', 'Верстка', 'design'),
('adaptive', 'Адаптив', 'design'),
('