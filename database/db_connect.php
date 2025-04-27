<?php
// Define the root path to make includes work from any directory
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Include configuration file
require_once ROOT_PATH . 'config/config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $conn;
    private $error;

    public function __construct() {
        // Set DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;

        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // Create PDO instance
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Connection Error: ' . $this->error;
        }
    }

    // Get connection
    public function getConnection() {
        return $this->conn;
    }

    // Execute query
    public function query($query) {
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    // Execute statement
    public function execute($stmt, $params = []) {
        return $stmt->execute($params);
    }

    // Get result set as array of objects
    public function resultSet($stmt, $params = []) {
        $this->execute($stmt, $params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Get single record as object
    public function single($stmt, $params = []) {
        $this->execute($stmt, $params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Get row count
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
