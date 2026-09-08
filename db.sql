-- ============================================================
-- Sea to Summit Trekking - Database Schema + Seed Data (v3)
-- Import this whole file via phpMyAdmin (Import tab) or:
--   mysql -u root -p < db.sql
--
-- This version replaces per-trek accommodation/transport rows with
-- a reusable GLOBAL CATALOG (accommodations / transports) that gets
-- attached to treks via pivot tables (trek_accommodations /
-- trek_transports), each with its own price for that trek. It also
-- adds a structured, expandable itinerary, rich-text trek content,
-- and social login support for customers.
-- ============================================================

CREATE DATABASE IF NOT EXISTS seatosummit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE seatosummit;

-- ------------------------------------------------------------
-- Admins (for /login.php -> /admin/dashboard.php)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin login: username = admin / password = admin123
INSERT INTO admins (username, password_hash) VALUES
('admin', '$2b$12$9PA5b.v2n1eTjXAePTOHj.GQWYdrbPkd1kVIjhsOolPsh8Cr5lyey');

-- ------------------------------------------------------------
-- Customers (public account login) — supports email/password
-- AND social login (Google / Facebook). password_hash is nullable
-- for accounts created purely via social login.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    google_id VARCHAR(100) DEFAULT NULL UNIQUE,
    facebook_id VARCHAR(100) DEFAULT NULL UNIQUE,
    avatar_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Treks
-- ------------------------------------------------------------
DROP TABLE IF EXISTS treks;
CREATE TABLE treks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    product_type ENUM('trek','tour') NOT NULL DEFAULT 'trek',
    region VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Nepal',
    difficulty ENUM('Easy','Moderate','Challenging','Extreme') NOT NULL,
    duration_days INT NOT NULL,
    max_altitude INT NOT NULL,
    best_season VARCHAR(100) NOT NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
    review_count INT NOT NULL DEFAULT 0,
    base_price DECIMAL(10,2) NOT NULL,
    badge VARCHAR(50) DEFAULT NULL,
    image_url VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,        -- rich-text HTML (from the admin editor)
    highlights TEXT NOT NULL,         -- rich-text HTML (from the admin editor)
    includes_list TEXT,               -- newline separated inclusions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Itinerary days — one row per day, each with a short summary
