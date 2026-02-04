<?php
/**
 * OpenElo - Migrations Registry
 *
 * Register all migrations in chronological order.
 * Each migration should have a unique timestamp prefix (YYYYMMDDHHMMSS).
 */

if (!defined('SRC_PATH')) {
    define('SRC_PATH', __DIR__);
}

return [
    '20240101000000_create_migrations_table',
    '20240101000001_create_initial_schema',
    '20240101000002_create_deletion_requests',
    '20240101000003_add_soft_delete',
];
