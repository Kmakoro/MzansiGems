-- ONE-TIME migration for existing populated installations. Back up MySQL first.
-- Do not re-run: the two ALTER statements intentionally fail if columns already exist.
-- Run before deploying the new PHP code. Do NOT re-import install.sql.
ALTER TABLE users
    ADD COLUMN must_reset_password TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 1;
CREATE TABLE IF NOT EXISTS login_throttles (
    bucket_key CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_started DATETIME NOT NULL,
    INDEX idx_login_window (window_started)
) ENGINE=InnoDB;

-- The repository published passwords for these seeded accounts.
-- Existing hashes cannot tell us the plaintext: require a reset for known demo users.
UPDATE users
SET must_reset_password = 1, auth_version = auth_version + 1
WHERE email IN (
 'admin@mzansigem.local','user@mzansigem.local',
 'admin@hiddengems.local','user@hiddengems.local',
 'ayanda@example.com','zanele@example.com','karabo@example.com',
 'anele@example.com','palesa@example.com'
);
DELETE FROM api_tokens WHERE user_id IN (SELECT id FROM users WHERE must_reset_password = 1);
DELETE FROM remember_tokens WHERE user_id IN (SELECT id FROM users WHERE must_reset_password = 1);

-- For additional legacy/test accounts, use an approved list and set
-- must_reset_password = 1, auth_version = auth_version + 1, then revoke tokens.
-- Never try to recover passwords from password_hash.
