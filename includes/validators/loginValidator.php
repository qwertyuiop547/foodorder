<?php
    function validateLogin($username, $password){
        $errors = [];

        if(empty($username)){
            $errors[] = "Username is required";
        } elseif(strlen($username) < 3) {
            $errors[] = "Username must be atleast 3 characters";
        }

        if(empty($password)){
            $errors[] = "Password is required";
        }

        return $errors;
    }
?>