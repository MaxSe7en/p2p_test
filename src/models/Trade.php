<?php
namespace App\Models;

use App\Helpers\DB;
use Exception;

class Trade
{
    public static function all(): array
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

    public static function find($id): mixed
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

    // public static function create($seller_id, $asset, $amount)
    // {
    //     return DB::insert(
    //         "INSERT INTO trades (seller_id, asset, amount) VALUES (?, ?, ?)",
    //         [$seller_id, $asset, $amount]
    //     );
    // }
    public static function create($seller_id, $asset, $amount): bool|string
    {
        $db = DB::connect();

        // Check if seller has enough balance
        $wallet = DB::select(
            "SELECT * FROM wallets WHERE user_id = ? AND asset = ?",
            [$seller_id, $asset]
        );

        if (!$wallet || $wallet['balance'] < $amount) {
            throw new Exception("Insufficient balance");
        }

        // Begin transaction
        $db->beginTransaction();
        try {
            // Lock the funds
            DB::update(
                "UPDATE wallets SET balance = balance - ?, locked_balance = locked_balance + ? 
             WHERE user_id = ? AND asset = ?",
                [$amount, $amount, $seller_id, $asset]
            );

            // Create trade
            $tradeId = DB::insert(
                "INSERT INTO trades (seller_id, asset, amount) VALUES (?, ?, ?)",
                [$seller_id, $asset, $amount]
            );

            // Log transaction
            DB::insert(
                "INSERT INTO transactions (user_id, asset, amount, type, trade_id) 
             VALUES (?, ?, ?, 'lock', ?)",
                [$seller_id, $asset, $amount, $tradeId]
            );

            $db->commit();
            return $tradeId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function buy($tradeId, $buyerId): bool
    {
        return DB::update(
            "UPDATE trades SET buyer_id=?, status='in_progress' WHERE id=? AND status='open'",
            [$buyerId, $tradeId]
        );
    }

    // public static function release($tradeId, $sellerId)
    // {
    //     // Only allow release if seller owns the trade and it's in progress
    //     return DB::update(
    //         "UPDATE trades SET status='completed' 
    //      WHERE id=? AND seller_id=? AND status='in_progress'",
    //         [$tradeId, $sellerId]
    //     );
    // }

    public static function release($tradeId, $sellerId): bool
    {
        $trade = self::find($tradeId);

        if (!$trade || $trade['seller_id'] != $sellerId || $trade['status'] != 'in_progress') {
            return false;
        }

        $db = DB::connect();
        $db->beginTransaction();
        try {
            // Unlock seller's funds
            DB::update(
                "UPDATE wallets SET locked_balance = locked_balance - ? 
             WHERE user_id = ? AND asset = ?",
                [$trade['amount'], $sellerId, $trade['asset']]
            );

            // Give to buyer
            DB::update(
                "UPDATE wallets SET balance = balance + ? 
             WHERE user_id = ? AND asset = ?",
                [$trade['amount'], $trade['buyer_id'], $trade['asset']]
            );

            // Update trade status
            DB::update("UPDATE trades SET status='completed' WHERE id=?", [$tradeId]);

            // Log transactions
            DB::insert(
                "INSERT INTO transactions (user_id, asset, amount, type, trade_id) 
             VALUES (?, ?, ?, 'release', ?)",
                [$sellerId, $trade['asset'], $trade['amount'], $tradeId]
            );

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function cancel($tradeId, $userId): array
    {
        $db = DB::connect();
        $trade = DB::select("SELECT * FROM trades WHERE id = ?", [$tradeId]);

        if (!$trade) {
            return [
                "success" => false,
                "message" => "Trade not found",
                "data" => null
            ];
        }

        if (!in_array($trade['status'], ['open', 'in_progress'])) {
            return [
                "success" => false,
                "message" => "Trade cannot be cancelled in its current state",
                "data" => null
            ];
        }

        if ($trade['seller_id'] != $userId && $trade['buyer_id'] != $userId) {
            return [
                "success" => false,
                "message" => "You are not authorized to cancel this trade",
                "data" => null
            ];
        }

        $db->beginTransaction();
        try {
            // Update trade status
            DB::update(
                "UPDATE trades SET status='cancelled' WHERE id=?",
                [$tradeId]
            );

            // If seller is cancelling, unlock funds
            if ($trade['seller_id'] == $userId) {
                DB::update(
                    "UPDATE wallets SET balance = balance + ?, locked_balance = locked_balance - ? 
                 WHERE user_id=? AND asset=?",
                    [$trade['amount'], $trade['amount'], $userId, $trade['asset']]
                );

                // Log unlock transaction
                DB::insert(
                    "INSERT INTO transactions (user_id, asset, amount, type, trade_id) 
                 VALUES (?, ?, ?, 'unlock', ?)",
                    [$userId, $trade['asset'], $trade['amount'], $tradeId]
                );
            }

            $db->commit();
            return [
                "success" => true,
                "message" => "Trade cancelled successfully",
                "data" => ["trade_id" => $tradeId, "cancelled_by" => $userId]
            ];
        } catch (Exception $e) {
            $db->rollBack();
            return [
                "success" => false,
                "message" => $e->getMessage(),
                "data" => null
            ];
        }
    }



    public static function updateStatus($id, $status): bool
    {
        return DB::update(
            "UPDATE trades SET status=? WHERE id=?",
            [$status, $id]
        );
    }

    public static function delete($id): bool
    {
        return DB::delete("DELETE FROM trades WHERE id=?", [$id]);
    }
}
