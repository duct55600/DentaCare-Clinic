<?php
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'dental_clinic';
    $username = 'root';
    $password = '193225'; 
    $port = 3306;

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Kết nối CSDL thất bại: " . $e->getMessage());
    }
}

$pdo = getDbConnection();
?>