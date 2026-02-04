<?php
/**
 * Migration: Add Match Rating Tracking
 *
 * Adds columns to store ratings and rating changes at match creation time.
 * This ensures rating changes are frozen at match time, even if approved later.
 */

return [
    'up' => function($db, $dbType) {
        // Add columns to matches table
        if ($dbType === 'mysql') {
            $db->exec("
                ALTER TABLE matches
                ADD COLUMN white_rating_before INT DEFAULT 1500,
                ADD COLUMN black_rating_before INT DEFAULT 1500,
                ADD COLUMN white_rating_change INT DEFAULT 0,
                ADD COLUMN black_rating_change INT DEFAULT 0
            ");
        } else {
            // SQLite
            $db->exec("ALTER TABLE matches ADD COLUMN white_rating_before INTEGER DEFAULT 1500");
            $db->exec("ALTER TABLE matches ADD COLUMN black_rating_before INTEGER DEFAULT 1500");
            $db->exec("ALTER TABLE matches ADD COLUMN white_rating_change INTEGER DEFAULT 0");
            $db->exec("ALTER TABLE matches ADD COLUMN black_rating_change INTEGER DEFAULT 0");
        }
    },

    'down' => function($db, $dbType) {
        // SQLite doesn't support DROP COLUMN easily, so we skip the down migration
        // For MySQL, we could drop the columns but we'll keep them for data preservation
    }
];
