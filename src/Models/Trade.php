<?php
namespace App\Models;

use App\Exceptions\Console;
use App\Helpers\DB;
use Exception;

class Trade
{
    public static function all(): array
    {
        return DB::selectAll("
            SELECT 
                t.id, t.asset, t.amount, t.status,
                t.seller_id, t.buyer_id,
                s.username AS seller_name,
                b.username AS buyer_name
            FROM trades t
            JOIN users s ON t.seller_id = s.id
            LEFT JOIN users b ON t.buyer_id = b.id
            ORDER BY t.id DESC
        ");
    }

    public static function find($id): ?array
    {
        $trade = DB::select("
            SELECT 
                t.id, t.asset, t.amount, t.status,
                t.seller_id, t.buyer_id,
                s.username AS seller_name,
                b.username AS buyer_name
            FROM trades t
            JOIN users s ON t.seller_id = s.id
            LEFT JOIN users b ON t.buyer_id = b.id
            WHERE t.id = ?
        ", [$id]);

        return $trade ?: null;
    }

    public static function getUserChats($userId): array
    {
        $dTrades = DB::selectAll("
            SELECT id, seller_id, buyer_id, status 
            FROM trades 
            WHERE seller_id = ? OR buyer_id = ?
        ", [$userId, $userId]);

        Console::log2("Trades for user ======> $userId:", $dTrades);

        $dChats = DB::selectAll("
            SELECT trade_id, COUNT(*) as msg_count 
            FROM chat_messages 
            GROUP BY trade_id
        ");

        Console::log2("Chat messages per trade:========> ", $dChats);

        return DB::selectAll("
            SELECT 
                t.id, t.asset, t.amount, t.status,
                t.seller_id, t.buyer_id,
                s.username AS seller_name,
                b.username AS buyer_name,
                CASE 
                    WHEN t.seller_id = ? THEN COALESCE(b.username, 'No Buyer')
                    ELSE s.username
                END AS counterparty_name,
                (SELECT COUNT(*) FROM chat_messages WHERE trade_id = t.id) as message_count,
                (SELECT MAX(created_at) FROM chat_messages WHERE trade_id = t.id) as last_message_at
            FROM trades t
            JOIN users s ON t.seller_id = s.id
            LEFT JOIN users b ON t.buyer_id = b.id
            WHERE (t.seller_id = ? OR t.buyer_id = ?)
            AND EXISTS (SELECT 1 FROM chat_messages WHERE trade_id = t.id)
            ORDER BY last_message_at DESC, t.id DESC
        ", [$userId, $userId, $userId]);
    }

    public static function getTradeMessages($tradeId): array
    {
        return DB::selectAll("
            SELECT 
                cm.id,
                cm.sender_id,
                cm.message,
                cm.created_at,
                u.username as sender_name
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.trade_id = ?
            ORDER BY cm.created_at ASC
        ", [$tradeId]);
    }

    public static function create($seller_id, $asset, $amount): int
    {
        $db = DB::connect();

        // Check if seller has enough balance
        $wallet = DB::select(
            "SELECT * FROM wallets WHERE user_id = ? AND asset = ?",
            [$seller_id, $asset]
        );

        if (!$wallet) {
            Console::log("Wallet not found for user $seller_id and asset $asset");
            throw new Exception("Wallet not found");
        }

        if ($wallet['balance'] < $amount) {
            Console::log("Insufficient balance for user $seller_id and asset $asset");
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
            Console::log2("Error creating trade: ", $e->getMessage());
            throw new Exception("Failed to create trade: " . $e->getMessage());
        }
    }

    public static function buy($tradeId, $buyerId): bool
    {
        $trade = DB::select("SELECT * FROM trades WHERE id = ?", [$tradeId]);

        if (!$trade) {
            Console::log("Trade not found");
            throw new Exception("Trade not found");
        }

        if ($trade['status'] !== 'open') {
            Console::log("==========Trade is not available for purchase=========");
            throw new Exception("Trade is not available for purchase");
        }

        if ($trade['seller_id'] == $buyerId) {
            Console::log("You cannot buy your own trade");
            throw new Exception("You cannot buy your own trade");
        }

        $result = DB::update(
            "UPDATE trades SET buyer_id=?, status='in_progress' WHERE id=? AND status='open'",
            [$buyerId, $tradeId]
        );

        if (!$result) {
            Console::log("Failed to update trade");
            throw new Exception("Failed to update trade");
        }

        return true;
    }

    public static function release($tradeId, $sellerId): bool
    {
        $trade = DB::select("SELECT * FROM trades WHERE id = ?", [$tradeId]);

        if (!$trade) {
            Console::log("Trade not found");
            throw new Exception("Trade not found");
        }

        if ($trade['seller_id'] != $sellerId) {
            Console::log("You are not authorized to release this trade");
            throw new Exception("You are not authorized to release this trade");
        }

        if ($trade['status'] != 'in_progress') {
            Console::log("Trade cannot be released in its current state");
            throw new Exception("Trade cannot be released in its current state");
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
            Console::log2("Error releasing trade: ", $e->getMessage());
            throw new Exception("Failed to release trade: " . $e->getMessage());
        }
    }

    public static function cancel($tradeId, $userId): bool
    {
        $db = DB::connect();
        $trade = DB::select("SELECT * FROM trades WHERE id = ?", [$tradeId]);

        if (!$trade) {
            Console::error("Trade not found");
            throw new Exception("Trade not found");
        }

        if (!in_array($trade['status'], ['open', 'in_progress'])) {
            Console::error("Trade cannot be cancelled in its current state");
            throw new Exception("Trade cannot be cancelled in its current state");
        }

        if ($trade['seller_id'] != $userId && $trade['buyer_id'] != $userId) {
            Console::error("You are not authorized to cancel this trade");
            throw new Exception("You are not authorized to cancel this trade");
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

                DB::insert(
                    "INSERT INTO transactions (user_id, asset, amount, type, trade_id) 
                     VALUES (?, ?, ?, 'unlock', ?)",
                    [$userId, $trade['asset'], $trade['amount'], $tradeId]
                );
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Failed to cancel trade: " . $e->getMessage());
        }
    }

    public static function updateStatus($id, $status): bool
    {
        $result = DB::update(
            "UPDATE trades SET status=? WHERE id=?",
            [$status, $id]
        );

        if (!$result) {
            throw new Exception("Failed to update trade status");
        }

        return true;
    }

    public static function delete($id): bool
    {
        $trade = self::find($id);

        if (!$trade) {
            throw new Exception("Trade not found");
        }

        $result = DB::delete("DELETE FROM trades WHERE id=?", [$id]);

        if (!$result) {
            throw new Exception("Failed to delete trade");
        }

        return true;
    }
}