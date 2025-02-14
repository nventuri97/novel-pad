import API_CONFIG from "./config.js";

document.addEventListener('DOMContentLoaded', () => {
  const usersTableBody = document.querySelector('#usersTable tbody');
  const logoutButton   = document.getElementById('logoutButton');
  const adminEmailElem = document.getElementById('adminEmail'); 

  // New part
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
  //////////////////////////

  function showError(msg) {
    alert(msg);
  }

  //list of users
  function loadUsers() {
    fetch(API_CONFIG.adminDashboard(), {
      method: 'GET',
      credentials: 'include'
    })
    .then(resp => {
      if (!resp.ok) {
        throw new Error(`HTTP error! status: ${resp.status}`);
      }
      return resp.json();
    })
    .then(data => {
      if (!data.success) {
        showError(data.message || "Error loading users");
        return;
      }
      // Set the admin email on the page
      if (data.adminEmail) {
        adminEmailElem.textContent = data.adminEmail;
      }

      // data.users is an array of { email, nickname, is_premium }
      renderUsersTable(data.users);
    })
    .catch(err => {
      showError("Error fetching users: " + err);
    });
  }

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
    .then(resp => resp.json())
    .then(data => {
      if (data.success) {
        // Update the status locally
        user.is_premium = newStatus;
        rowElement.cells[2].textContent = user.is_premium ? 'Premium' : 'Standard';
        rowElement.querySelector('.toggle-btn').textContent = user.is_premium ? 'Set Standard' : 'Set Premium';
      } else {
        showError(data.message || "Error updating premium status");
      }
    })
    .catch(err => {
      showError("Error toggling premium: " + err);
    });
  }

  //  Logout
  if (logoutButton) {
    logoutButton.addEventListener('click', () => {
      fetch(API_CONFIG.logoutAdmin(), {
        method: 'POST',
        credentials: 'include'
      })
      .then(resp => resp.json())
      .then(data => {
        if (data.success) {
          window.location.href = 'admin_login.html';
        } else {
          showError("Error logging out");
        }
      })
      .catch(err => {
        showError("Error logging out: " + err);
      });
    });
  }

  // Initialize
  loadUsers();
});
