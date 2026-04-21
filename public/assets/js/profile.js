// ======================
// JavaScript для страницы профиля исполнителя
// ======================

document.addEventListener('DOMContentLoaded', () => {
    // Инициализация всех функций
    initAvatarUpload();
    initPortfolioUpload();
    initTextareaCounter();
    initFormValidation();
    initCheckboxStyling();
    initPortfolioDelete();
    
    // Подключение drag & drop для портфолио
    setupPortfolioDragAndDrop();
});

// ======================
// ЗАГРУЗКА АВАТАРА
// ======================

function initAvatarUpload() {
    const avatarDropZone = document.getElementById('avatarDropZone');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (!avatarDropZone || !avatarInput || !avatarPreview) return;
    
    // Клик по зоне загрузки
    avatarDropZone.addEventListener('click', () => {
        avatarInput.click();
    });
    
    // Предпросмотр при выборе файла
    avatarInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        // Валидация файла
        if (!validateImageFile(file, {
            maxSize: 5 * 1024 * 1024, // 5 МБ
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp']
        })) {
            showError('avatar', 'Файл должен быть JPG, PNG или WebP, не более 5 МБ');
            return;
        }
        
        // Предпросмотр
        const reader = new FileReader();
        reader.onload = (event) => {
            avatarPreview.src = event.target.result;
            showSuccess('Аватар выбран для загрузки');
        };
        reader.readAsDataURL(file);
        
        // Автоматическая отправка формы
        setTimeout(() => {
            const form = document.getElementById('profileForm');
            if (!form) return;
            
            // Создаём скрытое поле для действия
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'upload_avatar';
            form.appendChild(actionInput);
            
            form.submit();
        }, 1000);
    });
    
    // Drag & drop для аватара
    avatarDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        avatarDropZone.classList.add('drag-over');
    });
    
    avatarDropZone.addEventListener('dragleave', () => {
        avatarDropZone.classList.remove('drag-over');
    });
    
    avatarDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        avatarDropZone.classList.remove('drag-over');
        
        const file = e.dataTransfer.files[0];
        if (!file) return;
        
        // Симулируем выбор файла
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        avatarInput.files = dataTransfer.files;
        
        // Триггерим событие change
        avatarInput.dispatchEvent(new Event('change'));
    });
}

// ======================
// ПОРТФОЛИО
// ======================

function initPortfolioUpload() {
    const portfolioAddBtn = document.getElementById('portfolioAddBtn');
    const portfolioInput = document.getElementById('portfolioInput');
    const portfolioGrid = document.getElementById('portfolioGrid');
    
    if (!portfolioAddBtn || !portfolioInput || !portfolioGrid) return;
    
    // Клик по кнопке добавления
    portfolioAddBtn.addEventListener('click', () => {
        portfolioInput.click();
    });
    
    // Обработка выбора файлов
    portfolioInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;
        
        // Проверяем количество работ
        const currentCount = document.querySelectorAll('.portfolio-item:not(.portfolio-item--add)').length;
        if (currentCount + files.length > 20) {
            showError('portfolio', `Максимум 20 работ в портфолио. Можно добавить ещё ${20 - currentCount} работ`);
            return;
        }
        
        // Валидация и предпросмотр каждого файла
        files.forEach(file => {
            if (!validateImageFile(file, {
                maxSize: 10 * 1024 * 1024, // 10 МБ
                allowedTypes: ['image/jpeg', 'image/png', 'image/webp']
            })) {
                showError('portfolio', 'Файлы должны быть JPG, PNG или WebP, не более 10 МБ');
                return;
            }
            
            // AJAX загрузка файла
            uploadPortfolioFile(file);
        });
        
        // Сбрасываем input
        portfolioInput.value = '';
    });
}

