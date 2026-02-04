# Database Migrations

This directory contains database migration files for OpenElo.

## How to Run Migrations

### From Command Line (Recommended)
```bash
php -f migrate.php
```

### From Browser (Legacy Hosting)
Visit: `https://yourdomain.com/migrate.php`

## How to Create a New Migration

1. Create a new file in `src/migrations/` with the format:
   ```
   YYYYMMDDHHMMSS_description.php
   ```
   Example: `20240115120000_add_user_avatar.php`

2. Register the migration in `src/migrations.php`:
   ```php
   return [
       // ... existing migrations
       '20240115120000_add_user_avatar',
   ];
   ```

3. Write the migration code:
   ```php
   <?php
   return [
       'up' => function(PDO $db, string $dbType) {
           if ($dbType === 'mysql') {
               $db->exec("ALTER TABLE players ADD COLUMN avatar VARCHAR(255)");
           } else {
               // SQLite
               $db->exec("ALTER TABLE players ADD COLUMN avatar TEXT");
           }
       },

       'down' => function(PDO $db, string $dbType) {
           // SQLite doesn't support DROP COLUMN easily
           // You might need to recreate the table
           if ($dbType === 'mysql') {
               $db->exec("ALTER TABLE players DROP COLUMN avatar");
           }
       }
   ];
   ```

## Database Support

The migration system supports both:
- **SQLite** (default)
- **MySQL/MariaDB**

Use the `$dbType` parameter to write database-specific queries.

## Tips

- Always test migrations on a backup database first
- Use transactions (they're automatic in the migration runner)
- Keep migrations small and focused
- Never edit a migration that has already been executed
- Use descriptive names for migrations
