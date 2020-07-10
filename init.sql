CREATE DATABASE IF NOT EXISTS mailer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS mailer @localhost IDENTIFIED WITH mysql_native_password BY '';
GRANT ALL PRIVILEGES ON mailer.* TO mailer @localhost;
FLUSH PRIVILEGES;

USE mailer;

CREATE TABLE contact_form_messages (
	id BIGINT(255) NOT NULL AUTO_INCREMENT,
	referrer VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	ip VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	name VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	email VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	message TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) COLLATE = 'utf8mb4_unicode_ci';