-- (always visible) and an optional longer detail description
-- (shown when the visitor expands that day on the trek page).
-- ------------------------------------------------------------
DROP TABLE IF EXISTS itinerary_days;
CREATE TABLE itinerary_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    day_label VARCHAR(100) NOT NULL,      -- e.g. "Day 1"
    title VARCHAR(200) NOT NULL,          -- e.g. "Fly to Lukla, trek to Phakding"
    short_desc VARCHAR(300) DEFAULT NULL, -- one-liner, always visible
    detail_desc TEXT DEFAULT NULL,        -- longer text, shown when expanded
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Accommodations (GLOBAL catalog — manage independently in admin,
-- then attach to any trek)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS accommodations;
CREATE TABLE accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Transports (GLOBAL catalog — same idea as accommodations)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS transports;
CREATE TABLE transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Pivot: which accommodations are attached to which trek, and at
-- what price for THAT trek (defaults to the catalog price, but can
-- be overridden per trek).
-- ------------------------------------------------------------
DROP TABLE IF EXISTS trek_accommodations;
CREATE TABLE trek_accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    extra_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Pivot: same idea for transports
-- ------------------------------------------------------------
DROP TABLE IF EXISTS trek_transports;
CREATE TABLE trek_transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    transport_id INT NOT NULL,
    extra_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE,
    FOREIGN KEY (transport_id) REFERENCES transports(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Reviews (manually-curated Google-style reviews shown on homepage)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
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

-- ------------------------------------------------------------
-- Posts (announcements / news / trail info)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
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

INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES
('welcome-to-our-new-site', 'Welcome to our new website', 'We have rebuilt our site from the ground up to make planning your trek easier.', 'We are excited to launch our redesigned website, built to make it simpler to browse treks, customise your accommodation and transport, and book online. If you run into any issues or have suggestions, reach out via WhatsApp -- we would love to hear from you.', 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?w=900', 1, CURDATE()),
('autumn-2026-trail-conditions', 'Autumn 2026 trail conditions update', 'Current conditions across the Khumbu and Annapurna regions ahead of peak season.', 'Trails across the Khumbu and Annapurna regions are in excellent condition heading into the autumn season. Teahouses are open at all standard stops, and both Thorong La and the routes toward Everest Base Camp are clear. As always, our guides carry altitude-monitoring equipment and a full first-aid kit on every departure.', 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=900', 1, CURDATE());

-- ------------------------------------------------------------
-- Bookings
-- accommodation_id / transport_id point at the PIVOT tables
-- (trek_accommodations / trek_transports), since that's what
-- carries the trek-specific price at the time of booking.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trek_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    transport_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    group_size INT NOT NULL DEFAULT 1,
    start_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trek_id) REFERENCES treks(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES trek_accommodations(id),
    FOREIGN KEY (transport_id) REFERENCES trek_transports(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Site settings (key/value store powering "Edit Website" + social login)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS site_settings;
CREATE TABLE site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Sea to Summit'),
('site_name_sub', 'Trekking'),
('whatsapp_number', '9779800000000'),

('hero_bg_image', 'https://images.unsplash.com/photo-1517824806704-9040b037703b?w=1600'),
('hero_eyebrow', 'SINCE 1997 &middot; HIMALAYAN SPECIALISTS'),
('hero_line1', 'SEA TO'),
('hero_line2', 'SUMMIT'),
('hero_line3', 'TREKKING.'),
('hero_description', 'Expert-guided treks through Nepal''s most extraordinary landscapes. Fully customisable routes, accommodation, and transport since 1997.'),

('stat_years', '27+'),
('stat_years_label', 'YEARS EXPERIENCE'),
('stat_trekkers', '4,800+'),
('stat_trekkers_label', 'HAPPY TREKKERS'),
('stat_routes', '28'),
('stat_routes_label', 'TREK ROUTES'),
('stat_safety', '100%'),
('stat_safety_label', 'SAFETY RECORD'),

('why_eyebrow', 'WHY SEA TO SUMMIT TREKKING'),
('why_heading_line1', 'BUILT FOR THE'),
('why_heading_accent', 'SERIOUS'),
('why_heading_line2', 'TREKKER'),
('why_image', 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=900'),
('feature1_title', 'FULLY CUSTOMISABLE'),
('feature1_desc', 'Choose your hotel tier, transportation, group size, and dates. Build your ideal expedition.'),
('feature2_title', 'EXPERT LOCAL GUIDES'),
('feature2_desc', 'All guides are certified, English-speaking, and from the trekking regions themselves.'),
('feature3_title', 'SAFETY FIRST'),
('feature3_desc', 'Altitude monitoring, satellite communication, and comprehensive emergency evacuation plans.'),

('cta_line1', 'READY TO'),
('cta_accent', 'SUMMIT'),
('cta_line2', 'SOMETHING?'),

('footer_extra', 'Kathmandu, Nepal &middot; Since 1997'),

('about_eyebrow', 'OUR STORY'),
('about_heading', 'GUIDING THE HIMALAYAS SINCE 1997'),
('about_body', 'Sea to Summit Trekking was founded in 1997 by a small group of Kathmandu-based mountain guides who wanted to do things differently -- fully customisable itineraries, transparent pricing, and guides who are actually from the regions they walk through.

Nearly three decades on, we have guided thousands of trekkers safely to some of the most remote and beautiful corners of Nepal, from the glacial moraines of Everest Base Camp to the high desert of Upper Mustang. Every trip is still planned by hand, not a template.'),
('about_image', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=900'),
('about_mission_title', 'OUR MISSION'),
('about_mission_body', 'To make world-class Himalayan trekking safe, transparent, and genuinely local -- putting income directly into the communities whose mountains we are lucky enough to walk through.'),

('google_review_link', 'https://www.google.com/search?q=sea+to+summit+trekking+reviews'),
('google_avg_rating', '4.8'),
('google_review_count', '621'),

('google_client_id', ''),
('google_client_secret', ''),
('facebook_app_id', ''),
('facebook_app_secret', ''),

('ght_eyebrow', 'OUR BIGGEST ACHIEVEMENT'),
('ght_heading', 'THE GREAT HIMALAYAN TRAIL'),
('ght_description', 'Our guides have completed the full Great Himalayan Trail across Nepal -- an unbroken high-altitude route running the entire length of the country, east to west, linking the foot of the world''s tallest peaks.'),
('ght_stat1_num', '1,700km'),
('ght_stat1_label', 'TOTAL DISTANCE'),
('ght_stat2_num', '150+'),
('ght_stat2_label', 'DAYS END-TO-END'),
('ght_stat3_num', '8'),
('ght_stat3_label', 'MOUNTAIN RANGES CROSSED'),

('logo_color_path', ''),
('logo_white_path', ''),
('hero_video_path', '');


-- ------------------------------------------------------------
-- Accommodations (global catalog)
-- ------------------------------------------------------------
INSERT INTO accommodations (id, name, description, image_url, default_price) VALUES
(1, 'Teahouse Lodge', 'Traditional mountain teahouses with basic twin rooms and shared facilities.', 'https://images.unsplash.com/photo-1501876725168-00c445821c9e?w=600', 0),
(2, 'Comfort Lodge', 'Upgraded lodge rooms with attached bathroom and hot shower.', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600', 250),
(3, 'Boutique Mountain Stay', 'Curated boutique guesthouses with superior comfort and local cuisine.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600', 650),
(4, 'Luxury Eco-Resort', 'Premium eco-lodges at key stops with en-suite, heated rooms, and dining.', 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=600', 780),
(5, 'Heritage Lodge', 'Traditional Mustangi-style lodges with upgraded comfort.', 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600', 260),
(6, 'Guesthouse Standard', 'Local guesthouses with twin rooms.', 'https://images.unsplash.com/photo-1520277739336-7bf67edfa768?w=600', 0);

-- ------------------------------------------------------------
-- Transports (global catalog)
-- ------------------------------------------------------------
INSERT INTO transports (id, name, description, image_url, default_price) VALUES
(1, 'Local Bus', 'Public shared bus transfer to the trailhead.', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600', 0),
(2, 'Private Jeep', 'Private 4x4 transfer, faster and more comfortable.', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600', 350),
(3, 'Tourist Bus', 'Shared tourist bus Kathmandu-Pokhara, comfortable coaches.', 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600', 0),
(4, 'Domestic Flight', 'Short domestic flight to save travel time.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=600', 200),
(5, 'Scenic Flight (Lukla)', 'Classic 40-minute mountain flight from Kathmandu to Lukla airstrip.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=600', 0),
(6, 'Helicopter Transfer', 'Private helicopter for a faster and more scenic entry and exit.', 'https://images.unsplash.com/photo-1608236465209-9f0387d81f8d?w=600', 1200),
(7, 'Scenic Flight (Jomsom)', 'Short mountain flight from Pokhara to Jomsom.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=600', 0);


-- ------------------------------------------------------------
-- Treks
-- ------------------------------------------------------------
INSERT INTO treks (id, slug, title, region, country, difficulty, duration_days, max_altitude, best_season, rating, review_count, base_price, badge, image_url, description, highlights, includes_list) VALUES

(1, 'everest-base-camp', 'Everest Base Camp', 'Khumbu Region', 'Nepal', 'Challenging', 14, 5364, 'March-May, Sep-Nov', 4.9, 842, 1890.0, 'Most Popular', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=1200', '<p>Stand beneath the world''s highest peak at 5,364 metres. The Everest Base Camp Trek is one of the most iconic adventure journeys on earth &mdash; traversing Sherpa villages, ancient monasteries, and glacial moraines to the foot of Everest herself.</p>', '<ul><li>Views of Everest, Lhotse, Nuptse and Ama Dablam</li><li>Visit Tengboche Monastery</li><li>Sunrise from Kala Patthar (5,545m)</li><li>Sherpa culture and hospitality</li><li>Sagarmatha National Park</li></ul>', 'Airport transfers
Domestic flights (Kathmandu-Lukla-Kathmandu)
Teahouse accommodation
All meals during the trek
Licensed English-speaking guide and porters
Sagarmatha National Park permits
First aid kit and basic medical support'),
(2, 'annapurna-circuit', 'Annapurna Circuit', 'Annapurna Region', 'Nepal', 'Challenging', 18, 5416, 'Oct-Nov, Mar-Apr', 4.8, 621, 1650.0, 'Editor''s Choice', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?w=1200', '<p>Circumnavigate the Annapurna massif through diverse landscapes &mdash; subtropical forests, alpine meadows, and the world''s deepest gorge &mdash; culminating at Thorong La Pass at 5,416m.</p>', '<ul><li>Cross Thorong La Pass at 5,416m</li><li>Muktinath Temple and sacred springs</li><li>Tatopani hot springs</li><li>Diverse landscapes from jungle to desert</li><li>Traditional Gurung and Thakali villages</li></ul>', 'Ground transportation to/from trailhead
Teahouse accommodation
All meals during the trek
Licensed English-speaking guide and porters
Annapurna Conservation Area permits (ACAP)
TIMS card'),
(3, 'langtang-valley', 'Langtang Valley Trek', 'Langtang Region', 'Nepal', 'Moderate', 10, 4984, 'Mar-May, Sep-Dec', 4.7, 358, 980.0, 'Best Value', 'https://images.unsplash.com/photo-1516481352817-1e5efaa39e6a?w=1200', '<p>Known as the &ldquo;valley of glaciers&rdquo;, Langtang offers dramatic mountain scenery just a short drive from Kathmandu, combined with rich Tamang culture and the chance to visit the sacred Gosainkunda lakes.</p>', '<ul><li>Close to Kathmandu, less crowded trails</li><li>Langtang Lirung views (7,227m)</li><li>Kyanjin Gompa monastery</li><li>Tamang heritage villages</li><li>Optional side trip to Kyanjin Ri</li></ul>', 'Ground transportation to/from trailhead
Teahouse accommodation
All meals during the trek
Licensed English-speaking guide and porters
Langtang National Park permits
TIMS card'),
(4, 'manaslu-circuit', 'Manaslu Circuit', 'Manaslu Region', 'Nepal', 'Extreme', 16, 5106, 'Sep-Nov, Mar-Apr', 4.8, 214, 2100.0, NULL, 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1200', '<p>A remote, restricted-area trek circling the eighth-highest mountain on earth. Fewer crowds, dramatic scenery, and genuine Himalayan wilderness await on this off-the-beaten-path classic.</p>', '<ul><li>Cross the remote Larkya La Pass (5,106m)</li><li>Restricted area &mdash; special permit required</li><li>Views of Manaslu (8,163m)</li><li>Remote Tibetan-influenced villages</li><li>Fewer crowds than Everest/Annapurna</li></ul>', 'Ground transportation to/from trailhead
Teahouse accommodation
All meals during the trek
Licensed guide, porters, and restricted-area permits
Manaslu Conservation Area permit
Special restricted area permit fees'),
(5, 'upper-mustang', 'Upper Mustang Trek', 'Mustang Region', 'Nepal', 'Moderate', 12, 3840, 'Year-round (rain-shadow area)', 4.6, 176, 2350.0, NULL, 'https://images.unsplash.com/photo-1571401835393-8c5f35328320?w=1200', '<p>Explore the former Kingdom of Lo, a high-altitude desert landscape of eroded canyons, ancient cave monasteries, and the walled city of Lo Manthang &mdash; one of the last strongholds of traditional Tibetan Buddhist culture.</p>', '<ul><li>Walled city of Lo Manthang</li><li>Ancient cave monasteries and cliff dwellings</li><li>Dramatic desert-like landscape</li><li>Rain-shadow region &mdash; trekkable in monsoon</li><li>Rich Tibetan Buddhist culture</li></ul>', 'Domestic flights (Jomsom sectors)
Teahouse/guesthouse accommodation
All meals during the trek
Licensed guide and restricted-area permit fees
Upper Mustang special permit
Annapurna Conservation Area permit');


-- ------------------------------------------------------------
-- Trek <-> Accommodation attachments
-- ------------------------------------------------------------
INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default) VALUES

(1, 1, 0, 1),
(1, 2, 280, 0),
(1, 4, 780, 0),
(2, 1, 0, 1),
(2, 2, 240, 0),
(2, 3, 680, 0),
(3, 1, 0, 1),
(3, 2, 150, 0),
(4, 1, 0, 1),
(4, 2, 320, 0),
(5, 6, 0, 1),
(5, 5, 260, 0);


-- ------------------------------------------------------------
-- Trek <-> Transport attachments
-- ------------------------------------------------------------
INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default) VALUES

(1, 5, 0, 1),
(1, 6, 1200, 0),
(2, 3, 0, 1),
(2, 2, 450, 0),
(2, 4, 180, 0),
(3, 1, 0, 1),
(3, 2, 220, 0),
(4, 2, 0, 1),
(4, 1, -60, 0),
(5, 7, 0, 1),
(5, 2, -150, 0);


-- ------------------------------------------------------------
-- Itinerary days
-- ------------------------------------------------------------
INSERT INTO itinerary_days (trek_id, sort_order, day_label, title, short_desc, detail_desc) VALUES

(1, 0, 'Day 1', 'Fly to Lukla, trek to Phakding', 'Short scenic flight followed by an easy first day on the trail.', 'Depart Kathmandu on an early mountain flight to Lukla (2,860m), one of the most scenic short flights in the world. From there it''s a gentle 3-4 hour walk down to Phakding, following the Dudh Koshi river, giving your body an easy start before the climbing begins.'),
(1, 1, 'Day 2', 'Trek to Namche Bazaar', 'A steady climb into the unofficial capital of the Khumbu.', 'Today''s trail crosses several suspension bridges over the Dudh Koshi before a steep switchback climb up to Namche Bazaar (3,440m), the main Sherpa trading town and your first proper taste of Khumbu altitude.'),
(1, 2, 'Day 3', 'Acclimatisation day in Namche', NULL, 'A rest day built into the schedule for safe acclimatisation. Most trekkers hike up to the Everest View Hotel or the Khumjung valley for the afternoon, then return to Namche to sleep.'),
(1, 3, 'Day 4', 'Trek to Tengboche', NULL, 'The trail drops to the river then climbs steadily to Tengboche (3,860m), home to the region''s most famous monastery and, on a clear day, spectacular views of Ama Dablam.'),
(1, 4, 'Day 5', 'Trek to Dingboche', NULL, 'Continuing up the valley, the trees thin out and the terrain opens up as you reach Dingboche (4,410m), a farming village at the edge of the alpine zone.'),
(1, 5, 'Day 6', 'Acclimatisation day in Dingboche', NULL, 'A second acclimatisation day, typically spent hiking up Nangkartshang Peak for sweeping views before returning to Dingboche.'),
(1, 6, 'Day 7', 'Trek to Lobuche', NULL, 'A shorter but higher-altitude day, walking past memorials to climbers lost on Everest before reaching Lobuche (4,940m).'),
(1, 7, 'Day 8', 'Trek to Gorak Shep, visit Everest Base Camp', 'The big day &mdash; reaching Base Camp itself.', 'An early start to Gorak Shep (5,164m) followed by the trek out to Everest Base Camp (5,364m) itself, weaving across the Khumbu Glacier''s rocky moraine to stand where expeditions begin their summit attempts.'),
(1, 8, 'Day 9', 'Hike Kala Patthar, trek down to Pheriche', 'Sunrise views of Everest from Kala Patthar.', 'A pre-dawn hike up Kala Patthar (5,545m) for the best close-up sunrise views of Everest''s summit anywhere on the trek, then a long descent down to Pheriche.'),
(1, 9, 'Day 10', 'Trek to Namche Bazaar', NULL, 'Retracing the trail back down through Tengboche to Namche Bazaar, with noticeably easier breathing as the altitude drops.'),
(1, 10, 'Day 11', 'Trek to Lukla', NULL, 'The final trekking day, descending back through Phakding to Lukla.'),
(1, 11, 'Day 12', 'Fly back to Kathmandu', NULL, 'Morning flight back to Kathmandu, with the rest of the day free to rest or explore the city.'),
(1, 12, 'Day 13', 'Contingency / rest day', NULL, 'A buffer day built in for flight delays (common in the mountains), or simply a free day in Kathmandu.'),
(1, 13, 'Day 14', 'Departure', NULL, 'Transfer to the airport for your onward flight home.'),
(2, 0, 'Day 1', 'Drive to Besisahar, trek to Bhulbhule', NULL, 'Drive from Kathmandu to Besisahar then continue by local jeep to Bhulbhule, where the trek officially begins.'),
(2, 1, 'Day 2-6', 'Trek through Chame, Pisang, Manang', 'Gradual ascent through pine forests into the high Manang valley.', 'Five days of steady walking through Chame, Pisang and on to Manang, moving from lush forest into the drier, high-altitude Manang valley with the Annapurna range towering overhead.'),
(2, 2, 'Day 7', 'Acclimatisation in Manang', NULL, 'A rest day in Manang (3,540m), usually spent hiking up to nearby viewpoints or the Gangapurna glacier lake.'),
(2, 3, 'Day 8-9', 'Trek to Thorong Phedi', NULL, 'Continuing up the valley to Thorong Phedi, the last stop before the pass, at the foot of the Thorong La climb.'),
(2, 4, 'Day 10', 'Cross Thorong La Pass to Muktinath', 'The highest point of the trek.', 'A pre-dawn start for the long, cold climb over Thorong La Pass (5,416m) &mdash; the highlight and highest point of the circuit &mdash; before descending into the high desert of Muktinath.'),
(2, 5, 'Day 11', 'Trek to Jomsom', NULL, 'An easier day descending through the dramatic, wind-swept Kali Gandaki valley to Jomsom.'),
(2, 6, 'Day 12-16', 'Trek through Marpha, Tatopani, Ghorepani', NULL, 'Following the Kali Gandaki further south through apple orchards in Marpha, down to the natural hot springs at Tatopani, then climbing again to Ghorepani.'),
(2, 7, 'Day 17', 'Sunrise at Poon Hill, trek to Nayapul', 'One of Nepal''s most famous sunrise viewpoints.', 'An early climb to Poon Hill for a panoramic sunrise over the Annapurna and Dhaulagiri ranges, before descending to Nayapul to end the trekking portion of the trip.'),
(2, 8, 'Day 18', 'Drive back to Pokhara/Kathmandu', NULL, 'Final drive back to Pokhara (with an optional onward connection to Kathmandu).'),
(3, 0, 'Day 1', 'Drive from Kathmandu to Syabrubesi', NULL, 'A scenic 7-8 hour drive from Kathmandu, winding through the hills to the trailhead town of Syabrubesi.'),
(3, 1, 'Day 2', 'Trek to Lama Hotel', NULL, 'Entering Langtang National Park and following the Langtang Khola through dense rhododendron and bamboo forest.'),
(3, 2, 'Day 3', 'Trek to Langtang Village', NULL, 'The valley opens up as you pass through Langtang Village, rebuilt after the 2015 earthquake, with Langtang Lirung now visible ahead.'),
(3, 3, 'Day 4', 'Trek to Kyanjin Gompa', NULL, 'A short but beautiful day up to Kyanjin Gompa (3,870m), the highlight of the valley, surrounded by glaciers on three sides.'),
(3, 4, 'Day 5', 'Acclimatisation, hike Kyanjin Ri', 'Optional summit day for the best views in the valley.', 'A rest day with an optional early climb up Kyanjin Ri (4,773m) for what many trekkers consider the best panorama of the whole trip.'),
(3, 5, 'Day 6', 'Trek back to Lama Hotel', NULL, 'Retracing the trail back down the valley.'),
(3, 6, 'Day 7', 'Trek to Syabrubesi', NULL, 'The final trekking day, descending back to the trailhead.'),
(3, 7, 'Day 8', 'Drive back to Kathmandu', NULL, 'Return drive to Kathmandu.'),
(3, 8, 'Day 9', 'Contingency day', NULL, 'A buffer day in case of delays, or free time in Kathmandu.'),
(3, 9, 'Day 10', 'Departure', NULL, 'Transfer to the airport for your onward flight home.'),
(4, 0, 'Day 1', 'Drive to Soti Khola', NULL, 'A long but scenic drive from Kathmandu to the trailhead at Soti Khola.'),
(4, 1, 'Day 2-7', 'Trek through Machha Khola, Jagat, Deng, Namrung, Lho, Samagaon', NULL, 'Six days following the Budhi Gandaki river deep into the restricted Manaslu region, passing through increasingly remote villages as Manaslu itself comes into view near Samagaon.'),
(4, 2, 'Day 8', 'Acclimatisation in Samagaon', NULL, 'A rest day, often spent visiting the Pungyen Monastery or a nearby glacier viewpoint.'),
(4, 3, 'Day 9', 'Trek to Samdo', NULL, 'A short day to the last real village before the pass, close to the Tibetan border.'),
(4, 4, 'Day 10', 'Trek to Dharamsala (Larkya Phedi)', NULL, 'A high, exposed day to the basic shelter at Larkya Phedi, positioning for the pass crossing.'),
(4, 5, 'Day 11', 'Cross Larkya La Pass to Bimthang', 'The highest and hardest day of the trek.', 'A very long, pre-dawn day crossing Larkya La Pass (5,106m), the trek''s dramatic high point, before descending into the beautiful Bimthang valley.'),
(4, 6, 'Day 12-15', 'Trek down through Tilije, Dharapani', NULL, 'Descending through forest and farmland, eventually rejoining the lower Annapurna Circuit trail.'),
(4, 7, 'Day 16', 'Drive back to Kathmandu', NULL, 'Final drive back to Kathmandu.'),
(5, 0, 'Day 1', 'Fly to Jomsom, trek to Kagbeni', NULL, 'A short scenic flight to Jomsom followed by an easy walk to Kagbeni, the gateway to Upper Mustang.'),
(5, 1, 'Day 2-4', 'Trek through Chele, Syangbochen, Ghami', NULL, 'Three days deep into the high desert landscape of Upper Mustang, passing eroded cliffs, chortens, and small Tibetan-influenced villages.'),
(5, 2, 'Day 5', 'Trek to Tsarang', NULL, 'Continuing north to Tsarang, home to a large historic dzong (fort) and monastery.'),
(5, 3, 'Day 6', 'Trek to Lo Manthang', 'Arriving at the walled former capital.', 'A relatively short walk to Lo Manthang (3,840m), the walled former capital of the Kingdom of Lo, still enclosed by its original mud-brick walls.'),
(5, 4, 'Day 7', 'Explore Lo Manthang and nearby monasteries', NULL, 'A full day exploring the walled city itself and nearby centuries-old cave monasteries.'),
(5, 5, 'Day 8-11', 'Trek back via Dhakmar, Ghiling, Chhusang', NULL, 'Returning south via a slightly different route, taking in the red cliffs of Dhakmar along the way.'),
(5, 6, 'Day 12', 'Trek to Jomsom, fly to Pokhara', NULL, 'Final walk back to Jomsom followed by a scenic flight to Pokhara.');


-- ------------------------------------------------------------
-- Tours (same treks table, product_type = 'tour')
-- ------------------------------------------------------------
INSERT INTO treks (id, slug, title, product_type, region, country, difficulty, duration_days, max_altitude, best_season, rating, review_count, base_price, badge, image_url, description, highlights, includes_list) VALUES

(6, 'kathmandu-valley-cultural-tour', 'Kathmandu Valley Cultural Tour', 'tour', 'Kathmandu Valley', 'Nepal', 'Easy', 4, 1400, 'Year-round', 4.7, 156, 450.0, 'Cultural Immersion', 'https://images.unsplash.com/photo-1553856622-59b319ca0abd?w=1200', '<p>Explore the living heritage of the Kathmandu Valley &mdash; UNESCO World Heritage durbar squares, ancient stupas, and centuries-old craft traditions, all within a short drive of each other.</p>', '<ul><li>Kathmandu, Patan and Bhaktapur Durbar Squares</li><li>Swayambhunath and Boudhanath stupas</li><li>Traditional pottery and thangka painting workshops</li><li>Local guide fluent in the valley''s history</li><li>No trekking required &mdash; easy paced and comfortable</li></ul>', 'Private vehicle and driver throughout
Hotel accommodation
Daily breakfast
Licensed English-speaking cultural guide
All monument entry fees'),
(7, 'chitwan-jungle-safari', 'Chitwan Jungle Safari', 'tour', 'Chitwan National Park', 'Nepal', 'Easy', 3, 150, 'Oct-Mar', 4.6, 98, 320.0, 'Wildlife', 'https://images.unsplash.com/photo-1544963150-b599c0eaa3cd?w=1200', '<p>Swap the mountains for the jungle on this short lowland safari in Chitwan National Park, home to one-horned rhinos, wild elephants, and if you''re lucky, the elusive Bengal tiger.</p>', '<ul><li>Jungle walks with trained naturalist guides</li><li>Canoe ride along the Rapti River</li><li>Elephant breeding centre visit</li><li>Tharu cultural village and dance show</li><li>Excellent birdwatching year-round</li></ul>', 'Ground transportation to/from Chitwan
Jungle lodge accommodation
All meals during the safari
Naturalist guide and all park activities
Chitwan National Park entry fees');


INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default) VALUES

(6, 2, 0, 1),
(6, 4, 220, 0),
(7, 1, 0, 1),
(7, 3, 180, 0);


INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default) VALUES

(6, 2, 0, 1),
(7, 2, 0, 1),
(7, 4, 90, 0);


INSERT INTO itinerary_days (trek_id, sort_order, day_label, title, short_desc, detail_desc) VALUES

(6, 0, 'Day 1', 'Kathmandu Durbar Square and Swayambhunath', 'Old-city temples and the hilltop ''Monkey Temple''.', 'A gentle walking tour through Kathmandu Durbar Square''s palace complex and temples, followed by a visit to Swayambhunath, one of the oldest Buddhist sites in the valley, with panoramic views over the city.'),
(6, 1, 'Day 2', 'Boudhanath and Patan Durbar Square', NULL, 'Morning visit to Boudhanath, one of the largest stupas in the world and a major hub for the Tibetan Buddhist community, followed by an afternoon exploring the exceptional Newari architecture of Patan Durbar Square.'),
(6, 2, 'Day 3', 'Bhaktapur', 'A well-preserved medieval city.', 'A full day in Bhaktapur, the best-preserved of the valley''s three royal cities, known for its pottery square, wood carving, and traditional Newari life continuing much as it has for centuries.'),
(6, 3, 'Day 4', 'Departure', NULL, 'Free morning for last-minute shopping or a craft workshop, then transfer to the airport for your onward flight.'),
(7, 0, 'Day 1', 'Drive to Chitwan, afternoon village walk', NULL, 'Drive from Kathmandu or Pokhara to Chitwan, arriving in time for a gentle walk through a nearby Tharu village to learn about the indigenous community of the lowlands.'),
(7, 1, 'Day 2', 'Full-day jungle activities', 'Jungle walk, canoe ride, and elephant centre.', 'A full day of activities inside and around the national park: an early jungle walk on the lookout for rhinos and deer, a canoe float down the Rapti River spotting crocodiles and birdlife, and a visit to the elephant breeding centre.'),
(7, 2, 'Day 3', 'Sunrise birdwatching, departure', NULL, 'An early birdwatching walk for the valley''s prolific bird life, then breakfast and transfer back to Kathmandu or onward to your next destination.');


-- ------------------------------------------------------------
-- Mountain profile posts (linked from the Great Himalayan Trail
-- map hotspots on the homepage)
-- ------------------------------------------------------------
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('chyoro-ri', 'Chyoro Ri (6,034m)', 'A remote trekking peak in far-western Nepal, marking the wild western end of the Great Himalayan Trail.', 'Chyoro Ri stands at 6,034 metres in the remote far-west of Nepal, close to Rara Lake and the Shey Phoksundo region -- some of the least-visited high country in the entire Himalaya. Along the Great Himalayan Trail, this section marks the wild western frontier of the route, days from the nearest road, where trails pass through Rara National Park and skirt the edge of Dolpo.

Few trekkers make it this far west, which is exactly the appeal: empty trails, untouched Tibetan-influenced villages, and mountain wilderness that seems to stretch forever. Our western GHT departures include this stretch for trekkers who want to see Nepal well beyond the popular circuits.', 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('dhaulagiri', 'Dhaulagiri (8,167m)', 'The world''s seventh-highest mountain, and one of the most dramatic massifs on the Great Himalayan Trail.', 'At 8,167 metres, Dhaulagiri is the seventh-highest mountain on Earth and the dominant peak of its own range, rising dramatically above the Kali Gandaki -- the deepest gorge in the world. Its name means "White Mountain" in Sanskrit, and on a clear day its enormous south face is visible from as far away as Pokhara.

On the Great Himalayan Trail, the Dhaulagiri massif marks the transition out of the Annapurna region into the trail''s central stretch. The classic Dhaulagiri Circuit, crossing French Col and Dhampus Pass above 5,000 metres, is one of the most demanding and rewarding high routes in Nepal, and forms part of several of our custom western-trail itineraries.', 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('annapurna', 'Annapurna (8,091m)', 'The tenth-highest mountain in the world and the centrepiece of one of Nepal''s most iconic trekking regions.', 'Annapurna I rises to 8,091 metres and gives its name to the entire massif and conservation area that surrounds it -- home to the classic Annapurna Circuit and Annapurna Base Camp treks. The name means "Goddess of the Harvests" in Sanskrit, reflecting its significance to the farming communities in its shadow.

On the Great Himalayan Trail, the Annapurna section is one of the most visually spectacular, with the trail weaving beneath the massif''s southern flank before continuing east toward Manaslu. Our <a href="trek-detail.php?slug=annapurna-circuit">Annapurna Circuit trek</a> covers much of this same ground for trekkers who want the highlights without the full end-to-end GHT commitment.', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('ganesh-himal', 'Ganesh Himal (7,422m)', 'A lesser-visited range named for Ganesh, the elephant-headed Hindu deity, between Manaslu and Langtang.', 'Ganesh Himal reaches 7,422 metres and sits in a quiet stretch of the Himalaya between the more famous Manaslu and Langtang ranges. It''s named for Ganesh, the elephant-headed Hindu god of new beginnings, and the massif has a similarly rounded, distinctive silhouette when viewed from the trail.

Because it lies off the main circuit routes, the Ganesh Himal section of the Great Himalayan Trail sees far fewer trekkers than its neighbours, offering a genuine sense of wilderness and untouched Tamang and Gurung villages along the connecting trails between Manaslu and Langtang.', 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('manaslu', 'Manaslu (8,163m)', 'The eighth-highest mountain in the world, known as the ''Mountain of the Spirit.''', 'Manaslu stands 8,163 metres tall and takes its name from the Sanskrit word for "soul" or "spirit" -- fitting for a mountain long regarded as sacred by the communities who live in its valleys. It was first summited in 1956 by a Japanese expedition, and the Manaslu region remains one of the more remote and restricted parts of Nepal open to trekkers.

The Manaslu Circuit, crossing the dramatic Larkya La Pass at 5,106 metres, is widely considered one of the finest alternatives to the busier Everest and Annapurna routes, and forms a key link in the middle section of the Great Himalayan Trail. See our full <a href="trek-detail.php?slug=manaslu-circuit">Manaslu Circuit itinerary</a> for the standalone version of this route.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('langtang', 'Langtang Lirung (7,234m)', 'The closest major Himalayan peak to Kathmandu, and heart of the ''valley of glaciers.''', 'Langtang Lirung rises to 7,234 metres above the Langtang Valley, often called the "valley of glaciers" and the closest major Himalayan range to Kathmandu -- reachable by road in under a day. The valley was badly affected by the 2015 earthquake, which triggered a landslide that devastated Langtang village; the community has since rebuilt, and the trail welcomes trekkers again.

On the Great Himalayan Trail, Langtang connects the Ganesh Himal section to the high passes of Panch Pokhari and on toward the Everest region, and the standalone <a href="trek-detail.php?slug=langtang-valley">Langtang Valley Trek</a> remains one of our best-value itineraries for trekkers short on time.', 'https://images.unsplash.com/photo-1516481352817-1e5efaa39e6a?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('mount-everest', 'Mount Everest / Sagarmatha (8,848m)', 'The highest point on Earth, and the mountain most Nepal treks are named for.', 'At 8,848 metres, Everest -- known in Nepal as Sagarmatha, "Forehead of the Sky" -- is the highest point on the planet and the mountain that first put Himalayan trekking on the map worldwide. It sits within Sagarmatha National Park, a UNESCO World Heritage Site, surrounded by other giants including Lhotse, Nuptse, and Ama Dablam.

Most trekkers experience Everest not by climbing it but by trekking to Everest Base Camp or the sunrise viewpoint of Kala Patthar, both covered in detail on our <a href="trek-detail.php?slug=everest-base-camp">Everest Base Camp trek</a>. On the Great Himalayan Trail, the Everest region marks roughly the halfway point of the full east-west route.', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('gokyo-ri', 'Gokyo Ri (5,357m)', 'A trekking peak above the turquoise Gokyo Lakes, with one of the best panoramic views in the Khumbu.', 'Gokyo Ri is a 5,357-metre viewpoint above the string of turquoise Gokyo Lakes on the western side of the Khumbu valley, a short but steep climb rewarded with one of the widest panoramas in the entire Everest region -- four peaks above 8,000 metres visible at once, including Everest, Lhotse, Makalu, and Cho Oyu.

Many trekkers combine a visit to Gokyo Ri with the classic Everest Base Camp route via the Cho La Pass, making it a highlight for those with a little extra time in the Khumbu.', 'https://images.unsplash.com/photo-1516481352817-1e5efaa39e6a?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('lhotse', 'Lhotse (8,516m)', 'The fourth-highest mountain in the world, directly connected to Everest by the South Col.', 'Lhotse reaches 8,516 metres, making it the fourth-highest mountain on Earth, and is physically connected to Everest via the South Col -- the two share much of the same climbing route up to that point. Its name means "South Peak" in Tibetan, reflecting its position just south of Everest''s summit.

While Lhotse itself is a serious mountaineering objective rather than a trekking destination, it dominates the skyline throughout the Everest Base Camp trek and is one of the most photographed peaks from Kala Patthar and Gokyo Ri.', 'https://images.unsplash.com/photo-1544198365-f5d60b6d8190?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('makalu', 'Makalu (8,485m)', 'The fifth-highest mountain in the world, a striking four-sided pyramid east of Everest.', 'Makalu stands 8,485 metres tall, the fifth-highest mountain in the world, and is instantly recognisable by its steep, four-sided pyramid shape rising isolated from the surrounding ridgelines east of Everest. Unlike its famous neighbour, Makalu sees relatively few visitors -- the approach trek is longer, wilder, and considerably less developed.

On the Great Himalayan Trail, the Makalu section is among the most physically demanding, crossing high, remote passes with minimal infrastructure -- a highlight for experienced trekkers looking for genuine solitude between the Everest and Kangchenjunga regions.', 'https://images.unsplash.com/photo-1571401835393-8c5f35328320?w=900', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('kangchenjunga', 'Kangchenjunga (8,586m)', 'The third-highest mountain in the world, straddling the Nepal-India border and considered sacred.', 'Kangchenjunga rises to 8,586 metres on the Nepal-India border, the third-highest mountain in the world and the easternmost giant on the Great Himalayan Trail. Its name means "Five Treasures of the Snow," referring to its five distinct summits, and it holds deep spiritual significance for the local Sikkimese and Nepali communities -- by tradition, some early expeditions stopped just short of the true summit out of respect for that belief.

The Kangchenjunga region marks the eastern terminus of the full Great Himalayan Trail, a remote and rarely-trekked corner of Nepal bordering Sikkim, rich in biodiversity and largely untouched by mass tourism.', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?w=900', 1, CURDATE());

-- ------------------------------------------------------------
-- Additional mountain/lake profile posts (Great Himalayan Trail
-- map v2 -- see index.php $ghtPeaks)
-- ------------------------------------------------------------
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('ama-dablam', 'Ama Dablam (6,812m)', 'One of the most photographed mountains in the world, its ridgelines said to resemble a mother''s necklace.', 'Ama Dablam rises to 6,812 metres above the Khumbu valley and is widely considered one of the most beautiful mountains on Earth -- its name translates roughly to "mother''s necklace," describing the hanging glacier on its flank that resembles the traditional double-pendant jewellery worn by Sherpa women.

Unlike its much taller neighbours, Ama Dablam is a serious technical climb despite its modest height, and remains a favourite training peak for climbers preparing for the 8,000-metre giants nearby. For trekkers, it''s one of the constant, unmistakable silhouettes throughout the Everest Base Camp trail, especially striking from Tengboche and Dingboche.', 'assets/images/mountains/full/ama-dablam.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('baruntse', 'Baruntse (7,129m)', 'A striking trekking peak between the Everest and Makalu regions, popular with climbers as a stepping stone to the 8,000ers.', 'Baruntse stands 7,129 metres tall in the remote saddle of territory between the Everest and Makalu regions, connected to both by high, glaciated passes. It''s classified as a trekking peak in Nepal''s permit system, but the climb itself is a genuine mountaineering objective, often used as an acclimatisation and skills climb before an attempt on Everest or Makalu.

Few trekkers pass this way -- reaching Baruntse base camp typically means combining the Everest and Makalu approach trails via the Amphu Labtsa or West Col, making it one of the more adventurous and remote corners of our eastern Nepal itineraries.', 'assets/images/mountains/full/baruntse.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('cho-oyu', 'Cho Oyu (8,188m)', 'The world''s sixth-highest mountain, and often considered the most approachable of the 8,000-metre peaks.', 'Cho Oyu reaches 8,188 metres and sits almost on the Nepal-Tibet border at the head of the Gokyo valley, its name meaning "Turquoise Goddess" in Tibetan. Among the fourteen 8,000-metre peaks, it has a reputation as one of the more technically straightforward to climb from its standard route, which has made it a common choice for climbers attempting their first 8,000er.

For trekkers rather than climbers, Cho Oyu is best known as the towering backdrop to the Gokyo Lakes and Gokyo Ri, visible for days on the western side of the Everest Base Camp / Three Passes routes.', 'assets/images/mountains/full/cho-oyu.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('gokyo-lakes', 'Gokyo Lakes (4,750m)', 'A chain of sacred turquoise glacial lakes in the Gokyo valley, one of the highest freshwater lake systems in the world.', 'The Gokyo Lakes sit at around 4,750 metres in a side valley west of the main Everest Base Camp trail, a chain of six glacial lakes fed by meltwater from the Ngozumpa Glacier -- Nepal''s largest. The lakes are considered sacred by both Hindus and Buddhists, and the largest, Dudh Pokhari, sits beside Gokyo village itself.

Most visitors combine the lakes with a climb up nearby Gokyo Ri for the classic panorama of four 8,000-metre peaks, and the route on to Everest Base Camp via the Cho La Pass makes for one of the finest extended treks in the Khumbu.', 'assets/images/mountains/full/gokyo-lakes.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('kyanjin-ri', 'Kyanjin Ri (4,773m)', 'A relatively accessible viewpoint above Kyanjin Gompa, with some of the best close-up glacier views in the Langtang valley.', 'Kyanjin Ri rises to 4,773 metres directly above Kyanjin Gompa, the highest permanent settlement in the Langtang valley, and is one of the most rewarding short side-climbs in Nepal relative to the effort involved. From the top, Langtang Lirung''s glaciers feel close enough to touch, alongside sweeping views back down the valley.

It''s a natural high point of the standalone Langtang Valley Trek and a common acclimatisation climb before continuing further into the range, making it a highlight for trekkers with only a short window of time near Kathmandu.', 'assets/images/mountains/full/kyanjin-ri.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('mera-peak', 'Mera Peak (6,476m)', 'Nepal''s highest trekking peak, and a popular first major climb for those new to Himalayan mountaineering.', 'Mera Peak tops out at 6,476 metres in the Hinku valley south of Everest, and holds the distinction of being the highest of Nepal''s officially classified "trekking peaks" -- meaning it requires less technical climbing skill than a full mountaineering expedition, while still delivering a genuine, high-altitude summit experience.

From the summit, on a clear day, climbers are rewarded with views of five of the world''s six highest mountains: Everest, Kangchenjunga, Lhotse, Makalu, and Cho Oyu. It''s a common stepping stone for trekkers looking to move into climbing, and one of the more remote approaches in our eastern Nepal programme.', 'assets/images/mountains/full/mera-peak.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('phewa-lake', 'Phewa Lake (742m)', 'Pokhara''s centrepiece lake, gateway to the Annapurna region, with the iconic Tal Barahi Temple at its heart.', 'Phewa Lake sits at a gentle 742 metres in Pokhara, Nepal''s adventure-tourism hub and the usual starting point for Annapurna region treks. On calm mornings the lake mirrors the Annapurna range in the water, and the small Tal Barahi Temple, perched on an island near its shore, is one of the most photographed spots in the city.

For most of our Annapurna trekkers, Phewa Lake is the first and last stop of the trip -- a relaxed bookend of lakeside cafes and mountain views before or after the trail.', 'assets/images/mountains/full/phewa-lake.png', 1, CURDATE());
INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('tilicho-lake', 'Tilicho Lake (4,919m)', 'One of the highest lakes of its size in the world, a dramatic detour on the classic Annapurna Circuit.', 'Tilicho Lake sits at 4,919 metres beneath the sheer walls of the Tilicho Peak massif, and is regularly cited as one of the highest lakes of its size anywhere on Earth. Reaching it involves a dramatic detour off the main Annapurna Circuit trail, crossing an exposed, landslide-prone traverse that is itself one of the more memorable stretches of the route.

The reward is a vast, still expanse of turquoise water ringed by scree and glacier, a genuine highlight for trekkers doing the full Annapurna Circuit rather than the shorter Base Camp trek.', 'assets/images/mountains/full/tilicho-lake.png', 1, CURDATE());

-- Upgrade the artwork on the mountain posts reused from the previous map version
UPDATE posts SET image_url = 'assets/images/mountains/full/annapurna.png' WHERE slug = 'annapurna';
UPDATE posts SET image_url = 'assets/images/mountains/full/everest.png' WHERE slug = 'mount-everest';
UPDATE posts SET image_url = 'assets/images/mountains/full/kanchenjunga.png' WHERE slug = 'kangchenjunga';
UPDATE posts SET image_url = 'assets/images/mountains/full/langtang-lirung.png' WHERE slug = 'langtang';
UPDATE posts SET image_url = 'assets/images/mountains/full/lhotse.png' WHERE slug = 'lhotse';
UPDATE posts SET image_url = 'assets/images/mountains/full/makalu.png' WHERE slug = 'makalu';
UPDATE posts SET image_url = 'assets/images/mountains/full/manaslu.png' WHERE slug = 'manaslu';
