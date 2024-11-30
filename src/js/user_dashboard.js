import API_CONFIG from "./config.js";

// Passing user data from PHP to JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');

    const logoutButton = document.getElementsByClassName('logout-button')[0];

    // Fetch user data from the server
    fetch(API_CONFIG.userDashboard(), {
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

    // Logout button event listener to handle user logout
    logoutButton.addEventListener('click', function (event) {
        event.preventDefault();
        // Fetch user data from the server
        fetch(API_CONFIG.logout(), {
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

    const addNovelButton = document.getElementById('addNovelButton');
    const addNovelModal = document.getElementById('addNovelModal');
    const closeModal = document.getElementById('closeModal');
    const addNovelForm = document.getElementById('addNovelForm');

    // Show the modal when the add novel button is clicked
    addNovelButton.addEventListener('click', function () {
        addNovelModal.style.display = 'block';
    });

    // Close the modal when the close button is clicked
    closeModal.addEventListener('click', function () {
        addNovelModal.style.display = 'none';
    });

    // Close the modal when clicking outside the modal content
    window.addEventListener('click', function (event) {
        if (event.target === addNovelModal) {
            addNovelModal.style.display = 'none';
        }
    });

    // Handle form submission
    addNovelForm.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        // Collect form data
        const formData = new FormData(addNovelForm);

        // Send form data to the server via Fetch API
        fetch(API_CONFIG.add_novel(), {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the modal and reset the form
                addNovelModal.style.display = 'none';
                addNovelForm.reset();
                alert(data.message); // Show success message
            } else {
                // Show error message
                errorMessage.innerText = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.innerText = 'An error occurred. Please try again.';
        });
    });

});
