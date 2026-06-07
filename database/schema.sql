-- SMS Shortcode database schema
-- Recreates tables after InnoDB engine corruption (error #1932)

CREATE DATABASE IF NOT EXISTS sms_shortcode
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE sms_shortcode;

CREATE TABLE IF NOT EXISTS technicians (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_technicians_email (email),
    KEY idx_technicians_category (category),
    KEY idx_technicians_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS inbound_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender VARCHAR(50) NOT NULL,
    full_name VARCHAR(255) NOT NULL DEFAULT 'Unknown',
    message TEXT NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    status VARCHAR(50) NOT NULL DEFAULT 'new',
    channel VARCHAR(50) NOT NULL DEFAULT 'sms',
    message_type VARCHAR(50) NOT NULL DEFAULT 'text',
    source VARCHAR(50) NOT NULL DEFAULT 'unknown',
    caller_name VARCHAR(255) NULL,
    user_email VARCHAR(255) NULL,
    external_id VARCHAR(255) NULL,
    ticket_status VARCHAR(50) NOT NULL DEFAULT 'new',
    assigned_to VARCHAR(255) NULL,
    assigned_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inbound_status (status),
    KEY idx_inbound_category (category),
    KEY idx_inbound_source (source),
    KEY idx_inbound_created_at (created_at),
    KEY idx_inbound_sender (sender)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
