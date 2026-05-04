<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config/app.php';

function register($name, $email, $password){
    global $conn;

    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){
        return false;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, role)
        VALUES (?, ?, ?, 'customer')
    ");

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    return $stmt->execute();
}

function login($username, $password){
    global $conn;

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE name = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    // ssssssesssionnnnn set
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];

    return true;
}

function logout(){
    session_destroy();
    session_unset();

    header('Location: ../public/login.php');
    exit;
}
?>
