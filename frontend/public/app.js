class BillSplitter {
    static API_URL = 'http://localhost:8000';
    static CHECK_URL = '/users/check';
    static DEBOUNCE_DELAY = 500;

    constructor() {
        // Основные элементы
        this.form = document.getElementById('userForm');
        this.nameInput = document.getElementById('name');
        this.emailInput = document.getElementById('email');
        this.nameError = document.getElementById('nameError');
        this.emailError = document.getElementById('emailError');
        this.submitBtn = document.getElementById('submitBtn');
        this.tableBody = document.getElementById('participantsTable').querySelector('tbody');
        this.resetBtn = document.getElementById('resetBtn');
        this.summaryEl = document.getElementById('summary');

        // Состояние
        this.isLoading = false;
        this.debounceTimers = {};

        // Инициализация
        this.init();
    }

    init() {
        this.loadParticipants();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Форма
        this.form.addEventListener('submit', e => this.handleSubmit(e));

        // Кнопка сброса
        this.resetBtn.addEventListener('click', () => this.resetParticipants());

        // Live-валидация
        // this.nameInput.addEventListener('input', () => {
        //     this.clearError('name');
        //     this.debounce('name', () => this.validateField('name', this.nameInput.value.trim()));
        // });
        //
        // this.emailInput.addEventListener('input', () => {
        //     this.clearError('email');
        //     this.debounce('email', () => this.validateField('email', this.emailInput.value.trim()));
        // });
    }

    async loadParticipants() {
        this.setLoading(true);

        try {
            const response = await fetch(BillSplitter.API_URL);

            if (!response.ok) {
                throw new Error('Failed to load participants: ${response.status}');
            }

            const users = await response.json();
            this.renderParticipants(users);
            this.updateSummary(users.length);
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.setLoading(false);
        }
    }

    renderParticipants(users) {
        this.tableBody.innerHTML = '';

        if (users.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="3" class="empty-state">No participants added yet</td>';
            this.tableBody.appendChild(row);
            return;
        }

        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML =
                `<td>${user.name}</td><td>${user.email}</td><td>${user.share.toFixed(2)}€</td>`;
            this.tableBody.appendChild(row);
        });
    }

    updateSummary(count) {
        const share = count > 0 ? (100 / count).toFixed(2) : 0;
        this.summaryEl.textContent =
            `${count}participant${count !== 1 ? 's' : ''},${share}€ each`;
    }

    async handleSubmit(e) {
        e.preventDefault();

        const data = {
            name: this.nameInput.value.trim(),
            email: this.emailInput.value.trim()
        };

        // Валидация
        if (!this.validateForm(data)) return;

        this.setLoading(true, 'submit');

        try {
            const response = await fetch(BillSplitter.API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `Request failed: ${response.status}`);
            }

            // Очистка формы
            this.form.reset();
            await this.loadParticipants();
            this.showNotification('Participant added successfully!', 'success');
        } catch (error) {
            this.handleSubmitError(error);
        } finally {
            this.setLoading(false, 'submit');
        }
    }

    async resetParticipants() {
        if (!confirm('Are you sure you want to reset all participants? This cannot be undone.')) {
            return;
        }

        this.setLoading(true, 'reset');

        try {
            const response = await fetch(BillSplitter.API_URL, {
                method: 'DELETE'
            });

            if (!response.ok) {
                throw new Error('Failed to reset participants');
            }

            await this.loadParticipants();
            this.showNotification('All participants have been reset', 'success');
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.setLoading(false, 'reset');
        }
    }

    async validateField(field, value) {
        if (!value || (field === 'name' && value.length < 2)) return;

        try {
            const params = new URLSearchParams({field, value});
            const response = await fetch(`${BillSplitter.CHECK_URL}? ${params}`);

            if (!response.ok) return;

            const data = await response.json();
            const errorElement = field === 'name' ? this.nameError : this.emailError;

            if (data.exists) {
                this.markFieldInvalid(field, `${field === 'name' ? 'Name' : 'Email'}already exists`);
            } else {
                this.markFieldValid(field);
            }
        } catch (error) {
            console.error('Validation error:', error);
        }
    }

    validateForm(data) {
        let isValid = true;

        // Валидация имени
        if (data.name.length < 2) {
            this.markFieldInvalid('name', 'Name must be at least 2 characters');
            isValid = false;
        }

        // Валидация email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.email)) {
            this.markFieldInvalid('email', 'Please enter a valid email');
            isValid = false;
        }

        return isValid;
    }

    markFieldInvalid(field, message) {
        const input = field === 'name' ? this.nameInput : this.emailInput;
        const errorElement = field === 'name' ? this.nameError : this.emailError;

        input.classList.add('invalid');
        input.classList.remove('valid');
        errorElement.textContent = message;
    }

    markFieldValid(field) {
        const input = field === 'name' ? this.nameInput : this.emailInput;
        const errorElement = field === 'name' ? this.nameError : this.emailError;

        input.classList.add('valid');
        input.classList.remove('invalid');
        errorElement.textContent = '';
    }

    clearError(field) {
        const input = field === 'name' ? this.nameInput : this.emailInput;
        const errorElement = field === 'name' ? this.nameError : this.emailError;

        input.classList.remove('invalid', 'valid');
        errorElement.textContent = '';
    }

    handleSubmitError(error) {
        try {
            const errorData = JSON.parse(error.message);

            if (errorData.errors) {
                if (errorData.errors.name) {
                    this.markFieldInvalid('name', errorData.errors.name);
                }
                if (errorData.errors.email) {
                    this.markFieldInvalid('email', errorData.errors.email);
                }
                this.showNotification('Please fix form errors', 'warning');
                return;
            }
        } catch {
            // Не JSON ошибка
        }

        this.showNotification(error.message, 'error');
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = type;
        notification.innerHTML = `<span class="notification-icon">${type === 'error' ? '⚠️' : '✓'}</span> <span>${message}</span>`;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 5000);
    }

    setLoading(isLoading, context = 'global') {
        this.isLoading = isLoading;

        if (context === 'submit') {
            this.submitBtn.classList.toggle('loading', isLoading);
            this.submitBtn.disabled = isLoading;
        } else if (context === 'reset') {
            this.resetBtn.classList.toggle('loading', isLoading);
            this.resetBtn.disabled = isLoading;
        }
    }

    debounce(id, func) {
        clearTimeout(this.debounceTimers[id]);
        this.debounceTimers[id] = setTimeout(func, BillSplitter.DEBOUNCE_DELAY);
    }
}

// Инициализация приложения
document.addEventListener('DOMContentLoaded', () => new BillSplitter());