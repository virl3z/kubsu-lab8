// Мобильное меню
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');

if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('active');
    });
}

// Мобильные выпадающие меню
const mobileDropdowns = document.querySelectorAll('.mobile-dropdown');

mobileDropdowns.forEach(dropdown => {
    const toggle = dropdown.querySelector('.dropdown-toggle');
    const menu = dropdown.querySelector('.mobile-dropdown-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            menu.classList.toggle('active');
        });
    }
});

// Слайдер
class Slider {
    constructor() {
        this.slides = document.querySelectorAll('.slide');
        this.dots = document.querySelectorAll('.dot');
        this.prevBtn = document.querySelector('.slider-prev');
        this.nextBtn = document.querySelector('.slider-next');
        this.currentSlide = 0;
        
        if (this.slides.length > 0) {
            this.init();
        }
    }
    
    init() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prevSlide());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.nextSlide());
        }
        
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        this.updateSlider();
        
        // Автопрокрутка каждые 5 секунд
        this.interval = setInterval(() => this.nextSlide(), 5000);
    }
    
    updateSlider() {
        const wrapper = document.querySelector('.slider-wrapper');
        if (wrapper) {
            wrapper.style.transform = `translateX(-${this.currentSlide * 100}%)`;
        }
        
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentSlide);
        });
        
        this.slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === this.currentSlide);
        });
    }
    
    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.updateSlider();
    }
    
    prevSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.updateSlider();
    }
    
    goToSlide(index) {
        this.currentSlide = index;
        this.updateSlider();
    }
}

// ============================================
// AJAX отправка формы на веб-сервис (API)
// ============================================
class APIContactForm {
    constructor() {
        this.form = document.getElementById('contact-form');
        this.messageEl = document.getElementById('form-message');
        
        if (this.form) {
            this.init();
            this.loadUserDataIfLoggedIn();
        }
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    // Загрузка данных пользователя из БД если авторизован
    async loadUserDataIfLoggedIn() {
        const isLoggedIn = document.body.dataset.loggedIn === 'true';
        const userId = document.body.dataset.userId;
        
        if (isLoggedIn && userId) {
            try {
                const response = await fetch(`/lab8/api.php/${userId}`);
                const data = await response.json();
                
                if (response.ok && data && data.id) {
                    document.getElementById('full_name').value = data.full_name || '';
                    document.getElementById('surname').value = data.surname || '';
                    document.getElementById('email').value = data.email || '';
                    document.getElementById('phone').value = data.phone || '';
                    document.getElementById('birth_date').value = data.birth_date || '';
                    
                    if (data.gender) {
                        const maleRadio = document.querySelector('input[name="gender"][value="male"]');
                        const femaleRadio = document.querySelector('input[name="gender"][value="female"]');
                        if (data.gender === 'male' && maleRadio) {
                            maleRadio.checked = true;
                        } else if (data.gender === 'female' && femaleRadio) {
                            femaleRadio.checked = true;
                        }
                    }
                    
                    if (data.languages) {
                        const select = document.getElementById('languages');
                        for (let i = 0; i < select.options.length; i++) {
                            select.options[i].selected = data.languages.includes(select.options[i].value);
                        }
                    }
                    
                    document.getElementById('biography').value = data.biography || '';
                    document.getElementById('question').value = data.question || '';
                    document.getElementById('experience').value = data.experience || '';
                    
                    if (data.agreed) {
                        const agreedCheckbox = document.querySelector('input[name="agreed"]');
                        if (agreedCheckbox) agreedCheckbox.checked = true;
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки данных:', error);
            }
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = this.form.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Отправка...';
        submitBtn.disabled = true;
        
        // Собираем данные формы
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
        
        // Определяем авторизован ли пользователь
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
                        `✅ Данные успешно сохранены!<br><br>
                         🔑 <strong>Ваш логин:</strong> ${escapeHtml(result.login)}<br>
                         🔒 <strong>Ваш пароль:</strong> ${escapeHtml(result.password)}<br><br>
                         ⚠️ <strong>Сохраните эти данные!</strong> Они понадобятся для входа.<br>
                         <a href="login.php" style="color: #155724; font-weight: bold;">👉 Перейти к входу</a>`, 
                        'success'
                    );
                    this.form.reset();
                    // ЛОГИН И ПАРОЛЬ БУДУТ ВИДНЫ 60 СЕКУНД, ПОТОМ СТРАНИЦА ПЕРЕЗАГРУЗИТСЯ
                    setTimeout(() => location.reload(), 60000);
                } else {
                    this.showMessage('✅ Данные успешно обновлены!', 'success');
                    setTimeout(() => location.reload(), 60000);
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
            
            // Сообщение об ошибке можно убрать через 8 секунд
            if (type === 'error') {
                setTimeout(() => {
                    this.messageEl.style.display = 'none';
                }, 8000);
            }
        }
    }
}

// Вспомогательная функция для экранирования HTML (защита от XSS)
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    // Инициализируем слайдер
    new Slider();
    
    // Инициализируем форму с API
    new APIContactForm();
    
    // Закрываем мобильное меню при клике на ссылку
    document.querySelectorAll('.mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
            }
        });
    });
    
    // Плавная прокрутка для якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#' || href === '') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});