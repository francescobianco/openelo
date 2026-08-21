<?php
/**
 * Chess School Platform - Classes and Students
 *
 * A class (corso/classe) belongs to exactly one instructor.
 * A student belongs to one instructor (their address book) and can be
 * enrolled in many classes through class_students.
 *
 * students.player_id is an optional bridge to the legacy OpenELO `players`
 * table, so that in the future a student can be promoted to a rated player
 * inside a circuit without duplicating the person.
 */
return [
    'up' => function(PDO $db, string $dbType) {
        $engine  = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $autoInc = $dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';

        $db->exec("
            CREATE TABLE IF NOT EXISTS classes (
                id $intType PRIMARY KEY $autoInc,
                instructor_id $intType NOT NULL,
                name VARCHAR(190) NOT NULL,
                description TEXT,
                level VARCHAR(30) DEFAULT 'beginner',
                location VARCHAR(190),
                schedule VARCHAR(190),
                start_date DATE,
                end_date DATE,
                max_students $intType,
                color VARCHAR(20),
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                deleted_at DATETIME,
                FOREIGN KEY (instructor_id) REFERENCES instructors(id)
            )$engine
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS students (
                id $intType PRIMARY KEY $autoInc,
                instructor_id $intType NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(190),
                phone VARCHAR(50),
                birth_date DATE,
                guardian_name VARCHAR(190),
                guardian_email VARCHAR(190),
                guardian_phone VARCHAR(50),
                level VARCHAR(30) DEFAULT 'beginner',
                rating $intType,
                notes TEXT,
                player_id $intType,
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                deleted_at DATETIME,
                FOREIGN KEY (instructor_id) REFERENCES instructors(id)
            )$engine
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS class_students (
                id $intType PRIMARY KEY $autoInc,
                class_id $intType NOT NULL,
                student_id $intType NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                left_at DATETIME,
                FOREIGN KEY (class_id) REFERENCES classes(id),
                FOREIGN KEY (student_id) REFERENCES students(id)
            )$engine
        ");

        $indexes = [
            'CREATE INDEX idx_classes_instructor ON classes(instructor_id)',
            'CREATE INDEX idx_students_instructor ON students(instructor_id)',
            'CREATE UNIQUE INDEX idx_class_students_pair ON class_students(class_id, student_id)',
        ];
        foreach ($indexes as $sql) {
            try {
                $db->exec($sql);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '1061') === false) throw $e;
            }
        }
    },

    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS class_students");
        $db->exec("DROP TABLE IF EXISTS students");
        $db->exec("DROP TABLE IF EXISTS classes");
    }
];
