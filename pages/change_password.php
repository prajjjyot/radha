<?php
session_start();
include("../config/functions.php");

$db = new db_functions();

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Fetch current password from DB
    $conn = $db->getConnection();
    $query = "SELECT PASSWORD FROM qgen_registration WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_password);
    if (!$stmt->fetch()) {
        echo "<script>alert('User not found'); window.location.href='profile.php';</script>";
        exit;
    }
    $stmt->close();

    // 2. Verify old password
    if ($old_password !== $current_password) {
        echo "<script>alert('Old password is incorrect'); window.location.href='profile.php';</script>";
        exit;
    }

    // 3. Ensure new password != old password
    if ($old_password === $new_password) {
        echo "<script>alert('New password cannot be the same as old password'); window.location.href='profile.php';</script>";
        exit;
    }

    // 4. Ensure confirm password matches
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirm password do not match'); window.location.href='profile.php';</script>";
        exit;
    }

    // 5. Update password (plain text version)
    $update_query = "UPDATE qgen_registration SET PASSWORD = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_password, $user_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Password updated successfully'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Error updating password'); window.location.href='profile.php';</script>";
    }
    $update_stmt->close();
}
?>
