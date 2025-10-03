<?php
namespace App\Workers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Helpers\DB;

/**
 * Simple chat server implementing rooms per trade_id.
 * Protocol: client sends JSON { action: 'join'|'message'|'leave', trade_id: <int>, sender_id: <int>, message: <string> }
 * Server broadcasts JSON { action: 'message', trade_id, message_id, sender_id, sender_name, message, created_at }
 */
class ChatServer implements MessageComponentInterface {
    /** @var \SplObjectStorage */
    protected $clients;

    /** Map connection -> ['trade_id'=>int, 'sender_id'=>int] */
    protected $meta;

    /** Map trade_id => array of ConnectionInterface (for quick broadcast) */
    protected $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->meta = [];
        $this->rooms = [];
        echo "ChatServer created\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->meta[$conn->resourceId] = null;
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        // parse JSON
        $data = json_decode($msg, true);
        if (!$data) {
            $from->send(json_encode(['error' => 'invalid_json']));
            return;
        }

        $action = $data['action'] ?? null;

        if ($action === 'join') {
            $this->handleJoin($from, $data);
            return;
        }

        if ($action === 'leave') {
            $this->handleLeave($from, $data);
            return;
        }

        if ($action === 'message') {
            $this->handleMessage($from, $data);
            return;
        }

        $from->send(json_encode(['error' => 'unknown_action']));
    }

    protected function handleJoin(ConnectionInterface $conn, array $data) {
        $tradeId = isset($data['trade_id']) ? (int)$data['trade_id'] : null;
        $senderId = isset($data['sender_id']) ? (int)$data['sender_id'] : null;

        if (!$tradeId || !$senderId) {
            $conn->send(json_encode(['error' => 'join_requires_trade_id_and_sender_id']));
            return;
        }

        // store metadata
        $this->meta[$conn->resourceId] = ['trade_id' => $tradeId, 'sender_id' => $senderId];

        if (!isset($this->rooms[$tradeId])) {
            $this->rooms[$tradeId] = [];
        }
        $this->rooms[$tradeId][$conn->resourceId] = $conn;

        $conn->send(json_encode(['action' => 'joined', 'trade_id' => $tradeId]));

        echo "Conn {$conn->resourceId} joined trade {$tradeId}\n";
    }

    protected function handleLeave(ConnectionInterface $conn, array $data) {
        $meta = $this->meta[$conn->resourceId] ?? null;
        if (!$meta) {
            $conn->send(json_encode(['error' => 'not_in_room']));
            return;
        }
        $tradeId = $meta['trade_id'];
        unset($this->rooms[$tradeId][$conn->resourceId]);
        $this->meta[$conn->resourceId] = null;
        $conn->send(json_encode(['action' => 'left', 'trade_id' => $tradeId]));
        echo "Conn {$conn->resourceId} left trade {$tradeId}\n";
    }

    protected function handleMessage(ConnectionInterface $conn, array $data) {
        $meta = $this->meta[$conn->resourceId] ?? null;
        if (!$meta) {
            $conn->send(json_encode(['error' => 'must_join_first']));
            return;
        }
        $tradeId = $meta['trade_id'];
        $senderId = $meta['sender_id'];
        $messageText = isset($data['message']) ? trim($data['message']) : '';

        if ($messageText === '') {
            $conn->send(json_encode(['error' => 'empty_message']));
            return;
        }

        // Persist message to DB
        try {
            $db = DB::connect();
            $stmt = $db->prepare("INSERT INTO messages (trade_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$tradeId, $senderId, $messageText]);
            $messageId = $db->lastInsertId();

            // fetch sender username
            $stmt2 = $db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt2->execute([$senderId]);
            $user = $stmt2->fetch(\PDO::FETCH_ASSOC);
            $senderName = $user['username'] ?? 'unknown';

            // prepare outgoing payload
            $payload = [
                'action' => 'message',
                'trade_id' => $tradeId,
                'message_id' => (int)$messageId,
                'sender_id' => (int)$senderId,
                'sender_name' => $senderName,
                'message' => $messageText,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // broadcast to everyone in that room
            if (isset($this->rooms[$tradeId])) {
                foreach ($this->rooms[$tradeId] as $resourceId => $clientConn) {
                    $clientConn->send(json_encode($payload));
                }
            }

            echo "Trade {$tradeId} message from {$senderId}: {$messageText}\n";

        } catch (\Exception $ex) {
            $conn->send(json_encode(['error' => 'db_error', 'detail' => $ex->getMessage()]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove from clients and rooms
        $this->clients->detach($conn);
        $meta = $this->meta[$conn->resourceId] ?? null;
        if ($meta) {
            $tradeId = $meta['trade_id'];
            if (isset($this->rooms[$tradeId][$conn->resourceId])) {
                unset($this->rooms[$tradeId][$conn->resourceId]);
            }
        }
        unset($this->meta[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
