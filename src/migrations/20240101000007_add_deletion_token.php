<?php
/**
 * Migration: Add token to deletion requests for secure approval/rejection
 */

return [
    'up' => function($db, $dbType) {
        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE deletion_requests ADD COLUMN token VARCHAR(64) NULL");
        } else {
            $db->exec("ALTER TABLE deletion_requests ADD COLUMN token TEXT");
        }
    },

    'down' => function($db, $dbType) {
        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE deletion_requests DROP COLUMN token");
        }
    }
];
