import API_CONFIG from "./config.js";

document.addEventListener('DOMContentLoaded', () => {
  const usersTableBody = document.querySelector('#usersTable tbody');
  const logoutButton   = document.getElementById('logoutButton');
  const adminEmailElem = document.getElementById('adminEmail'); 

  const userIcon = document.getElementById("userIcon");
  const dropdownMenu = document.getElementById("dropdownMenu");
  const changePassword = document.getElementById("changePassword");

  // Toggle dropdown menu on user icon click
  userIcon.addEventListener("click", () => {
    dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
  });

  // Close dropdown if clicked outside
  document.addEventListener("click", (event) => {
      if (!userIcon.contains(event.target) && !dropdownMenu.contains(event.target)) {
          dropdownMenu.style.display = "none";
      }
  });

  // Change password action
  changePassword.addEventListener("click", () => {
      window.location.href = "admin_change_password.html"; // Redirect to change password page
  });

  function handleError(error, userMessage = "An unexpected error occurred.") {
    console.error('Error:', error);
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) {
      errorMessage.innerText = userMessage;
      errorMessage.style.display = 'block';
    } else {
      alert(userMessage);
    }
  }

  fetch(API_CONFIG.adminDashboard(), {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  })
  .then(response => {
    if (response.status === 405) {
        window.location.href = "/error.html?error=Method%20not%20allowed";
        return;
    }
    else if (response.status === 401) {
        window.location.href = "/error.html?error=Unauthorized";
        return;
    }
    else if (response.status === 403) {
        window.location.href = "/error.html?error=Forbidden";
        return;
    }
    else if (response.status === 419) {
        window.location.href = "/error.html?error=Session%20expired";
        return;
    }
    else if (response.status === 500) {
        handleError("Internal server error. Please try again later.", "Internal server error. Please try again later.");
        return;
    }

    if (!response.ok) {
        return response.text().then(text => {
            throw new Error(`HTTP error! status: ${response.status} - ${text}`);
        });
    }
    return response.json();
  })
  .then(data => {
    // Set the admin email on the page
    if (data.adminEmail) {
      adminEmailElem.textContent = data.adminEmail;
    }
    else {
      handleError(data.message, data.message);
    }

    // data.users is an array of { email, nickname, is_premium }
    renderUsersTable(data.users);
  })
  .catch(error => {
    handleError("An error occurred. Please try again later.", "An error occurred. Please try again later.");
    return;
  });

  //users table
  function renderUsersTable(users) {
    usersTableBody.innerHTML = '';
    users.forEach(user => {
      const row = document.createElement('tr');

      const premiumText = user.is_premium ? 'Premium' : 'Standard';
      const toggleLabel = user.is_premium ? 'Set Standard' : 'Set Premium';
      
      row.innerHTML = `
        <td>${user.email}</td>
        <td>${user.nickname}</td>
        <td>${premiumText}</td>
        <td><button class="toggle-btn">${toggleLabel}</button></td>
      `;

      const toggleBtn = row.querySelector('.toggle-btn');
      toggleBtn.addEventListener('click', () => togglePremium(user, row));

      usersTableBody.appendChild(row);
    });
  }

  // Function to toggle is_premium status
  function togglePremium(user, rowElement) {
    const newStatus = !user.is_premium;
    fetch(API_CONFIG.adminDashboard(), {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        nickname: user.nickname,
        newStatus: newStatus
      })
    })
    .then(response => {
      if (response.status === 405) {
          window.location.href = "/error.html?error=Method%20not%20allowed";
          return;
      }
      else if (response.status === 401) {
          window.location.href = "/error.html?error=Unauthorized";
          return;
      }
      else if (response.status === 403) {
          window.location.href = "/error.html?error=Forbidden";
          return;
      }
      else if (response.status === 419) {
          window.location.href = "/error.html?error=Session%20expired";
          return;
      }
      else if (response.status === 500) {
          handleError("Internal server error. Please try again later.", "Internal server error. Please try again later.");
          return;
      }

      if (!response.ok) {
          return response.text().then(text => {
              throw new Error(`HTTP error! status: ${response.status} - ${text}`);
          });
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Update the status locally
        user.is_premium = newStatus;
        rowElement.cells[2].textContent = user.is_premium ? 'Premium' : 'Standard';
        rowElement.querySelector('.toggle-btn').textContent = user.is_premium ? 'Set Standard' : 'Set Premium';
      } else {
        handleError(data.message, data.message)
      }
    })
    .catch(error => {
      handleError(error, "An error occurred. Please try again later.");
      return;
    });
  }

  // Handle logout
  logoutButton.addEventListener('click', function (event) {
    event.preventDefault();
    fetch(API_CONFIG.adminLogout(), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
    })
    .then(response => {
      if (response.status === 405) {
          window.location.href = "/error.html?error=Method%20not%20allowed";
          return;
      }
      else if (response.status === 401) {
          window.location.href = "/error.html?error=Unauthorized";
          return;
      }
      else if (response.status === 403) {
          window.location.href = "/error.html?error=Forbidden";
          return;
      }
      else if (response.status === 419) {
          window.location.href = "/error.html?error=Session%20expired";
          return;
      }
      else if (response.status === 500) {
          handleError("Internal server error. Please try again later.", "Internal server error. Please try again later.");
          return;
      }

      if (!response.ok) {
          return response.text().then(text => {
              throw new Error(`HTTP error! status: ${response.status} - ${text}`);
          });
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        window.location.href = 'admin_login.html';
      } else {
        handleError(data.message, data.message);
      }
    })
    .catch(err => {
      handleError(error, "An error occurred. Please try again later.");
      return;
    });
  });

});
