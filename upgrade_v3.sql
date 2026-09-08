-- ============================================================
-- upgrade_v3.sql
--
-- Run this ONLY if you already had a previous version installed
-- (with per-trek accommodation_options / transport_options tables)
-- and want to keep your existing treks and bookings.
--
-- ⚠️  BACK UP YOUR DATABASE FIRST (phpMyAdmin → your database →
--     Export → Go) — this migration restructures core tables.
--
-- If you're starting completely fresh, ignore this file — just
-- import db.sql, it already includes everything below.
--
-- Run once, top to bottom. How to run: phpMyAdmin → your database
-- → SQL tab → paste this whole file → Go.
--
-- What this does:
--  1. Adds social-login columns to customers (google_id, facebook_id,
--     avatar_url) and makes password_hash nullable.
--  2. Creates the new global catalog (accommodations / transports)
--     and pivot tables (trek_accommodations / trek_transports),
--     migrating your existing per-trek options into them.
--  3. Repoints existing bookings at the new pivot rows so nothing
--     breaks, then drops the old accommodation_options /
--     transport_options tables.
--  4. Creates the empty itinerary_days table. NOTE: your existing
--     treks' old single-text itinerary is left in place (renamed to
--     itinerary_legacy so nothing is lost) but is NOT shown on the
--     site anymore — the new site reads from itinerary_days. Re-add
--     each trek's day-by-day itinerary once via Admin → Treks → Edit
--     (a one-time task per trek).
-- ============================================================

USE seatosummit;

-- 1) Customer accounts: add social login support
ALTER TABLE customers MODIFY password_hash VARCHAR(255) DEFAULT NULL;
ALTER TABLE customers ADD COLUMN google_id VARCHAR(100) DEFAULT NULL UNIQUE;
ALTER TABLE customers ADD COLUMN facebook_id VARCHAR(100) DEFAULT NULL UNIQUE;
ALTER TABLE customers ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL;

-- 2) New global catalog + pivot tables
CREATE TABLE IF NOT EXISTS accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS trek_accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    extra_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS trek_transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    transport_id INT NOT NULL,
    extra_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE,
    FOREIGN KEY (transport_id) REFERENCES transports(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS itinerary_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    day_label VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    short_desc VARCHAR(300) DEFAULT NULL,
    detail_desc TEXT DEFAULT NULL,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE
);

-- Preserve the old itinerary text (not auto-converted — see notes above)
ALTER TABLE treks CHANGE itinerary itinerary_legacy TEXT DEFAULT NULL;

-- 3) Migrate existing per-trek options into the global catalog,
--    deduplicating by name+description (so "Teahouse Lodge" used on
--    5 treks becomes ONE reusable catalog entry, not five)
INSERT INTO accommodations (name, description, default_price)
SELECT name, description, MIN(extra_price)
FROM accommodation_options
GROUP BY name, description;

INSERT INTO transports (name, description, default_price)
SELECT name, description, MIN(extra_price)
FROM transport_options
GROUP BY name, description;

-- 4) Recreate each trek's attachment to the catalog, temporarily
--    tracking the old row id so we can repoint bookings afterward
ALTER TABLE trek_accommodations ADD COLUMN old_option_id INT DEFAULT NULL;
INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default, old_option_id)
SELECT ao.trek_id, acc.id, ao.extra_price, ao.is_default, ao.id
FROM accommodation_options ao
JOIN accommodations acc ON acc.name = ao.name AND acc.description = ao.description;

ALTER TABLE trek_transports ADD COLUMN old_option_id INT DEFAULT NULL;
INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default, old_option_id)
SELECT tro.trek_id, tr.id, tro.extra_price, tro.is_default, tro.id
FROM transport_options tro
JOIN transports tr ON tr.name = tro.name AND tr.description = tro.description;

-- 5) Repoint existing bookings at the new pivot rows
UPDATE bookings b
JOIN trek_accommodations ta ON ta.old_option_id = b.accommodation_id
SET b.accommodation_id = ta.id;

UPDATE bookings b
JOIN trek_transports tt ON tt.old_option_id = b.transport_id
SET b.transport_id = tt.id;

ALTER TABLE trek_accommodations DROP COLUMN old_option_id;
ALTER TABLE trek_transports DROP COLUMN old_option_id;

-- 6) Swap bookings' foreign keys from the old tables to the new pivot
--    tables (constraint names are auto-generated and vary, so this
--    looks them up dynamically rather than hardcoding a name)
SET @fk1 := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'
  AND COLUMN_NAME = 'accommodation_id' AND REFERENCED_TABLE_NAME = 'accommodation_options' LIMIT 1);
SET @sql1 := IF(@fk1 IS NOT NULL, CONCAT('ALTER TABLE bookings DROP FOREIGN KEY ', @fk1), 'SELECT 1');
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @fk2 := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'
  AND COLUMN_NAME = 'transport_id' AND REFERENCED_TABLE_NAME = 'transport_options' LIMIT 1);
SET @sql2 := IF(@fk2 IS NOT NULL, CONCAT('ALTER TABLE bookings DROP FOREIGN KEY ', @fk2), 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

ALTER TABLE bookings ADD FOREIGN KEY (accommodation_id) REFERENCES trek_accommodations(id);
ALTER TABLE bookings ADD FOREIGN KEY (transport_id) REFERENCES trek_transports(id);

DROP TABLE accommodation_options;
DROP TABLE transport_options;

-- 7) New site_settings for the About page, Google Reviews, and Social Login
--    (INSERT IGNORE — safe to re-run, won't overwrite anything you've edited)
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('about_eyebrow', 'OUR STORY'),
('about_heading', 'GUIDING THE HIMALAYAS SINCE 1997'),
('about_body', 'Sea to Summit Trekking was founded in 1997 by a small group of Kathmandu-based mountain guides who wanted to do things differently -- fully customisable itineraries, transparent pricing, and guides who are actually from the regions they walk through.\n\nNearly three decades on, we have guided thousands of trekkers safely to some of the most remote and beautiful corners of Nepal, from the glacial moraines of Everest Base Camp to the high desert of Upper Mustang. Every trip is still planned by hand, not a template.'),
('about_image', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=900'),
('about_mission_title', 'OUR MISSION'),
('about_mission_body', 'To make world-class Himalayan trekking safe, transparent, and genuinely local -- putting income directly into the communities whose mountains we are lucky enough to walk through.'),
('google_review_link', 'https://www.google.com/search?q=sea+to+summit+trekking+reviews'),
('google_avg_rating', '4.8'),
('google_review_count', '621'),
('google_client_id', ''),
('google_client_secret', ''),
('facebook_app_id', ''),
('facebook_app_secret', '');

-- New reviews/posts tables (only if you're upgrading from a version
-- that didn't have them yet — harmless to run even if you already do)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_name VARCHAR(150) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    review_text TEXT NOT NULL,
    review_date DATE DEFAULT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    excerpt VARCHAR(300) DEFAULT NULL,
    body TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
