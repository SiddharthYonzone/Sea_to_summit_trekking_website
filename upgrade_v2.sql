-- ============================================================
-- upgrade_v2.sql
--
-- Run this ONLY if you already had the previous version installed
-- (the one with the admin CMS / "Edit Website" panel) and don't want
-- to lose your treks/bookings by re-importing db.sql from scratch.
--
-- If you're starting completely fresh, ignore this file — just
-- import db.sql, it already includes everything below.
--
-- Run once. How to run: phpMyAdmin → select the `seatosummit`
-- database → SQL tab → paste this whole file → Go.
-- ============================================================

USE seatosummit;

-- New: customer accounts (separate login from admin)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- New: link bookings to a customer account (nullable — guest bookings still work)
ALTER TABLE bookings ADD COLUMN customer_id INT DEFAULT NULL AFTER transport_id;
ALTER TABLE bookings ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- New: optional photo per accommodation/transport option (for the Hotels & Transport page)
ALTER TABLE accommodation_options ADD COLUMN image_url VARCHAR(255) DEFAULT NULL;
ALTER TABLE transport_options ADD COLUMN image_url VARCHAR(255) DEFAULT NULL;

-- New: manually-curated Google-style reviews shown on the homepage
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

INSERT INTO reviews (reviewer_name, rating, review_text, review_date, avatar_url, sort_order) VALUES
('Sarah Mitchell', 5, 'Our guide knew every switchback of the Annapurna Circuit and somehow still made every day feel fresh. Genuinely the best-organised trek I have ever done.', '2025-11-02', NULL, 1),
('Daniel Osei', 5, 'Booked Everest Base Camp through Sea to Summit and they handled every detail, from the Lukla flight to altitude monitoring. Would book again in a heartbeat.', '2025-10-14', NULL, 2),
('Priya Nair', 4, 'Langtang Valley was stunning and the teahouses were better than expected. Only reason it is not 5 stars is the WiFi on day 2 was patchy -- not their fault!', '2025-09-28', NULL, 3),
('Tomas Novak', 5, 'Fully customised our Manaslu itinerary around a tight schedule and it went off without a hitch. Professional, safety-first, and genuinely warm people.', '2025-08-19', NULL, 4);

-- New: posts (announcements / trail info page)
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

INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES
('welcome-to-our-new-site', 'Welcome to our new website', 'We have rebuilt our site from the ground up to make planning your trek easier.', 'We are excited to launch our redesigned website, built to make it simpler to browse treks, customise your accommodation and transport, and book online. If you run into any issues or have suggestions, reach out via WhatsApp -- we would love to hear from you.', 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?w=900', 1, CURDATE()),
('autumn-2026-trail-conditions', 'Autumn 2026 trail conditions update', 'Current conditions across the Khumbu and Annapurna regions ahead of peak season.', 'Trails across the Khumbu and Annapurna regions are in excellent condition heading into the autumn season. Teahouses are open at all standard stops, and both Thorong La and the routes toward Everest Base Camp are clear. As always, our guides carry altitude-monitoring equipment and a full first-aid kit on every departure.', 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=900', 1, CURDATE());

-- New: About Us + Google Reviews settings (safe to re-run — INSERT IGNORE)
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('about_eyebrow', 'OUR STORY'),
('about_heading', 'GUIDING THE HIMALAYAS SINCE 1997'),
('about_body', 'Sea to Summit Trekking was founded in 1997 by a small group of Kathmandu-based mountain guides who wanted to do things differently -- fully customisable itineraries, transparent pricing, and guides who are actually from the regions they walk through.\n\nNearly three decades on, we have guided thousands of trekkers safely to some of the most remote and beautiful corners of Nepal, from the glacial moraines of Everest Base Camp to the high desert of Upper Mustang. Every trip is still planned by hand, not a template.'),
('about_image', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=900'),
('about_mission_title', 'OUR MISSION'),
('about_mission_body', 'To make world-class Himalayan trekking safe, transparent, and genuinely local -- putting income directly into the communities whose mountains we are lucky enough to walk through.'),
('google_review_link', 'https://www.google.com/search?q=sea+to+summit+trekking+reviews'),
('google_avg_rating', '4.8'),
('google_review_count', '621');
