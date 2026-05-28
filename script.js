// Мобильное меню и слайдер (оставляем как есть)

class APIContactForm {
    constructor() {
        this.form = document.getElementById('contact-form');
        this.messageEl = document.getElementById('form-message');
        
        if (this.form) {
            console.log('Форма найдена, AJAX включён');
            // Отключаем стандартную отправку формы
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleSubmit(e);
            });
            this.loadUserDataIfLoggedIn();
        } else {
            console.log('Форма НЕ найдена!');
        }
    }
    
    async handleSubmit(e) {
        console.log('Отправка через AJAX');
        
        const submitBtn = this.form.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Отправка...';
        submitBtn.disabled = true;
        
        const formData = new FormData(this.form);
        const data = {};
        
        formData.forEach((value, key) => {
            if (key === 'languages[]') {
                if (!data.languages) data.languages = [];
                data.languages.push(value);
            } else {
                data[key] = value;
            }
        });
        
        const isLoggedIn = document.body.dataset.loggedIn === 'true';
        const userId = document.body.dataset.userId;
        
        const url = (isLoggedIn && userId) ? `/lab8/api.php/${userId}` : '/lab8/api.php';
        const method = (isLoggedIn && userId) ? 'PUT' : 'POST';
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                if (result.login && result.password) {
                    this.showMessage(
                        `✅ Данные сохранены!<br><br>
                         🔑 Логин: <strong>${escapeHtml(result.login)}</strong><br>
                         🔒 Пароль: <strong>${escapeHtml(result.password)}</strong><br><br>
                         Сохраните эти данные!`, 
                        'success'
                    );
                    this.form.reset();
                    // НЕТ ПЕРЕЗАГРУЗКИ!
                } else {
                    this.showMessage('✅ Данные обновлены!', 'success');
                }
            } else {
                if (result.errors) {
                    let errorMsg = '';
                    for (let key in result.errors) {
                        errorMsg += `⚠️ ${escapeHtml(result.errors[key])}<br>`;
                    }
                    this.showMessage(errorMsg, 'error');
                } else {
                    this.showMessage(`❌ Ошибка: ${escapeHtml(result.error || 'Неизвестная ошибка')}`, 'error');
                }
            }
        } catch (error) {
            this.showMessage(`❌ Ошибка сети: ${escapeHtml(error.message)}`, 'error');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }
    
    showMessage(text, type) {
        if (this.messageEl) {
            this.messageEl.innerHTML = text;
            this.messageEl.className = type;
            this.messageEl.style.display = 'block';
        }
    }
    
    async loadUserDataIfLoggedIn() {
        // ... код загрузки данных ...
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
    new Slider();
    new APIContactForm();
});