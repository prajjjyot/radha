<?php
session_start();
include '../config/functions.php';

$name = $_POST['name'] ?? '';
$queryType = $_POST['query_type'] ?? '';
$avatar = $_POST['avatar'] ?? 'avatar1.png'; // default fallback

// You can store it in DB or just in session for now
$_SESSION['name'] = $name;
$_SESSION['avatar'] = $avatar;

// Optional: update DB if you store it there too
// $stmt = $conn->prepare("UPDATE users SET name=?, query_type=?, avatar=? WHERE email=?");
// $stmt->bind_param("ssss", $name, $queryType, $avatar, $_SESSION['logged_in_email']);
// $stmt->execute();
// $stmt->close();
header("Location: ../index.php");
    exit();

?>