<?php
namespace App\Models;

use App\Exceptions\Console;
use App\Helpers\DB;
use Exception;

class User
{
    public static function create($username, $password): bool|string
    {
        // Basic validations
        Console::log2("creating user: ", $username);
        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }
        Console::log2("creating user: ", $username);

        // Check if username already exists
        $existing = DB::select("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            throw new Exception("Username already taken.");
        }

        // Hash password
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $db = DB::connect();
        $db->beginTransaction();
        try {
            // Insert user
            $userId = DB::insert(
                "INSERT INTO users (username, password) VALUES (?, ?)",
                [$username, $hashed]
            );

            $defaultAssets = [
                "USD" => 100000.00,
                "BTC" => 100,
                "ETH" => 500,
                "USDT" => 100000.00,
                "BNB" => 100,
                "SOL" => 100,
                "ADA" => 1000,
                "XRP" => 1000,
                "DOT" => 500,
                "DOGE" => 10000,
                "LTC" => 100,
            ];

            foreach ($defaultAssets as $asset => $amount) {
                DB::insert(
                    "INSERT INTO wallets (user_id, asset, balance, locked_balance) VALUES (?, ?, ?, 0)",
                    [$userId, $asset, $amount]
                );

                // Log initial deposit transaction
                DB::insert(
                    "INSERT INTO transactions (user_id, asset, amount, type) VALUES (?, ?, ?, 'deposit')",
                    [$userId, $asset, $amount]
                );
            }

            $db->commit();
            return $userId;
        } catch (Exception $e) {
            $db->rollBack();
            Console::log2("Error creating user: ", $e->getMessage());
            throw $e;
        }
    }

    public static function findByUsername($username)
    {
        $user = DB::select("SELECT * FROM users WHERE username = ?", [$username]);
        if (!$user) {
            return null;
        }
        Console::log2("User found: ", $user['id']);
        $wallets = DB::selectAll(
            "SELECT asset, balance, locked_balance FROM wallets WHERE user_id = ?",
            [$user['id']]
        );

        $user['wallets'] = $wallets ?: [];
        return $user;
    }

    public static function verifyLogin($username, $password)
    {
        $user = self::findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}

