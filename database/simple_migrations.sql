-- Миграции для проекта ITFreelance (упрощенная версия)
-- Создание таблиц для профиля исполнителя

-- ======================
-- 1. Расширение таблицы users
-- ======================
ALTER TABLE `users`
ADD COLUMN `last_name` VARCHAR(50) NULL AFTER `name`,
ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `phone`,
ADD COLUMN `region` VARCHAR(100) NULL AFTER `email`,
ADD COLUMN `experience` VARCHAR(50) NULL AFTER `region`,
ADD COLUMN `rate` INT NULL AFTER `experience`,
ADD COLUMN `specialization` VARCHAR(150) NULL AFTER `rate`,
ADD COLUMN `website` VARCHAR(255) NULL AFTER `specialization`,
ADD COLUMN `telegram` VARCHAR(100) NULL AFTER `website`,
ADD COLUMN `vk` VARCHAR(100) NULL AFTER `telegram`,
ADD COLUMN `about` TEXT NULL AFTER `vk`,
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;

-- ======================
-- 2. Таблица навыков пользователя (user_skills)
-- ======================
CREATE TABLE `user_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `skill_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_user_skills_user_id` ON `user_skills`(`user_id`);

-- ======================
-- 3. Таблица инструментов пользователя (user_tools)
-- ======================
CREATE TABLE `user_tools` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tool_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_user_tools_user_id` ON `user_tools`(`user_id`);

-- ======================
-- 4. Таблица портфолио (portfolio_items)
-- ======================
CREATE TABLE `portfolio_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `title` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_portfolio_items_user_id` ON `portfolio_items`(`user_id`);
CREATE INDEX `idx_portfolio_items_created_at` ON `portfolio_items`(`created_at`);

-- ======================
-- 5. Обновление существующих записей
-- ======================
UPDATE `users` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL;