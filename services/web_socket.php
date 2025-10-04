<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Workers\ChatServer;
use React\Socket\Server as Reactor;
use React\EventLoop\Factory;

$loop = Factory::create();
$webSock = new Reactor('127.0.0.1:8080', $loop);

$webServer = new IoServer(
    new HttpServer(new WsServer(new ChatServer())),
    $webSock,
    $loop
);

echo "WebSocket server running on ws://127.0.0.1:8080\n";
$loop->run();
