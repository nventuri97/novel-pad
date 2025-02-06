import API_CONFIG from "./config.js";
import "./zxcvbn.js";

document.getElementById('registerForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    const submitButton = document.getElementById('register-button');

    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const full_name = document.getElementById('full_name').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
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
    
    if (password.length < 8) {
        errorMessage.textContent = "Password must be at least 8 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        return;
    } else {
        const result = zxcvbn(password, [String(email), String(full_name)]);
        if (result.score < 3) {
            errorMessage.textContent = "Password is too weak. Please use a stronger password.";
            errorMessage.style.display = 'block';
            document.getElementById('password').focus();
            return;
        }
    }

    // passwordPolicy.style.display = 'none';
    submitButton.disabled = true;
    submitButton.textContent = 'Registering...';

    // Send the data to the backend (register.php)
    fetch(API_CONFIG.register(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email,
            password: password,
            full_name: full_name,
            recaptcharesponse: recaptcharesponse
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successMessage.textContent = data.message;
            successMessage.style.display = 'block';
            document.getElementById('registerForm').reset(); // Reset form on success

            // Optionally, you can redirect to the user dashboard if success
            window.location.href = '../confirm.html';  // Or any other page
        } else {
            errorMessage.textContent = "Email already exists. Please try again.";
            errorMessage.style.display = 'block';
            submitButton.disabled = false;
            submitButton.textContent = 'Register';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
        submitButton.textContent = 'Register';
        submitButton.disabled = false;
        grecaptcha.reset();
    });
});
