<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); 
    }

    $local = 'localhost';
    $user  = 'root';
    $pass  = '';
    $db    = 'grocery_mart_db'; 
    $port = 3306;
    
    $conn = new mysqli($local, $user, $pass, $db, $port);

    
    if($conn->connect_error){
        die("Kết nối CSDL thất bại: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4"); 
?>