-- =====================================================================
-- Kupiana — Seed Data
-- =====================================================================
-- Run AFTER schema.sql:
--     /Applications/XAMPP/bin/mysql -uroot < database/schema.sql
--     /Applications/XAMPP/bin/mysql -uroot < database/seed.sql
--
-- Provides the minimum needed for a working system, plus a small demo
-- catalog so the storefront and admin lists are not empty.
--
-- ACCOUNTS
--     admin@kupiana.test / admin123   (super_admin)
--     staff@kupiana.test / admin123   (manager)
--     user@kupiana.test  / user123    (customer)
--
-- Passwords are bcrypt (password_hash, PASSWORD_BCRYPT). Never MD5.
-- =====================================================================

USE `kupiana`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `role_permissions`;
TRUNCATE TABLE `user_roles`;
TRUNCATE TABLE `permissions`;
TRUNCATE TABLE `roles`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `countries`;
TRUNCATE TABLE `states`;
TRUNCATE TABLE `currencies`;
TRUNCATE TABLE `tax_rates`;
TRUNCATE TABLE `hsn_codes`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `brands`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `product_categories`;
TRUNCATE TABLE `product_images`;
TRUNCATE TABLE `attributes`;
TRUNCATE TABLE `attribute_values`;
TRUNCATE TABLE `tags`;
TRUNCATE TABLE `warehouses`;
TRUNCATE TABLE `suppliers`;
TRUNCATE TABLE `inventory`;
TRUNCATE TABLE `wallets`;
TRUNCATE TABLE `coupons`;
TRUNCATE TABLE `settings`;
TRUNCATE TABLE `email_templates`;
TRUNCATE TABLE `sms_templates`;
TRUNCATE TABLE `pages`;
TRUNCATE TABLE `faqs`;
TRUNCATE TABLE `banners`;
TRUNCATE TABLE `testimonials`;
TRUNCATE TABLE `blog_categories`;

SET FOREIGN_KEY_CHECKS = 1;
SET @now = NOW();

-- =====================================================================
-- Geography, currency, tax
-- =====================================================================

INSERT INTO `countries` (`id`,`name`,`iso2`,`iso3`,`phone_code`,`currency_code`,`created_at`,`updated_at`) VALUES
(1,'India','IN','IND','+91','INR',@now,@now),
(2,'United States','US','USA','+1','USD',@now,@now),
(3,'United Kingdom','GB','GBR','+44','GBP',@now,@now);

-- Indian states with their official GST state codes.
INSERT INTO `states` (`country_id`,`name`,`code`,`created_at`,`updated_at`) VALUES
(1,'Jammu and Kashmir','01',@now,@now),
(1,'Himachal Pradesh','02',@now,@now),
(1,'Punjab','03',@now,@now),
(1,'Chandigarh','04',@now,@now),
(1,'Uttarakhand','05',@now,@now),
(1,'Haryana','06',@now,@now),
(1,'Delhi','07',@now,@now),
(1,'Rajasthan','08',@now,@now),
(1,'Uttar Pradesh','09',@now,@now),
(1,'Bihar','10',@now,@now),
(1,'Sikkim','11',@now,@now),
(1,'Arunachal Pradesh','12',@now,@now),
(1,'Nagaland','13',@now,@now),
(1,'Manipur','14',@now,@now),
(1,'Mizoram','15',@now,@now),
(1,'Tripura','16',@now,@now),
(1,'Meghalaya','17',@now,@now),
(1,'Assam','18',@now,@now),
(1,'West Bengal','19',@now,@now),
(1,'Jharkhand','20',@now,@now),
(1,'Odisha','21',@now,@now),
(1,'Chhattisgarh','22',@now,@now),
(1,'Madhya Pradesh','23',@now,@now),
(1,'Gujarat','24',@now,@now),
(1,'Maharashtra','27',@now,@now),
(1,'Karnataka','29',@now,@now),
(1,'Goa','30',@now,@now),
(1,'Lakshadweep','31',@now,@now),
(1,'Kerala','32',@now,@now),
(1,'Tamil Nadu','33',@now,@now),
(1,'Puducherry','34',@now,@now),
(1,'Andaman and Nicobar Islands','35',@now,@now),
(1,'Telangana','36',@now,@now),
(1,'Andhra Pradesh','37',@now,@now),
(1,'Ladakh','38',@now,@now);

INSERT INTO `currencies` (`code`,`name`,`symbol`,`exchange_rate`,`is_default`,`created_at`,`updated_at`) VALUES
('INR','Indian Rupee','₹',1.000000,1,@now,@now),
('USD','US Dollar','$',0.012000,0,@now,@now);

INSERT INTO `tax_rates` (`id`,`name`,`rate`,`type`,`is_default`,`created_at`,`updated_at`) VALUES
(1,'GST 0%',0.00,'gst',0,@now,@now),
(2,'GST 5%',5.00,'gst',0,@now,@now),
(3,'GST 12%',12.00,'gst',0,@now,@now),
(4,'GST 18%',18.00,'gst',1,@now,@now),
(5,'GST 28%',28.00,'gst',0,@now,@now);

INSERT INTO `hsn_codes` (`code`,`description`,`gst_rate`,`created_at`,`updated_at`) VALUES
('0904','Pepper and dried chillies',5.00,@now,@now),
('0908','Nutmeg, mace and cardamom',5.00,@now,@now),
('0910','Ginger, turmeric and mixed spices',5.00,@now,@now),
('1207','Oil seeds and oleaginous fruits',5.00,@now,@now),
('1508','Groundnut oil and fractions',5.00,@now,@now),
('1513','Coconut oil and fractions',5.00,@now,@now),
('1515','Sesame oil and other fixed vegetable oils',5.00,@now,@now);

-- =====================================================================
-- Roles
-- =====================================================================

