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
    const nickname = document.getElementById('nickname').value.trim();
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
        grecaptcha.reset();
        return;
    }

    if (nickname.length < 4) {
        errorMessage.textContent = "Nickname must be at least 4 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('nickname').focus();
        grecaptcha.reset();
        return;
    }
    
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (password.length < 8) {
        errorMessage.textContent = "Password must be at least 8 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        grecaptcha.reset();
        return;
    } else if (!passwordRegex.test(password)) {
        errorMessage.textContent = "Password must agree password policy";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        grecaptcha.reset();
        return;
    } else {
        const result = zxcvbn(password, [String(email), String(nickname)]);
        if (result.score < 4) {
            errorMessage.textContent = "Password is too weak. Please use a stronger password.";
            errorMessage.style.display = 'block';
            document.getElementById('password').focus();
            grecaptcha.reset();
            return;
        }
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Registering...';

    // Send the data to the backend (register.php)
    fetch(API_CONFIG.register(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email,
            password: password,
            nickname: nickname,
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
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
            submitButton.disabled = false;
            submitButton.textContent = 'Register';
            grecaptcha.reset();
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
