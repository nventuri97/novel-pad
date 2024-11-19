// Passing user data from PHP to JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');

    logoutButton = document.getElementsByClassName('logout-button')[0];

    // Fetch user data from the server
    fetch('../api/user_dashboard.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Attempt to parse JSON
    })
    .then(data => {
        if (data.success) {
            const user = data.data;
            document.getElementById('fullName').innerText = user.full_name || 'N/A';
            document.getElementById('email').innerText = user.email || 'N/A';
            document.getElementById('fullNameText').innerText = user.full_name || 'N/A';
            document.getElementById('status').innerText = user.is_premium ? 'Premium' : 'Standard';
        } else {
            console.error('Error:', data.message);
            alert(data.message); // Show error message
        }
    })
    .catch(error => {
        console.error('Error fetching user data:', error);
        alert("An error occurred. Please try again later.");
    });

    logoutButton.addEventListener('click', function (event) {
        event.preventDefault();
        // Fetch user data from the server
        fetch('../api/logout.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json(); // Attempt to parse JSON
        })
        .then(data => {
            if (data.success) {
                window.location.href = '../login.html';
            } else {
                console.error('Error:', data.message);
                alert(data.message); // Show error message
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            alert("An error occurred. Please try again later.");
        });
    });
    
});
