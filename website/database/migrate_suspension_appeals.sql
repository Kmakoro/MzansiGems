-- Run this once in phpMyAdmin on an existing MzansiGems database.
-- Non-destructive: no existing tables or user content are dropped or altered.
-- Apply BEFORE deploying the PHP suspension/appeals pages.

CREATE TABLE IF NOT EXISTS suspension_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('open','appealed','under_review','reinstated') NOT NULL DEFAULT 'open',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_suspension_case_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_suspension_case_admin FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_suspension_case_user (user_id, id),
    INDEX idx_suspension_case_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suspension_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    sender_user_id INT UNSIGNED NULL,
    sender_kind ENUM('member','admin') NOT NULL DEFAULT 'member',
    message VARCHAR(2000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_suspension_message_case FOREIGN KEY (case_id) REFERENCES suspension_cases(id) ON DELETE CASCADE,
    CONSTRAINT fk_suspension_message_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_suspension_messages_case (case_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
