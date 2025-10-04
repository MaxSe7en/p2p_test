<?php
namespace App\Controllers;

use App\Models\Trade;
use App\Exceptions\Console;
use Exception;

class TradeController
{
    /**
     * Standard success response
     */
    private static function success($data = null, string $message = null, int $statusCode = 200): array
    {
        http_response_code($statusCode);
        
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Standard error response
     */
    private static function error(string $message, int $statusCode = 400): array
    {
        http_response_code($statusCode);
        
        return [
            'success' => false,
            'message' => $message,
            'data' => null
        ];
    }

    public static function index(): array
    {
        try {
            Console::log("Fetching all trades");
            $trades = Trade::all();
            return self::success($trades);
        } catch (Exception $e) {
            Console::log2("Error fetching trades: ", $e->getMessage());
            return self::error("Failed to fetch trades", 500);
        }
    }

    public static function show($id): array
    {
        try {
            Console::log2("Fetching trade: ", $id);
            $trade = Trade::find($id);
            
            if (!$trade) {
                return self::error("Trade not found", 404);
            }
            
            return self::success($trade);
        } catch (Exception $e) {
            Console::log2("Error fetching trade: ", $e->getMessage());
            return self::error("Failed to fetch trade", 500);
        }
    }

    public static function store($data): array
    {
        try {
            Console::log2("Creating trade with data: ", $data);
            
            // Validate required fields
            if (!isset($data['seller_id']) || !isset($data['asset']) || !isset($data['amount'])) {
                return self::error("Missing required fields: seller_id, asset, amount", 422);
            }
            
            // Additional validation
            if ($data['amount'] <= 0) {
                return self::error("Amount must be greater than zero", 422);
            }
            
            $tradeId = Trade::create($data['seller_id'], $data['asset'], $data['amount']);
            $trade = Trade::find($tradeId);
            
            return self::success($trade, "Trade created successfully", 201);
        } catch (Exception $e) {
            Console::log2("Error creating trade: ", $e->getMessage());
            return self::error($e->getMessage(), 400);
        }
    }

    public static function buy($id, $data): array
    {
        try {
            Console::log2("Buyer taking trade: ", $id);
            
            if (!isset($data['buyer_id'])) {
                return self::error("Missing required field: buyer_id", 422);
            }
            
            Trade::buy($id, $data['buyer_id']);
            $trade = Trade::find($id);
            
            return self::success($trade, "Trade taken successfully");
        } catch (Exception $e) {
            Console::log2("Error buying trade: ", $e->getMessage());
            
            // Handle specific error cases
            if (strpos($e->getMessage(), "not found") !== false) {
                return self::error($e->getMessage(), 404);
            }
            
            return self::error($e->getMessage(), 400);
        }
    }

    public static function release($id, $data): array
    {
        try {
            Console::log2("Releasing trade: ", $id);
            
            if (!isset($data['seller_id'])) {
                return self::error("Missing required field: seller_id", 422);
            }
            
            Trade::release($id, $data['seller_id']);
            $trade = Trade::find($id);
            
            return self::success($trade, "Trade released successfully");
        } catch (Exception $e) {
            Console::log2("Error releasing trade: ", $e->getMessage());
            
            if (strpos($e->getMessage(), "not found") !== false) {
                return self::error($e->getMessage(), 404);
            }
            
            if (strpos($e->getMessage(), "not authorized") !== false) {
                return self::error($e->getMessage(), 403);
            }
            
            return self::error($e->getMessage(), 400);
        }
    }

    public static function cancel($id, $data): array
    {
        try {
            Console::log2("Cancelling trade: ", $id);
            
            if (!isset($data['user_id'])) {
                return self::error("Missing required field: user_id", 422);
            }
            
            Trade::cancel($id, $data['user_id']);
            $trade = Trade::find($id);
            
            return self::success($trade, "Trade cancelled successfully");
        } catch (Exception $e) {
            Console::log2("Error cancelling trade: ", $e->getMessage());
            
            if (strpos($e->getMessage(), "not found") !== false) {
                return self::error($e->getMessage(), 404);
            }
            
            if (strpos($e->getMessage(), "not authorized") !== false) {
                return self::error($e->getMessage(), 403);
            }
            
            return self::error($e->getMessage(), 400);
        }
    }

    public static function getUserChats($userId): array
    {
        try {
            Console::log2("Fetching chats for user: ", $userId);
            $chats = Trade::getUserChats($userId);
            return self::success($chats);
        } catch (Exception $e) {
            Console::log2("Error fetching user chats: ", $e->getMessage());
            return self::error("Failed to fetch chats", 500);
        }
    }

    public static function getTradeMessages($tradeId): array
    {
        try {
            Console::log2("Fetching messages for trade: ", $tradeId);
            
            // Validate trade ID
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                return self::error("Invalid trade ID", 400);
            }
            
            $messages = Trade::getTradeMessages($tradeId);
            return self::success($messages, "Messages retrieved successfully");
            
        } catch (Exception $e) {
            Console::log2("Error fetching trade messages: ", $e->getMessage());
            return self::error("Failed to fetch messages", 500);
        }
    }

    public static function update($id, $data): array
    {
        try {
            Console::log2("Updating trade status: ", $id);
            
            if (!isset($data['status'])) {
                return self::error("Missing required field: status", 422);
            }
            
            // Validate status
            $validStatuses = ['open', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                return self::error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 422);
            }
            
            Trade::updateStatus($id, $data['status']);
            $trade = Trade::find($id);
            
            return self::success($trade, "Trade status updated successfully");
        } catch (Exception $e) {
            Console::log2("Error updating trade: ", $e->getMessage());
            
            if (strpos($e->getMessage(), "not found") !== false) {
                return self::error($e->getMessage(), 404);
            }
            
            return self::error($e->getMessage(), 400);
        }
    }

    public static function destroy($id): array
    {
        try {
            Console::log2("Deleting trade: ", $id);
            Trade::delete($id);
            
            return self::success(null, "Trade deleted successfully");
        } catch (Exception $e) {
            Console::log2("Error deleting trade: ", $e->getMessage());
            
            if (strpos($e->getMessage(), "not found") !== false) {
                return self::error($e->getMessage(), 404);
            }
            
            return self::error($e->getMessage(), 400);
        }
    }
}