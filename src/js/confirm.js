import API_CONFIG from "./config.js";

// Passing user data from PHP to JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    const confirmLabel = document.getElementById('confirm-label');

    if(window.location.search.length > 0) {
        const queryParams = new URLSearchParams(window.location.search);

        const token = queryParams.get('token');

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
                window.location.href = '../login.html';  // Or any other page
            } else {
                confirmLabel.textContent = 'Something goes wrong, please try again or contact us.';
                errorMessage.textContent = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            errorMessage.textContent = "An error occurred. Please try again.";
            errorMessage.style.display = 'block';
        });
    }
});