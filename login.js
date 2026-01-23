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
function validateLoginEmail(input) {
    const email = input.value;
    const errorDiv = document.getElementById('loginEmailError');
    
    if (email === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    // Vérification avec regex pour @gmail.com
    const gmailRegex = /@gmail\.com$/i;
    if (!gmailRegex.test(email)) {
        errorDiv.textContent = "L'email doit se terminer par @gmail.com";
        errorDiv.style.display = 'block';
        input.setCustomValidity("L'email doit se terminer par @gmail.com");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}
function validateLoginPassword(input) {
    const password = input.value;
    const errorDiv = document.getElementById('loginPasswordError');
    
    if (password === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    if (password.length < 6) {
        errorDiv.textContent = "Le mot de passe doit contenir au moins 6 caractères";
        errorDiv.style.display = 'block';
        input.setCustomValidity("Le mot de passe doit contenir au moins 6 caractères");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

// Validation en temps réel pour le formulaire d'inscription
function validateRegisterEmail(input) {
    const email = input.value;
    const errorDiv = document.getElementById('registerEmailError');
    
    if (email === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    // Vérification avec regex pour @gmail.com
    const gmailRegex = /@gmail\.com$/i;
    if (!gmailRegex.test(email)) {
        errorDiv.textContent = "L'email doit se terminer par @gmail.com";
        errorDiv.style.display = 'block';
        input.setCustomValidity("L'email doit se terminer par @gmail.com");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

function validateRegisterPassword(input) {
    const password = input.value;
    const errorDiv = document.getElementById('registerPasswordError');
    
    if (password === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    if (password.length < 6) {
        errorDiv.textContent = "Le mot de passe doit contenir au moins 6 caractères";
        errorDiv.style.display = 'block';
        input.setCustomValidity("Le mot de passe doit contenir au moins 6 caractères");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

// Validation des formulaires avant soumission
//seconnecter
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    const emailInput = this.querySelector('input[name="username"]');
    const passwordInput = this.querySelector('input[name="password"]');
    let valid = true;
    
    if (!/@gmail\.com$/i.test(emailInput.value)) {
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
        valid = false;
    }
    
    if (valid && passwordInput.value.length < 6) {
        alert("Le mot de passe doit contenir au moins 6 caractères");
        passwordInput.focus();
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
    }
});
//sinscrire
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const usernameInput = this.querySelector('input[name="username"]');
    const emailInput = this.querySelector('input[name="email"]');
    const passwordInput = this.querySelector('input[name="password"]');
    let valid = true;
    
    if (usernameInput.value.length < 3) {
        alert("Le nom d'utilisateur doit contenir au moins 3 caractères");
        usernameInput.focus();
        valid = false;
    }
    
    if (valid && !/@gmail\.com$/i.test(emailInput.value)) {
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
        valid = false;
    }
    
    if (valid && passwordInput.value.length < 6) {
        alert("Le mot de passe doit contenir au moins 6 caractères");
        passwordInput.focus();
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
    }
});