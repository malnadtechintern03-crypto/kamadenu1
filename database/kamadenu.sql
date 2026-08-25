-- ==============================================================================
-- KAMADENU GOUSHALA PLATFORM - COMPLETE DATABASE SCHEMA & SEED DATA
-- Version: 1.0 (Phase 1)
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS `hero_slides`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `expense_categories`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `blog_posts`;
DROP TABLE IF EXISTS `blog_categories`;
DROP TABLE IF EXISTS `videos`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `gallery_categories`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `product_categories`;
DROP TABLE IF EXISTS `receipts`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `sponsors`;
DROP TABLE IF EXISTS `adoptions`;
DROP TABLE IF EXISTS `seva_programs`;
DROP TABLE IF EXISTS `cow_notes`;
DROP TABLE IF EXISTS `cow_vaccinations`;
DROP TABLE IF EXISTS `cow_medical_records`;
DROP TABLE IF EXISTS `cow_images`;
DROP TABLE IF EXISTS `cows`;
DROP TABLE IF EXISTS `cow_breeds`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

-- ==============================================================================
-- 1. AUTHENTICATION & ACCESS CONTROL
-- ==============================================================================

CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `slug`, `name`, `description`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full system access, settings, user management, and financials'),
(2, 'manager', 'Goushala Manager', 'Access to cows, medical records, seva, donations, and store orders'),
(3, 'editor', 'Content Editor', 'Access to blog stories, media gallery, videos, and inquiries');

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard password for all demo accounts is "admin123"
INSERT INTO `users` (`id`, `role_id`, `username`, `name`, `email`, `phone`, `password_hash`, `avatar`, `status`) VALUES
(1, 1, 'admin', 'Mahant Shri Radheyshyam', 'admin@kamadenu.org', '+91 9876543210', '$2y$10$mCy.KnT0TDPYPNLAQHahNufNJr2MsKtgW0VeGepqtwH6f6lST7LE6', NULL, 'active'),
(2, 2, 'manager', 'Ramesh Patel (Manager)', 'manager@kamadenu.org', '+91 9876543211', '$2y$10$mCy.KnT0TDPYPNLAQHahNufNJr2MsKtgW0VeGepqtwH6f6lST7LE6', NULL, 'active'),
(3, 3, 'editor', 'Priya Sharma (Seva Editor)', 'editor@kamadenu.org', '+91 9876543212', '$2y$10$mCy.KnT0TDPYPNLAQHahNufNJr2MsKtgW0VeGepqtwH6f6lST7LE6', NULL, 'active');

CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 2. SYSTEM SETTINGS
-- ==============================================================================

CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `group_name` VARCHAR(50) DEFAULT 'general',
  `is_public` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `group_name`, `is_public`) VALUES
