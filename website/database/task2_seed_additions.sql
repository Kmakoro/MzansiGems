-- Run this after migrate_task2_compliance.sql on an EXISTING database.
-- It adds simulated Task 2 records without dropping existing data.
USE hidden_gems;

INSERT IGNORE INTO users
(full_name,email,city,password_hash,email_verified_at,bio,role,status,level,points,created_at) VALUES
('Ayanda Khumalo','ayanda@example.com','Johannesburg','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Weekend food explorer.','user','active',2,220,'2026-05-12 09:00:00'),
('Zanele Dube','zanele@example.com','Durban','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Beach walks and hidden cafes.','user','active',2,180,'2026-05-13 09:00:00'),
('Karabo Molefe','karabo@example.com','Pretoria','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Student discovering affordable places.','user','active',1,90,'2026-05-14 09:00:00'),
('Anele Mthembu','anele@example.com','Gqeberha','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Nature and local culture.','user','active',1,70,'2026-05-15 09:00:00'),
('Palesa Seabi','palesa@example.com','Bloemfontein','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Coffee, study spaces and art.','user','active',1,60,'2026-05-16 09:00:00');

INSERT INTO email_verification_tokens (user_id,token_hash,expires_at)
SELECT id,SHA2(CONCAT('task2-verify-',id),256),'2026-01-02 00:00:00' FROM users ORDER BY id LIMIT 10;

INSERT IGNORE INTO remember_tokens (user_id,selector,token_hash,expires_at)
SELECT id,LPAD(id,16,'0'),SHA2(CONCAT('task2-remember-',id),256),'2026-01-02 00:00:00' FROM users ORDER BY id LIMIT 10;

INSERT IGNORE INTO api_tokens (user_id,token_hash,name,last_used_at,expires_at)
SELECT id,SHA2(CONCAT('task2-api-',id),256),'Expired Task 2 simulation','2026-01-01 00:00:00','2026-01-02 00:00:00'
FROM users ORDER BY id LIMIT 10;

INSERT INTO password_resets (user_id,token_hash,expires_at,used_at)
SELECT id,SHA2(CONCAT('task2-reset-',id),256),'2026-01-02 00:00:00','2026-01-01 12:00:00'
FROM users ORDER BY id LIMIT 10;

INSERT IGNORE INTO gem_images (gem_id,image_path,alt_text,sort_order) VALUES
(6,'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80','Reading garden',1),
(7,'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=80','Rooftop music',1),
(8,'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=1200&q=80','Waterfall trail',1),
(9,'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80','Community table',1);

INSERT IGNORE INTO saved_gems (user_id,gem_id,personal_note) VALUES
(1,2,'Try the trail early in the morning.'),(1,4,'Return for coffee with friends.'),
(2,3,'Good weekend activity.'),(3,1,'Book before visiting.'),(4,2,'Take photos on the next visit.');

INSERT IGNORE INTO gem_likes (user_id,gem_id) VALUES (1,2),(2,3),(4,1);
INSERT IGNORE INTO review_likes (user_id,review_id) VALUES (1,3),(2,2),(3,2),(4,3),(4,4);

INSERT INTO reports (reporter_id,gem_id,violation_type,details,status,created_at) VALUES
(1,2,'Incorrect information','Please confirm the opening hours.','resolved','2026-05-12 10:00:00'),
(2,3,'Duplicate content','This may duplicate another listing.','dismissed','2026-05-12 10:10:00'),
(3,4,'Incorrect location','Please confirm the map location.','resolved','2026-05-12 10:20:00'),
(4,5,'Outdated information','The activity details may have changed.','pending','2026-05-12 10:30:00'),
(1,6,'Image issue','Please verify the cover image.','pending','2026-05-12 10:40:00'),
(2,1,'Accessibility information','Accessibility details are missing.','resolved','2026-05-12 10:50:00'),
(3,7,'Opening hours','Please confirm closing time.','pending','2026-05-12 11:00:00'),
(4,3,'Description issue','The description could be clearer.','dismissed','2026-05-12 11:10:00');

INSERT INTO settings (setting_key,setting_value) VALUES
('support_message','Use Profile > Settings > Help & Support for assistance.'),
('mobile_sso_provider','Firebase Google Authentication'),
('task2_seed_version','1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO activity_log (user_id,activity_type,description,created_at) VALUES
(1,'profile_updated','Task 2 simulated profile update.','2026-05-12 12:00:00'),
(2,'gem_saved','Task 2 simulated save event.','2026-05-12 12:05:00'),
(3,'gem_liked','Task 2 simulated like event.','2026-05-12 12:10:00'),
(4,'review_liked','Task 2 simulated review like.','2026-05-12 12:15:00'),
(1,'settings_updated','Task 2 simulated settings update.','2026-05-12 12:20:00'),
(2,'mobile_login','Task 2 simulated mobile sign-in.','2026-05-12 12:25:00');
