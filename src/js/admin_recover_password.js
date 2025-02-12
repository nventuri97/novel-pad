import API_CONFIG from "./config.js";

document.getElementById('adminRecoveryForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const errorElem = document.getElementById('error-message');
    const successElem = document.getElementById('success-message');
    errorElem.style.display = 'none';
    successElem.style.display = 'none';

    const email = document.getElementById('admin-email').value.trim();

    // Validazione base dell'email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorElem.textContent = "Please enter a valid email address.";
        errorElem.style.display = 'block';
        document.getElementById('admin-email').focus();
        return;
    }

    fetch(API_CONFIG.adminRecoverPassword(), {
        method: 'POST',
        body: new URLSearchParams({
            email: email
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successElem.textContent = data.message;
            successElem.style.display = 'block';
            document.getElementById('adminRecoveryForm').reset();
        } else {
            errorElem.textContent = data.message;
            errorElem.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorElem.textContent = error.message;
        errorElem.style.display = 'block';
    });
});
