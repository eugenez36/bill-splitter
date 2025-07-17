CREATE TABLE IF NOT EXISTS users
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50)  NOT NULL,
    email      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    CONSTRAINT name_min_length CHECK (CHAR_LENGTH(name) >= 2)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;