<?php
namespace App\Controllers;

use App\Models\Trade;
use App\Exceptions\Console;

class TradeController
{
    public static function index()
    {
        Console::log2("Fetching all trades ", Trade::all());
        return Trade::all();
    }

    public static function show($id)
    {
        return Trade::find($id);
    }

    public static function store($data)
    {
        return Trade::create($data['seller_id'], $data['asset'], $data['amount']);
    }

    public static function buy($id, $data)
    {
        $result = Trade::buy($id, $data['buyer_id']);
        if ($result) {
            return [
                "message" => "Trade taken by buyer",
                "trade" => Trade::find($id)
            ];
        }
        return ["error" => "Trade cannot be bought (maybe already in progress or completed)"];
    }

    public static function release($id, $data)
    {
        $result = Trade::release($id, $data['seller_id']);
        if ($result) {
            return [
                "message" => "Trade released to buyer",
                "trade" => Trade::find($id)
            ];
        }
        return ["error" => "Trade cannot be released (check seller or status)"];
    }

    public static function cancel($id, $data)
    {
        $result = Trade::cancel($id, $data['user_id']);
        if ($result) {
            return [
                "message" => "Trade cancelled",
                "trade" => Trade::find($id)
            ];
        }
        return ["error" => "Trade cannot be cancelled (wrong user or already completed)"];
    }


    public static function update($id, $data)
    {
        return Trade::updateStatus($id, $data['status']);
    }

    public static function destroy($id)
    {
        return Trade::delete($id);
    }
}
