USE hidden_gems;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL DEFAULT 'Android app',
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_token_user (user_id),
    INDEX idx_api_token_expiry (expires_at)
) ENGINE=InnoDB;
