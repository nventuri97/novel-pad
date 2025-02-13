import API_CONFIG from "./config.js";

document.getElementById('adminLoginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
        errorMessage.style.display = 'block';
        return;
    }

    if (email === '' || password === '') {
        errorMessage.textContent = "Both fields are required.";
        errorMessage.style.display = 'block';
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorMessage.textContent = "Please enter a valid email address.";
        errorMessage.style.display = 'block';
        document.getElementById('email').focus();
        return;
    }

    // Perform the fetch with form-urlencoded credentials
    fetch(API_CONFIG.adminLogin(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email,
            password: password,
            recaptcharesponse: recaptcharesponse
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'include' // Important for handling sessions/cookies
    })
    .then(response => {
        if (response.status === 405) {
            window.location.href = "/error.html?error=Method%20not%20allowed";
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
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // If the force_password_change flag is true, redirect to the password change page
            if (data.force_password_change) {
                window.location.href = 'admin_change_password.html';
            } else {
                window.location.href = 'admin_dashboard.html';
            }
        } else {
            errorMessage.textContent = data.message || "Login failed.";
            errorMessage.style.display = 'block';
            grecaptcha.reset();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
        grecaptcha.reset();
    });
});
