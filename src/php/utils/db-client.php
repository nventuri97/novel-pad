<?php
openlog("add_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

class db_client {
    public static function get_connection($db_name) {
        $config = include 'config.php';

        $user = $config['db_user'];
        $pass = $config['db_password'];
        $host = $config['db_host'];
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Connecting to database: $db_name");
        $conn = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }
}
?>
