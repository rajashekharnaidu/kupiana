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
('6109','T-shirts, singlets and other vests, knitted',5.00,@now,@now),
('6203','Men''s suits, jackets, trousers',12.00,@now,@now),
('4202','Trunks, suitcases, handbags, wallets',18.00,@now,@now),
('8517','Telephone sets, smartphones',18.00,@now,@now),
('8518','Headphones, earphones, speakers',18.00,@now,@now),
('9503','Toys, puzzles and games',12.00,@now,@now),
('3304','Beauty and skincare preparations',18.00,@now,@now);

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
(1,NULL,'Electronics','electronics','Phones, audio and everyday tech.','fa-mobile-screen',0,1,1,@now,@now),
(2,NULL,'Fashion','fashion','Clothing and accessories for every day.','fa-shirt',0,2,1,@now,@now),
(3,NULL,'Home & Living','home-living','Furnishing and decor essentials.','fa-couch',0,3,1,@now,@now),
(4,NULL,'Beauty','beauty','Skincare and personal care.','fa-spa',0,4,0,@now,@now),
(5,1,'Audio','audio','Headphones, earbuds and speakers.',NULL,1,1,0,@now,@now),
(6,1,'Smartphones','smartphones','Latest smartphones and accessories.',NULL,1,2,0,@now,@now),
(7,2,'Men','fashion-men','Menswear and accessories.',NULL,1,1,0,@now,@now),
(8,2,'Women','fashion-women','Womenswear and accessories.',NULL,1,2,0,@now,@now),
(9,2,'Bags','bags','Backpacks, totes and wallets.',NULL,1,3,0,@now,@now),
(10,3,'Kitchen','kitchen','Cookware and kitchen tools.',NULL,1,1,0,@now,@now);

