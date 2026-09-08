-- ============================================================
-- upgrade_v6.sql
--
-- Run this if you already had the previous version installed (GHT
-- map with the hand-drawn peaks) and want the new version: a real
-- GHT route map image with 15 clickable mountain/lake artwork
-- buttons instead of 11 plain dot markers.
--
-- You ALSO need to copy these files from this update's zip by hand
-- (SQL can't add files):
--   assets/images/ght-map.png            (replaces the old one)
--   assets/images/mountains/*.png         (15 small marker icons)
--   assets/images/mountains/full/*.png    (15 full-size blog header images)
--
-- Run once. How to run: phpMyAdmin -> your database -> SQL tab ->
-- paste this whole file -> Go.
-- ============================================================

USE seatosummit;

INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('ama-dablam', 'Ama Dablam (6,812m)', 'One of the most photographed mountains in the world, its ridgelines said to resemble a mother''s necklace.', 'Ama Dablam rises to 6,812 metres above the Khumbu valley and is widely considered one of the most beautiful mountains on Earth -- its name translates roughly to "mother''s necklace," describing the hanging glacier on its flank that resembles the traditional double-pendant jewellery worn by Sherpa women.

Unlike its much taller neighbours, Ama Dablam is a serious technical climb despite its modest height, and remains a favourite training peak for climbers preparing for the 8,000-metre giants nearby. For trekkers, it''s one of the constant, unmistakable silhouettes throughout the Everest Base Camp trail, especially striking from Tengboche and Dingboche.', 'assets/images/mountains/full/ama-dablam.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('baruntse', 'Baruntse (7,129m)', 'A striking trekking peak between the Everest and Makalu regions, popular with climbers as a stepping stone to the 8,000ers.', 'Baruntse stands 7,129 metres tall in the remote saddle of territory between the Everest and Makalu regions, connected to both by high, glaciated passes. It''s classified as a trekking peak in Nepal''s permit system, but the climb itself is a genuine mountaineering objective, often used as an acclimatisation and skills climb before an attempt on Everest or Makalu.

Few trekkers pass this way -- reaching Baruntse base camp typically means combining the Everest and Makalu approach trails via the Amphu Labtsa or West Col, making it one of the more adventurous and remote corners of our eastern Nepal itineraries.', 'assets/images/mountains/full/baruntse.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('cho-oyu', 'Cho Oyu (8,188m)', 'The world''s sixth-highest mountain, and often considered the most approachable of the 8,000-metre peaks.', 'Cho Oyu reaches 8,188 metres and sits almost on the Nepal-Tibet border at the head of the Gokyo valley, its name meaning "Turquoise Goddess" in Tibetan. Among the fourteen 8,000-metre peaks, it has a reputation as one of the more technically straightforward to climb from its standard route, which has made it a common choice for climbers attempting their first 8,000er.

For trekkers rather than climbers, Cho Oyu is best known as the towering backdrop to the Gokyo Lakes and Gokyo Ri, visible for days on the western side of the Everest Base Camp / Three Passes routes.', 'assets/images/mountains/full/cho-oyu.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('gokyo-lakes', 'Gokyo Lakes (4,750m)', 'A chain of sacred turquoise glacial lakes in the Gokyo valley, one of the highest freshwater lake systems in the world.', 'The Gokyo Lakes sit at around 4,750 metres in a side valley west of the main Everest Base Camp trail, a chain of six glacial lakes fed by meltwater from the Ngozumpa Glacier -- Nepal''s largest. The lakes are considered sacred by both Hindus and Buddhists, and the largest, Dudh Pokhari, sits beside Gokyo village itself.

Most visitors combine the lakes with a climb up nearby Gokyo Ri for the classic panorama of four 8,000-metre peaks, and the route on to Everest Base Camp via the Cho La Pass makes for one of the finest extended treks in the Khumbu.', 'assets/images/mountains/full/gokyo-lakes.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('kyanjin-ri', 'Kyanjin Ri (4,773m)', 'A relatively accessible viewpoint above Kyanjin Gompa, with some of the best close-up glacier views in the Langtang valley.', 'Kyanjin Ri rises to 4,773 metres directly above Kyanjin Gompa, the highest permanent settlement in the Langtang valley, and is one of the most rewarding short side-climbs in Nepal relative to the effort involved. From the top, Langtang Lirung''s glaciers feel close enough to touch, alongside sweeping views back down the valley.

It''s a natural high point of the standalone Langtang Valley Trek and a common acclimatisation climb before continuing further into the range, making it a highlight for trekkers with only a short window of time near Kathmandu.', 'assets/images/mountains/full/kyanjin-ri.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('mera-peak', 'Mera Peak (6,476m)', 'Nepal''s highest trekking peak, and a popular first major climb for those new to Himalayan mountaineering.', 'Mera Peak tops out at 6,476 metres in the Hinku valley south of Everest, and holds the distinction of being the highest of Nepal''s officially classified "trekking peaks" -- meaning it requires less technical climbing skill than a full mountaineering expedition, while still delivering a genuine, high-altitude summit experience.

From the summit, on a clear day, climbers are rewarded with views of five of the world''s six highest mountains: Everest, Kangchenjunga, Lhotse, Makalu, and Cho Oyu. It''s a common stepping stone for trekkers looking to move into climbing, and one of the more remote approaches in our eastern Nepal programme.', 'assets/images/mountains/full/mera-peak.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('phewa-lake', 'Phewa Lake (742m)', 'Pokhara''s centrepiece lake, gateway to the Annapurna region, with the iconic Tal Barahi Temple at its heart.', 'Phewa Lake sits at a gentle 742 metres in Pokhara, Nepal''s adventure-tourism hub and the usual starting point for Annapurna region treks. On calm mornings the lake mirrors the Annapurna range in the water, and the small Tal Barahi Temple, perched on an island near its shore, is one of the most photographed spots in the city.

For most of our Annapurna trekkers, Phewa Lake is the first and last stop of the trip -- a relaxed bookend of lakeside cafes and mountain views before or after the trail.', 'assets/images/mountains/full/phewa-lake.png', 1, CURDATE());
INSERT IGNORE INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES ('tilicho-lake', 'Tilicho Lake (4,919m)', 'One of the highest lakes of its size in the world, a dramatic detour on the classic Annapurna Circuit.', 'Tilicho Lake sits at 4,919 metres beneath the sheer walls of the Tilicho Peak massif, and is regularly cited as one of the highest lakes of its size anywhere on Earth. Reaching it involves a dramatic detour off the main Annapurna Circuit trail, crossing an exposed, landslide-prone traverse that is itself one of the more memorable stretches of the route.

The reward is a vast, still expanse of turquoise water ringed by scree and glacier, a genuine highlight for trekkers doing the full Annapurna Circuit rather than the shorter Base Camp trek.', 'assets/images/mountains/full/tilicho-lake.png', 1, CURDATE());

-- Upgrade artwork on the mountain posts reused from the previous map version
UPDATE posts SET image_url = 'assets/images/mountains/full/annapurna.png' WHERE slug = 'annapurna';
UPDATE posts SET image_url = 'assets/images/mountains/full/everest.png' WHERE slug = 'mount-everest';
UPDATE posts SET image_url = 'assets/images/mountains/full/kanchenjunga.png' WHERE slug = 'kangchenjunga';
UPDATE posts SET image_url = 'assets/images/mountains/full/langtang-lirung.png' WHERE slug = 'langtang';
UPDATE posts SET image_url = 'assets/images/mountains/full/lhotse.png' WHERE slug = 'lhotse';
UPDATE posts SET image_url = 'assets/images/mountains/full/makalu.png' WHERE slug = 'makalu';
UPDATE posts SET image_url = 'assets/images/mountains/full/manaslu.png' WHERE slug = 'manaslu';
