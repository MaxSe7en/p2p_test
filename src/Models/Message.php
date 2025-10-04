<?php
namespace App\Models;

use App\Helpers\DB;

class Message {
    public static function create($tradeId, $senderId, $message) {
        return DB::insert(
            "INSERT INTO messages (trade_id, sender_id, message) VALUES (?, ?, ?)",
            [$tradeId, $senderId, $message]
        );
    }

    public static function getByTrade($tradeId) {
        return DB::selectAll("
            SELECT m.id, m.message, m.created_at, u.username AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.trade_id = ?
            ORDER BY m.created_at ASC
        ", [$tradeId]);
    }
}
