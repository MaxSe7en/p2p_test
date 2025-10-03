<?php
use App\Controllers\UserController;
use App\Controllers\TradeController;


Flight::route('GET /trades', function() {
    Flight::json(TradeController::index());
});

Flight::route('GET /trades/@id', function($id) {
    Flight::json(TradeController::show($id));
});

Flight::route('POST /trades', function() {
    $data = Flight::request()->data->getData();
    $id = TradeController::store($data);
    Flight::json(["message" => "Trade created", "id" => $id]);
});

Flight::route('PUT /trades/@id', function($id) {
    $data = Flight::request()->data->getData();
    TradeController::update($id, $data);
    Flight::json(["message" => "Trade updated"]);
});

Flight::route('DELETE /trades/@id', function($id) {
    TradeController::destroy($id);
    Flight::json(["message" => "Trade deleted"]);
});


// User register
Flight::route('POST /register', function() {
    $data = Flight::request()->data->getData();
    $res = UserController::register($data);
    Flight::json($res);
});

// User login
Flight::route('POST /login', function() {
    $data = Flight::request()->data->getData();
    $res = UserController::login($data);
    Flight::json($res);
});

// Buyer joins a trade
Flight::route('POST /trades/@id/buy', function($id) {
    $data = Flight::request()->data->getData();
    $res = TradeController::buy($id, $data);
    Flight::json($res);
});

// Seller releases a trade
Flight::route('POST /trades/@id/release', function($id) {
    $data = Flight::request()->data->getData();
    $res = TradeController::release($id, $data);
    Flight::json($res);
});

// Cancel a trade
Flight::route('POST /trades/@id/cancel', function($id) {
    $data = Flight::request()->data->getData();
    $res = TradeController::cancel($id, $data);
    Flight::json($res);
});

Flight::route('/', function(){
    echo 'Welcome to the P2P Trading API';
});