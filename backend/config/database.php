<?php
// Database configuration for BlockHaus
class Database {
    private $host = 'localhost';
    private $db_name = 'blocaavc_db';
    private $username = 'blocaavc_db';
    private $password = 'PRD8ySw.L3&w';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