INSERT INTO `roles` (`id`,`name`,`slug`,`description`,`is_system`,`created_at`,`updated_at`) VALUES
(1,'Super Administrator','super_admin','Unrestricted access. Bypasses all permission checks.',1,@now,@now),
(2,'Administrator','admin','Full back-office access except system-critical settings.',1,@now,@now),
(3,'Manager','manager','Manages catalog, orders, inventory and customers.',1,@now,@now),
(4,'Staff','staff','Day-to-day order processing and support.',1,@now,@now),
(5,'Customer','customer','Storefront shopper. No back-office access.',1,@now,@now);

-- =====================================================================
-- Permissions
-- =====================================================================
-- Named <module>.<action>. Acl::can() also honours a granted "<module>.*".

INSERT INTO `permissions` (`name`,`slug`,`module`,`action`,`created_at`,`updated_at`) VALUES
-- Dashboard
('View Dashboard','dashboard.view','dashboard','view',@now,@now),
-- Catalog
('View Products','products.view','products','view',@now,@now),
('Create Products','products.create','products','create',@now,@now),
('Edit Products','products.edit','products','edit',@now,@now),
('Delete Products','products.delete','products','delete',@now,@now),
('Export Products','products.export','products','export',@now,@now),
('View Categories','categories.view','categories','view',@now,@now),
('Create Categories','categories.create','categories','create',@now,@now),
('Edit Categories','categories.edit','categories','edit',@now,@now),
('Delete Categories','categories.delete','categories','delete',@now,@now),
('View Brands','brands.view','brands','view',@now,@now),
('Create Brands','brands.create','brands','create',@now,@now),
('Edit Brands','brands.edit','brands','edit',@now,@now),
('Delete Brands','brands.delete','brands','delete',@now,@now),
('View Attributes','attributes.view','attributes','view',@now,@now),
('Manage Attributes','attributes.manage','attributes','manage',@now,@now),
('View Variants','variants.view','variants','view',@now,@now),
('Manage Variants','variants.manage','variants','manage',@now,@now),
('View Tags','tags.view','tags','view',@now,@now),
('Manage Tags','tags.manage','tags','manage',@now,@now),
('View Reviews','reviews.view','reviews','view',@now,@now),
('Moderate Reviews','reviews.moderate','reviews','moderate',@now,@now),
-- Sales
('View Orders','orders.view','orders','view',@now,@now),
('Edit Orders','orders.edit','orders','edit',@now,@now),
('Cancel Orders','orders.cancel','orders','cancel',@now,@now),
('Export Orders','orders.export','orders','export',@now,@now),
('View Shipping','shipping.view','shipping','view',@now,@now),
('Manage Shipping','shipping.manage','shipping','manage',@now,@now),
('View Returns','returns.view','returns','view',@now,@now),
('Manage Returns','returns.manage','returns','manage',@now,@now),
('View Refunds','refunds.view','refunds','view',@now,@now),
('Manage Refunds','refunds.manage','refunds','manage',@now,@now),
('View Invoices','invoices.view','invoices','view',@now,@now),
('Manage Invoices','invoices.manage','invoices','manage',@now,@now),
('View Payments','payments.view','payments','view',@now,@now),
('Manage Payments','payments.manage','payments','manage',@now,@now),
('View Coupons','coupons.view','coupons','view',@now,@now),
('Manage Coupons','coupons.manage','coupons','manage',@now,@now),
('View Offers','offers.view','offers','view',@now,@now),
('Manage Offers','offers.manage','offers','manage',@now,@now),
-- Inventory
('View Inventory','inventory.view','inventory','view',@now,@now),
('Manage Inventory','inventory.manage','inventory','manage',@now,@now),
('View Warehouses','warehouses.view','warehouses','view',@now,@now),
('Manage Warehouses','warehouses.manage','warehouses','manage',@now,@now),
('View Purchases','purchases.view','purchases','view',@now,@now),
('Manage Purchases','purchases.manage','purchases','manage',@now,@now),
('View Suppliers','suppliers.view','suppliers','view',@now,@now),
('Manage Suppliers','suppliers.manage','suppliers','manage',@now,@now),
-- People
('View Customers','customers.view','customers','view',@now,@now),
('Edit Customers','customers.edit','customers','edit',@now,@now),
('Export Customers','customers.export','customers','export',@now,@now),
('View Users','users.view','users','view',@now,@now),
('Manage Users','users.manage','users','manage',@now,@now),
('View Roles','roles.view','roles','view',@now,@now),
('Manage Roles','roles.manage','roles','manage',@now,@now),
('View Permissions','permissions.view','permissions','view',@now,@now),
('Manage Permissions','permissions.manage','permissions','manage',@now,@now),
-- Content
('View CMS','cms.view','cms','view',@now,@now),
('Manage CMS','cms.manage','cms','manage',@now,@now),
('View Banners','banners.view','banners','view',@now,@now),
('Manage Banners','banners.manage','banners','manage',@now,@now),
('View Blog','blog.view','blog','view',@now,@now),
('Manage Blog','blog.manage','blog','manage',@now,@now),
('View Testimonials','testimonials.view','testimonials','view',@now,@now),
('Manage Testimonials','testimonials.manage','testimonials','manage',@now,@now),
('View FAQs','faqs.view','faqs','view',@now,@now),
('Manage FAQs','faqs.manage','faqs','manage',@now,@now),
('View Contact Messages','contacts.view','contacts','view',@now,@now),
('Manage Contact Messages','contacts.manage','contacts','manage',@now,@now),
('View Newsletter','newsletter.view','newsletter','view',@now,@now),
('Manage Newsletter','newsletter.manage','newsletter','manage',@now,@now),
('View SEO','seo.view','seo','view',@now,@now),
('Manage SEO','seo.manage','seo','manage',@now,@now),
-- Insights
('View Reports','reports.view','reports','view',@now,@now),
('Export Reports','reports.export','reports','export',@now,@now),
-- System
('View Notifications','notifications.view','notifications','view',@now,@now),
('Manage Notifications','notifications.manage','notifications','manage',@now,@now),
('View Templates','templates.view','templates','view',@now,@now),
('Manage Templates','templates.manage','templates','manage',@now,@now),
('View Settings','settings.view','settings','view',@now,@now),
('Manage Settings','settings.manage','settings','manage',@now,@now),
('Manage Backups','backups.manage','backups','manage',@now,@now),
('View Audit Logs','audit.view','audit','view',@now,@now);