function initPortfolioDelete() {
    // Обработчик удаления работ (делегирование событий)
    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.portfolio-item__delete');
        if (!deleteBtn) return;
        
        const portfolioItem = deleteBtn.closest('.portfolio-item');
        const itemId = portfolioItem.dataset.id;
        
        if (!itemId) {
            // Если это предпросмотр (без сохранения в БД)
            portfolioItem.remove();
            updatePortfolioGrid();
            return;
        }
        
        // AJAX удаление
        if (confirm('Удалить эту работу из портфолио?')) {
            deletePortfolioItem(itemId, portfolioItem);
        }
    });
}

function deletePortfolioItem(itemId, element) {
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    
    fetch('/profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'delete_portfolio_ajax',
            item_id: itemId,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.remove();
            updatePortfolioGrid();
            showSuccess(data.message);
        } else {
            showError('portfolio', data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting portfolio item:', error);
        showError('portfolio', 'Ошибка при удалении работы');
    });
}

function uploadPortfolioFile(file) {
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_portfolio_ajax');
    formData.append('csrf_token', csrfToken);
    
    // Показываем плейсхолдер загрузки
    const loadingItem = createPortfolioLoadingItem();
    const portfolioGrid = document.getElementById('portfolioGrid');
    if (portfolioGrid) {
        portfolioGrid.insertBefore(loadingItem, document.getElementById('portfolioAddBtn'));
    }
    
    fetch('/profile.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingItem.remove();
        
        if (data.success) {
            // Добавляем новую работу в сетку
            addPortfolioItemToGrid(data.id, data.path);
            showSuccess(data.message);
        } else {
            showError('portfolio', data.message);
        }
    })
    .catch(error => {
        loadingItem.remove();
        console.error('Error uploading portfolio file:', error);
        showError('portfolio', 'Ошибка при загрузке файла');
    });
}

function createPortfolioLoadingItem() {
    const div = document.createElement('div');
    div.className = 'portfolio-item';
    div.innerHTML = `
        <div class="portfolio-item__loading">
            <div class="loading-spinner"></div>
            <span>Загрузка...</span>
        </div>
    `;
    return div;
}

function addPortfolioItemToGrid(itemId, imagePath) {
    const portfolioGrid = document.getElementById('portfolioGrid');
    if (!portfolioGrid) return;
    
    const portfolioItem = document.createElement('div');
    portfolioItem.className = 'portfolio-item';
    portfolioItem.dataset.id = itemId;
    portfolioItem.innerHTML = `
        <img src="${imagePath}" alt="Работа в портфолио" class="portfolio-item__image">
        <button type="button" class="portfolio-item__delete" title="Удалить">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    `;
    
    // Вставляем перед кнопкой добавления
    portfolioGrid.insertBefore(portfolioItem, document.getElementById('portfolioAddBtn'));
    updatePortfolioGrid();
}

function updatePortfolioGrid() {
    const portfolioGrid = document.getElementById('portfolioGrid');
    if (!portfolioGrid) return;
    
    // Обновляем количество работ в подсказке
    const currentCount = document.querySelectorAll('.portfolio-item:not(.portfolio-item--add)').length;
    const hintElement = document.querySelector('.portfolio-hint');
    if (hintElement) {
        hintElement.textContent = `Перетащите изображения сюда или нажмите на кнопку +. Максимум 20 работ. Загружено: ${currentCount}/20.`;
    }
    
    // Показываем/скрываем кнопку добавления
    const addBtn = document.getElementById('portfolioAddBtn');
    if (addBtn) {
        addBtn.style.display = currentCount >= 20 ? 'none' : 'flex';
    }
}

function setupPortfolioDragAndDrop() {
    const portfolioGrid = document.getElementById('portfolioGrid');
    if (!portfolioGrid) return;
    
    portfolioGrid.addEventListener('dragover', (e) => {
        e.preventDefault();
        portfolioGrid.classList.add('drag-over');
    });
    
    portfolioGrid.addEventListener('dragleave', () => {
        portfolioGrid.classList.remove('drag-over');
    });
    
    portfolioGrid.addEventListener('drop', (e) => {
        e.preventDefault();
        portfolioGrid.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files).filter(file => 
            file.type.startsWith('image/')
        );
        
        if (files.length === 0) return;
        
        // Вставляем файлы в input
        const portfolioInput = document.getElementById('portfolioInput');
        if (portfolioInput) {
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            portfolioInput.files = dataTransfer.files;
            
            // Триггерим событие change
            portfolioInput.dispatchEvent(new Event('change'));
        }
    });
}

