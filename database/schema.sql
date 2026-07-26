CREATE DATABASE IF NOT EXISTS kupiana CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kupiana;

CREATE TABLE roles (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(80) NOT NULL,
	slug VARCHAR(80) NOT NULL UNIQUE,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(190) NOT NULL UNIQUE,
	password CHAR(32) NOT NULL,
	first_name VARCHAR(100) NOT NULL,
	last_name VARCHAR(100) NOT NULL,
	phone VARCHAR(40) NULL,
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	email_verified_at DATETIME NULL,
	last_login_at DATETIME NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	INDEX users_active_idx (is_active)
);

CREATE TABLE user_roles (
	user_id INT UNSIGNED NOT NULL,
	role_id INT UNSIGNED NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id, role_id),
	CONSTRAINT user_roles_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT user_roles_role_fk FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE user_addresses (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	type ENUM('shipping','billing') NOT NULL DEFAULT 'shipping',
	full_name VARCHAR(160) NOT NULL,
	phone VARCHAR(40) NULL,
	address_line_1 VARCHAR(190) NOT NULL,
	address_line_2 VARCHAR(190) NULL,
	city VARCHAR(100) NOT NULL,
	state VARCHAR(100) NULL,
	postal_code VARCHAR(30) NOT NULL,
	country_code CHAR(2) NOT NULL,
	is_default TINYINT(1) NOT NULL DEFAULT 0,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT user_addresses_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	INDEX user_addresses_lookup_idx (user_id, type, is_default)
);

CREATE TABLE categories (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	parent_id INT UNSIGNED NULL,
	name VARCHAR(160) NOT NULL,
	slug VARCHAR(190) NOT NULL UNIQUE,
	meta_title VARCHAR(190) NULL,
	meta_description VARCHAR(255) NULL,
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	sort_order INT NOT NULL DEFAULT 0,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT categories_parent_fk FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
	INDEX categories_active_idx (is_active, sort_order)
);

CREATE TABLE products (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(190) NOT NULL,
	slug VARCHAR(190) NOT NULL UNIQUE,
	short_description VARCHAR(255) NULL,
	description TEXT NULL,
	sku VARCHAR(100) NOT NULL UNIQUE,
	status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
	price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	sale_price DECIMAL(12,2) NULL,
	currency CHAR(3) NOT NULL DEFAULT 'INR',
	stock_quantity INT NOT NULL DEFAULT 0,
	weight DECIMAL(10,3) NULL,
	meta_title VARCHAR(190) NULL,
	meta_description VARCHAR(255) NULL,
	canonical_url VARCHAR(255) NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	INDEX products_status_idx (status),
	INDEX products_price_idx (price)
);

CREATE TABLE product_categories (
	product_id INT UNSIGNED NOT NULL,
	category_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (product_id, category_id),
	CONSTRAINT product_categories_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
	CONSTRAINT product_categories_category_fk FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE product_images (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	product_id INT UNSIGNED NOT NULL,
	image_path VARCHAR(255) NOT NULL,
	alt_text VARCHAR(190) NULL,
	sort_order INT NOT NULL DEFAULT 0,
	is_primary TINYINT(1) NOT NULL DEFAULT 0,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT product_images_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
	INDEX product_images_sort_idx (product_id, sort_order)
);

CREATE TABLE product_variants (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	product_id INT UNSIGNED NOT NULL,
	sku VARCHAR(100) NOT NULL UNIQUE,
	name VARCHAR(160) NOT NULL,
	price DECIMAL(12,2) NULL,
	stock_quantity INT NOT NULL DEFAULT 0,
	attributes JSON NULL,
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT product_variants_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
	INDEX product_variants_active_idx (product_id, is_active)
);

CREATE TABLE carts (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NULL,
	session_id VARCHAR(128) NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT carts_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
	INDEX carts_session_idx (session_id)
);

CREATE TABLE cart_items (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	cart_id INT UNSIGNED NOT NULL,
	product_id INT UNSIGNED NOT NULL,
	variant_id INT UNSIGNED NULL,
	quantity INT UNSIGNED NOT NULL DEFAULT 1,
	unit_price DECIMAL(12,2) NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT cart_items_cart_fk FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
	CONSTRAINT cart_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
	CONSTRAINT cart_items_variant_fk FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE orders (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	order_number VARCHAR(40) NOT NULL UNIQUE,
	user_id INT UNSIGNED NULL,
	status ENUM('pending','paid','processing','shipped','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
	currency CHAR(3) NOT NULL DEFAULT 'INR',
	subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	tax_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	customer_email VARCHAR(190) NOT NULL,
	shipping_address JSON NULL,
	billing_address JSON NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
	INDEX orders_status_idx (status),
	INDEX orders_customer_idx (customer_email)
);

CREATE TABLE order_items (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	order_id INT UNSIGNED NOT NULL,
	product_id INT UNSIGNED NULL,
	variant_id INT UNSIGNED NULL,
	product_name VARCHAR(190) NOT NULL,
	sku VARCHAR(100) NOT NULL,
	quantity INT UNSIGNED NOT NULL,
	unit_price DECIMAL(12,2) NOT NULL,
	line_total DECIMAL(12,2) NOT NULL,
	CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
	CONSTRAINT order_items_variant_fk FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE payments (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	order_id INT UNSIGNED NOT NULL,
	provider VARCHAR(80) NOT NULL,
	provider_reference VARCHAR(190) NULL,
	status ENUM('pending','authorized','captured','failed','refunded') NOT NULL DEFAULT 'pending',
	amount DECIMAL(12,2) NOT NULL,
	currency CHAR(3) NOT NULL DEFAULT 'INR',
	payload JSON NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT payments_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	INDEX payments_status_idx (status)
);

CREATE TABLE product_reviews (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	product_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED NULL,
	rating TINYINT UNSIGNED NOT NULL,
	title VARCHAR(160) NULL,
	body TEXT NULL,
	status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT product_reviews_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
	CONSTRAINT product_reviews_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
	INDEX product_reviews_status_idx (product_id, status)
);

INSERT INTO roles (id, name, slug) VALUES
	(1, 'Administrator', 'admin'),
	(2, 'Customer', 'user')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (id, email, password, first_name, last_name, is_active, email_verified_at) VALUES
	(1, 'admin@kupiana.test', '0192023a7bbd73250516f069df18b500', 'Kupiana', 'Admin', 1, NOW()),
	(2, 'user@kupiana.test', '6ad14ba9986e3615423dfca256d04e3f', 'Kupiana', 'Customer', 1, NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO user_roles (user_id, role_id) VALUES
	(1, 1),
	(2, 2)
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);
