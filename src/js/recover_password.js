import API_CONFIG from "./config.js";

document.getElementById('recoveryForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    // Get field values
    const email = document.getElementById('e-mail').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
        errorMessage.style.display = 'block';
        return;
    }

    // Example validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorMessage.textContent = "Please enter a valid email address.";
        errorMessage.style.display = 'block';
        document.getElementById('email').focus();
        return;
    }

    fetch(API_CONFIG.recover_password(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email,
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
            document.getElementById('recoveryForm').reset(); // Reset form on success
        } else {
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
        }
        grecaptcha.reset();
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = error.message;
        errorMessage.style.display = 'block';
    })

});