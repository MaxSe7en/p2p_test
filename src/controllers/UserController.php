<?php
namespace App\Controllers;

use App\Models\User;

class UserController {
    public static function register($data) {
        $id = User::create($data['username'], $data['password']);
        return ["message" => "User registered successfully", "id" => $id];
    }

    public static function login($data) {
        $user = User::verifyLogin($data['username'], $data['password']);
        if ($user) {
            // For test simplicity, return user data (no JWT/session here)
            return ["message" => "Login successful", "user" => $user];
        }
        return ["error" => "Invalid credentials"];
    }
}