// ======================
// ВАЛИДАЦИЯ ФОРМЫ
// ======================

function initFormValidation() {
    const form = document.getElementById('profileForm');
    if (!form) return;
    
    // Слушаем событие submit для валидации перед отправкой
    form.addEventListener('submit', (e) => {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Валидация обязательных полей при потере фокуса
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', () => {
            validateField(field);
        });
        
        // Очищаем ошибку при вводе
        field.addEventListener('input', () => {
            clearError(field);
        });
    });
    
    // Валидация URL поля
    const websiteField = form.querySelector('#website');
    if (websiteField) {
        websiteField.addEventListener('blur', () => {
            if (websiteField.value.trim() && !isValidUrl(websiteField.value)) {
                showError(websiteField, 'Введите корректный URL (начинается с http:// или https://)');
            } else {
                clearError(websiteField);
            }
        });
    }
    
    // Валидация поля ставки
    const rateField = form.querySelector('#rate');
    if (rateField) {
        rateField.addEventListener('blur', () => {
            if (rateField.value && (!Number.isInteger(parseFloat(rateField.value)) || parseFloat(rateField.value) < 0)) {
                showError(rateField, 'Ставка должна быть положительным числом');
            } else {
                clearError(rateField);
            }
        });
    }
    
    // Валидация Telegram
    const telegramField = form.querySelector('#telegram');
    if (telegramField) {
        telegramField.addEventListener('blur', () => {
            if (telegramField.value.trim() && !isValidTelegram(telegramField.value)) {
                showError(telegramField, 'Введите корректный Telegram (например: @username или username)');
            } else {
                clearError(telegramField);
            }
        });
    }
}

function validateForm() {
    const form = document.getElementById('profileForm');
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Проверяем обязательные поля
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Проверяем навыки (минимум 1)
    const skills = form.querySelectorAll('input[name="skills[]"]:checked');
    if (skills.length === 0) {
        showError('skills', 'Выберите хотя бы один навык');
        isValid = false;
    } else {
        clearError('skills');
    }
    
    // Проверяем инструменты (минимум 1)
    const tools = form.querySelectorAll('input[name="tools[]"]:checked');
    if (tools.length === 0) {
        showError('tools', 'Выберите хотя бы один инструмент');
        isValid = false;
    } else {
        clearError('tools');
    }
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name || field.id;
    
    if (field.required && !value) {
        showError(field, 'Это поле обязательно для заполнения');
        return false;
    }
    
    // Дополнительная валидация в зависимости от типа поля
    switch (field.type) {
        case 'email':
            if (value && !isValidEmail(value)) {
                showError(field, 'Введите корректный email');
                return false;
            }
            break;
            
        case 'url':
            if (value && !isValidUrl(value)) {
                showError(field, 'Введите корректный URL');
                return false;
            }
            break;
            
        case 'number':
            if (value && isNaN(parseFloat(value))) {
                showError(field, 'Введите число');
                return false;
            }
            break;
    }
    
    clearError(field);
    return true;
}

// ======================
// СЧЕТЧИК СИМВОЛОВ ДЛЯ TEXTAREA
// ======================

