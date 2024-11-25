import API_CONFIG from "./config";

document.getElementById('registerForm').addEventListener('submit', function(event) {
    // Prevent the form from submitting traditionally (default behavior)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const email = document.getElementById('email').value.trim();
    const full_name = document.getElementById('full_name').value.trim();

    // Validation
    if (username.length < 4) {
        errorMessage.textContent = "Username must be at least 4 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('username').focus();
        return;
    }

    if (password.length < 6) {
        errorMessage.textContent = "Password must be at least 6 characters.";
        errorMessage.style.display = 'block';
        document.getElementById('password').focus();
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorMessage.textContent = "Please enter a valid email address.";
        errorMessage.style.display = 'block';
        document.getElementById('email').focus();
        return;
    }

    // Send the data to the backend (register.php)
    fetch(API_CONFIG.register(), {
        method: 'POST',
        body: new URLSearchParams({
            username: username,
            email: email,
            password: password,
            full_name: full_name
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
    });
});
