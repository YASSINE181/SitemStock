// login.js - Version simplifiée
document.addEventListener('DOMContentLoaded', function() {
    const authWrapper = document.querySelector('.auth-wrapper');
    const loginTrigger = document.querySelector('.login-trigger');
    const registerTrigger = document.querySelector('.register-trigger');
    
    // Basculer entre login et register
    if (registerTrigger) {
        registerTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            authWrapper.classList.add('toggled');
        });
    }
    
    if (loginTrigger) {
        loginTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            authWrapper.classList.remove('toggled');
        });
    }
    
    // Effet sur les champs
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Vérifier l'état initial
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
});