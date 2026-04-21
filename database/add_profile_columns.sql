-- Добавление полей профиля в таблицу users

-- Проверяем существование каждого поля перед добавлением
SET @db = 'itfreelance';
SET @table = 'users';

-- 1. last_name
SET @col = 'last_name';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(50) NULL AFTER name'),
    'SELECT \'Column last_name already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. avatar
SET @col = 'avatar';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(255) NULL AFTER phone'),
    'SELECT \'Column avatar already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. region
SET @col = 'region';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(100) NULL AFTER email'),
    'SELECT \'Column region already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. experience
SET @col = 'experience';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(50) NULL AFTER region'),
    'SELECT \'Column experience already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. rate
SET @col = 'rate';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' INT NULL AFTER experience'),
    'SELECT \'Column rate already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. specialization
SET @col = 'specialization';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(150) NULL AFTER rate'),
    'SELECT \'Column specialization already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. website
SET @col = 'website';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(255) NULL AFTER specialization'),
    'SELECT \'Column website already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. telegram
SET @col = 'telegram';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(100) NULL AFTER website'),
    'SELECT \'Column telegram already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. vk
SET @col = 'vk';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' VARCHAR(100) NULL AFTER telegram'),
    'SELECT \'Column vk already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 10. about
SET @col = 'about';
SELECT COUNT(*) INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
    CONCAT('ALTER TABLE ', @table, ' ADD COLUMN ', @col, ' TEXT NULL AFTER vk'),
    'SELECT \'Column about already exists\'');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Profile columns added successfully' AS message;