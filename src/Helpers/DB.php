<?php
namespace App\Helpers;

use App\Exceptions\Console;
use PDO;

class DB
{
    private static $pdo;

    public static function connect()
    {
        if (!self::$pdo) {
            $config = require __DIR__ . '/../Config/config.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true, // persistent connection for long-running processes
            ];
            self::$pdo = new PDO($dsn, $config['user'], $config['password'], $options);

        }
        return self::$pdo;
    }

    public static function select(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function selectAll(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        // Console::log("Executing SQL: $sql with params: " . json_encode($params));
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return self::connect()->lastInsertId();
    }

    public static function update(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        return $stmt->execute($params);
    }
}
