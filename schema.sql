-- Face Register Module — database schema
-- Run this once to set up the required tables.

CREATE DATABASE IF NOT EXISTS face_db;
USE face_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    timestamp DATETIME NOT NULL
);
