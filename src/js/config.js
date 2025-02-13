// config.js
const API_CONFIG = {
    BASE_URL: '../php/api/',
    userDashboard() {
        return `${this.BASE_URL}user_dashboard.php`;
    },
    logout() {
        return `${this.BASE_URL}logout.php`;
    },
    logoutAdmin() {
        return `${this.BASE_URL}admin_logout.php`;
    },
    adminChangePassword() {
        return `${this.BASE_URL}admin_change_password.php`;
    },  
    login() {
        return `${this.BASE_URL}login.php`;
    },
    register() {
        return `${this.BASE_URL}register.php`;
    },
    add_novel() {
        return `${this.BASE_URL}add_novel.php`;
    },
    get_novels() {
        return `${this.BASE_URL}get_novels.php`;
    },
    confirm() {
        return `${this.BASE_URL}confirm.php`;
    },
    recover_password() {
        return `${this.BASE_URL}password_recover.php`;
    },
    reset_password() {
        return `${this.BASE_URL}reset_password.php`;
    },
    get_other_novels() {
        return `${this.BASE_URL}get_other_novels.php`;
    },
    adminLogin() {
        return `${this.BASE_URL}admin_login.php`;
    },
    adminDashboard() {
        return `${this.BASE_URL}admin_dashboard.php`;
    }

};

export default API_CONFIG;
