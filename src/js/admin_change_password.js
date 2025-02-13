import API_CONFIG from "./config.js";
import "./zxcvbn.js";

document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const errorElem = document.getElementById('error-message');
    const successElem = document.getElementById('success-message');
    
    errorElem.style.display = 'none';
    successElem.style.display = 'none';
    
    const newPassword = document.getElementById('newPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();
    
    if (newPassword === '' || confirmPassword === '') {
        errorElem.textContent = "Please fill in all fields.";
        errorElem.style.display = 'block';
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
    
    if (newPassword !== confirmPassword) {
        errorElem.textContent = "Passwords do not match.";
        errorElem.style.display = 'block';
        return;
    }
    
    fetch(API_CONFIG.adminChangePassword(), {
        method: 'POST',
        body: new URLSearchParams({
            newPassword: newPassword
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'include'
    })
    .then(response => {
        if (response.status === 405) {
            window.location.href = "/error.html?error=Method%20not%20allowed";
            return;
        }
        else if (response.status === 401) {
            window.location.href = "/error.html?error=Unauthorized";
            return;
        }
        else if (response.status === 403) {
            window.location.href = "/error.html?error=Forbidden";
            return;
        }
        else if (response.status === 419) {
            window.location.href = "/error.html?error=Session%20expired";
            return;
        }
        else if (response.status === 500) {
            handleError("Internal server error. Please try again later.", "Internal server error. Please try again later.");
            return;
        }

        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status} - ${text}`);
            });
        }
        return response.json(); // Parse JSON response
    })
    .then(data => {
        console.log("Parsed JSON:", data);
        if (data.success) {
            successElem.textContent = "Password changed successfully! Redirecting...";
            successElem.style.display = 'block';
            setTimeout(() => {
                window.location.href = 'admin_dashboard.html';
            }, 2000);
        } else {
            errorElem.textContent = data.message || "Error changing password.";
            errorElem.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        errorElem.textContent = "An error occurred. Please try again.";
        errorElem.style.display = 'block';
    });
});
