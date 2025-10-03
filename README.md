# 📖 P2P Escrow Trading System – API Documentation

Base URL:

```
http://localhost:8000
```

---

## 🔐 Authentication

### Register User

**POST** `/register`

**Request Body**

```json
{
  "username": "alice",
  "password": "secret123"
}
```

**Response**

```json
{
  "message": "User registered successfully",
  "id": 1
}
```

---

### Login User

**POST** `/login`

**Request Body**

```json
{
  "username": "alice",
  "password": "secret123"
}
```

**Response**

```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "username": "alice",
    "password": "...hashed...",
    "created_at": "2025-10-02 12:00:00"
  }
}
```

---

## 📦 Trades

### List All Trades

**GET** `/trades`

**Response**

```json
[
  {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "open",
    "seller_name": "alice",
    "buyer_name": null
  }
]
```

---

### Get Single Trade

**GET** `/trades/{id}`

**Response**

```json
{
  "id": 1,
  "asset": "BTC",
  "amount": "100.00",
  "status": "in_progress",
  "seller_name": "alice",
  "buyer_name": "bob"
}
```

---

### Create Trade

**POST** `/trades`

**Request Body**

```json
{
  "seller_id": 1,
  "asset": "BTC",
  "amount": 100
}
```

**Response**

```json
{
  "message": "Trade created",
  "id": 1
}
```

---

### Buy Trade

**POST** `/trades/{id}/buy`

**Request Body**

```json
{
  "buyer_id": 2
}
```

**Response**

```json
{
  "message": "Trade taken by buyer",
  "trade": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "in_progress",
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

---

### Release Trade

**POST** `/trades/{id}/release`

**Request Body**

```json
{
  "seller_id": 1
}
```

**Response**

```json
{
  "message": "Trade released to buyer",
  "trade": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "completed",
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

---

### Cancel Trade

**POST** `/trades/{id}/cancel`

**Request Body**

```json
{
  "user_id": 2
}
```

**Response**

```json
{
  "message": "Trade cancelled",
  "trade": {
    "id": 1,
    "asset": "BTC",
    "amount": "100.00",
    "status": "cancelled",
    "seller_name": "alice",
    "buyer_name": "bob"
  }
}
```

---

## 💬 Chat (REST API)

### Send Message

**POST** `/trades/{id}/messages`

**Request Body**

```json
{
  "sender_id": 2,
  "message": "Hello, I am ready to pay"
}
```

**Response**

```json
{
  "message": "Message sent",
  "id": 1
}
```

---

### Fetch Messages

**GET** `/trades/{id}/messages`

**Response**

```json
[
  {
    "id": 1,
    "message": "Hello, I am ready to pay",
    "created_at": "2025-10-02 12:00:00",
    "sender_name": "bob"
  },
  {
    "id": 2,
    "message": "Okay, please proceed",
    "created_at": "2025-10-02 12:01:00",
    "sender_name": "alice"
  }
]
```

---

## ⚡ Chat (WebSocket)

**Server URL**:

```
ws://localhost:8080
```

### Join a Trade Chat

Send:

```json
{
  "action": "join",
  "trade_id": 1,
  "sender_id": 2
}
```

Response:

```json
{
  "action": "joined",
  "trade_id": 1
}
```

---

### Send a Message

Send:

```json
{
  "action": "message",
  "message": "Is the trade still available?"
}
```

Broadcasted to all clients in the room:

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

---

### Leave Trade Chat

Send:

```json
{
  "action": "leave",
  "trade_id": 1
}
```

Response:

```json
{
  "action": "left",
  "trade_id": 1
}
```