('site_name', 'Kamadenu Goushala', 'general', 1),
('site_tagline', 'Preserving Sacred Indigenous Cows with Vedic Care & Love', 'general', 1),
('site_email', 'seva@kamadenugoushala.org', 'general', 1),
('site_phone', '+91 98450 12345', 'general', 1),
('site_whatsapp', '+91 98450 12345', 'general', 1),
('site_address', 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore Rural, Karnataka - 562103', 'general', 1),
('google_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d124414.2882200593!2d77.5833!3d13.3700!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTPCsDIyJzEyLjAiTiA3N8KwMzUnMDAuMCJF!5e0!3m2!1sen!2sin!4v1600000000000', 'general', 1),
('facebook_url', 'https://facebook.com/kamadenugoushala', 'social', 1),
('instagram_url', 'https://instagram.com/kamadenugoushala', 'social', 1),
('youtube_url', 'https://youtube.com/@kamadenugoushala', 'social', 1),
('twitter_url', 'https://twitter.com/kamadenuseva', 'social', 1),
('years_of_seva', '14', 'impact', 1),
('currency_symbol', '₹', 'finance', 1),
('currency_code', 'INR', 'finance', 1),
('razorpay_key_id', 'rzp_test_KamadenuTestKey123', 'payment', 0),
('razorpay_key_secret', 'KamadenuSecretKeyTesting2026', 'payment', 0),
('razorpay_mode', 'test', 'payment', 0),
('enable_razorpay', '1', 'payment', 1),
('tax_exemption_info', 'Donations are eligible for 50% Tax Exemption under Section 80G of the Income Tax Act. Registration No: AABTK9812RF20214.', 'donation', 1),
('bank_name', 'State Bank of India', 'bank', 1),
('bank_account_name', 'Kamadenu Goushala Charitable Trust', 'bank', 1),
('bank_account_number', '398201948571', 'bank', 1),
('bank_ifsc', 'SBIN0004281', 'bank', 1),
('bank_branch', 'Nandi Hills Branch, Bangalore', 'bank', 1),
('upi_id', 'kamadenu@sbi', 'bank', 1);

-- ==============================================================================
-- 2B. HOMEPAGE HERO SECTION SLIDES
-- ==============================================================================

CREATE TABLE `hero_slides` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `eyebrow` VARCHAR(100) DEFAULT 'KAMADENU GOUSHALA',
  `title` VARCHAR(255) NOT NULL,
  `subtitle` TEXT DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT 'assets/images/hero-cow.jpg',
  `btn_primary_text` VARCHAR(80) DEFAULT 'Support a Cow',
  `btn_primary_url` VARCHAR(255) DEFAULT '/donate.php',
  `btn_primary_icon` VARCHAR(50) DEFAULT 'bi-heart-fill',
  `btn_secondary_text` VARCHAR(80) DEFAULT 'Explore Our Goushala',
  `btn_secondary_url` VARCHAR(255) DEFAULT '/about.php',
  `btn_secondary_icon` VARCHAR(50) DEFAULT 'bi-compass',
  `badge_text` VARCHAR(100) DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hero_slides` (`id`, `eyebrow`, `title`, `subtitle`, `image_path`, `btn_primary_text`, `btn_primary_url`, `btn_primary_icon`, `btn_secondary_text`, `btn_secondary_url`, `btn_secondary_icon`, `badge_text`, `display_order`, `is_active`) VALUES
(1, 'KAMADENU GOUSHALA • NANDI HILLS', 'Every Life Deserves Care & Dignity.', 'Protecting, healing, and nurturing rescued indigenous cows with unconditional love, Vedic seva, and 24x7 veterinary medicine.', 'assets/images/hero-cow.jpg', 'Support a Rescued Cow', '/donate.php', 'bi-heart-fill', 'Explore Our Sanctuary', '/about.php', 'bi-compass', '80G Tax Exemption Available', 1, 1),
(2, 'SACRED BOS INDICUS HERITAGE', 'Preserving Sacred Indigenous Cows', 'Sanctuary home to over 500+ pure Gir, Sahiwal, Hallikar, and Malnad Gidda breeds thriving peacefully with 100% transparent daily seva.', 'assets/images/breeds/gir.jpg', 'Adopt a Gau Mata', '/adopt.php', 'bi-suit-heart-fill', 'Meet Our Cows', '/cows.php', 'bi-person-badge', '15-Acre Organic Pasture', 2, 1),
(3, 'AUSPICIOUS GAU SEVA & GRĀSA DĀNA', 'Feed a Sacred Cow Today (Grāsa Dāna)', 'Experience divine blessings by sponsoring nutritious green grass, dry jowar husk, Ayurvedic mineral supplements, and emergency ambulance rescues.', 'assets/images/about-goushala.jpg', 'Feed a Cow (from ₹101)', '/feed.php', 'bi-gift-fill', 'Watch Live Darshan', '/videos.php', 'bi-camera-reels', 'Instant 80G Digital Receipt', 3, 1);


-- ==============================================================================
-- 3. COW DIRECTORY & MEDICAL RECORD SYSTEM
-- ==============================================================================

CREATE TABLE `cow_breeds` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `origin_region` VARCHAR(150) DEFAULT NULL,
  `characteristics` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cow_breeds` (`id`, `name`, `slug`, `origin_region`, `characteristics`, `image`, `description`) VALUES
(1, 'Gir', 'gir', 'Saurashtra / Gujarat', 'Prominent convex forehead, long pendulous ears, gentle temperament, world-renowned A2 milk producer', 'assets/images/breeds/gir.jpg', 'The Gir cow is an iconic indigenous breed native to the Gir hills of Gujarat. Known for its distinct rounded hump, deep red/speckled coat, and immense spiritual aura.'),
(2, 'Sahiwal', 'sahiwal', 'Punjab / Haryana Region', 'Reddish-dun coat, loose skin, docile nature, high heat tolerance and disease resistance', 'assets/images/breeds/sahiwal.jpg', 'Sahiwal is one of the most prolific indigenous zebu cattle breeds in India, revered for motherly nurturing and resilient constitution.'),
(3, 'Hallikar', 'hallikar', 'Karnataka (Mysuru / Tumakuru)', 'Long tapering horns, muscular build, agile gait, dark grey to silver hues', 'assets/images/breeds/hallikar.jpg', 'Originating from Karnataka, the Hallikar is a sacred heritage draught breed known for endurance and regal posture.'),
(4, 'Tharparkar', 'tharparkar', 'Thar Desert / Rajasthan', 'White to light grey coat, medium horns, extraordinary desert drought resistance', 'assets/images/breeds/tharparkar.jpg', 'A drought-hardy dual purpose indigenous breed that thrives peacefully with profound gratitude even in arid conditions.'),
(5, 'Rathi', 'rathi', 'North-Western Rajasthan', 'Brown and white patches, medium size, sweet and calm demeanor', 'assets/images/breeds/rathi.jpg', 'Revered in Rajasthan for sweet temperament and calm disposition in holy Ashrams and Goushalas.'),
(6, 'Malnad Gidda', 'malnad-gidda', 'Western Ghats, Karnataka', 'Compact miniature stature, black or brown coat, extremely agile and mineral-rich A2 milk', 'assets/images/breeds/malnad-gidda.jpg', 'A dwarf indigenous breed native to the rainforest Western Ghats of Karnataka, highly resistant to monsoon ailments.');

CREATE TABLE `cows` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `breed_id` INT UNSIGNED NOT NULL,
  `gender` ENUM('female', 'male', 'calf_female', 'calf_male') NOT NULL DEFAULT 'female',
  `date_of_birth` DATE DEFAULT NULL,
  `rescue_date` DATE NOT NULL,
  `rescue_story` TEXT DEFAULT NULL,
  `health_status` ENUM('healthy', 'under_treatment', 'critical', 'recovering', 'elderly_care') DEFAULT 'healthy',
  `status` ENUM('active', 'adopted', 'transferred', 'deceased') DEFAULT 'active',
  `is_featured` TINYINT(1) DEFAULT 0,
  `main_image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`breed_id`) REFERENCES `cow_breeds` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cows` (`id`, `cow_code`, `name`, `slug`, `breed_id`, `gender`, `date_of_birth`, `rescue_date`, `rescue_story`, `health_status`, `status`, `is_featured`, `main_image`, `description`) VALUES
(1, 'KG-2023-01', 'Kamadhenu', 'kamadhenu', 1, 'female', '2019-04-12', '2023-01-15', 'Rescued from an illegal transit vehicle near the highway checkpoint. She was malnourished with a severe horn fracture. Today she is radiant, joyful, and leads our morning goshala prayers.', 'healthy', 'active', 1, 'assets/images/cows/kamadhenu.jpg', 'Our matriarch cow with majestic curved horns and a deeply calm, loving presence. She loves fresh jaggery and green grass treats from visitors.'),
(2, 'KG-2023-04', 'Nandini', 'nandini', 1, 'female', '2020-08-20', '2023-03-10', 'Found abandoned near a temple perimeter during severe monsoon rains. Treated for respiratory distress and leg infection. Fully rejuvenated and mother to little Krishna.', 'healthy', 'adopted', 1, 'assets/images/cows/nandini.jpg', 'A gentle Gir cow with expressive eyes. She is currently adopted by the Sharma family and enjoys daily Vedic grooming.'),
(3, 'KG-2023-09', 'Ganga', 'ganga', 2, 'female', '2018-11-05', '2023-06-22', 'Rescued during a distress distress call from a closed dairy farm where older cattle were neglected. She received complete nutritional rehabilitation.', 'healthy', 'active', 1, 'assets/images/cows/ganga.jpg', 'Revered for her tranquil nature and deep reddish coat. Ganga enjoys walking in our open herbal pasture under the morning sun.'),
(4, 'KG-2024-02', 'Balaram', 'balaram', 3, 'male', '2021-02-14', '2024-01-18', 'A regal Hallikar bull rescued from illegal cattle trafficking. He had multiple lacerations and severe fear of humans. Now affectionate and energetic.', 'recovering', 'active', 1, 'assets/images/cows/balaram.jpg', 'A majestic Hallikar bull with sharp tapering horns and immense strength. He is undergoing gentle behavioral bonding and loves carrot treats.'),
(5, 'KG-2024-05', 'Surabhi', 'surabhi', 4, 'female', '2017-03-30', '2024-03-05', 'Surabhi is a senior Tharparkar mother cow rescued from a drought-hit village where feed had completely run out.', 'elderly_care', 'active', 1, 'assets/images/cows/surabhi.jpg', 'An elderly white mother cow receiving special warm mash diets, calcium supplements, and cushioned bedding in our senior sanctuary shed.'),
(6, 'KG-2024-08', 'Gauri', 'gauri', 5, 'female', '2022-05-19', '2024-05-11', 'Found trapped in a construction ditch with deep leg wounds. Our emergency ambulance rescued her within 45 minutes.', 'healthy', 'active', 0, 'assets/images/cows/gauri.jpg', 'A sweet, playful Rathi cow with distinctive spotted markings. Fully healed and roaming the open pastures happily.'),
(7, 'KG-2024-11', 'Gopal (Calf)', 'gopal', 6, 'calf_male', '2024-02-10', '2024-02-10', 'Born in our sanctuary shelter to our rescued Malnad Gidda mother. He is the lively star of our sanctuary.', 'healthy', 'active', 1, 'assets/images/cows/gopal.jpg', 'A joyful little Malnad Gidda bull calf who skips around playfully and loves greeting visitors with his little wet nose.'),
(8, 'KG-2024-14', 'Tulsi', 'tulsi', 2, 'female', '2019-09-15', '2024-08-01', 'Rescued from plastic ingestion crisis. Successfully operated by our veterinary surgeon team and 14kg of plastic waste was removed.', 'under_treatment', 'active', 0, 'assets/images/cows/tulsi.jpg', 'Currently under veterinary observation and digestive probiotic diet. Recovering steadily with high spirits.');

CREATE TABLE `cow_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cow_images` (`cow_id`, `image_path`, `caption`, `is_primary`) VALUES
(1, 'assets/images/cows/kamadhenu_1.jpg', 'Kamadhenu enjoying morning herbal feed', 1),
(1, 'assets/images/cows/kamadhenu_2.jpg', 'Resting under the banyan tree shade', 0),
(2, 'assets/images/cows/nandini_1.jpg', 'Nandini during the Gopashtami festival puja', 1),
(3, 'assets/images/cows/ganga_1.jpg', 'Ganga in the open green pasture', 1),
(4, 'assets/images/cows/balaram_1.jpg', 'Balaram showcasing Hallikar regal posture', 1);

CREATE TABLE `cow_medical_records` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_id` INT UNSIGNED NOT NULL,
  `doctor` VARCHAR(150) NOT NULL,
  `diagnosis` VARCHAR(255) NOT NULL,
  `treatment` TEXT NOT NULL,
  `medicine` TEXT DEFAULT NULL,
  `visit_date` DATE NOT NULL,
  `next_visit` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cow_medical_records` (`cow_id`, `doctor`, `diagnosis`, `treatment`, `medicine`, `visit_date`, `next_visit`, `notes`) VALUES
(1, 'Dr. H. V. Narayana (Chief Vet)', 'Right Horn Fissure & Dehydration (At Rescue)', 'Antiseptic horn dressing, IV fluid rehydration, mineral booster', 'Meloxicam, Enrofloxacin, Multivitamin Bolus', '2023-01-15', '2023-01-22', 'Complete wound closure achieved after 3 weeks. Full health restored.'),
(1, 'Dr. H. V. Narayana (Chief Vet)', 'Annual Comprehensive Health Check', 'General physical check, dewroming, hoof trimming', 'Albendazole 3g, Liver tonic', '2024-01-10', '2025-01-10', 'Excellent vitals, optimal body score, shiny coat.'),
(4, 'Dr. Ananya Rao', 'Deep epidermal lacerations on hind leg', 'Surgical debridement, topical healing spray, bandaging', 'Ceftiofur, Chlorhexidine wash, Himax herbal ointment', '2024-01-18', '2024-02-05', 'Wound healed smoothly. Minor scar remaining, gait 100% normal.'),
(8, 'Dr. H. V. Narayana (Chief Vet)', 'Rumen Impaction due to Foreign Ingesta (Plastic)', 'Rumenotomy surgery, fluid support, ruminal flora restoration', 'Probiotic yeast, Calcium Borogluconate, Flunixin', '2024-08-01', '2024-08-25', 'Surgery successful. Patient eating green grass, steady recovery.');

CREATE TABLE `cow_vaccinations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_id` INT UNSIGNED NOT NULL,
  `vaccine_name` VARCHAR(150) NOT NULL,
  `vaccination_date` DATE NOT NULL,
  `next_due_date` DATE DEFAULT NULL,
  `administered_by` VARCHAR(150) DEFAULT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cow_vaccinations` (`cow_id`, `vaccine_name`, `vaccination_date`, `next_due_date`, `administered_by`, `batch_number`, `notes`) VALUES
(1, 'Foot and Mouth Disease (FMD) Quadrivalent', '2024-02-15', '2024-08-15', 'Dr. Ananya Rao', 'FMD-2024-88', 'Administered bi-annual booster. No adverse reaction.'),
(1, 'Haemorrhagic Septicaemia (HS) + Black Quarter (BQ)', '2024-05-10', '2025-05-10', 'Dr. H. V. Narayana', 'HSBQ-9910', 'Pre-monsoon immunization complete.'),
(2, 'Foot and Mouth Disease (FMD) Quadrivalent', '2024-02-15', '2024-08-15', 'Dr. Ananya Rao', 'FMD-2024-89', 'Booster given on time.'),
(3, 'Foot and Mouth Disease (FMD) Quadrivalent', '2024-02-15', '2024-08-15', 'Dr. Ananya Rao', 'FMD-2024-90', 'Vaccinated successfully.'),
(4, 'Haemorrhagic Septicaemia (HS)', '2024-05-10', '2025-05-10', 'Dr. H. V. Narayana', 'HS-2024-11', 'Administered during recovery phase.');

CREATE TABLE `cow_notes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `note_date` DATE NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 4. GAU SEVA PROGRAMS, DONATIONS & ADOPTIONS
-- ==============================================================================

CREATE TABLE `seva_programs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `suggested_amount` DECIMAL(10,2) NOT NULL DEFAULT 501.00,
  `icon_class` VARCHAR(50) DEFAULT 'bi-heart',
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seva_programs` (`id`, `title`, `slug`, `subtitle`, `description`, `suggested_amount`, `icon_class`, `image`, `display_order`) VALUES
(1, 'Feed a Cow (Grāsa Dāna)', 'feed-a-cow', 'Provide fresh green fodder, nutritional dry husk and jaggery', 'Feeding a sacred cow is considered one of the highest Vedic virtues. Your contribution provides a full day of balanced fresh green grass, jowar dry fodder, protein mash, and mineral supplements.', 501.00, 'bi-flower1', 'assets/images/seva/feed.jpg', 1),
(2, 'Cow Medical Care & Surgery', 'medical-care', 'Sponsor life-saving surgery, antibiotics, and regular veterinary visits', 'Hundreds of rescued cows come to us injured from accidents or illegal handling. This seva covers vet visits, wound surgery, x-rays, bandages, and post-operative medications.', 1001.00, 'bi-bandaid', 'assets/images/seva/medical.jpg', 2),
(3, 'Cow Rescue & Ambulance', 'cow-rescue', 'Fund 24x7 emergency rescue ambulance, fuel, and rapid response gear', 'We operate a 24x7 animal ambulance across the region to rescue abandoned, highway accident, or illegally trafficked cows.', 2501.00, 'bi-truck', 'assets/images/seva/rescue.jpg', 3),
(4, 'Adopt a Cow (Māsa Seva)', 'adopt-a-cow', 'Become a parent guardian for a rescued cow with personalized monthly updates', 'Adopt a specific cow of your choice. You will receive an official Adoption Certificate, photo updates, and are welcome to visit and groom your adopted cow anytime.', 3000.00, 'bi-suit-heart-fill', 'assets/images/seva/adopt.jpg', 4),
(5, 'Lifelong Senior Cow Sponsorship', 'sponsor-a-cow', 'Support aging, blind, and differently-abled elderly cows', 'Elderly non-lactating cows often get abandoned. We ensure they live with sacred dignity until their natural last breath in our specialized senior sanctuary sheds.', 5001.00, 'bi-shield-check', 'assets/images/seva/sponsor.jpg', 5),
(6, 'Shelter & Solar Infrastructure', 'shelter-support', 'Build hygienic sheds, automatic water troughs, and shaded resting paddocks', 'Help expand hygienic shed roofs, eco-friendly solar water heating, and organic bio-gas systems to sustain our ever-growing rescued family.', 10001.00, 'bi-house-heart', 'assets/images/seva/shelter.jpg', 6);

CREATE TABLE `donations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `donation_number` VARCHAR(40) NOT NULL UNIQUE,
  `seva_program_id` INT UNSIGNED DEFAULT NULL,
  `cow_id` INT UNSIGNED DEFAULT NULL,
  `donor_name` VARCHAR(150) NOT NULL,
  `donor_email` VARCHAR(150) NOT NULL,
  `donor_phone` VARCHAR(25) NOT NULL,
  `donor_pan` VARCHAR(20) DEFAULT NULL,
  `donor_address` TEXT DEFAULT NULL,
  `donor_city` VARCHAR(100) DEFAULT NULL,
  `donor_state` VARCHAR(100) DEFAULT NULL,
  `donor_pincode` VARCHAR(20) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `purpose` VARCHAR(255) DEFAULT 'General Gau Seva',
  `is_80g_claimed` TINYINT(1) DEFAULT 1,
  `status` ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seva_program_id`) REFERENCES `seva_programs` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `donations` (`id`, `donation_number`, `seva_program_id`, `cow_id`, `donor_name`, `donor_email`, `donor_phone`, `donor_pan`, `donor_address`, `amount`, `purpose`, `status`) VALUES
