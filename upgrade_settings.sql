-- ============================================================
-- upgrade_settings.sql
--
-- Run this ONLY if you already imported the original db.sql and
-- don't want to lose your existing bookings/treks. It just adds
-- the new site_settings table used by the "Edit Website" admin page.
--
-- If you're starting fresh, ignore this file — just import db.sql,
-- it already includes this table.
--
-- How to run: phpMyAdmin → select the `seatosummit` database →
-- SQL tab → paste this whole file → Go.
-- ============================================================

USE seatosummit;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
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

('footer_extra', 'Kathmandu, Nepal &middot; Since 1997');
