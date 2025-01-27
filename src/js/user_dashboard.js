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

        // Handle the novel type select element
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
</head>
<body>
    <h1>${title}</h1>
    <p>Genre: ${genre}</p>
    <div>${storyContent}</div>
</body>
</html>`;

            const htmlFile = new Blob([htmlContent], { type: "text/html" });
            formData.append("file", htmlFile, `${title.replace(/\s+/g, "_")}.html`);
        }

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
                    
                    if (novel.type === "short_story") {
                        novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p><a href="${relativeFilePath}">Read the story</a></p>
                        </div>`;
                    } else if (novel.type === "full_novel") {
                        novelItem.innerHTML = `
                        <div class="novel-item">
                            <p><b>Title</b>: ${novel.title}</p>
                            <p><b>Genre</b>: ${novel.genre}</p>
                            <p><a href="${relativeFilePath}" download="${novel.title}.pdf">Download the story</a></p>
                        </div>`;
                    }
                    else {
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
                alert(data.message); // Show error message
            }
        })
        .catch(error => {
            console.error('Error fetching novels:', error);
            alert("An error occurred. Please try again later.");
        });
    }

    fetchAndDisplayNovels(); // Fetch and display novels

    // 5. POPOLAMENTO "GENRE" NEL FORM "ADD NOVEL"
    const genresEnum = [
        "Fantasy",
        "Science Fiction",
        "Romance",
        "Mystery",
        "Horror",
        "Thriller",
        "Historical",
        "Non-Fiction",
        "Young Adult",
        "Adventure"
        // Aggiungi altri generi se necessario
    ];

    function populateGenreSelect() {
        const genreSelect = document.getElementById('novelGenre');
        if (!genreSelect) {
            console.error(">>> Genre select element not found!");
            return;
        }

        // Rimuovi tutte le opzioni esistenti, tranne quella di default
        genreSelect.innerHTML = '<option value="">Select Genre</option>';

        // Aggiungi le opzioni dall'enum
        genresEnum.forEach(genre => {
            const option = document.createElement('option');
            option.value = genre.toLowerCase().replace(/\s+/g, '_'); 
            option.textContent = genre;
            genreSelect.appendChild(option);
        });
    }
    populateGenreSelect();

    // Validazione del Form per il Genre
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
    // 6. BOX DI DESTRA: CERCARE "ALTRE NOVEL"
    // -------------------------------------------------------------------
    // Riferimenti al box di destra
    const searchTermInput = document.getElementById('searchTerm'); // <input id="searchTerm">
    const selectGenreSearch = document.getElementById('selectGenre'); // <select id="selectGenre">
    const searchButton = document.getElementById('searchButton'); // <button id="searchButton">
    const otherNovelsList = document.getElementById('otherNovelsList'); // <div id="otherNovelsList">

    // Array globale per memorizzare TUTTE le "altre" novel
    let allOtherNovels = [];

    // 6.1 Popola la select "selectGenre" (destra) con generiEnum, mantenendo "All Genres" come prima opzione
    function populateRightBoxGenres() {
        if (!selectGenreSearch) {
            console.error(">>> selectGenre element not found!");
            return;
        }

        // Mantieni la prima opzione ("All Genres") e rimuovi le altre
        while (selectGenreSearch.options.length > 1) {
            selectGenreSearch.remove(1);
        }
        genresEnum.forEach(genre => {
            const option = document.createElement('option');
            option.value = genre.toLowerCase().replace(/\s+/g, '_');
            option.textContent = genre;
            selectGenreSearch.appendChild(option);
        });
        console.log(">>> Right box 'Genre' select populated with predefined genres.");
    }
    // Popoliamo la select
    populateRightBoxGenres();

    // 6.2 Funzione per fetchare le novel di altri autori
    function fetchAllOtherNovels() {
        fetch(API_CONFIG.get_other_novels(), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
            if (!response.ok) {
                // Se non è ok, leggi il testo per capire l'errore
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status} - ${text}`);
                });
            }
            // Altrimenti, parse come JSON
            return response.json();
        })
        .then(data => {
            if (data.success) {
                allOtherNovels = data.data;
                renderOtherNovels(allOtherNovels);
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

    // 6.3 Funzione per mostrare la lista (colonna di destra)
    function renderOtherNovels(novelsArray) {
        otherNovelsList.innerHTML = '';

        if (!novelsArray || novelsArray.length === 0) {
            otherNovelsList.innerHTML = '<p>No results found.</p>';
            return;
        }

        novelsArray.forEach(novel => {
            const relativeFilePath = novel.file_path.replace('/var/www/html/', '');
            const novelDiv = document.createElement('div');
            novelDiv.classList.add('novel-item');
            
            if (novel.type === "short_story") {
                novelDiv.innerHTML = `
                <div class="novel-item">
                    <p><b>Title</b>: ${novel.title}</p>
                    <p><b>Author</b>: ${novel.author ?? 'No author'}</p>
                    <p><b>Genre</b>: ${novel.genre}</p>
                    <p><a href="${relativeFilePath}">Read the story</a></p>
                </div>`;
            } else if (novel.type === "full_novel") {
                novelDiv.innerHTML = `
                <div class="novel-item">
                    <p><b>Title</b>: ${novel.title}</p>
                    <p><b>Author</b>: ${novel.author ?? 'No author'}</p>
                    <p><b>Genre</b>: ${novel.genre}</p>
                    <p><a href="${relativeFilePath}" download="${novel.title}.pdf">Download the story</a></p>
                </div>`;
            }
            else {
                novelDiv.innerHTML = `
                <div class="novel-item">
                    <p><b>Title</b>: ${novel.title}</p>
                    <p><b>Author</b>: ${novel.author ?? 'No author'}</p>
                    <p><b>Genre</b>: ${novel.genre}</p>
                    <p>Invalid novel type</p>
                </div>`;
            }

            otherNovelsList.appendChild(novelDiv);
        });
    }

    // 6.4 Funzione che filtra in base a searchTerm e selectGenre
    function applySearchFilters() {
        const searchTerm = searchTermInput.value.toLowerCase().trim();
        const selectedGenre = selectGenreSearch.value; // e.g. "fantasy", "science_fiction", ecc.

        let filtered = [...allOtherNovels];

        // Filtro per title/author
        if (searchTerm) {
            filtered = filtered.filter(novel => {
                const titleMatches = novel.title 
                    ? novel.title.toLowerCase().includes(searchTerm)
                    : false;
                const authorMatches = novel.author
                    ? novel.author.toLowerCase().includes(searchTerm)
                    : false;
                return titleMatches || authorMatches;
            });
        }

        // Filtro per genere
        if (selectedGenre) {
            // Controlla che novel.genre corrisponda
            filtered = filtered.filter(novel => novel.genre === selectedGenre);
        }

        // Render finale
        renderOtherNovels(filtered);
    }

    // 6.5 Collega il pulsante "Search"
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            console.log(">>> Search button clicked.");
            applySearchFilters();
        });
    }

    // 6.6 Avvia la fetch delle "altre novel" all'avvio
    fetchAllOtherNovels();

}); // Fine DOMContentLoaded
