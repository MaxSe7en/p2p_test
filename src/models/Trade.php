<?php
namespace App\Models;

use App\Helpers\DB;

class Trade
{
    public static function all()
    {
        return DB::selectAll("
            SELECT 
                t.id, t.asset, t.amount, t.status,
                s.username AS seller_name,
                b.username AS buyer_name
            FROM trades t
            JOIN users s ON t.seller_id = s.id
            LEFT JOIN users b ON t.buyer_id = b.id
            ORDER BY t.id DESC
        ");
    }

    public static function find($id)
    {
        return DB::select("
            SELECT 
                t.id, t.asset, t.amount, t.status,
                s.username AS seller_name,
                b.username AS buyer_name
            FROM trades t
            JOIN users s ON t.seller_id = s.id
            LEFT JOIN users b ON t.buyer_id = b.id
            WHERE t.id = ?
        ", [$id]);
    }

    public static function create($seller_id, $asset, $amount)
    {
        return DB::insert(
            "INSERT INTO trades (seller_id, asset, amount) VALUES (?, ?, ?)",
            [$seller_id, $asset, $amount]
        );
    }

    public static function buy($tradeId, $buyerId)
    {
        return DB::update(
            "UPDATE trades SET buyer_id=?, status='in_progress' WHERE id=? AND status='open'",
            [$buyerId, $tradeId]
        );
    }

    public static function release($tradeId, $sellerId)
    {
        // Only allow release if seller owns the trade and it's in progress
        return DB::update(
            "UPDATE trades SET status='completed' 
         WHERE id=? AND seller_id=? AND status='in_progress'",
            [$tradeId, $sellerId]
        );
    }

    public static function cancel($tradeId, $userId)
    {
        // Allow cancel if user is seller OR buyer, and status is open or in_progress
        return DB::update(
            "UPDATE trades SET status='cancelled' 
         WHERE id=? 
         AND (seller_id=? OR buyer_id=?) 
         AND status IN ('open','in_progress')",
            [$tradeId, $userId, $userId]
        );
    }


    public static function updateStatus($id, $status)
    {
        return DB::update(
            "UPDATE trades SET status=? WHERE id=?",
            [$status, $id]
        );
    }

    public static function delete($id)
    {
        return DB::delete("DELETE FROM trades WHERE id=?", [$id]);
    }
}
