<?php
    // Declare settings
    $host = 'localhost';
    $username = 'root';
    $password = 'Re367@mnh8422007';
    $database = 'tracker_db';

    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die('Connection failed: '. $conn->connect_error);
    } 
    // else {
    //     echo "Connected Successfully";
    // }
?>