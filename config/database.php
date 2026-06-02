<?php
class Database {
    private $host = "localhost";
    private $username = "mabw6842_maba";
    private $password = "K@8ksMntySxDvWD";
    private $db_name = "mabw6842_mabastore";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                die("Koneksi gagal: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

// Inisialisasi Session Global untuk fitur Cart
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>