-- ---------------------------------------------------------------------
-- Role -> permission grants
-- ---------------------------------------------------------------------
-- super_admin (1): every permission. Acl::is_super_admin() short-circuits
-- anyway, but the rows are seeded so the Roles UI shows the truth.
INSERT INTO `role_permissions` (`role_id`,`permission_id`,`created_at`,`updated_at`)
SELECT 1, `id`, @now, @now FROM `permissions`;

-- admin (2): everything except role/permission editing and backups.
INSERT INTO `role_permissions` (`role_id`,`permission_id`,`created_at`,`updated_at`)
SELECT 2, `id`, @now, @now FROM `permissions`
WHERE `slug` NOT IN ('roles.manage','permissions.manage','backups.manage');

-- manager (3): catalog, orders, inventory, customers, reports. Read-only CMS.
INSERT INTO `role_permissions` (`role_id`,`permission_id`,`created_at`,`updated_at`)
SELECT 3, `id`, @now, @now FROM `permissions`
WHERE `module` IN ('dashboard','products','categories','brands','attributes','variants','tags',
                   'reviews','orders','shipping','returns','refunds','invoices','payments',
                   'coupons','offers','inventory','warehouses','purchases','suppliers',
                   'customers','reports','notifications')
  AND `slug` NOT IN ('payments.manage','refunds.manage');

-- staff (4): day-to-day order handling and read-only catalog.
INSERT INTO `role_permissions` (`role_id`,`permission_id`,`created_at`,`updated_at`)
SELECT 4, `id`, @now, @now FROM `permissions`
WHERE `slug` IN ('dashboard.view','products.view','categories.view','brands.view','variants.view',
                 'reviews.view','orders.view','orders.edit','shipping.view','shipping.manage',
                 'returns.view','invoices.view','payments.view','inventory.view',
                 'customers.view','contacts.view','contacts.manage','notifications.view');

-- customer (5): no back-office permissions at all.

-- =====================================================================
-- Users
-- =====================================================================
-- bcrypt hashes generated with password_hash($pw, PASSWORD_BCRYPT).

INSERT INTO `users`
	(`id`,`uuid`,`first_name`,`last_name`,`email`,`phone`,`password`,`user_type`,
	 `email_verified_at`,`status`,`created_at`,`updated_at`) VALUES
(1,'c25d6879-aae7-4ac4-b7cf-ce2832b3aaa7','Kupiana','Admin','admin@kupiana.test','9000000001',
 '$2y$10$VXoyPtlcKnmxrlPS886d8ON9aOz.BqUWEOjgtiqlySiDCENF/Rk2y','staff',@now,'active',@now,@now),
(2,'b69b7914-0a86-43f2-acc3-185572057680','Store','Manager','staff@kupiana.test','9000000002',
 '$2y$10$VXoyPtlcKnmxrlPS886d8ON9aOz.BqUWEOjgtiqlySiDCENF/Rk2y','staff',@now,'active',@now,@now),
(3,'e5ed38ed-428a-43a1-ad06-6773b0deb25f','Demo','Customer','user@kupiana.test','9000000003',
 '$2y$10$ysIGmkY85Wk1l5Lour2WZu49dYi42Br54gYt5SlcSWpSpHzQTERU2','customer',@now,'active',@now,@now);

INSERT INTO `user_roles` (`user_id`,`role_id`,`created_at`,`updated_at`) VALUES
(1,1,@now,@now),
(2,3,@now,@now),
(3,5,@now,@now);

INSERT INTO `wallets` (`user_id`,`balance`,`created_at`,`updated_at`) VALUES
(3,0.00,@now,@now);

-- =====================================================================
-- Warehouse & supplier
-- =====================================================================

INSERT INTO `warehouses`
	(`id`,`name`,`code`,`contact_person`,`phone`,`email`,`address_line1`,`city`,`state`,
	 `state_code`,`postal_code`,`is_default`,`created_at`,`updated_at`) VALUES
(1,'Main Warehouse','WH-MAIN','Warehouse Manager','9000000010','warehouse@kupiana.test',
 'Plot 14, Industrial Area Phase II','Bengaluru','Karnataka','29','560058',1,@now,@now);

INSERT INTO `suppliers`
	(`id`,`name`,`code`,`contact_person`,`email`,`phone`,`gstin`,`city`,`state`,`state_code`,
	 `postal_code`,`payment_terms`,`created_at`,`updated_at`) VALUES
(1,'Northline Trading Co.','SUP-0001','Rahul Menon','sales@northline.test','9000000020',
 '29ABCDE1234F1Z5','Bengaluru','Karnataka','29','560001','Net 30',@now,@now);

-- =====================================================================
-- Catalog: categories, brands, attributes, products
-- =====================================================================

INSERT INTO `categories`
	(`id`,`parent_id`,`name`,`slug`,`description`,`icon`,`level`,`sort_order`,`is_featured`,`created_at`,`updated_at`) VALUES
