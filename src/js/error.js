document.addEventListener("DOMContentLoaded", function () {
    const errorMessage = new URLSearchParams(window.location.search).get('error');
    if (errorMessage) {
        document.getElementById('error-message').textContent = decodeURIComponent(errorMessage);
    }
});