function initTextareaCounter() {
    const aboutTextarea = document.getElementById('about');
    const aboutCounter = document.getElementById('aboutCounter');
    
    if (!aboutTextarea || !aboutCounter) return;
    
    // Устанавливаем начальное значение
    aboutCounter.textContent = aboutTextarea.value.length;
    
    // Обновляем счетчик при вводе
    aboutTextarea.addEventListener('input', () => {
        const length = aboutTextarea.value.length;
        aboutCounter.textContent = length;
        
        // Подсвечиваем если превышен лимит
        if (length > 1000) {
            aboutCounter.style.color = '#e74c3c';
            showError(aboutTextarea, 'Максимум 1000 символов');
        } else {
            aboutCounter.style.color = '';
            clearError(aboutTextarea);
        }
    });
}

// ======================
// СТИЛИ ЧЕКБОКСОВ
// ======================

function initCheckboxStyling() {
    // Автоматически добавляем классы к кастомным чекбоксам
    const checkboxes = document.querySelectorAll('.checkbox-input');
    checkboxes.forEach(checkbox => {
        // Инициализация состояния
        updateCheckboxStyle(checkbox);
        
        // Обновляем при изменении
        checkbox.addEventListener('change', () => {
            updateCheckboxStyle(checkbox);
        });
    });
}

function updateCheckboxStyle(checkbox) {
    const customCheckbox = checkbox.nextElementSibling;
    if (customCheckbox && customCheckbox.classList.contains('checkbox-custom')) {
        if (checkbox.checked) {
            customCheckbox.classList.add('checked');
        } else {
            customCheckbox.classList.remove('checked');
        }
    }
}

// ======================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ======================

function validateImageFile(file, options = {}) {
    const { maxSize = 5 * 1024 * 1024, allowedTypes = ['image/jpeg', 'image/png', 'image/webp'] } = options;
    
    // Проверка типа
    if (!allowedTypes.includes(file.type)) {
        return false;
    }
    
    // Проверка размера
    if (file.size > maxSize) {
        return false;
    }
    
    return true;
}

function isValidEmail(email) {
    const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return re.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

function isValidTelegram(telegram) {
    const re = /^@?[a-zA-Z0-9_]{5,32}$/;
    return re.test(telegram);
}

function showError(elementOrName, message) {
    let field;
    
    if (typeof elementOrName === 'string') {
        // Ищем поле по имени или id
        field = document.querySelector(`[name="${elementOrName}"], #${elementOrName}`);
        if (!field) {
            // Показываем общее сообщение
            showFlashMessage(message, 'error');
            return;
        }
    } else {
        field = elementOrName;
    }
    
    // Находим или создаем элемент для ошибки
    let errorSpan = field.parentElement.querySelector('.error-text');
    if (!errorSpan) {
        errorSpan = document.createElement('span');
        errorSpan.className = 'error-text';
        field.parentElement.appendChild(errorSpan);
    }
    
    errorSpan.textContent = message;
    field.parentElement.classList.add('has-error');
    
    // Прокручиваем к ошибке
    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearError(elementOrName) {
    let field;
    
    if (typeof elementOrName === 'string') {
        field = document.querySelector(`[name="${elementOrName}"], #${elementOrName}`);
        if (!field) return;
    } else {
        field = elementOrName;
    }
    
    const errorSpan = field.parentElement.querySelector('.error-text');
    if (errorSpan) {
        errorSpan.remove();
    }
    field.parentElement.classList.remove('has-error');
}

function showSuccess(message) {
    showFlashMessage(message, 'success');
}

function showFlashMessage(message, type = 'info') {
    // Создаем элемент сообщения
    const messageDiv = document.createElement('div');
    messageDiv.className = `flash-message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-family: var(--font-secondery);
        font-size: 14px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#51d62c';
    } else if (type === 'error') {
        messageDiv.style.backgroundColor = '#e74c3c';
    } else {
        messageDiv.style.backgroundColor = '#3498db';
    }
    
    // Добавляем CSS для анимации
    if (!document.querySelector('#flash-animation')) {
        const style = document.createElement('style');
        style.id = 'flash-animation';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Добавляем в DOM
    document.body.appendChild(messageDiv);
    
    // Автоматическое удаление через 5 секунд
    setTimeout(() => {
        messageDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }, 5000);
}