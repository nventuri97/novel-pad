import API_CONFIG from "./config.js";

document.getElementById('adminLoginForm').addEventListener('submit', function(event) {
    // Impedisci il comportamento predefinito del form (submit tradizionale)
    event.preventDefault();

    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';

    // Recupera i valori dai campi del form
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const recaptcharesponse = grecaptcha.getResponse();

    // Verifica che il reCAPTCHA sia stato completato
    if (!recaptcharesponse) {
        errorMessage.textContent = "Please complete the reCAPTCHA";
        errorMessage.style.display = 'block';
        return;
    }

    // Validazione dei campi obbligatori
    if (email === '' || password === '') {
        errorMessage.textContent = "Both fields are required.";
        errorMessage.style.display = 'block';
        return;
    }

    // Verifica del formato dell'email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorMessage.textContent = "Please enter a valid email address.";
        errorMessage.style.display = 'block';
        document.getElementById('email').focus();
        return;
    }

    // Effettua la richiesta all'endpoint del login per gli admin
    fetch(API_CONFIG.adminLogin(), {
      method: 'POST',
      body: new URLSearchParams({
        email: email,
        password: password,
        recaptcharesponse: recaptcharesponse
      }),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      }
    })
    .then(response => response.text())
    .then(text => {
      console.log("Raw response:", text);
      // Prova a fare il parse manuale
      try {
        const data = JSON.parse(text);
        console.log("Parsed JSON:", data);
      } catch (err) {
        console.error("JSON parse error:", err);
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
    });
});
