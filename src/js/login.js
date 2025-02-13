import API_CONFIG from "./config.js";

document.getElementById('loginForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    // Get field values
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
        errorMessage.style.display = 'block';
        return;
    }

    // Example validation
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

    fetch(API_CONFIG.login(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email,
            password: password,
            recaptcharesponse: recaptcharesponse
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
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
        return response.json(); // Parse JSON response
    })
    .then(data => {
        if (data.success) {
            successMessage.textContent = data.message;
            successMessage.style.display = 'block';
            document.getElementById('loginForm').reset(); // Reset form on success
            window.location.href = '../user_dashboard.html';  // Or any other page
        } else {
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
            grecaptcha.reset();
        }
    })
    .catch(error => {
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
        grecaptcha.reset();
    })

});