CREATE DATABASE IF NOT EXISTS hidden_gems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hidden_gems;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS review_likes;
DROP TABLE IF EXISTS gem_likes;
DROP TABLE IF EXISTS saved_gems;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS gem_images;
DROP TABLE IF EXISTS gems;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS email_verification_tokens;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    firebase_uid VARCHAR(128) NULL UNIQUE,
    city VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    bio VARCHAR(300) NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    level INT UNSIGNED NOT NULL DEFAULT 1,
    points INT UNSIGNED NOT NULL DEFAULT 0,
    notify_new_gems TINYINT(1) NOT NULL DEFAULT 1,
    notify_comments TINYINT(1) NOT NULL DEFAULT 1,
    notify_likes_saves TINYINT(1) NOT NULL DEFAULT 1,
    personalized_recommendations TINYINT(1) NOT NULL DEFAULT 1,
    show_saved_gems TINYINT(1) NOT NULL DEFAULT 1,
    show_activity_status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status),
    INDEX idx_users_city (city)
) ENGINE=InnoDB;

CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_verify_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_verify_token (token_hash),
    INDEX idx_verify_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(16) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_remember_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE api_tokens (
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

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reset_token (token_hash),
    INDEX idx_reset_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE gems (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description VARCHAR(500) NOT NULL,
    location VARCHAR(180) NOT NULL,
    city VARCHAR(100) NOT NULL,
    vibes VARCHAR(220) NOT NULL,
    budget_level ENUM('Free','Budget','Mid-range','Premium') NOT NULL,
    activity_type ENUM('Food','Fitness','Study','Nature','Nightlife','Culture') NOT NULL,
    operating_hours VARCHAR(100) NOT NULL DEFAULT 'All Day',
    cover_image VARCHAR(500) NULL,
    average_rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    review_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    UNIQUE KEY unique_slug (slug),
    CONSTRAINT fk_gem_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_gems_status_city (status, city),
    INDEX idx_gems_activity (activity_type),
    FULLTEXT INDEX ft_gems_search (title, description, location, city)
) ENGINE=InnoDB;

CREATE TABLE gem_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gem_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(180) NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_image_gem FOREIGN KEY (gem_id) REFERENCES gems(id) ON DELETE CASCADE,
    INDEX idx_images_gem_order (gem_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gem_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(300) NULL,
    vibes VARCHAR(220) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    is_edited TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_gem FOREIGN KEY (gem_id) REFERENCES gems(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_gem_review (user_id, gem_id),
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_reviews_status (status),
    INDEX idx_reviews_gem_status (gem_id, status)
) ENGINE=InnoDB;

CREATE TABLE saved_gems (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    gem_id INT UNSIGNED NOT NULL,
    personal_note VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_gem FOREIGN KEY (gem_id) REFERENCES gems(id) ON DELETE CASCADE,
    UNIQUE KEY unique_saved_gem (user_id, gem_id),
    INDEX idx_saved_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE gem_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    gem_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_like_gem FOREIGN KEY (gem_id) REFERENCES gems(id) ON DELETE CASCADE,
    UNIQUE KEY unique_gem_like (user_id, gem_id)
) ENGINE=InnoDB;

CREATE TABLE review_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    review_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_like_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review_like (user_id, review_id)
) ENGINE=InnoDB;

CREATE TABLE reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    gem_id INT UNSIGNED NULL,
    review_id INT UNSIGNED NULL,
    violation_type VARCHAR(100) NOT NULL,
    details VARCHAR(400) NULL,
    status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
    resolution_note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_gem FOREIGN KEY (gem_id) REFERENCES gems(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_reports_status (status)
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    activity_type VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_type (activity_type)
) ENGINE=InnoDB;

INSERT INTO users (full_name,email,city,password_hash,email_verified_at,bio,role,status,level,points,created_at) VALUES
('Naledi Dlamini','admin@mzansigem.local','Cape Town','$2y$12$KprBcK5rkUTQKESovAGQL.DVVzjpXBJajT7ooHpjneLn2CJj.K3EW','2026-01-01 00:00:00','Cape Town local sharing the best date spots. Coffee enthusiast.','admin','active',8,1820,'2026-01-10 10:00:00'),
('Thabo Mkhize','user@mzansigem.local','Johannesburg','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Always looking for the next underrated place.','user','active',5,740,'2026-02-02 11:00:00'),
('Lebo Mokoena','lebo@example.com','Pretoria','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Nature, food and quiet corners.','user','active',4,510,'2026-03-15 09:30:00'),
('Sipho Ndlovu','sipho@example.com','Durban','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Durban explorer and weekend runner.','user','active',3,320,'2026-04-07 14:20:00'),
('Lerato Mabena','lerato@example.com','Cape Town','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Art, coffee and live music.','user','suspended',2,190,'2026-05-01 08:10:00');

INSERT INTO gems (user_id,title,slug,description,location,city,vibes,budget_level,activity_type,operating_hours,cover_image,average_rating,review_count,status,created_at,approved_at) VALUES
(1,'The Pot Luck Club','the-pot-luck-club','Trendy rooftop restaurant with stunning city views and creative tapas-style dishes.','Woodstock, Cape Town','Cape Town','Romantic,Chill,Social','Premium','Food','12:30 - 23:00','https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1400&q=85',4.80,3,'approved','2026-01-15 12:00:00','2026-01-15 14:00:00'),
(2,'Delta Park Trail','delta-park-trail','A calm green escape with walking paths, birdlife and wide lawns hidden in the city.','Blairgowrie, Johannesburg','Johannesburg','Adventurous,Quiet,Chill','Free','Nature','06:00 - 18:00','https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=1400&q=85',4.60,2,'approved','2026-02-10 08:30:00','2026-02-10 10:00:00'),
(1,'Braamfontein Art Walk','braamfontein-art-walk','A colourful self-guided walk through murals, galleries and independent creative spaces.','Braamfontein, Johannesburg','Johannesburg','Social,Chill,Adventurous','Free','Culture','All Day','https://images.unsplash.com/photo-1549490349-8643362247b5?auto=format&fit=crop&w=1400&q=85',4.70,2,'approved','2026-02-18 15:00:00','2026-02-18 16:00:00'),
(3,'Truth Coffee Courtyard','truth-coffee-courtyard','An atmospheric coffee stop with bold industrial design and a quieter courtyard corner.','Buitenkant Street, Cape Town','Cape Town','Chill,Social,Quiet','Mid-range','Food','07:00 - 18:00','https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1400&q=85',4.50,2,'approved','2026-03-04 09:00:00','2026-03-04 11:00:00'),
(4,'Sunset Beach Run','sunset-beach-run','A scenic oceanfront route that is perfect for an easy run, walk or sunset reset.','Three Anchor Bay, Cape Town','Cape Town','Adventurous,Social,Chill','Free','Fitness','All Day','https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=1400&q=85',4.80,2,'approved','2026-03-20 17:00:00','2026-03-20 18:00:00'),
(3,'Jacaranda Reading Garden','jacaranda-reading-garden','A shaded and peaceful garden corner that works beautifully for reading and quiet study.','Hatfield, Pretoria','Pretoria','Quiet,Chill,Romantic','Free','Study','08:00 - 17:00','https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1400&q=85',4.40,1,'approved','2026-04-02 10:00:00','2026-04-02 12:00:00'),
(4,'Rooftop Vinyl Nights','rooftop-vinyl-nights','An intimate rooftop music experience featuring local vinyl selectors and skyline views.','Morningside, Durban','Durban','Social,Romantic,Adventurous','Mid-range','Nightlife','18:00 - 01:00','https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1400&q=85',4.30,1,'approved','2026-04-22 19:00:00','2026-04-23 08:00:00'),
(2,'Hidden Waterfall Picnic','hidden-waterfall-picnic','A short trail to a secluded waterfall with picnic rocks and cool forest shade.','Kloof, Durban','Durban','Adventurous,Romantic,Quiet','Budget','Nature','07:00 - 17:00','https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=1400&q=85',0,0,'pending','2026-05-08 09:00:00',NULL),
(3,'Soweto Sunday Table','soweto-sunday-table','A lively home-style Sunday lunch concept celebrating local dishes and community stories.','Orlando West, Johannesburg','Johannesburg','Social,Chill,Romantic','Budget','Food','11:00 - 17:00','https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1400&q=85',0,0,'pending','2026-05-09 11:00:00',NULL),
(4,'Durban Dawn Yoga Deck','durban-dawn-yoga-deck','A sunrise yoga space overlooking the ocean, ideal for a calm start to the day.','North Beach, Durban','Durban','Quiet,Chill,Social','Budget','Fitness','05:30 - 08:00','https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=1400&q=85',0,0,'pending','2026-05-10 06:00:00',NULL);

INSERT INTO gem_images (gem_id,image_path,alt_text,sort_order) VALUES
(1,'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80','Restaurant interior',1),
(1,'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=80','Creative dining plate',2),
(2,'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80','Green trail',1),
(3,'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=1200&q=80','Colourful mural',1),
(4,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80','Coffee cup',1),
(5,'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80','Outdoor route',1);

INSERT INTO reviews (gem_id,user_id,rating,comment,vibes,status,created_at) VALUES
(1,2,5,'This place is absolutely amazing. The atmosphere is perfect for a date night and the food was memorable.','Romantic,Food,Date Night','approved','2026-02-10 19:00:00'),
(1,3,5,'Great views and polished service. Book ahead for sunset.','Romantic,Social','approved','2026-02-18 20:00:00'),
(1,4,4,'Fantastic menu and atmosphere, though it can get busy.','Food,Social','approved','2026-03-04 21:00:00'),
(2,1,5,'A surprisingly peaceful green space for a weekend walk.','Quiet,Nature','approved','2026-02-22 09:00:00'),
(2,3,4,'Lovely paths and lots of open space.','Chill,Nature','approved','2026-03-01 13:00:00'),
(3,2,5,'One of the best creative walks in the city.','Culture,Social','approved','2026-03-12 15:00:00'),
(3,4,4,'Great with friends and easy to combine with lunch nearby.','Culture,Social','approved','2026-03-14 15:00:00'),
(4,2,4,'Excellent coffee and a memorable setting.','Food,Chill','approved','2026-03-22 09:00:00'),
(4,1,5,'The courtyard is a perfect quiet escape.','Quiet,Food','approved','2026-03-24 10:00:00'),
(5,1,5,'Beautiful route and a fantastic sunset.','Fitness,Adventurous','approved','2026-04-01 18:30:00'),
(5,3,5,'Safe, social and easy to enjoy at any pace.','Fitness,Social','approved','2026-04-03 18:00:00'),
(6,2,4,'Quiet and ideal for an afternoon reading session.','Study,Quiet','approved','2026-04-15 14:00:00'),
(7,3,4,'Good music and a relaxed crowd.','Nightlife,Social','approved','2026-05-01 22:00:00'),
(1,1,5,'Pending administrator test review.','Food,Romantic','pending','2026-05-11 12:00:00'),
(2,4,5,'The trail was worth the early start.','Nature,Adventurous','pending','2026-05-11 13:00:00'),
(3,3,4,'A strong cultural experience for visitors.','Culture,Social','pending','2026-05-11 14:00:00');

INSERT INTO saved_gems (user_id,gem_id,personal_note) VALUES
(1,3,'Return for the next gallery opening.'),(1,5,'Try the full route on Saturday morning.'),(2,1,'Book two weeks ahead.'),(2,2,'Bring a picnic blanket.'),(3,4,'Try the courtyard seating.');
INSERT INTO gem_likes (user_id,gem_id) VALUES (1,1),(1,3),(2,1),(2,2),(3,3),(4,5),(3,5);
INSERT INTO review_likes (user_id,review_id) VALUES (1,1),(3,1),(4,1),(1,2),(2,10);
INSERT INTO reports (reporter_id,gem_id,violation_type,details,status,created_at) VALUES
(3,1,'Inappropriate content','One photo may not match the location.','pending','2026-05-08 10:00:00'),
(2,7,'Incorrect information','Operating hours may have changed.','pending','2026-05-09 11:00:00');

INSERT INTO settings (setting_key,setting_value) VALUES
('site_name','Mzansi Gem'),('site_description','Discover South Africa''s hidden treasures'),
('allow_registration','1'),('require_email_verification','0'),('max_posts_per_day','10'),
('enable_moderation','1'),('auto_approve_content','0');

INSERT INTO activity_log (user_id,activity_type,description,created_at) VALUES
(4,'user_joined','Sipho Ndlovu joined the community.','2026-04-07 14:20:00'),
(3,'gem_submitted','Lebo Mokoena submitted Jacaranda Reading Garden.','2026-04-02 10:00:00'),
(2,'review_posted','Thabo Mkhize reviewed Braamfontein Art Walk.','2026-03-12 15:00:00'),
(1,'content_approved','Naledi approved Rooftop Vinyl Nights.','2026-04-23 08:00:00');


-- TASK2_SEED_USERS
INSERT INTO users (full_name,email,city,password_hash,email_verified_at,bio,role,status,level,points,created_at) VALUES
('Ayanda Khumalo','ayanda@example.com','Johannesburg','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Weekend food explorer.','user','active',2,220,'2026-05-12 09:00:00'),
('Zanele Dube','zanele@example.com','Durban','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Beach walks and hidden cafes.','user','active',2,180,'2026-05-13 09:00:00'),
('Karabo Molefe','karabo@example.com','Pretoria','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Student discovering affordable places.','user','active',1,90,'2026-05-14 09:00:00'),
('Anele Mthembu','anele@example.com','Gqeberha','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Nature and local culture.','user','active',1,70,'2026-05-15 09:00:00'),
('Palesa Seabi','palesa@example.com','Bloemfontein','$2y$12$A7TgkFun9r82x0JLBM3Eoer4k.aUbzWYWb4PU4kzQHLue2LhnCroy','2026-01-01 00:00:00','Coffee, study spaces and art.','user','active',1,60,'2026-05-16 09:00:00');

INSERT INTO gem_images (gem_id,image_path,alt_text,sort_order) VALUES
(6,'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80','Reading garden',1),
(7,'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=80','Rooftop music',1),
(8,'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=1200&q=80','Waterfall trail',1),
(9,'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80','Community table',1);

INSERT INTO saved_gems (user_id,gem_id,personal_note) VALUES
(1,2,'Try the trail early in the morning.'),
(1,4,'Return for coffee with friends.'),
(2,3,'Good weekend activity.'),
(3,1,'Book before visiting.'),
(4,2,'Take photos on the next visit.');

INSERT INTO gem_likes (user_id,gem_id) VALUES (1,2),(2,3),(4,1);
INSERT INTO review_likes (user_id,review_id) VALUES (1,3),(2,2),(3,2),(4,3),(4,4);

INSERT INTO settings (setting_key,setting_value) VALUES
('support_message','Use Profile > Settings > Help & Support for assistance.'),
('mobile_sso_provider','Firebase Google Authentication'),
('task2_seed_version','1');

INSERT INTO activity_log (user_id,activity_type,description,created_at) VALUES
(1,'profile_updated','Task 2 simulated profile update.','2026-05-12 12:00:00'),
(2,'gem_saved','Task 2 simulated save event.','2026-05-12 12:05:00'),
(3,'gem_liked','Task 2 simulated like event.','2026-05-12 12:10:00'),
(4,'review_liked','Task 2 simulated review like.','2026-05-12 12:15:00'),
(1,'settings_updated','Task 2 simulated settings update.','2026-05-12 12:20:00'),
(2,'mobile_login','Task 2 simulated mobile sign-in.','2026-05-12 12:25:00');
