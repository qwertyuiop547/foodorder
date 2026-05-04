<?php
function redirect($path) {
    header("Location: $path");
    exit;
}

function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

function requireLogIn(){
    if(!isLoggedIn()){
        redirect('login.php');
    }
}

function requireAdmin(){
    if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
        redirect('../login.php');
        exit;
    }
}

function hasRole($role){
    if(!isset($_SESSION['role'])){
        return false;
    }

    if(is_array($role)){
        return in_array($_SESSION['role'], $role, true);
    }

    return $_SESSION['role'] === $role;
}

function requiredRole($role, $redirectPath = 'login.php'){
    if(!hasRole($role)){
        redirect($redirectPath);
    }
}

function post($key, $default = null){
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function postInt($key, $default = null){
    return isset($_POST[$key]) ? (int) trim($_POST[$key]) : $default;
}

function setFlash($message, $type = 'success'){
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
}

function getFlash(){
    if(!isset($_SESSION['flash'])){
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function handleErrors($errors, $redirectPath) {
        if(!empty($errors)){
            foreach ($errors as $error){
                setFlash($error, 'error');
            }
            redirect($redirectPath);
        }
}

function countTable($conn, $table){
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row['count'] ?? 0);
}

function getAll($conn, $sql){
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

function totalRevenue($conn, $table){
    $sql = "SELECT SUM(total_amount) AS total_revenue from $table";
    $result = mysqli_query($conn, $sql);

    $row = mysqli_fetch_assoc($result);

    return $row['total_revenue'] ?? 0;
}

function e($value){
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function countFoodItems($conn, $condition = ''){
    $sql = "SELECT COUNT(*) as count FROM food_items" . ($condition ? " WHERE $condition" : "");
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row['count'] ?? 0);
}
?>
