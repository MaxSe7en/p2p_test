<?php
use App\Controllers\UserController;
use App\Controllers\TradeController;

// Welcome route
Flight::route('/', function () {
    Flight::json([
        'success' => true,
        'message' => 'Welcome to the P2P Trading API',
        'data' => [
            'version' => '1.0',
            'endpoints' => [
                'GET /trades' => 'Get all trades',
                'GET /trades/:id' => 'Get a specific trade',
                'POST /trades' => 'Create a new trade',
                'PUT /trades/:id' => 'Update trade status',
                'DELETE /trades/:id' => 'Delete a trade',
                'POST /trades/:id/buy' => 'Buy a trade',
                'POST /trades/:id/release' => 'Release a trade',
                'POST /trades/:id/cancel' => 'Cancel a trade',
                'GET /trades/user/:id/chats' => 'Get user chats',
                'POST /register' => 'Register a new user',
                'POST /login' => 'Login user'
            ]
        ]
    ]);
});

// Trade routes
Flight::route('GET /trades', function () {
    $response = TradeController::index();
    Flight::json($response);
});

Flight::route('GET /trades/@id', function ($id) {
    $response = TradeController::show($id);
    Flight::json($response);
});

Flight::route('GET /trades/user/@id/chats', function ($id) {
    $response = TradeController::getUserChats($id);
    Flight::json($response);
});

Flight::route('GET /trades/@id/messages', function ($id) {
    $response = TradeController::getTradeMessages($id);
    Flight::json($response);
});

Flight::route('POST /trades', function () {
    $data = Flight::request()->data->getData();
    $response = TradeController::store($data);
    Flight::json($response);
});

Flight::route('PUT /trades/@id', function ($id) {
    $data = Flight::request()->data->getData();
    $response = TradeController::update($id, $data);
    Flight::json($response);
});

Flight::route('DELETE /trades/@id', function ($id) {
    $response = TradeController::destroy($id);
    Flight::json($response);
});

// Trade action routes
Flight::route('POST /trades/@id/buy', function ($id) {
    $data = Flight::request()->data->getData();
    $response = TradeController::buy($id, $data);
    Flight::json($response);
});

Flight::route('POST /trades/@id/release', function ($id) {
    $data = Flight::request()->data->getData();
    $response = TradeController::release($id, $data);
    Flight::json($response);
});

Flight::route('POST /trades/@id/cancel', function ($id) {
    $data = Flight::request()->data->getData();
    $response = TradeController::cancel($id, $data);
    Flight::json($response);
});

// Authentication routes
Flight::route('POST /register', function () {
    $data = Flight::request()->data->getData();
    $response = UserController::register($data);
    Flight::json($response);
});

Flight::route('POST /login', function () {
    $data = Flight::request()->data->getData();
    $response = UserController::login($data);
    Flight::json($response);
});

// 404 handler
Flight::map('notFound', function () {
    Flight::json([
        'success' => false,
        'message' => 'Route not found',
        'data' => null
    ], 404);
});