INSERT INTO `brands` (`id`,`name`,`slug`,`description`,`is_featured`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Aurex','aurex','Audio equipment engineered for clarity.',1,1,@now,@now),
(2,'Northwind','northwind','Durable everyday carry and bags.',1,2,@now,@now),
(3,'Lumen','lumen','Minimal home and lighting goods.',1,3,@now,@now),
(4,'Verda','verda','Clean, plant-derived skincare.',0,4,@now,@now);

INSERT INTO `attributes` (`id`,`name`,`slug`,`type`,`is_variation`,`is_filterable`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Colour','colour','color',1,1,1,@now,@now),
(2,'Size','size','button',1,1,2,@now,@now),
(3,'Material','material','select',0,1,3,@now,@now);

INSERT INTO `attribute_values` (`attribute_id`,`value`,`slug`,`color_code`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'Black','black','#111827',1,@now,@now),
(1,'White','white','#f9fafb',2,@now,@now),
(1,'Navy','navy','#1e3a8a',3,@now,@now),
(1,'Sand','sand','#d6c7a8',4,@now,@now),
(2,'S','s',NULL,1,@now,@now),
(2,'M','m',NULL,2,@now,@now),
(2,'L','l',NULL,3,@now,@now),
(2,'XL','xl',NULL,4,@now,@now),
(3,'Cotton','cotton',NULL,1,@now,@now),
(3,'Leather','leather',NULL,2,@now,@now),
(3,'Recycled Nylon','recycled-nylon',NULL,3,@now,@now);

INSERT INTO `tags` (`name`,`slug`,`created_at`,`updated_at`) VALUES
('New Arrival','new-arrival',@now,@now),
('Best Seller','best-seller',@now,@now),
('Eco Friendly','eco-friendly',@now,@now),
('Limited Edition','limited-edition',@now,@now);

INSERT INTO `products`
	(`id`,`uuid`,`name`,`slug`,`sku`,`brand_id`,`category_id`,`tax_rate_id`,`type`,
	 `short_description`,`description`,`price`,`mrp`,`cost_price`,`hsn_code`,
	 `stock_quantity`,`low_stock_threshold`,`weight`,
	 `is_featured`,`is_trending`,`is_bestseller`,`is_new_arrival`,
	 `rating_average`,`rating_count`,`sold_count`,`published_at`,`status`,`created_at`,`updated_at`) VALUES
(1,'11111111-1111-4111-8111-111111111101','Aurex Studio Wireless Headphones','aurex-studio-wireless-headphones','AUR-HP-001',
 1,5,4,'simple','Over-ear wireless headphones with 40-hour battery and adaptive noise cancelling.',
 '<p>Studio-grade drivers, adaptive noise cancelling and a 40-hour battery. Memory-foam ear cushions and a folding frame make them equally at home on a commute or a long-haul flight.</p>',
 8499.00,11999.00,5200.00,'8518',45,10,0.290,1,1,1,0,4.60,38,120,@now,'active',@now,@now),
(2,'11111111-1111-4111-8111-111111111102','Aurex Pulse Wireless Earbuds','aurex-pulse-wireless-earbuds','AUR-EB-002',
 1,5,4,'simple','Compact true-wireless earbuds with a 28-hour charging case.',
 '<p>Six-hour playback per charge and 28 hours total with the pocketable case. IPX5 water resistance and touch controls.</p>',
 3299.00,4999.00,1850.00,'8518',80,15,0.058,1,1,1,1,4.30,64,240,@now,'active',@now,@now),
(3,'11111111-1111-4111-8111-111111111103','Northwind Daypack 22L','northwind-daypack-22l','NWD-BP-003',
 2,9,4,'variable','Water-resistant 22-litre daypack in recycled nylon.',
 '<p>A padded 16-inch laptop sleeve, water-resistant recycled nylon shell and a clamshell opening that actually lets you pack properly.</p>',
 4250.00,5500.00,2400.00,'4202',30,8,0.780,1,0,1,0,4.70,22,86,@now,'active',@now,@now),
(4,'11111111-1111-4111-8111-111111111104','Northwind Bifold Wallet','northwind-bifold-wallet','NWD-WL-004',
 2,9,4,'simple','Full-grain leather bifold with RFID blocking.',
 '<p>Full-grain leather that ages well, six card slots and an RFID-blocking lining.</p>',
 1899.00,2499.00,900.00,'4202',120,20,0.110,0,1,0,1,4.40,17,58,@now,'active',@now,@now),
(5,'11111111-1111-4111-8111-111111111105','Lumen Ceramic Table Lamp','lumen-ceramic-table-lamp','LMN-LP-005',
 3,3,4,'simple','Hand-glazed ceramic lamp with a linen shade.',
 '<p>Hand-glazed stoneware base with a natural linen shade. Warm, diffused light for a bedside or console.</p>',
 3750.00,4500.00,1900.00,'9405',18,5,1.950,1,0,0,1,4.80,9,24,@now,'active',@now,@now),
(6,'11111111-1111-4111-8111-111111111106','Lumen Stoneware Mug Set of 4','lumen-stoneware-mug-set','LMN-MG-006',
 3,10,3,'simple','Reactive-glaze stoneware mugs, 350ml each.',
 '<p>Four 350ml mugs with a reactive glaze, so no two are quite alike. Dishwasher and microwave safe.</p>',
 1650.00,2200.00,780.00,'6912',6,10,1.400,0,0,1,0,4.50,31,140,@now,'active',@now,@now),
(7,'11111111-1111-4111-8111-111111111107','Verda Vitamin C Serum 30ml','verda-vitamin-c-serum','VRD-SR-007',
 4,4,4,'simple','15% vitamin C with hyaluronic acid.',
 '<p>A 15% L-ascorbic acid serum buffered with hyaluronic acid and vitamin E. Fragrance free.</p>',
 1299.00,1799.00,520.00,'3304',0,10,0.060,0,1,0,1,4.20,53,310,@now,'active',@now,@now),
(8,'11111111-1111-4111-8111-111111111108','Kupiana Essential Cotton Tee','kupiana-essential-cotton-tee','KPN-TS-008',
 NULL,7,2,'variable','240gsm combed cotton crew neck.',
 '<p>240gsm combed organic cotton, pre-shrunk, with a ribbed collar that keeps its shape.</p>',
 899.00,1299.00,350.00,'6109',200,25,0.220,0,0,1,1,4.10,88,520,@now,'active',@now,@now),
(9,'11111111-1111-4111-8111-111111111109','Lumen Linen Cushion Cover','lumen-linen-cushion-cover','LMN-CC-009',
 3,3,3,'simple','Stonewashed linen cover, 45x45cm.',
 '<p>Stonewashed European linen with a concealed zip. Cover only.</p>',
 749.00,999.00,300.00,'6304',60,10,0.180,0,0,0,1,4.00,12,40,@now,'active',@now,@now),
(10,'11111111-1111-4111-8111-111111111110','Aurex Desk Speaker','aurex-desk-speaker','AUR-SP-010',
 1,5,4,'simple','Compact bookshelf speaker with Bluetooth 5.3.',
 '<p>A 40W compact desk speaker with Bluetooth 5.3, USB-C and a 3.5mm aux input.</p>',
 5499.00,6999.00,3100.00,'8518',12,10,1.100,0,1,0,0,4.30,14,47,@now,'draft',@now,@now);

INSERT INTO `product_categories` (`product_id`,`category_id`,`created_at`,`updated_at`) VALUES
(1,1,@now,@now),(1,5,@now,@now),
(2,1,@now,@now),(2,5,@now,@now),
(3,2,@now,@now),(3,9,@now,@now),
(4,2,@now,@now),(4,9,@now,@now),
(5,3,@now,@now),
(6,3,@now,@now),(6,10,@now,@now),
(7,4,@now,@now),
(8,2,@now,@now),(8,7,@now,@now),
(9,3,@now,@now),
(10,1,@now,@now),(10,5,@now,@now);

-- Placeholder imagery. Replace with real uploads through the admin panel.
INSERT INTO `product_images` (`product_id`,`image_path`,`alt_text`,`sort_order`,`is_primary`,`created_at`,`updated_at`) VALUES
(1,'products/placeholder.svg','Aurex Studio Wireless Headphones',0,1,@now,@now),
(2,'products/placeholder.svg','Aurex Pulse Wireless Earbuds',0,1,@now,@now),
(3,'products/placeholder.svg','Northwind Daypack 22L',0,1,@now,@now),
(4,'products/placeholder.svg','Northwind Bifold Wallet',0,1,@now,@now),
(5,'products/placeholder.svg','Lumen Ceramic Table Lamp',0,1,@now,@now),
(6,'products/placeholder.svg','Lumen Stoneware Mug Set',0,1,@now,@now),
(7,'products/placeholder.svg','Verda Vitamin C Serum',0,1,@now,@now),
(8,'products/placeholder.svg','Kupiana Essential Cotton Tee',0,1,@now,@now),
(9,'products/placeholder.svg','Lumen Linen Cushion Cover',0,1,@now,@now),
(10,'products/placeholder.svg','Aurex Desk Speaker',0,1,@now,@now);

-- Opening stock in the main warehouse. variant_id 0 = product has no variant.
-- Deliberately varied so the Low Stock and Out of Stock screens have data:
--   product 6  -> 6 units against a threshold of 10 (low stock)
--   product 7  -> 0 units                            (out of stock)
INSERT INTO `inventory` (`product_id`,`variant_id`,`warehouse_id`,`quantity`,`reorder_level`,`created_at`,`updated_at`) VALUES
(1,0,1,45,10,@now,@now),
(2,0,1,80,15,@now,@now),
(3,0,1,30,8,@now,@now),
(4,0,1,120,20,@now,@now),
(5,0,1,18,5,@now,@now),
(6,0,1,6,10,@now,@now),
(7,0,1,0,10,@now,@now),
(8,0,1,200,25,@now,@now),
(9,0,1,60,10,@now,@now),
(10,0,1,12,10,@now,@now);

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
('Sound, considered.','Up to 30% off the Aurex audio range.','banners/placeholder.svg','shop','Shop Audio','home_slider',1,@now,@now),
('Built to be carried.','Northwind bags in recycled nylon.','banners/placeholder.svg','category/bags','Shop Bags','home_slider',2,@now,@now),
('New in Home & Living','Warm ceramics and soft linen.','banners/placeholder.svg','category/home-living','Explore','home_banner',1,@now,@now);

INSERT INTO `pages` (`title`,`slug`,`content`,`is_system`,`meta_title`,`meta_description`,`created_at`,`updated_at`) VALUES
('About Us','about','<h2>About Kupiana</h2><p>Kupiana is a curated commerce destination for refined everyday pieces, thoughtful gifting and beautiful essentials. Replace this copy from Admin &rsaquo; Website CMS &rsaquo; Pages.</p>',1,'About Us','Learn about Kupiana, our story and what we stand for.',@now,@now),
('Privacy Policy','privacy-policy','<h2>Privacy Policy</h2><p>This placeholder describes what data we collect, why we collect it and how it is stored. Replace it with your reviewed legal copy before launch.</p>',1,'Privacy Policy','How Kupiana collects, uses and protects your personal data.',@now,@now),
('Terms of Use','terms','<h2>Terms of Use</h2><p>Placeholder terms governing use of this website and the purchase of goods. Replace with your reviewed legal copy before launch.</p>',1,'Terms of Use','The terms governing your use of the Kupiana website.',@now,@now),
('Return Policy','return-policy','<h2>Return Policy</h2><p>Most items can be returned within 7 days of delivery in their original condition and packaging. Replace with your final policy.</p>',1,'Return Policy','How to return or exchange an item purchased from Kupiana.',@now,@now),
('Shipping Policy','shipping-policy','<h2>Shipping Policy</h2><p>Orders are dispatched within 1&ndash;2 business days. Free shipping applies to orders above &#8377;999. Replace with your final policy.</p>',1,'Shipping Policy','Delivery timelines, charges and coverage for Kupiana orders.',@now,@now);

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
('Ananya Rao','Product Designer',NULL,5,'The packaging was genuinely lovely and the headphones arrived a day early. Easily my best online order this year.',1,1,@now,@now),
('Vikram Shah','Founder','Wren Studio',5,'Ordered mugs for the whole studio. Consistent quality, and the invoice and GST details were spot on.',2,1,@now,@now),
('Meera Iyer',NULL,NULL,4,'Great daypack. Took one star off only because I wanted a second colour option, which they have since added.',3,1,@now,@now);

INSERT INTO `blog_categories` (`name`,`slug`,`description`,`created_at`,`updated_at`) VALUES
('Buying Guides','buying-guides','Advice on choosing the right product.',@now,@now),
('Behind the Brand','behind-the-brand','Stories from the makers we work with.',@now,@now),
('News','news','Announcements and launches.',@now,@now);

-- =====================================================================
-- System settings
-- =====================================================================
-- These override application/config/app.php at runtime via the Settings library.

INSERT INTO `settings` (`setting_key`,`setting_value`,`setting_group`,`setting_type`,`label`,`is_public`,`created_at`,`updated_at`) VALUES
('site_name','Kupiana','general','text','Site Name',1,@now,@now),
('site_tagline','Curated commerce, delivered.','general','text','Tagline',1,@now,@now),
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

('meta_title','Kupiana — Curated Online Store','seo','text','Default Meta Title',1,@now,@now),
('meta_description','Shop curated electronics, fashion, home and beauty at Kupiana. Free shipping above ₹999.','seo','textarea','Default Meta Description',1,@now,@now),
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