(1, 'DON-2024-00101', 1, 1, 'Venkatesh Murthy', 'venkat.m@gmail.com', '+91 9844012345', 'ABCDE1234F', 'Jayanagar 4th Block, Bangalore', 501.00, 'Feed a Cow (Grāsa Dāna)', 'success'),
(2, 'DON-2024-00102', 2, 8, 'Sunita Deshmukh', 'sunita.d@outlook.com', '+91 9822054321', 'BKPD89123G', 'Kothrud, Pune, Maharashtra', 2501.00, 'Tulsi Plastic Surgery Medical Fund', 'success'),
(3, 'DON-2024-00103', 4, 2, 'Aditya Sharma', 'aditya.sharma@yahoo.com', '+91 9988776655', 'CRSPA9081H', 'Indiranagar, Bangalore', 3000.00, 'Monthly Adoption for Nandini', 'success'),
(4, 'DON-2024-00104', 3, NULL, 'Rajeshwari Iyer', 'r.iyer@gmail.com', '+91 9741098765', 'AIYPR1245K', 'Malleswaram, Bangalore', 5001.00, 'Emergency Rescue Fuel Support', 'success');

CREATE TABLE `adoptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `adoption_number` VARCHAR(40) NOT NULL UNIQUE,
  `cow_id` INT UNSIGNED NOT NULL,
  `adopter_name` VARCHAR(150) NOT NULL,
  `adopter_email` VARCHAR(150) NOT NULL,
  `adopter_phone` VARCHAR(25) NOT NULL,
  `adopter_address` TEXT DEFAULT NULL,
  `duration_months` INT NOT NULL DEFAULT 1,
  `monthly_amount` DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `certificate_number` VARCHAR(60) NOT NULL UNIQUE,
  `certificate_issued_at` DATETIME DEFAULT NULL,
  `status` ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `adoptions` (`id`, `adoption_number`, `cow_id`, `adopter_name`, `adopter_email`, `adopter_phone`, `adopter_address`, `duration_months`, `monthly_amount`, `total_amount`, `start_date`, `end_date`, `certificate_number`, `status`) VALUES
(1, 'ADP-2024-001', 2, 'Aditya Sharma', 'aditya.sharma@yahoo.com', '+91 9988776655', 'Indiranagar, Bangalore', 6, 3000.00, 18000.00, '2024-03-10', '2024-09-10', 'KG-CERT-2024-001', 'active'),
(2, 'ADP-2024-002', 1, 'Deepak S. Rao', 'deepak.rao@gmail.com', '+91 9845099887', 'Whitefield, Bangalore', 12, 3000.00, 36000.00, '2024-01-15', '2025-01-15', 'KG-CERT-2024-002', 'active');

CREATE TABLE `sponsors` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cow_id` INT UNSIGNED NOT NULL,
  `sponsor_name` VARCHAR(150) NOT NULL,
  `sponsor_email` VARCHAR(150) DEFAULT NULL,
  `sponsor_phone` VARCHAR(25) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `frequency` ENUM('one_time', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cow_id`) REFERENCES `cows` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sponsors` (`cow_id`, `sponsor_name`, `sponsor_email`, `amount`, `frequency`, `start_date`, `status`) VALUES
(1, 'Deepak S. Rao', 'deepak.rao@gmail.com', 3000.00, 'monthly', '2024-01-15', 'active'),
(2, 'Aditya Sharma', 'aditya.sharma@yahoo.com', 3000.00, 'monthly', '2024-03-10', 'active'),
(5, 'Smt. Gayatri Devi', 'gayatri.d@gmail.com', 5001.00, 'monthly', '2024-04-01', 'active');

CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `reference_type` ENUM('donation', 'adoption', 'order') NOT NULL,
  `reference_id` INT UNSIGNED NOT NULL,
  `gateway` ENUM('razorpay', 'upi', 'bank_transfer', 'cash', 'cheque') DEFAULT 'razorpay',
  `gateway_order_id` VARCHAR(100) DEFAULT NULL,
  `gateway_payment_id` VARCHAR(100) DEFAULT NULL,
  `gateway_signature` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'INR',
  `status` ENUM('created', 'authorized', 'captured', 'refunded', 'failed') DEFAULT 'captured',
  `payment_details_json` TEXT DEFAULT NULL,
  `paid_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `transaction_id`, `reference_type`, `reference_id`, `gateway`, `gateway_payment_id`, `amount`, `status`, `paid_at`) VALUES
(1, 'TXN-20240801-001', 'donation', 1, 'upi', 'UPI-98471209384', 501.00, 'captured', '2024-08-01 10:15:00'),
(2, 'TXN-20240802-002', 'donation', 2, 'razorpay', 'pay_Ob79283749281', 2501.00, 'captured', '2024-08-02 14:30:00'),
(3, 'TXN-20240803-003', 'donation', 3, 'razorpay', 'pay_Ob79998129841', 3000.00, 'captured', '2024-08-03 16:45:00'),
(4, 'TXN-20240804-004', 'donation', 4, 'bank_transfer', 'NEFT-SBIN-89123', 5001.00, 'captured', '2024-08-04 11:20:00');

CREATE TABLE `receipts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `reference_type` ENUM('donation', 'adoption', 'order') NOT NULL,
  `reference_id` INT UNSIGNED NOT NULL,
  `payment_id` INT UNSIGNED DEFAULT NULL,
  `donor_name` VARCHAR(150) NOT NULL,
  `donor_pan` VARCHAR(20) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `tax_exemption_80g` TINYINT(1) DEFAULT 1,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `receipts` (`receipt_number`, `reference_type`, `reference_id`, `payment_id`, `donor_name`, `donor_pan`, `amount`) VALUES
('REC-80G-2024-0101', 'donation', 1, 1, 'Venkatesh Murthy', 'ABCDE1234F', 501.00),
('REC-80G-2024-0102', 'donation', 2, 2, 'Sunita Deshmukh', 'BKPD89123G', 2501.00),
('REC-80G-2024-0103', 'donation', 3, 3, 'Aditya Sharma', 'CRSPA9081H', 3000.00),
('REC-80G-2024-0104', 'donation', 4, 4, 'Rajeshwari Iyer', 'AIYPR1245K', 5001.00);

-- ==============================================================================
-- 5. GOUSHALA PRODUCTS & E-COMMERCE
-- ==============================================================================

CREATE TABLE `product_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `image`, `display_order`) VALUES
(1, 'Vedic A2 Gir Cow Ghee', 'a2-ghee', 'Traditional Bilona churned hand-made golden A2 cultured ghee from free-grazing indigenous Gir cows.', 'assets/images/products/cat_ghee.jpg', 1),
(2, 'Panchagavya & Wellness', 'panchagavya', 'Authentic Ayurvedic formulations prepared following ancient Caraka Samhita guidelines.', 'assets/images/products/cat_wellness.jpg', 2),
(3, 'Natural Gomutra Arka', 'gomutra-products', 'Triple distilled purified Gomutra Arka with potent herbal infusions for natural immunity.', 'assets/images/products/cat_arka.jpg', 3),
(4, 'Organic Farm & Pooja Seva', 'organic-products', 'Natural cow dung dhoop, organic vermicompost, and sacred Vibhuti.', 'assets/images/products/cat_pooja.jpg', 4);

CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `discount_price` DECIMAL(10,2) DEFAULT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) DEFAULT '500ml',
  `main_image` VARCHAR(255) DEFAULT NULL,
  `whatsapp_number` VARCHAR(50) DEFAULT NULL,
  `whatsapp_message` TEXT DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `slug`, `short_description`, `description`, `price`, `discount_price`, `stock_quantity`, `unit`, `main_image`, `is_featured`) VALUES
(1, 1, 'GHEE-A2-500', 'Pure Vedic A2 Gir Cow Bilona Ghee (500ml)', 'pure-vedic-a2-gir-cow-bilona-ghee-500ml', 'Traditional wooden churned cultured curd ghee from free-grazing Gir cows in glass jar.', 'Our Vedic A2 Ghee is prepared using the sacred 5-step Bilona method: boiling raw milk in clay pots, making curd, churning with wooden bilona in clockwise & anticlockwise directions, extracting butter, and slow-simmering over cow-dung cake fire. Rich in A2 beta-casein, butyric acid, and fat-soluble vitamins.', 1450.00, 1299.00, 85, '500 ml', 'assets/images/products/ghee_500.jpg', 1),
(2, 1, 'GHEE-A2-1000', 'Pure Vedic A2 Gir Cow Bilona Ghee (1000ml)', 'pure-vedic-a2-gir-cow-bilona-ghee-1000ml', 'Full 1-litre glass jar of golden aromatic A2 bilona ghee packed with medicinal essence.', 'Premium 1-litre pack of pure medicinal A2 Gir Cow Ghee. Perfect for daily culinary use, ayurvedic remedies, nasya, and sacred Vedic havans.', 2800.00, 2499.00, 45, '1000 ml', 'assets/images/products/ghee_1000.jpg', 1),
(3, 2, 'PAN-WELL-200', 'Ayurvedic Panchagavya Ghrita (200g)', 'ayurvedic-panchagavya-ghrita-200g', 'Potent classical blend of 5 sacred cow elements for cognitive wellness and deep rejuvenation.', 'Prepared strictly under licensed Ayurvedic supervision using indigenous cow milk, curd, ghee, gomutra arka, and purified cow dung extract according to classical texts.', 650.00, 580.00, 30, '200 g', 'assets/images/products/panchagavya.jpg', 0),
(4, 3, 'ARKA-TULSI-500', 'Distilled Gomutra Arka with Holy Tulsi (500ml)', 'distilled-gomutra-arka-tulsi-500ml', 'Micro-filtered copper-distilled cow urine infused with organic Rama Tulsi leaves.', 'Gomutra Arka is celebrated in Ayurveda as a natural detoxifier and rasayana. Triple-distilled and delicately blended with fresh Himalayan tulsi extract.', 240.00, 199.00, 120, '500 ml', 'assets/images/products/arka_tulsi.jpg', 1),
(5, 4, 'DHOOP-SAMB-20', 'Organic Cow Dung Herbal Sambrani Cups (Box of 20)', 'organic-cow-dung-herbal-sambrani-cups', 'Charcoal-free purifying aromatic cups crafted with cow dung, loban, and guggul.', 'Fill your home with sacred temple vibes. 100% natural, chemical-free, cleanses ambient air and repels insects with therapeutic Vedic fragrance.', 320.00, 275.00, 150, 'Box of 20', 'assets/images/products/dhoop_cups.jpg', 1);

CREATE TABLE `product_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `display_order` INT DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_images` (`product_id`, `image_path`, `display_order`) VALUES
(1, 'assets/images/products/ghee_500.jpg', 1),
(2, 'assets/images/products/ghee_1000.jpg', 1);

CREATE TABLE `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `addresses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT UNSIGNED NOT NULL,
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `pincode` VARCHAR(20) NOT NULL,
  `landmark` VARCHAR(150) DEFAULT NULL,
  `is_default` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(40) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED NOT NULL,
  `shipping_address_id` INT UNSIGNED DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_charge` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `order_status` ENUM('placed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'placed',
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `courier_name` VARCHAR(100) DEFAULT NULL,
  `customer_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 6. MEDIA GALLERY & STORIES
-- ==============================================================================

CREATE TABLE `gallery_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery_categories` (`id`, `name`, `slug`) VALUES
(1, 'Cow Rescue Operations', 'cow-rescue'),
(2, 'Daily Goushala Seva', 'daily-seva'),
(3, 'Festivals & Gopashtami', 'festivals'),
(4, 'Veterinary Medical Care', 'medical-care'),
(5, 'Pasture & Nature Sanctuary', 'pasture');

CREATE TABLE `gallery` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` TEXT DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `gallery_categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery` (`category_id`, `title`, `image_path`, `caption`, `display_order`, `is_featured`) VALUES
(2, 'Morning Gomata Puja & Aarti', 'assets/images/gallery/morning_aarti.jpg', 'Daily sunrise prayers and flower offerings at Kamadenu Goushala.', 1, 1),
(1, 'Emergency Ambulance Rescue at Highway', 'assets/images/gallery/rescue_action.jpg', 'Our 24x7 ambulance team safely stabilizing an injured cow.', 2, 1),
(5, 'Sacred Gir Cows Grazing in Lush Pastures', 'assets/images/gallery/pasture_gir.jpg', 'Free-range joyful grazing in our 15-acre organic herbal sanctuary.', 3, 0),
(3, 'Gopashtami Grand Celebration', 'assets/images/gallery/gopashtami.jpg', 'Devotees and children offering jaggery and grass to mother cows.', 4, 1),
(4, 'Veterinary Health Checkup Camp', 'assets/images/gallery/medical_camp.jpg', 'Routine hoof trimming and probiotic nutrition assessment.', 5, 0),
(2, 'Fresh Green Fodder Distribution', 'assets/images/gallery/green_fodder.jpg', 'Nutritional organic fodder cultivated right inside our eco-farm.', 6, 0);

CREATE TABLE `videos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `youtube_url` VARCHAR(255) NOT NULL,
  `youtube_video_id` VARCHAR(50) NOT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `videos` (`title`, `youtube_url`, `youtube_video_id`, `thumbnail`, `description`, `display_order`) VALUES
('A Day in the Life at Kamadenu Goushala', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ', 'assets/images/videos/thumb_1.jpg', 'Experience the peace, morning aarti, feeding routines, and medical care at our sanctuary.', 1),
('From Pain to Peace: The Story of Kamadhenu', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ', 'assets/images/videos/thumb_2.jpg', 'How an injured fractured mother cow found healing and love with us.', 2),
('Traditional Bilona A2 Ghee Making Process', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ', 'assets/images/videos/thumb_3.jpg', 'Step by step Vedic process of wooden churned curd ghee.', 3);

CREATE TABLE `blog_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `blog_categories` (`id`, `name`, `slug`) VALUES
(1, 'Rescue Stories', 'rescue-stories'),
(2, 'Vedic Wisdom & Cow Science', 'cow-science'),
(3, 'Sanctuary Updates', 'sanctuary-updates'),
(4, 'Ayurveda & Health', 'ayurveda-health');

CREATE TABLE `blog_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `excerpt` TEXT NOT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_published` TINYINT(1) DEFAULT 1,
  `views_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON UPDATE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `published_at`, `is_published`) VALUES
(1, 1, 1, 'The Miracle Recovery of Kamadhenu: From Fractures to Faith', 'miracle-recovery-of-kamadhenu', 'When our rescue vehicle arrived on that chilly January night, her chances appeared grim. Today, her serene eyes shower blessings upon every pilgrim.', '<p>On January 15th, 2023, our 24x7 emergency helpline received an urgent call regarding a severely injured cow stranded near the highway bypass. Upon arrival, our veterinary team found a young Gir cow suffering from dehydration and a compound horn injury.</p><p>Through prompt wound sterilization, nutritional fluid administration, and relentless loving care from our goshala caretakers, she made an astonishing recovery over 40 days. Today, named Kamadhenu, she leads the herd into morning grazing with immense grace.</p>', 'assets/images/blog/kamadhenu_story.jpg', '2024-01-20 09:00:00', 1),
(2, 2, 3, 'Why Indigenous Indian Cows (Bos Indicus) Have Humps and A2 Milk', 'why-indigenous-indian-cows-have-humps-and-a2-milk', 'Discover the remarkable Vedic science behind the Suryaketu Nadi in indigenous humped zebu cattle and the health virtues of A2 beta-casein.', '<p>Ancient Vedic texts and modern cellular research converge on the uniqueness of Indian indigenous cattle (Bos Indicus). The prominent hump contains the Suryaketu Nadi, which absorbs beneficial solar rays and enriches the milk with golden carotene and pure A2 beta-casein proteins.</p><p>Unlike hybrid A1 milk varieties, A2 milk is gentle on human digestion, boosts cognitive clarity, and protects against inflammatory distress.</p>', 'assets/images/blog/a2_milk_science.jpg', '2024-03-12 11:30:00', 1),
(3, 4, 3, 'The 5 Sacred Elements of Panchagavya in Classical Ayurveda', '5-sacred-elements-of-panchagavya', 'Exploring how Milk, Curd, Ghee, Gomutra, and Gomaya form the foundation of ecological health and holistic healing.', '<p>Panchagavya is not just a traditional concoction—it is a closed-loop biological marvel that rejuvenates depleted soil, enhances immune defense in humans, and maintains ecological equilibrium in agriculture.</p>', 'assets/images/blog/panchagavya_guide.jpg', '2024-05-18 15:00:00', 1);

CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(25) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `reply_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contact_messages` (`name`, `email`, `mobile`, `subject`, `message`, `is_read`) VALUES
('Suresh Natarajan', 'suresh.n@gmail.com', '+91 9845011223', 'Sanctuary Visit on Sunday with Family', 'Namaste, we would like to visit Kamadenu Goushala this Sunday morning with 6 family members to perform Gau Puja on my mothers 70th birthday. Please confirm visiting timings.', 1),
('Ananya Deshmukh', 'ananya.d@gmail.com', '+91 9823044556', 'Bulk Order of A2 Bilona Ghee for Temple Havan', 'We require 10 liters of pure A2 Gir Cow Ghee for our upcoming temple consecration festival. Kindly share pricing and delivery schedule to Bangalore city.', 0);

CREATE TABLE `testimonials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `designation` VARCHAR(150) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `rating` TINYINT UNSIGNED DEFAULT 5,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `testimonials` (`name`, `designation`, `location`, `content`, `rating`, `avatar`, `is_featured`) VALUES
('Dr. Arvind Shastri', 'Vedic Scholar & Author', 'Bengaluru, Karnataka', 'Visiting Kamadenu Goushala brings an indescribable calmness to the soul. The spotless cleanliness, veterinary dedication, and spiritual sanctity with which each cow is nurtured is deeply commendable.', 5, 'assets/images/testimonials/avatar1.jpg', 1),
('Meera Singhania', 'Regular Monthly Cow Adopter', 'Mumbai, Maharashtra', 'Adopting Nandini has been a truly heartwarming blessing for our family. The monthly photo updates and medical reports give total confidence that every rupee is spent with complete purity.', 5, 'assets/images/testimonials/avatar2.jpg', 1),
('Raghavendra Hegde', 'Organic Farmer & Seva Volunteer', 'Shivamogga, Karnataka', 'Their dedication to preserving rare indigenous breeds like Malnad Gidda and Hallikar is remarkable. A true benchmark for modern Gau Seva institutions in India.', 5, 'assets/images/testimonials/avatar3.jpg', 1);

-- ==============================================================================
-- 7. EXPENSES & FINANCIAL TRANSPARENCY
-- ==============================================================================

CREATE TABLE `expense_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icon_class` VARCHAR(50) DEFAULT 'bi-cash-stack',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `expense_categories` (`id`, `name`, `slug`, `icon_class`) VALUES
(1, 'Green & Dry Cattle Feed', 'feed', 'bi-flower1'),
(2, 'Medicines & Veterinary Surgeries', 'medicine', 'bi-capsule'),
(3, 'Veterinary Doctor & Staff Compensation', 'staff', 'bi-people'),
(4, 'Electricity & Solar Maintenance', 'electricity', 'bi-lightning-charge'),
(5, 'Water Supply & Filtration', 'water', 'bi-droplet'),
(6, 'Sanctuary Shed Maintenance & Upgrades', 'maintenance', 'bi-tools'),
(7, 'Rescue Ambulance & Fuel Transport', 'transport', 'bi-truck'),
(8, 'Miscellaneous Operational Expenses', 'other', 'bi-gear');

CREATE TABLE `expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `vendor_name` VARCHAR(150) DEFAULT NULL,
  `receipt_invoice_file` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `expenses` (`category_id`, `amount`, `expense_date`, `title`, `description`, `vendor_name`) VALUES
(1, 45000.00, '2024-07-05', 'Bulk Organic Green Grass & Jowar Husk Purchase', '15 tonnes of premium fresh fodder delivered for all sheds', 'Sri Manjunatha Agro Farms'),
(2, 18500.00, '2024-07-12', 'Veterinary Surgical Supplies & Emergency Antibiotics', 'IV fluids, sterile dressings, emergency deworming kits', 'Sanjivani Animal Pharma'),
(7, 8400.00, '2024-07-18', '24x7 Animal Ambulance Diesel & Service', 'Fuel for 14 rescue calls and vehicle maintenance', 'Indian Oil Corporation'),
(6, 12000.00, '2024-07-25', 'Monsoon Waterproof Shed Roofing Repair', 'Reinforced roofing sheets for elderly cow shed B', 'Shiva Building Materials'),
(1, 48000.00, '2024-08-03', 'Monthly Cattle Feed & Mineral Nutrient Mix', 'Balanced protein mash and cattle mineral lick blocks', 'Karnataka Agro Feeds');

-- ==============================================================================
-- INDEXES FOR MAXIMUM QUERY PERFORMANCE
-- ==============================================================================

CREATE INDEX idx_cows_status_breed ON `cows` (`status`, `breed_id`);
CREATE INDEX idx_cows_health ON `cows` (`health_status`);
CREATE INDEX idx_cows_featured ON `cows` (`is_featured`);
CREATE INDEX idx_donations_status_created ON `donations` (`status`, `created_at`);
CREATE INDEX idx_payments_ref ON `payments` (`reference_type`, `reference_id`);
CREATE INDEX idx_adoptions_status ON `adoptions` (`status`, `end_date`);
CREATE INDEX idx_products_category ON `products` (`category_id`, `is_active`);
CREATE INDEX idx_blog_published ON `blog_posts` (`is_published`, `published_at`);
CREATE INDEX idx_expenses_date_category ON `expenses` (`expense_date`, `category_id`);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
