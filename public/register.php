<?php
session_start();

require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../includes/validators/userValidator.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = post('username');
    $email = post('email');
    $password = post('password');

    $errors = validateUser($username, $email, $password);

    if(!empty($errors)){
        foreach ($errors as $error){
            setFlash($error, "error");
        } 
            redirect("register.php");
        }

    if(register($username, $email, $password)){
        setFlash("Account created successfully!");
        redirect('login.php');
    } else { 
        setFlash("Email already exists", "error");
        redirect('register.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FoodPulse</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Join FoodPulse today</p>
            </div>
            
            <form action="register.php" method="post" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>

                <button type="submit" class="btn-auth">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>

            <?php include '../template/alerts.php'; ?>
        </div>
    </div>
</body>
</html>