(1,NULL,'Organic Spices','organic-spices','Certified organic spice powders and everyday Indian pantry essentials.','fa-seedling',0,1,1,@now,@now),
(2,NULL,'Cold-Pressed Oils','cold-pressed-oils','Cold-pressed and wood-pressed oils for everyday cooking and finishing.','fa-bottle-droplet',0,2,1,@now,@now),
(3,NULL,'Whole Spices','whole-spices','Whole spices selected for aroma, freshness and traceable sourcing.','fa-mortar-pestle',0,3,1,@now,@now),
(4,NULL,'Ground Masalas','ground-masalas','Fresh-ground masala blends made in small batches.','fa-jar',0,4,1,@now,@now),
(5,1,'Turmeric & Ginger','turmeric-ginger','Golden turmeric, dry ginger and warming root spices.','fa-leaf',1,1,0,@now,@now),
(6,3,'Pepper & Cardamom','pepper-cardamom','Peppercorns, cardamom and fragrant whole spices.','fa-pepper-hot',1,2,0,@now,@now),
(7,2,'Sesame & Groundnut Oils','sesame-groundnut-oils','Groundnut and sesame oils pressed slowly for natural flavour.','fa-oil-can',1,1,0,@now,@now),
(8,2,'Coconut & Mustard Oils','coconut-mustard-oils','Coconut and mustard oils for traditional kitchens.','fa-bottle-water',1,2,0,@now,@now),
(9,4,'Herbal Blends','herbal-blends','Herbal spice blends for teas, broths and everyday wellness.','fa-spa',1,1,0,@now,@now),
(10,1,'Pantry Staples','pantry-staples','Organic pantry staples for flavourful daily cooking.','fa-wheat-awn',1,3,0,@now,@now);

