-- ============================================================
-- upgrade_v4.sql
--
-- Run this if you already had the previous version installed (the one
-- with the accommodation/transport catalog, social login, and rich-text
-- trek editor) and want to keep your existing treks and bookings.
--
-- If you're starting completely fresh, ignore this file — just import
-- db.sql, it already includes everything below.
--
-- Run once. How to run: phpMyAdmin → your database → SQL tab → paste
-- this whole file → Go.
--
-- What this does:
--  1. Adds a product_type column to treks (values 'trek' or 'tour') so
--     the same table can power both the Treks and new Tours sections.
--     All your existing rows are set to 'trek' automatically.
--  2. Adds site_settings for the uploadable logo, the optional homepage
--     video, and the new "Great Himalayan Trail" homepage section.
-- ============================================================

USE seatosummit;

ALTER TABLE treks ADD COLUMN product_type ENUM('trek','tour') NOT NULL DEFAULT 'trek' AFTER title;

-- New settings (INSERT IGNORE — safe to re-run, won't overwrite anything you've edited)
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
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

-- Optional: seed two sample tours so the new Tours section isn't empty.
-- Safe to skip/delete afterward from Admin → Tours if you don't want them.
INSERT INTO treks (slug, title, product_type, region, country, difficulty, duration_days, max_altitude, best_season, rating, review_count, base_price, badge, image_url, description, highlights, includes_list) VALUES
('kathmandu-valley-cultural-tour', 'Kathmandu Valley Cultural Tour', 'tour', 'Kathmandu Valley', 'Nepal', 'Easy', 4, 1400, 'Year-round', 4.7, 156, 450.00, 'Cultural Immersion', 'https://images.unsplash.com/photo-1553856622-59b319ca0abd?w=1200',
 '<p>Explore the living heritage of the Kathmandu Valley &mdash; UNESCO World Heritage durbar squares, ancient stupas, and centuries-old craft traditions, all within a short drive of each other.</p>',
 '<ul><li>Kathmandu, Patan and Bhaktapur Durbar Squares</li><li>Swayambhunath and Boudhanath stupas</li><li>Traditional pottery and thangka painting workshops</li><li>Local guide fluent in the valley''s history</li><li>No trekking required &mdash; easy paced and comfortable</li></ul>',
 'Private vehicle and driver throughout\nHotel accommodation\nDaily breakfast\nLicensed English-speaking cultural guide\nAll monument entry fees'),
('chitwan-jungle-safari', 'Chitwan Jungle Safari', 'tour', 'Chitwan National Park', 'Nepal', 'Easy', 3, 150, 'Oct-Mar', 4.6, 98, 320.00, 'Wildlife', 'https://images.unsplash.com/photo-1544963150-b599c0eaa3cd?w=1200',
 '<p>Swap the mountains for the jungle on this short lowland safari in Chitwan National Park, home to one-horned rhinos, wild elephants, and if you''re lucky, the elusive Bengal tiger.</p>',
 '<ul><li>Jungle walks with trained naturalist guides</li><li>Canoe ride along the Rapti River</li><li>Elephant breeding centre visit</li><li>Tharu cultural village and dance show</li><li>Excellent birdwatching year-round</li></ul>',
 'Ground transportation to/from Chitwan\nJungle lodge accommodation\nAll meals during the safari\nNaturalist guide and all park activities\nChitwan National Park entry fees');

-- Attach accommodation/transport catalog items to the two sample tours
-- (uses whichever catalog ids happen to match "Teahouse Lodge" style
-- entries in your database — adjust in Admin → Tours → Edit if needed)
SET @tour1 := (SELECT id FROM treks WHERE slug = 'kathmandu-valley-cultural-tour');
SET @tour2 := (SELECT id FROM treks WHERE slug = 'chitwan-jungle-safari');
SET @accom_basic := (SELECT id FROM accommodations ORDER BY default_price ASC LIMIT 1);
SET @trans_basic := (SELECT id FROM transports ORDER BY default_price ASC LIMIT 1);

INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default)
SELECT @tour1, @accom_basic, 0, 1 WHERE @accom_basic IS NOT NULL;
INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default)
SELECT @tour1, @trans_basic, 0, 1 WHERE @trans_basic IS NOT NULL;
INSERT INTO trek_accommodations (trek_id, accommodation_id, extra_price, is_default)
SELECT @tour2, @accom_basic, 0, 1 WHERE @accom_basic IS NOT NULL;
INSERT INTO trek_transports (trek_id, transport_id, extra_price, is_default)
SELECT @tour2, @trans_basic, 0, 1 WHERE @trans_basic IS NOT NULL;
