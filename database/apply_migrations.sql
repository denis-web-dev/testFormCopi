-- Применение миграций для ITFreelance

-- ======================
-- 1. Добавление недостающих колонок в users (с проверкой существования)
-- ======================
-- Добавляем колонку last_name если её нет
SET @dbname = DATABASE();
SET @table_name = 'users';
SET @column_name = 'last_name';
SET @check_sql = CONCAT(
    'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS ',
    'WHERE TABLE_SCHEMA = \'', @dbname, '\' ',
    'AND TABLE_NAME = \'', @table_name, '\' ',
    'AND COLUMN_NAME = \'', @column_name, '\''
);
PREPARE stmt FROM @check_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN last_name VARCHAR(50) NULL AFTER name',
    'SELECT \'Column last_name already exists\' AS message'
);
PREPARE stmt FROM @add_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Аналогично для остальных колонок
SET @columns = JSON_ARRAY(
    'avatar', 'VARCHAR(255)', 'phone',
    'region', 'VARCHAR(100)', 'email',
    'experience', 'VARCHAR(50)', 'region',
    'rate', 'INT', 'experience',
    'specialization', 'VARCHAR(150)', 'rate',
    'website', 'VARCHAR(255)', 'specialization',
    'telegram', 'VARCHAR(100)', 'website',
    'vk', 'VARCHAR(100)', 'telegram',
    'about', 'TEXT', 'vk'
);

-- Создадим временную таблицу для обработки
CREATE TEMPORARY TABLE IF NOT EXISTS temp_columns (
    col_name VARCHAR(64),
    col_type VARCHAR(64),
    after_col VARCHAR(64),
    processed BOOLEAN DEFAULT FALSE
);

-- Очистим и заполним временную таблицу
TRUNCATE TABLE temp_columns;

INSERT INTO temp_columns (col_name, col_type, after_col)
VALUES 
    ('avatar', 'VARCHAR(255)', 'phone'),
    ('region', 'VARCHAR(100)', 'email'),
    ('experience', 'VARCHAR(50)', 'region'),
    ('rate', 'INT', 'experience'),
    ('specialization', 'VARCHAR(150)', 'rate'),
    ('website', 'VARCHAR(255)', 'specialization'),
    ('telegram', 'VARCHAR(100)', 'website'),
    ('vk', 'VARCHAR(100)', 'telegram'),
    ('about', 'TEXT', 'vk');

-- Процедура для добавления колонок
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS add_missing_columns()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_col_name VARCHAR(64);
    DECLARE v_col_type VARCHAR(64);
    DECLARE v_after_col VARCHAR(64);
    DECLARE cur CURSOR FOR SELECT col_name, col_type, after_col FROM temp_columns WHERE processed = FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_col_name, v_col_type, v_after_col;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Проверяем существует ли колонка
        SET @check = CONCAT(
            'SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS ',
            'WHERE TABLE_SCHEMA = DATABASE() ',
            'AND TABLE_NAME = \'users\' ',
            'AND COLUMN_NAME = \'', v_col_name, '\''
        );
        PREPARE stmt FROM @check;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        IF @exists = 0 THEN
            -- Добавляем колонку
            SET @add = CONCAT(
                'ALTER TABLE users ADD COLUMN ', v_col_name, ' ', v_col_type,
                ' NULL AFTER ', v_after_col
            );
            PREPARE stmt FROM @add;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            SET @msg = CONCAT('Added column: ', v_col_name);
            SELECT @msg AS message;
        ELSE
            SET @msg = CONCAT('Column already exists: ', v_col_name);
            SELECT @msg AS message;
        END IF;

        UPDATE temp_columns SET processed = TRUE WHERE col_name = v_col_name;
    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- Выполняем процедуру
CALL add_missing_columns();

-- Удаляем временную таблицу и процедуру
DROP TEMPORARY TABLE IF EXISTS temp_columns;
DROP PROCEDURE IF EXISTS add_missing_columns;

-- ======================
-- 2. Создание таблицы user_skills (если не существует)
-- ======================
CREATE TABLE IF NOT EXISTS `user_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `skill_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаём индекс если не существует
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'user_skills' 
                     AND INDEX_NAME = 'idx_user_skills_user_id');
                     
IF @index_exists = 0 THEN
    CREATE INDEX `idx_user_skills_user_id` ON `user_skills`(`user_id`);
END IF;

-- ======================
-- 3. Создание таблицы user_tools (если не существует)
-- ======================
CREATE TABLE IF NOT EXISTS `user_tools` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tool_key` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаём индекс если не существует
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'user_tools' 
                     AND INDEX_NAME = 'idx_user_tools_user_id');
                     
IF @index_exists = 0 THEN
    CREATE INDEX `idx_user_tools_user_id` ON `user_tools`(`user_id`);
END IF;

-- ======================
-- 4. Создание таблицы portfolio_items (если не существует)
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

-- Создаём индексы если не существуют
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'portfolio_items' 
                     AND INDEX_NAME = 'idx_portfolio_items_user_id');
IF @index_exists = 0 THEN
    CREATE INDEX `idx_portfolio_items_user_id` ON `portfolio_items`(`user_id`);
END IF;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'portfolio_items' 
                     AND INDEX_NAME = 'idx_portfolio_items_created_at');
IF @index_exists = 0 THEN
    CREATE INDEX `idx_portfolio_items_created_at` ON `portfolio_items`(`created_at`);
END IF;

-- ======================
-- 5. Проверка и вывод статуса
-- ======================
SELECT 'Migration completed successfully' AS status;

-- Показать структуру таблиц
SHOW TABLES;

DESCRIBE users;
DESCRIBE user_skills;
DESCRIBE user_tools;
DESCRIBE portfolio_items;