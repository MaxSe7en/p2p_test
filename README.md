# P2P Escrow Trading System - API Documentation

## Base URL
```
[http://localhost:8000](https://alphaseven.online/p2p_test/)
```

## WebSocket Server
```
[ws://localhost:8080](wss://alphaseven.online/ws/)
```

---

## 📋 Table of Contents
- [Authentication](#authentication)
- [Trades](#trades)
- [User Management](#user-management)
- [Chat System](#chat-system)
- [WebSocket Chat](#websocket-chat)

---

## 🔐 Authentication

### Register User
**POST** `/register`

**Request Body:**
```json
{
  "username": "alice",
  "password": "secret123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1
  }
}
```

### Login User
**POST** `/login`

**Request Body:**
```json
{
  "username": "alice",
  "password": "secret123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "id": 1,
    "username": "alice",
    "created_at": "2025-10-02 12:00:00",
    "wallets": [
      {
        "asset": "USD",
        "balance": "100000.00000000",
        "locked_balance": "0.00000000"
      }
    ]
  }
}
```

---

## 📦 Trades

### List All Trades
**GET** `/trades`

**Response:**
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "asset": "BTC",
      "amount": "100.00",
      "status": "open",
      "seller_id": 1,
      "buyer_id": null,
      "seller_name": "alice",
      "buyer_name": null
    }
  ]
}
```

### Get Single Trade
**GET** `/trades/{id}`

**Response:**
```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "in_progress",
    "seller_id": 1,
    "buyer_id": 2,
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

### Create Trade
**POST** `/trades`

**Request Body:**
```json
{
  "seller_id": 1,
  "asset": "BTC",
  "amount": 100
}
```

**Response:**
```json
{
  "success": true,
  "message": "Trade created successfully",
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "open",
    "seller_id": 1,
    "buyer_id": null,
    "seller_name": "alice",
    "buyer_name": null
  }
}
```

### Update Trade Status
**PUT** `/trades/{id}`

**Request Body:**
```json
{
  "status": "completed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Trade status updated successfully",
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "completed",
    "seller_id": 1,
    "buyer_id": 2,
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

### Delete Trade
**DELETE** `/trades/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Trade deleted successfully",
  "data": null
}
```

---

## 🛒 Trade Actions

### Buy Trade
**POST** `/trades/{id}/buy`

**Request Body:**
```json
{
  "buyer_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Trade taken successfully",
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "in_progress",
    "seller_id": 1,
    "buyer_id": 2,
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

### Release Trade
**POST** `/trades/{id}/release`

**Request Body:**
```json
{
  "seller_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Trade released successfully",
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "completed",
    "seller_id": 1,
    "buyer_id": 2,
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

### Cancel Trade
**POST** `/trades/{id}/cancel`

**Request Body:**
```json
{
  "user_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Trade cancelled successfully",
  "data": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "cancelled",
    "seller_id": 1,
    "buyer_id": 2,
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

---

## 👤 User Management

### Get User Wallets
**GET** `/users/{id}/wallets`

**Response:**
```json
{
  "success": true,
  "message": "Wallets retrieved successfully",
  "data": [
    {
      "asset": "USD",
      "balance": "100000.00000000",
      "locked": "0.00000000"
    },
    {
      "asset": "BTC",
      "balance": "100.00000000",
      "locked": "0.00000000"
    }
  ]
}
```

### Get User Chats
**GET** `/trades/user/{id}/chats`

**Response:**
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "asset": "BTC",
      "amount": "100.00",
      "status": "in_progress",
      "seller_id": 1,
      "buyer_id": 2,
      "seller_name": "alice",
      "buyer_name": "bob",
      "counterparty_name": "bob",
      "message_count": 5,
      "last_message_at": "2025-10-02 12:05:00"
    }
  ]
}
```

---

## 💬 Chat System (REST API)

### Get Trade Messages
**GET** `/trades/{id}/messages`

**Response:**
```json
{
  "success": true,
  "message": "Messages retrieved successfully",
  "data": [
    {
      "id": 1,
      "sender_id": 2,
      "sender_name": "bob",
      "message": "Hello, I am ready to pay",
      "created_at": "2025-10-02 12:00:00"
    },
    {
      "id": 2,
      "sender_id": 1,
      "sender_name": "alice",
      "message": "Okay, please proceed",
      "created_at": "2025-10-02 12:01:00"
    }
  ]
}
```

---

## ⚡ WebSocket Chat

### Connection
**URL:** `ws://[localhost:8080](ws://alphaseven.online/ws/)`

### Join Trade Chat
**Send:**
```json
{
  "action": "join",
  "trade_id": 1,
  "sender_id": 2
}
```

**Response:**
```json
{
  "action": "joined",
  "trade_id": 1
}
```

### Send Message
**Send:**
```json
{
  "action": "message",
  "message": "Is the trade still available?"
}
```

**Broadcasted to all clients in the room:**
```json
{
  "action": "message",
  "trade_id": 1,
  "message_id": 10,
  "sender_id": 2,
  "sender_name": "bob",
  "message": "Is the trade still available?",
  "created_at": "2025-10-02 12:05:00"
}
```

### Leave Trade Chat
**Send:**
```json
{
  "action": "leave",
  "trade_id": 1
}
```

**Response:**
```json
{
  "action": "left",
  "trade_id": 1
}
```

---

## 📊 Trade Status Reference

| Status | Description |
|--------|-------------|
| `open` | Trade is available for purchase |
| `in_progress` | Trade has been bought, awaiting completion |
| `completed` | Trade has been successfully completed |
| `cancelled` | Trade has been cancelled |

---

## 🎯 Error Responses

All endpoints follow a consistent error format:

```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

---

## 🚀 Quick Start

1. **Start the Web Server:**
   ```bash
   php -S localhost:8000
   ```

2. **Start the WebSocket Server:**
   ```bash
   php services/web_socket.php
   ```

3. **Test the API:**
   ```bash
   curl -X GET [http://localhost:8000](https://alphaseven.online/p2p_test/)/trades
   ```

---
