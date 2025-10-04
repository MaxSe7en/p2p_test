<?php
namespace App\Controllers;

use App\Models\Message;

class ChatController {
    public static function sendMessage($tradeId, $data) {
        $id = Message::create($tradeId, $data['sender_id'], $data['message']);
        return ["message" => "Message sent", "id" => $id];
    }

    public static function getMessages($tradeId) {
        return Message::getByTrade($tradeId);
    }
}
