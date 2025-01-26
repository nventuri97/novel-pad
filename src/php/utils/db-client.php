<?php
class db_client {
    public static function get_connection($db_name) {
        $user = "admin";
        $pass = "admin";
        $host = "mysql";
        $conn = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }
}
?>
