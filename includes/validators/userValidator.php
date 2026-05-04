<?php
    function validateUser($username, $email, $password){
        $errors = [];

        if(empty($username)){
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3){
            $errors[] = "Username must be at least 3 characters";
        }

        if (empty($email)) {
        $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
        $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        return $errors;
    }
?>