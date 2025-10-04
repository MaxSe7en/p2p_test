<?php
namespace App\Controllers;

use App\Exceptions\Console;
use App\Models\User;
use Exception;

class UserController {
    public static function register($data) {
        try {
            $id = User::create($data['username'], $data['password']);
            Console::log2("User registered successfully: ", $id);
            return [
                "success" => true,
                "message" => "User registered successfully",
                "data" => ["id" => $id]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
                "data" => null
            ];
        }
    }

    public static function login($data) {
        try {
            $user = User::verifyLogin($data['username'], $data['password']);
            if ($user) {
                return [
                    "success" => true,
                    "message" => "Login successful",
                    "data" => $user
                ];
            }
            return [
                "success" => false,
                "message" => "Invalid credentials",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
                "data" => null
            ];
        }
    }
}