INSERT INTO `brands` (`id`,`name`,`slug`,`description`,`is_featured`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Kupiana Organics','kupiana-organics','Kupiana Organics curates clean, traceable spices and pantry staples.',1,1,@now,@now),
(2,'Malabar Grove','malabar-grove','Malabar Grove specialises in pepper, cardamom and coastal whole spices.',1,2,@now,@now),
(3,'Deccan Press','deccan-press','Deccan Press makes slow-pressed cooking oils from quality seeds and nuts.',1,3,@now,@now),
(4,'Nilgiri Botanics','nilgiri-botanics','Nilgiri Botanics blends aromatic herbs and fresh-ground masalas.',1,4,@now,@now);

-- Every category has its own photograph, named after its slug. Keep this
-- slug-driven: tools/build_images.php writes categories/<slug>.jpg, so adding
-- a category needs a recipe there and nothing here.
UPDATE `categories` SET `image` = CONCAT('categories/', `slug`, '.jpg'),
`banner` = CASE WHEN `id` IN (1,2,3,4) THEN 'banners/organic-spices-hero.jpg' ELSE NULL END,
`meta_title` = CASE `id`
	WHEN 1 THEN 'Organic Spices Online'
	WHEN 2 THEN 'Cold-Pressed Oils Online'
	WHEN 3 THEN 'Whole Spices Online'
	WHEN 4 THEN 'Ground Masalas Online'
	ELSE `name`
END,
`meta_description` = CASE `id`
	WHEN 1 THEN 'Buy organic spices, turmeric, chilli powder and masalas online from Kupiana.'
	WHEN 2 THEN 'Shop cold-pressed groundnut, sesame, coconut and mustard oils from Kupiana.'
	WHEN 3 THEN 'Discover whole spices including pepper, cardamom and cumin at Kupiana.'
	WHEN 4 THEN 'Fresh-ground masalas made in small batches for everyday Indian cooking.'
	ELSE `description`
END,
`meta_keywords` = 'organic spices, cold pressed oils, masala, whole spices, Kupiana';

UPDATE `brands` SET `logo` = CASE `id`
	WHEN 1 THEN 'brands/kupiana-organics.svg'
	WHEN 2 THEN 'brands/malabar-grove.svg'
	WHEN 3 THEN 'brands/deccan-press.svg'
	WHEN 4 THEN 'brands/nilgiri-botanics.svg'
END,
`banner` = 'banners/organic-spices-hero.jpg',
`meta_title` = CASE `id`
	WHEN 1 THEN 'Kupiana Organics'
	WHEN 2 THEN 'Malabar Grove Spices'
	WHEN 3 THEN 'Deccan Press Oils'
	WHEN 4 THEN 'Nilgiri Botanics Masalas'
END,
`meta_description` = CASE `id`
	WHEN 1 THEN 'Shop organic spices and pantry staples by Kupiana Organics.'
	WHEN 2 THEN 'Buy Malabar pepper, cardamom and whole spices from Kupiana.'
	WHEN 3 THEN 'Shop cold-pressed cooking oils from Deccan Press.'
	WHEN 4 THEN 'Discover herbal blends and fresh masalas by Nilgiri Botanics.'
END;

INSERT INTO `attributes` (`id`,`name`,`slug`,`type`,`is_variation`,`is_filterable`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Pack Type','pack-type','select',0,1,1,@now,@now),
(2,'Pack Size','pack-size','button',1,1,2,@now,@now),
(3,'Source','source','select',0,1,3,@now,@now);

INSERT INTO `attribute_values` (`attribute_id`,`value`,`slug`,`color_code`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Powder','powder',NULL,1,@now,@now),
(1,'Whole','whole',NULL,2,@now,@now),
(1,'Oil','oil',NULL,3,@now,@now),
(1,'Blend','blend',NULL,4,@now,@now),
(2,'50g','50g',NULL,1,@now,@now),
(2,'100g','100g',NULL,2,@now,@now),
(2,'500ml','500ml',NULL,3,@now,@now),
(2,'1L','1l',NULL,4,@now,@now),
(3,'Organic Farm','organic-farm',NULL,1,@now,@now),
(3,'Single Origin','single-origin',NULL,2,@now,@now),
(3,'Small Batch','small-batch',NULL,3,@now,@now);

INSERT INTO `tags` (`name`,`slug`,`created_at`,`updated_at`) VALUES
('Fresh Harvest','fresh-harvest',@now,@now),
('Best Seller','best-seller',@now,@now),
('Certified Organic','certified-organic',@now,@now),
('Small Batch','small-batch',@now,@now);

INSERT INTO `products`
	(`id`,`uuid`,`name`,`slug`,`sku`,`brand_id`,`category_id`,`tax_rate_id`,`type`,
	 `short_description`,`description`,`price`,`mrp`,`cost_price`,`hsn_code`,
	 `stock_quantity`,`low_stock_threshold`,`weight`,
	 `is_featured`,`is_trending`,`is_bestseller`,`is_new_arrival`,
	 `rating_average`,`rating_count`,`sold_count`,`published_at`,`status`,`created_at`,`updated_at`) VALUES
(1,'11111111-1111-4111-8111-111111111101','Organic Lakadong Turmeric Powder','organic-lakadong-turmeric-powder','SP-TUR-100',
 1,5,2,'simple','High-curcumin organic Lakadong turmeric, ground fresh in small batches.',
 '<p>Our Lakadong turmeric is sourced from organic farms and milled in small batches to preserve its warm aroma, deep golden colour and naturally high curcumin character.</p>',
 249.00,299.00,130.00,'0910',140,15,0.100,1,1,1,0,4.80,64,260,@now,'active',@now,@now),
(2,'11111111-1111-4111-8111-111111111102','Malabar Black Pepper Whole','malabar-black-pepper-whole','SP-PEP-100',
 2,6,2,'simple','Sun-dried Malabar black peppercorns with bold aroma and clean heat.',
 '<p>Whole Malabar peppercorns are sun-dried after harvest and packed for freshness, giving everyday cooking a rounded heat and bright fragrance.</p>',
 329.00,399.00,180.00,'0910',90,15,0.100,1,0,1,0,4.70,51,190,@now,'active',@now,@now),
(3,'11111111-1111-4111-8111-111111111103','Cold-Pressed Groundnut Oil','cold-pressed-groundnut-oil','OIL-GND-1L',
 3,7,2,'simple','Cold-pressed groundnut oil for everyday Indian cooking and deep flavour.',
 '<p>Made from quality groundnuts and pressed slowly without high heat, this cooking oil keeps its natural nutty flavour for tadkas, saut&eacute;s and traditional recipes.</p>',
 399.00,499.00,250.00,'1508',70,12,1.000,1,1,1,0,4.60,42,160,@now,'active',@now,@now),
(4,'11111111-1111-4111-8111-111111111104','Wood-Pressed Sesame Oil','wood-pressed-sesame-oil','OIL-SES-1L',
 3,7,2,'simple','Wood-pressed sesame oil with a nutty aroma for cooking, pickles and finishing.',
 '<p>Wood-pressed sesame oil brings a deep, nutty profile to pickles, podis, stir-fries and South Indian cooking. Packed in a kitchen-friendly one-litre bottle.</p>',
 449.00,549.00,285.00,'1515',60,12,1.000,1,1,0,1,4.50,35,120,@now,'active',@now,@now),
(5,'11111111-1111-4111-8111-111111111105','Virgin Coconut Oil','virgin-coconut-oil','OIL-COC-500',
 3,8,2,'simple','Virgin coconut oil pressed from mature coconuts for cooking and pantry use.',
 '<p>Virgin coconut oil pressed from mature coconuts with a clean aroma and smooth texture, suitable for cooking, baking and everyday pantry use.</p>',
 299.00,359.00,180.00,'1513',85,15,0.500,1,0,0,1,4.40,29,135,@now,'active',@now,@now),
(6,'11111111-1111-4111-8111-111111111106','Kashmiri Chilli Powder','kashmiri-chilli-powder','SP-CHI-100',
 1,4,2,'simple','Vibrant Kashmiri chilli powder for rich colour and balanced warmth.',
 '<p>Kashmiri chilli powder is prized for its brilliant red colour and gentle heat, making curries, marinades and spice rubs look as good as they taste.</p>',
 219.00,269.00,115.00,'0910',110,15,0.100,0,1,0,1,4.60,48,210,@now,'active',@now,@now),
(7,'11111111-1111-4111-8111-111111111107','Organic Garam Masala Blend','organic-garam-masala-blend','SP-GAR-100',
 4,4,2,'simple','Small-batch garam masala made with roasted whole spices.',
 '<p>This garam masala is blended from roasted whole spices including cinnamon, cloves, pepper and cardamom, then ground in small batches for fresh aroma.</p>',
 279.00,349.00,145.00,'0910',45,15,0.100,1,1,1,0,4.80,57,180,@now,'active',@now,@now),
(8,'11111111-1111-4111-8111-111111111108','Green Cardamom Pods','green-cardamom-pods','SP-CAR-50',
 2,6,2,'simple','Aromatic green cardamom pods for sweets, chai and festive cooking.',
 '<p>Green cardamom pods deliver sweet, floral fragrance for chai, desserts, biryanis and festive recipes. Store airtight for best aroma.</p>',
 349.00,425.00,205.00,'0910',55,15,0.050,1,1,0,0,4.70,31,92,@now,'active',@now,@now),
(9,'11111111-1111-4111-8111-111111111109','Ginger Garlic Spice Mix','ginger-garlic-spice-mix','SP-GIN-100',
 4,9,2,'simple','Ready ginger-garlic spice mix for quick marinades, curries and stir-fries.',
 '<p>A convenient ginger-garlic spice mix for quick weekday cooking, marinades and curry bases, made without artificial colours.</p>',
 199.00,249.00,95.00,'0910',125,15,0.100,0,0,0,1,4.30,22,75,@now,'active',@now,@now),
(10,'11111111-1111-4111-8111-111111111110','Organic Cumin Seeds','organic-cumin-seeds','SP-CUM-100',
 1,3,2,'simple','Earthy organic cumin seeds selected for tempering and spice blends.',
 '<p>Organic cumin seeds with an earthy, warm profile for tempering dals, seasoning vegetables and grinding into fresh masalas.</p>',
 189.00,229.00,88.00,'0910',95,15,0.100,1,0,1,0,4.50,38,150,@now,'active',@now,@now);

INSERT INTO `product_categories` (`product_id`,`category_id`,`created_at`,`updated_at`) VALUES
(1,1,@now,@now),(1,5,@now,@now),
(2,3,@now,@now),(2,6,@now,@now),
(3,2,@now,@now),(3,7,@now,@now),
(4,2,@now,@now),(4,7,@now,@now),
(5,2,@now,@now),(5,8,@now,@now),
(6,1,@now,@now),(6,4,@now,@now),
(7,1,@now,@now),(7,4,@now,@now),
(8,3,@now,@now),(8,6,@now,@now),
(9,4,@now,@now),(9,9,@now,@now),
(10,1,@now,@now),(10,3,@now,@now);

UPDATE `categories` c
SET `product_count` = (
	SELECT COUNT(DISTINCT pc.`product_id`)
	FROM `product_categories` pc
	JOIN `products` p ON p.`id` = pc.`product_id`
	WHERE pc.`category_id` = c.`id`
		AND pc.`deleted_at` IS NULL
		AND pc.`status` = 'active'
		AND p.`status` = 'active'
		AND p.`deleted_at` IS NULL
);

-- Organic pantry imagery used by the storefront and admin catalog.
INSERT INTO `product_images` (`product_id`,`image_path`,`alt_text`,`sort_order`,`is_primary`,`created_at`,`updated_at`) VALUES
(1,'products/organic-lakadong-turmeric-powder.jpg','Organic Lakadong Turmeric Powder',0,1,@now,@now),
(2,'products/malabar-black-pepper-whole.jpg','Malabar Black Pepper Whole',0,1,@now,@now),
(3,'products/cold-pressed-groundnut-oil.jpg','Cold-Pressed Groundnut Oil',0,1,@now,@now),
(4,'products/wood-pressed-sesame-oil.jpg','Wood-Pressed Sesame Oil',0,1,@now,@now),
(5,'products/virgin-coconut-oil.jpg','Virgin Coconut Oil',0,1,@now,@now),
(6,'products/kashmiri-chilli-powder.jpg','Kashmiri Chilli Powder',0,1,@now,@now),
(7,'products/organic-garam-masala-blend.jpg','Organic Garam Masala Blend',0,1,@now,@now),
(8,'products/green-cardamom-pods.jpg','Green Cardamom Pods',0,1,@now,@now),
(9,'products/ginger-garlic-spice-mix.jpg','Ginger Garlic Spice Mix',0,1,@now,@now),
(10,'products/organic-cumin-seeds.jpg','Organic Cumin Seeds',0,1,@now,@now);

-- Opening stock in the main warehouse. variant_id 0 = product has no variant.
-- Pantry stock quantities give the admin inventory screens realistic data.
INSERT INTO `inventory` (`product_id`,`variant_id`,`warehouse_id`,`quantity`,`reorder_level`,`created_at`,`updated_at`) VALUES
(1,0,1,140,15,@now,@now),
(2,0,1,90,15,@now,@now),
(3,0,1,70,12,@now,@now),
(4,0,1,60,12,@now,@now),
(5,0,1,85,15,@now,@now),
(6,0,1,110,15,@now,@now),
(7,0,1,45,15,@now,@now),
(8,0,1,55,15,@now,@now),
(9,0,1,125,15,@now,@now),
(10,0,1,95,15,@now,@now);

-- =====================================================================
-- Promotions
-- =====================================================================

INSERT INTO `coupons`
	(`code`,`name`,`description`,`type`,`value`,`min_order_amount`,`max_discount_amount`,
	 `usage_limit`,`usage_limit_per_user`,`first_order_only`,`starts_at`,`expires_at`,`created_at`,`updated_at`) VALUES
('WELCOME10','Welcome 10%','10% off your first order, up to ₹500.','percentage',10.00,999.00,500.00,
 1000,1,1,@now,DATE_ADD(@now, INTERVAL 90 DAY),@now,@now),
('FLAT250','Flat ₹250 Off','₹250 off orders above ₹1999.','fixed',250.00,1999.00,NULL,
 NULL,3,0,@now,DATE_ADD(@now, INTERVAL 30 DAY),@now,@now),
('FREESHIP','Free Shipping','Free shipping on any order.','free_shipping',0.00,0.00,NULL,
 NULL,NULL,0,@now,DATE_ADD(@now, INTERVAL 60 DAY),@now,@now);

-- =====================================================================
-- CMS
-- =====================================================================

INSERT INTO `banners` (`title`,`subtitle`,`image`,`link_url`,`button_text`,`position`,`sort_order`,`created_at`,`updated_at`) VALUES
('Fresh-ground organic spices','Turmeric, chilli, garam masala and whole spices packed for aroma.','banners/organic-spices-hero.jpg','category/organic-spices','Shop Spices','home_slider',1,@now,@now),
('Cold-pressed oils for daily cooking','Groundnut, sesame and coconut oils pressed slowly in small batches.','banners/cold-pressed-oils.jpg','category/cold-pressed-oils','Shop Oils','home_slider',2,@now,@now),
('Build a cleaner pantry','Organic staples, spice blends and oils for everyday Indian kitchens.','banners/cleaner-pantry.jpg','shop','Explore Pantry','home_banner',1,@now,@now);

INSERT INTO `pages` (`title`,`slug`,`content`,`is_system`,`meta_title`,`meta_description`,`created_at`,`updated_at`) VALUES
('About Us','about','<h2>About Kupiana</h2><p>Kupiana is an organic pantry store for fresh-ground spices, whole masalas and cold-pressed cooking oils. We work with trusted growers and small-batch producers so everyday cooking tastes cleaner, brighter and more aromatic.</p>',1,'About Kupiana Organic Spices & Oils','Learn about Kupiana organic spices, whole masalas and cold-pressed cooking oils.',@now,@now),
('Privacy Policy','privacy-policy','<h2>Privacy Policy</h2><p>This policy explains how Kupiana collects and protects customer information while processing orders for organic spices, cooking oils and pantry goods.</p>',1,'Privacy Policy','How Kupiana protects customer information for pantry orders.',@now,@now),
('Terms of Use','terms','<h2>Terms of Use</h2><p>These terms govern use of the Kupiana website and purchase of organic spices, oils and pantry products. Please review final legal copy before launch.</p>',1,'Terms of Use','Terms for shopping organic spices, oils and pantry goods at Kupiana.',@now,@now),
('Return Policy','return-policy','<h2>Return Policy</h2><p>Food and pantry items can be returned only if delivered damaged, incorrect or unopened as per the final return policy. Please contact support within 7 days of delivery.</p>',1,'Return Policy for Organic Pantry Orders','Return guidance for organic spices, oils and pantry products ordered from Kupiana.',@now,@now),
('Shipping Policy','shipping-policy','<h2>Shipping Policy</h2><p>Organic spices and oils are packed securely and dispatched within 1&ndash;2 business days. Free shipping applies to orders above &#8377;999.</p>',1,'Shipping Organic Spices and Oils','Delivery timelines and packing details for Kupiana organic spices and oils.',@now,@now);

INSERT INTO `faqs` (`category`,`question`,`answer`,`sort_order`,`created_at`,`updated_at`) VALUES
('Orders','How do I track my order?','Sign in and open My Orders, or use the Track Order page with your order number and registered email.',1,@now,@now),
('Orders','Can I change or cancel my order?','Orders can be cancelled from My Orders until they are marked Packed. After that, please raise a return once the parcel arrives.',2,@now,@now),
('Shipping','How long does delivery take?','Metro locations typically receive orders in 2-4 business days. Other serviceable pin codes take 4-7 business days.',3,@now,@now),
('Shipping','Do you offer free shipping?','Yes. Shipping is free on all orders above ₹999. Below that a flat fee is shown at checkout.',4,@now,@now),
('Returns','What is your return window?','Most items can be returned within 7 days of delivery, unused and in their original packaging.',5,@now,@now),
('Returns','When will I get my refund?','Refunds are issued to the original payment method within 5-7 business days of the returned item passing inspection.',6,@now,@now),
('Payments','Which payment methods do you accept?','Cards, UPI and net banking through Razorpay, plus Cash on Delivery on eligible pin codes.',7,@now,@now),
('Payments','Is it safe to pay online?','Yes. Payments are processed by Razorpay over an encrypted connection. We never store your card details.',8,@now,@now);

INSERT INTO `testimonials` (`name`,`designation`,`company`,`rating`,`content`,`sort_order`,`is_featured`,`created_at`,`updated_at`) VALUES
('Ananya Rao','Home Chef',NULL,5,'The turmeric and garam masala smelled freshly ground the moment I opened the pack. My dal has genuinely more depth now.',1,1,@now,@now),
('Vikram Shah','Cafe Owner','Wren Kitchen',5,'We ordered cold-pressed sesame and groundnut oils for our kitchen. Clean packing, consistent flavour and GST details were spot on.',2,1,@now,@now),
('Meera Iyer','Food Blogger',NULL,4,'The cardamom pods and Kashmiri chilli powder are pantry keepers. Beautiful aroma, rich colour and quick delivery.',3,1,@now,@now);

INSERT INTO `blog_categories` (`name`,`slug`,`description`,`created_at`,`updated_at`) VALUES
('Spice Guides','spice-guides','How to choose, store and cook with organic spices.',@now,@now),
('Farm & Press Stories','farm-press-stories','Stories from growers, millers and cold-pressed oil makers.',@now,@now),
('Kitchen Notes','kitchen-notes','Recipes, pantry tips and seasonal kitchen ideas.',@now,@now);

-- =====================================================================
-- System settings
-- =====================================================================
-- These override application/config/app.php at runtime via the Settings library.

INSERT INTO `settings` (`setting_key`,`setting_value`,`setting_group`,`setting_type`,`label`,`is_public`,`created_at`,`updated_at`) VALUES
('site_name','Kupiana','general','text','Site Name',1,@now,@now),
('site_tagline','Organic spices and oils, delivered.','general','text','Tagline',1,@now,@now),
('support_email','support@kupiana.test','general','text','Support Email',1,@now,@now),
('support_phone','+91 90000 00000','general','text','Support Phone',1,@now,@now),
('address','Plot 14, Industrial Area Phase II, Bengaluru 560058','general','textarea','Business Address',1,@now,@now),
('gstin','29ABCDE1234F1Z5','general','text','Company GSTIN',0,@now,@now),
('currency_code','INR','general','text','Currency Code',1,@now,@now),
('maintenance_mode','0','general','bool','Maintenance Mode',0,@now,@now),

('free_shipping_threshold','999','shipping','number','Free Shipping Above',1,@now,@now),
('flat_shipping_rate','79','shipping','number','Flat Shipping Rate',1,@now,@now),
('cod_enabled','1','shipping','bool','Enable Cash on Delivery',1,@now,@now),
('cod_max_order_value','10000','shipping','number','COD Maximum Order Value',0,@now,@now),
('dispatch_days','2','shipping','number','Dispatch Within (days)',1,@now,@now),

('tax_enabled','1','tax','bool','Enable Tax',0,@now,@now),
('prices_include_tax','0','tax','bool','Prices Include Tax',0,@now,@now),
('default_tax_rate','18','tax','number','Default Tax Rate (%)',0,@now,@now),
('gst_enabled','1','tax','bool','Enable GST',0,@now,@now),
('origin_state_code','29','tax','text','Origin State Code',0,@now,@now),

('razorpay_enabled','0','payment','bool','Enable Razorpay',0,@now,@now),
('razorpay_key_id','','payment','text','Razorpay Key ID',0,@now,@now),
('razorpay_key_secret','','payment','text','Razorpay Key Secret',0,@now,@now),
('razorpay_webhook_secret','','payment','text','Razorpay Webhook Secret',0,@now,@now),

('low_stock_threshold','10','inventory','number','Low Stock Threshold',0,@now,@now),
('allow_backorder','0','inventory','bool','Allow Backorders',0,@now,@now),
('reserve_stock_on_order','1','inventory','bool','Reserve Stock on Order',0,@now,@now),

('meta_title','Kupiana — Organic Spices & Cold-Pressed Oils','seo','text','Default Meta Title',1,@now,@now),
('meta_description','Shop organic spices, whole masalas and cold-pressed cooking oils at Kupiana. Free shipping above ₹999.','seo','textarea','Default Meta Description',1,@now,@now),
('google_analytics_id','','seo','text','Google Analytics ID',1,@now,@now),

('reviews_require_approval','1','catalog','bool','Reviews Require Approval',0,@now,@now),
('reviews_verified_only','0','catalog','bool','Only Verified Buyers Can Review',0,@now,@now),
('products_per_page','12','catalog','number','Products Per Page',1,@now,@now);

-- =====================================================================
-- Notification templates
-- =====================================================================

INSERT INTO `email_templates` (`code`,`name`,`subject`,`body`,`variables`,`created_at`,`updated_at`) VALUES
('welcome','Welcome Email','Welcome to {{site_name}}, {{first_name}}!',
 '<p>Hi {{first_name}},</p><p>Thanks for creating an account at {{site_name}}. Your account is ready to use.</p><p><a href="{{login_url}}">Sign in</a></p>',
 'site_name,first_name,login_url',@now,@now),
('email_verification','Email Verification','Verify your {{site_name}} email address',
 '<p>Hi {{first_name}},</p><p>Please confirm your email address to activate your account. This link expires in {{expiry_minutes}} minutes.</p><p><a href="{{verify_url}}">Verify email</a></p>',
 'site_name,first_name,verify_url,expiry_minutes',@now,@now),
('password_reset','Password Reset','Reset your {{site_name}} password',
 '<p>Hi {{first_name}},</p><p>We received a request to reset your password. This link expires in {{expiry_minutes}} minutes. If you did not request this, you can safely ignore this email.</p><p><a href="{{reset_url}}">Reset password</a></p>',
 'site_name,first_name,reset_url,expiry_minutes',@now,@now),
('otp_login','Login OTP','{{otp}} is your {{site_name}} verification code',
 '<p>Your verification code is <strong>{{otp}}</strong>. It expires in {{expiry_minutes}} minutes.</p><p>Do not share this code with anyone.</p>',
 'site_name,otp,expiry_minutes',@now,@now),
('order_placed','Order Confirmation','Order {{order_number}} confirmed',
 '<p>Hi {{customer_name}},</p><p>Thanks for your order. We have received order <strong>{{order_number}}</strong> totalling {{order_total}}.</p><p><a href="{{order_url}}">View your order</a></p>',
 'customer_name,order_number,order_total,order_url',@now,@now),
('order_shipped','Order Shipped','Order {{order_number}} has shipped',
 '<p>Hi {{customer_name}},</p><p>Your order is on its way with {{courier_name}}. Tracking number: <strong>{{tracking_number}}</strong>.</p><p><a href="{{tracking_url}}">Track your parcel</a></p>',
 'customer_name,order_number,courier_name,tracking_number,tracking_url',@now,@now),
('order_delivered','Order Delivered','Order {{order_number}} delivered',
 '<p>Hi {{customer_name}},</p><p>Your order was delivered on {{delivered_at}}. We would love to hear what you think.</p><p><a href="{{review_url}}">Write a review</a></p>',
 'customer_name,order_number,delivered_at,review_url',@now,@now),
('order_cancelled','Order Cancelled','Order {{order_number}} cancelled',
 '<p>Hi {{customer_name}},</p><p>Order <strong>{{order_number}}</strong> has been cancelled. Any payment made will be refunded within 5-7 business days.</p>',
 'customer_name,order_number',@now,@now),
('refund_processed','Refund Processed','Refund for order {{order_number}}',
 '<p>Hi {{customer_name}},</p><p>We have processed a refund of {{refund_amount}} for order {{order_number}}. It should reach your account within 5-7 business days.</p>',
 'customer_name,order_number,refund_amount',@now,@now),
('contact_reply','Contact Reply','Re: {{subject}}',
 '<p>Hi {{name}},</p><p>{{reply}}</p><p>Regards,<br>{{site_name}} Support</p>',
 'name,subject,reply,site_name',@now,@now);

INSERT INTO `sms_templates` (`code`,`name`,`body`,`variables`,`created_at`,`updated_at`) VALUES
('otp_login','Login OTP','{{otp}} is your {{site_name}} OTP. Valid for {{expiry_minutes}} minutes. Do not share it with anyone.','site_name,otp,expiry_minutes',@now,@now),
('order_placed','Order Confirmation','Order {{order_number}} confirmed. Total {{order_total}}. Track it at {{order_url}} - {{site_name}}','site_name,order_number,order_total,order_url',@now,@now),
('order_shipped','Order Shipped','Order {{order_number}} shipped via {{courier_name}}. Tracking: {{tracking_number}} - {{site_name}}','order_number,courier_name,tracking_number,site_name',@now,@now),
('order_delivered','Order Delivered','Order {{order_number}} was delivered. Thanks for shopping with {{site_name}}.','order_number,site_name',@now,@now),
('order_cancelled','Order Cancelled','Order {{order_number}} has been cancelled. Refunds take 5-7 business days. - {{site_name}}','order_number,site_name',@now,@now);

-- End of seed.

-- Phase 3: authentication settings.
INSERT INTO `settings` (`setting_key`,`setting_value`,`setting_group`,`setting_type`,`label`,`is_public`,`created_at`,`updated_at`) VALUES
('require_email_verification','0','security','bool','Require Email Verification Before Sign-in',0,NOW(),NOW()),
('allow_otp_login','1','security','bool','Allow One-Time-Code Sign-in',0,NOW(),NOW()),
('zeptomail_api_key','','mail','text','ZeptoMail API Key (overrides .env)',0,NOW(),NOW()),
('mail_from_address','noreply@kupiana.test','mail','text','From Address',0,NOW(),NOW()),
('mail_from_name','Kupiana','mail','text','From Name',0,NOW(),NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();
