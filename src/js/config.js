// config.js
const API_CONFIG = {
    BASE_URL: '../api/',
    userDashboard() {
        return `${this.BASE_URL}user_dashboard.php`;
    },
    logout() {
        return `${this.BASE_URL}logout.php`;
    },
    login() {
        return `${this.BASE_URL}login.php`;
    },
    register() {
        return `${this.BASE_URL}register.php`;
    },
};

export default API_CONFIG;
