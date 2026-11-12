-- MzansiGems Task 3: additive, one-time migration for the EXISTING live database.
-- First export a complete SQL backup in HelioHost/Plesk phpMyAdmin.
-- Run once on the existing database. NEVER import install.sql on live.
-- DEFAULT 0 does not lock any existing account at migration time.
-- Weak passwords are identified only AFTER successful password verification
-- on the next password sign-in; password hashes cannot reveal password strength.
ALTER TABLE users
    ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0;

-- Verification after migration:
-- SHOW COLUMNS FROM users LIKE 'password_reset_required';
