import API_CONFIG from "./config.js";

// Passing user data from PHP to JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');

    if(window.location.search.length > 0) {
        const token = window.location.search.split('=')[1];
        fetch(API_CONFIG.confirm(), {
            method: 'POST',
            body: new URLSearchParams({
                token: token,
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successMessage.textContent = data.message;
                successMessage.style.display = 'block';
                // document.getElementById('registerForm').reset(); // Reset form on success

                // Optionally, you can redirect to the user dashboard if success
                window.location.href = '../login.html';  // Or any other page
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
    }
});