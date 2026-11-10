-- Additive migration. Back up the production database before running.
-- No existing users, sessions, gems, or reviews are deleted.
CREATE TABLE IF NOT EXISTS auth_login_attempts (
    attempt_key CHAR(64) NOT NULL PRIMARY KEY,
    failures INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    INDEX idx_auth_login_attempts_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
