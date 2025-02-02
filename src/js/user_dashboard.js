import API_CONFIG from "./config.js";

// Execute when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    const errorMessage = document.getElementById('error-message');
    const logoutButton = document.getElementsByClassName('logout-button')[0];

    // Fetch user data from the server and display it on the dashboard
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
        return response.json(); // Parse JSON response
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
            alert(data.message); // Display error message
        }
    })
    .catch(error => {
        console.error('Error fetching user data:', error);
        alert("An error occurred. Please try again later.");
        window.location.href = '../login.html'; // Redirect to login page
        return;
    });

    // Handle user logout when the logout button is clicked
    logoutButton.addEventListener('click', function (event) {
        event.preventDefault();
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
            return response.json(); // Parse JSON response
        })
        .then(data => {
            if (data.success) {
                window.location.href = '../login.html'; // Redirect to login page
            } else {
                console.error('Error:', data.message);
                alert(data.message); // Display error message
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
            alert("An error occurred. Please try again later.");
            window.location.href = '../login.html'; // Redirect to login page
            return;
        });
    });

    // Add Novel Modal functionality
    const addNovelButton = document.getElementById('addNovelButton');
    const addNovelModal = document.getElementById('addNovelModal');
    const closeModal = document.getElementById('closeModal');
    const addNovelForm = document.getElementById('addNovelForm');

    // Display the modal when the "Add Novel" button is clicked
    addNovelButton.addEventListener('click', function () {
        addNovelModal.style.display = 'block';
    });

    // Manage form fields based on the novel type
    const novelTypeSelect = document.getElementById("novelType");
    const shortStoryField = document.getElementById("shortStoryField");
    const fileField = document.getElementById("fileField");

    const handleNovelTypeChange = () => {
        const selectedType = novelTypeSelect.value;
        if (selectedType === "short_story") {
            shortStoryField.style.display = "block";
            fileField.style.display = "none";
        } else if (selectedType === "full_novel") {
            shortStoryField.style.display = "none";
            fileField.style.display = "block";
        } else {
            shortStoryField.style.display = "none";
            fileField.style.display = "none";
        }
    };

    // Attach event listener to the novel type select element
    if (novelTypeSelect) {
        novelTypeSelect.addEventListener("change", handleNovelTypeChange);
    }
    handleNovelTypeChange();

    // Close the modal when the close button or outside the modal is clicked
    closeModal.addEventListener('click', function () {
        addNovelModal.style.display = 'none';
    });
    window.addEventListener('click', function (event) {
        if (event.target === addNovelModal) {
            addNovelModal.style.display = 'none';
        }
    });

    // Handle form submission for adding a novel
    addNovelForm.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(addNovelForm);

        // Handle short story type
        if (formData.get("type") === "short_story") {
            const title = formData.get("title");
            const genre = formData.get("genre");
            const storyContent = formData.get("story_content");

            if (!storyContent.trim()) {
                errorMessage.innerText = "Please provide content for the story.";
                return;
            }

            const htmlContent = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${title}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .story-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
        }
        h1 {
            font-family: 'Georgia', serif;
            text-align: center;
            color: #333;
        }
        .genre {
            text-align: center;
            font-weight: bold;
            color: #555;
            background: #e0e0e0;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }
        .story-content {
            margin-top: 20px;
            line-height: 1.6;
            text-align: justify;
        }
        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="story-container">
        <h1>${title}</h1>
        <p class="genre">${genre}</p>
        <div class="story-content">${storyContent}</div>
    </div>
    <button class="back-button"  onclick="location.href='/user_dashboard.html'">Back to Dashboard</button>
</body>
</html>`;


            const htmlFile = new Blob([htmlContent], { type: "text/html" });
            formData.append("file", htmlFile, `${title.replace(/\s+/g, "_")}.html`);
        }

        // Send form data to the server
        fetch(API_CONFIG.add_novel(), {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addNovelModal.style.display = 'none'; // Close the modal
                addNovelForm.reset(); // Reset the form
                alert(data.message); // Display success message
                fetchAndDisplayNovels(); // Refresh the novel list
            } else {
                errorMessage.innerText = data.message; // Show error message
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.innerText = 'An error occurred. Please try again.';
            window.location.href = '../login.html'; // Redirect to login page
            return;
        });
    });

    // Fetch and display the user's novels
    function fetchAndDisplayNovels() {
        const novelsList = document.getElementById('novelsList');
        fetch(API_CONFIG.get_novels(), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json(); // Parse JSON response
        })
        .then(data => {
            if (data.success) {
                const novels = data.data;
                novelsList.innerHTML = ''; // Clear the list

                novels.forEach(novel => {
                    const novelItem = document.createElement('div');

                    // Generate the appropriate HTML based on the novel type
                    if (novel.type === "short_story") {
                        novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p><a href="${novel.file_path}">Read the story</a></p>
                        </div>`;
                    } else if (novel.type === "full_novel") {
                        novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p><a href="${novel.file_path}">Download the story</a></p>
                        </div>`;
                    } else {
                        novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p>Invalid novel type</p>
                        </div>`;
                    }
                    novelsList.appendChild(novelItem);
                });
            } else {
                console.error('Error:', data.message);
                alert(data.message); // Display error message
            }
        })
        .catch(error => {
            console.error('Error fetching novels:', error);
            alert("An error occurred. Please try again later.");
            window.location.href = '../login.html'; // Redirect to login page
            return;
        });
    }

    fetchAndDisplayNovels(); // Fetch and display novels

    // Populate the genre select dropdown
    const genresEnum = [
        "Fantasy", "Science Fiction", "Romance", "Mystery", "Horror",
        "Thriller", "Historical", "Non-Fiction", "Young Adult", "Adventure"
    ];

    function populateGenreSelect() {
        const genreSelect = document.getElementById('novelGenre');
        if (!genreSelect) {
            console.error("Genre select element not found!");
            return;
        }
        genreSelect.innerHTML = '<option value="">Select Genre</option>'; // Clear options
        genresEnum.forEach(genre => {
            const option = document.createElement('option');
            option.value = genre.toLowerCase().replace(/\s+/g, '_');
            option.textContent = genre;
            genreSelect.appendChild(option);
        });
    }
    populateGenreSelect();

    // Validate the form for genre selection
    addNovelForm.addEventListener('submit', function (event) {
        const selectedGenre = document.getElementById('novelGenre').value;
        if (selectedGenre === "") {
            event.preventDefault();
            errorMessage.innerText = "Please select a genre.";
            return;
        }
        const isValidGenre = genresEnum.some(genre =>
            genre.toLowerCase().replace(/\s+/g, '_') === selectedGenre
        );
        if (!isValidGenre) {
            event.preventDefault();
            errorMessage.innerText = "Invalid genre selected.";
        }
    });

    // -------------------------------------------------------------------
    // Right box: Search for "other novels"
    // -------------------------------------------------------------------
    const searchTermInput = document.getElementById('searchTerm');
    const selectGenreSearch = document.getElementById('selectGenre');
    const searchButton = document.getElementById('searchButton');
    const otherNovelsList = document.getElementById('otherNovelsList');

    let allOtherNovels = []; // Global array to store other novels

    // Populate the genre dropdown in the right box
    function populateRightBoxGenres() {
        if (!selectGenreSearch) {
            console.error("selectGenre element not found!");
            return;
        }
        // Keep the first option ("All Genres") and remove others
        while (selectGenreSearch.options.length > 1) {
            selectGenreSearch.remove(1);
        }
        genresEnum.forEach(genre => {
            const option = document.createElement('option');
            option.value = genre.toLowerCase().replace(/\s+/g, '_');
            option.textContent = genre;
            selectGenreSearch.appendChild(option);
        });
    }
    populateRightBoxGenres();

    // Fetch other authors' novels
    function fetchAllOtherNovels() {
        fetch(API_CONFIG.get_other_novels(), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status} - ${text}`);
                });
            }
            return response.json(); // Parse JSON response
        })
        .then(data => {
            if (data.success) {
                allOtherNovels = data.data;
                renderOtherNovels(allOtherNovels); // Render the novels
            } else {
                console.error('Error:', data.message);
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching other novels:', error);
            alert("An error occurred (other novels). Please try again later.");
        });
    }

    // Render the other novels in the right box
    function renderOtherNovels(novelsArray) {
        otherNovelsList.innerHTML = '';
        if (!novelsArray || novelsArray.length === 0) {
            otherNovelsList.innerHTML = '<p>No results found.</p>';
            return;
        }
        novelsArray.forEach(novel => {
            const relativeFilePath = novel.file_path.replace('/var/www/html/', '');
            const novelDiv = document.createElement('div');
            novelDiv.innerHTML = `
                <div class="novel-item">
                    <p><b>Title</b>: ${novel.title}</p>
                    <p><b>Genre</b>: ${novel.genre}</p>
                    <p><b>Author</b>: ${novel.author}</p>
                    <p><a href="${relativeFilePath}" ${novel.type === "full_novel" ? `download="${novel.title}.pdf"` : ""}>
                        ${novel.type === "full_novel" ? "Download" : "Read"} the story
                    </a></p>
                </div>`;
            otherNovelsList.appendChild(novelDiv);
        });
    }

    // Filter novels based on search criteria
    function filterOtherNovels() {
        const searchTerm = searchTermInput.value.toLowerCase();
        const selectedGenre = selectGenreSearch.value;
        const filteredNovels = allOtherNovels.filter(novel => {
            const matchesTitle = novel.title.toLowerCase().includes(searchTerm);
            const matchesGenre = selectedGenre === "" || novel.genre === selectedGenre;
            return matchesTitle && matchesGenre;
        });
        renderOtherNovels(filteredNovels);
    }

    // Attach event listeners for searching novels
    searchButton.addEventListener('click', filterOtherNovels);
    searchTermInput.addEventListener('input', filterOtherNovels);
    selectGenreSearch.addEventListener('change', filterOtherNovels);

    // Fetch other authors' novels on load
    fetchAllOtherNovels();
});
