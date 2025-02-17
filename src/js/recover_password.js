import API_CONFIG from "./config.js";

document.getElementById('recoveryForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    const submitButton = document.getElementById('send-button');
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
        grecaptcha.reset();
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Sending...';

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
    .then(response => {
        if (response.status === 405) {
            window.location.href = "/error.html?error=Method%20not%20allowed";
            return;
        }
        else if (response.status === 500) {
            window.location.href = "/error.html?error=Internal%20Server%20Error";
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
            successMessage.textContent = "Check your email";
            successMessage.style.display = 'block';
            document.getElementById('recoveryForm').reset(); // Reset form on success
            document.getElementById('recoveryForm').style.display = 'none';
            

        } else {
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
            submitButton.disabled = false;
            submitButton.textContent = 'Send';
            grecaptcha.reset();
        }
        grecaptcha.reset();
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = error.message;
        errorMessage.style.display = 'block';
        submitButton.textContent = 'Send';
        submitButton.disabled = false;
        grecaptcha.reset();
    });

});