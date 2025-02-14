import API_CONFIG from "./config.js";
import "./zxcvbn.js";

document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const errorMessage = document.getElementById('error-message');
    const successElem = document.getElementById('success-message');
    
    // Nascondi eventuali messaggi precedenti
    errorMessage.style.display = 'none';
    successElem.style.display = 'none';
    
    const currentPassword = document.getElementById('currentPassword').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();
    
    if (currentPassword === '' || newPassword === '' || confirmPassword === '') {
        errorMessage.textContent = "Please fill in all fields.";
        errorMessage.style.display = 'block';
        return;
    }

    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (newPassword.length < 8) {
        errorMessage.textContent = "Password must be at least 8 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        grecaptcha.reset();
        return;
    } else if (!passwordRegex.test(newPassword)) {
        errorMessage.textContent = "Password must agree password policy";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        grecaptcha.reset();
        return;
    } else {
        const result = zxcvbn(newPassword);
        if (result.score < 4) {
            errorMessage.textContent = "Password is too weak. Please use a stronger password.";
            errorMessage.style.display = 'block';
            document.getElementById('password').focus();
            grecaptcha.reset();
            return;
        }
    }

    if(newPassword === currentPassword) {
        errorMessage.textContent = "New password must be different from current password.";
        errorMessage.style.display = 'block';
        return;
    }
    
    if (newPassword !== confirmPassword) {
        errorMessage.textContent = "Passwords do not match.";
        errorMessage.style.display = 'block';
        return;
    }
    
    // Esegui la fetch con credenziali di tipo form-urlencoded
    fetch(API_CONFIG.change_password(), {
        method: 'POST',
        body: new URLSearchParams({
            currentPassword: currentPassword,
            newPassword: newPassword
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        // credentials: 'include' // Importante per gestire la sessione/cookie
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Parsed JSON:", data);
        if (data.success) {
            successElem.textContent = "Password changed successfully! Redirecting...";
            successElem.style.display = 'block';
            setTimeout(() => {
                window.location.href = 'user_dashboard.html';
            }, 2000);
        } else {
            errorMessage.textContent = data.message || "Error changing password.";
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
    });
});
