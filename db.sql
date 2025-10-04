CREATE TABLE wallets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  asset VARCHAR(10) NOT NULL,
  balance DECIMAL(20, 8) DEFAULT 0,
  locked_balance DECIMAL(20, 8) DEFAULT 0,
  UNIQUE KEY (user_id, asset),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  asset VARCHAR(10) NOT NULL,
  amount DECIMAL(20, 8) NOT NULL,
  type ENUM('deposit', 'withdraw', 'lock', 'unlock', 'release') NOT NULL,
  trade_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (trade_id) REFERENCES trades(id)
);

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trade_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trade_id` (`trade_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`),
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `asset` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('open','in_progress','completed','cancelled') DEFAULT 'open',
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `buyer_id` (`buyer_id`),
  CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  CONSTRAINT `trades_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `asset` varchar(10) NOT NULL,
  `balance` decimal(20,8) DEFAULT 0.00000000,
  `locked_balance` decimal(20,8) DEFAULT 0.00000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`asset`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
