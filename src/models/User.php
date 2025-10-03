<?php
namespace App\Models;

use App\Helpers\DB;

class User {
    public static function create($username, $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        return DB::insert(
            "INSERT INTO users (username, password) VALUES (?, ?)",
            [$username, $hashed]
        );
    }

    public static function findByUsername($username) {
        return DB::select("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function verifyLogin($username, $password) {
        $user = self::findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
