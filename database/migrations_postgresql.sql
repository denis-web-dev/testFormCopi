-- Миграции для проекта ITFreelance (PostgreSQL)
-- Создание таблиц для профиля исполнителя

-- ======================
-- 1. Таблица пользователей (users)
-- ======================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    last_name VARCHAR(50),
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    region VARCHAR(100) DEFAULT '',
    experience VARCHAR(50) DEFAULT '',
    rate INTEGER,
    sphere VARCHAR(255) DEFAULT '',
    about TEXT,
    website VARCHAR(255),
    telegram VARCHAR(100),
    vk VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Индексы для users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);

-- ======================
-- 2. Таблица навыков пользователя (user_skills)
-- ======================
CREATE TABLE IF NOT EXISTS user_skills (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_key VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_skills_user_id ON user_skills(user_id);

-- ======================
-- 3. Таблица инструментов пользователя (user_tools)
-- ======================
CREATE TABLE IF NOT EXISTS user_tools (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tool_key VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_tools_user_id ON user_tools(user_id);

-- ======================
-- 4. Таблица портфолио (portfolio_items)
-- ======================
CREATE TABLE IF NOT EXISTS portfolio_items (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    image_path VARCHAR(500) NOT NULL,
    title VARCHAR(150),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_portfolio_items_user_id ON portfolio_items(user_id);
CREATE INDEX IF NOT EXISTS idx_portfolio_items_created_at ON portfolio_items(created_at);

-- ======================
-- 5. Справочник навыков (skills_catalog)
-- ======================
CREATE TABLE IF NOT EXISTS skills_catalog (
    skill_key VARCHAR(50) PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- 6. Справочник инструментов (tools_catalog)
-- ======================
CREATE TABLE IF NOT EXISTS tools_catalog (
    tool_key VARCHAR(50) PRIMARY KEY,
    tool_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- 7. Наполнение справочников
-- ======================
INSERT INTO skills_catalog (skill_key, skill_name, category) VALUES
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
ON CONFLICT (skill_key) DO UPDATE SET skill_name = EXCLUDED.skill_name, category = EXCLUDED.category;

INSERT INTO tools_catalog (tool_key, tool_name, category) VALUES
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
ON CONFLICT (tool_key) DO UPDATE SET tool_name = EXCLUDED.tool_name, category = EXCLUDED.category;

-- ======================
-- 8. Триггер для автоматического обновления updated_at
-- ======================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ======================
-- 9. Тестовый пользователь (пароль: test123456)
-- ======================
-- INSERT INTO users (name, last_name, phone, email, password)
-- VALUES ('Тест', 'Пользователь', '+79991234567', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
