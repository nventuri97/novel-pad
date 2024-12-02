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
                document.getElementById('username').innerText = user.username || 'N/A';
                document.getElementById('email').innerText = user.email || 'N/A';
                document.getElementById('fullName').innerText = user.full_name || 'N/A';
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
                    fetchAndDisplayNovels(); // Fetch and display novels
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

    const novelsList = document.getElementById('novelsList');

    // Fetch all user novels
    // Function to fetch and display novels
    function fetchAndDisplayNovels() {
        fetch(API_CONFIG.get_novels(), {
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
                const novels = data.data;
                novelsList.innerHTML = ''; // Clear the list

                novels.forEach(novel => {
                    const relativeFilePath = novel.file_path.replace('/var/www/html/', '');
                    const novelItem = document.createElement('div');
                    novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Description</b>: ${novel.description}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p><a href="${relativeFilePath}">Go to the story</a></p>
                        </div>`;
                    novelsList.appendChild(novelItem);
                });
            } else {
                console.error('Error:', data.message);
                alert(data.message); // Show error message
            }
        })
        .catch(error => {
            console.error('Error fetching novels:', error);
            alert("An error occurred. Please try again later.");
        });
    }

    fetchAndDisplayNovels(); // Fetch and display novels
});
