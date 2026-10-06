-- Run this once on an existing Mzansi Gem database before enabling Firebase Google SSO.
USE hidden_gems;

ALTER TABLE users
    ADD COLUMN firebase_uid VARCHAR(128) NULL AFTER email,
    ADD UNIQUE KEY unique_firebase_uid (firebase_uid);
