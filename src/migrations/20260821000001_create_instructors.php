<?php
/**
 * Chess School Platform - Instructors (main platform user)
 *
 * The instructor is the primary user of the platform: an independent coach
 * (or a chess school owner) who registers with email + password and manages
 * their own classes and students.
 *
 * NOTE: this is the first table of the platform that uses a real
 * login/password flow. The legacy OpenELO entities (circuits, clubs, players)
 * remain untouched and keep working with their email-confirmation workflow.
 */
return [
    'up' => function(PDO $db, string $dbType) {
        $engine  = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $autoInc = $dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';

        $db->exec("
            CREATE TABLE IF NOT EXISTS instructors (
                id $intType PRIMARY KEY $autoInc,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                school_name VARCHAR(190),
                phone VARCHAR(50),
                city VARCHAR(190),
                bio TEXT,
                lang VARCHAR(5),
                email_verified $intType DEFAULT 0,
                status VARCHAR(20) DEFAULT 'active',
                last_login_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                deleted_at DATETIME
            )$engine
        ");

        try {
            $db->exec("CREATE UNIQUE INDEX idx_instructors_email ON instructors(email)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '1061') === false) throw $e;
        }

        // Single-purpose tokens: email verification and password reset
        $db->exec("
            CREATE TABLE IF NOT EXISTS instructor_tokens (
                id $intType PRIMARY KEY $autoInc,
                instructor_id $intType NOT NULL,
                token VARCHAR(64) NOT NULL,
                type VARCHAR(30) NOT NULL,
                used $intType DEFAULT 0,
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (instructor_id) REFERENCES instructors(id)
            )$engine
        ");

        try {
            $db->exec("CREATE UNIQUE INDEX idx_instructor_tokens_token ON instructor_tokens(token)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '1061') === false) throw $e;
        }
    },

    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS instructor_tokens");
        $db->exec("DROP TABLE IF EXISTS instructors");
    }
];
