import API_CONFIG from "./config.js";
import "./zxcvbn.js";

document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
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
        document.getElementById('newPassword').focus();
        document.getElementById('confirmPassword').focus();
        return;
    } else if (!passwordRegex.test(newPassword)) {
        errorMessage.textContent = "Password must agree password policy";
        errorMessage.style.display = 'block';
        document.getElementById('newPassword').focus();
        document.getElementById('confirmPassword').focus();
        return;
    } else {
        const result = zxcvbn(newPassword);
        if (result.score < 4) {
            errorMessage.textContent = "Password is too weak. Please use a stronger password.";
            errorMessage.style.display = 'block';
            document.getElementById('newPassword').focus();
            document.getElementById('confirmPassword').focus();
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
    
    fetch(API_CONFIG.adminChangePassword(), {
        method: 'POST',
        body: new URLSearchParams({
            currentPassword: currentPassword,
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
        if (data.success) {
            successMessage.textContent = "Password changed successfully! Redirecting...";
            successMessage.style.display = 'block';
            setTimeout(() => {
                window.location.href = 'admin_dashboard.html';
            }, 2000);
        } else {
            errorMessage.textContent = data.message || "Error changing password.";
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
    });
});
