import API_CONFIG from "./config.js";

document.getElementById('loginForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    // Get field values
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
        errorMessage.style.display = 'block';
        return;
    }

    // Example validation
    if (username === '' || password === '') {
        errorMessage.textContent = "Both fields are required.";
        errorMessage.style.display = 'block';
        return;
    }

    fetch(API_CONFIG.login(), {
        method: 'POST',
        body: new URLSearchParams({
            username: username,
            password: password,
            recaptcharesponse: recaptcharesponse
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response=>response.json())
    .then(data => {
        if (data.success) {
            successMessage.textContent = data.message;
            successMessage.style.display = 'block';
            document.getElementById('loginForm').reset(); // Reset form on success
            window.location.href = '../user_dashboard.html';  // Or any other page
        } else {
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
        grecaptcha.reset();
    })

});