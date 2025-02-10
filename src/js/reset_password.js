import API_CONFIG from "./config.js";
import "./zxcvbn.js";

document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    const resetContainer = document.getElementById('reset-container');
    const waitingContainer = document.getElementById('waiting-container');

    let email='';
    let nickname='';

    const queryParams = new URLSearchParams(window.location.search);

    const reset_token = queryParams.get('token');
    const id=queryParams.get('id');

    fetch(API_CONFIG.reset_password(), {
        method: 'POST',
        body: new URLSearchParams({
            reset_token: reset_token,
            id:id,
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            waitingContainer.style.display='none';
            resetContainer.style.display='block';

            email=data.email;
            nickname=data.nickname;
        } else {
            waitingContainer.innerHTML = '<h2 style="color: red;">'+data.message+'</h2>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = "An error occurred. Please try again.";
        errorMessage.style.display = 'block';
    });

    document.getElementById('resetForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const password = document.getElementById('password').value.trim();

        console.log("I'm changing password");

        if (password.length < 8) {
            errorMessage.textContent = "Password must be at least 8 characters.";
            errorMessage.style.display = 'block';
            document.getElementById('password').focus();
            return;
        } else {
            const result = zxcvbn(password, [String(nickname), String(email)]);
            if (result.score < 4) {
                errorMessage.textContent = "Password is too weak. Please use a stronger password.";
                errorMessage.style.display = 'block';
                document.getElementById('password').focus();
                return;
            }
        }

        fetch(API_CONFIG.reset_password(), {
            method: 'PUT',
            body: new URLSearchParams({
                password: password,
                id:id,
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
});