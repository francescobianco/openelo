<?php
/**
 * OpenElo - Utility Functions
 */

/**
 * Normalize name for similarity comparison
 * - Lowercase
 * - Remove accents
 * - Remove special characters
 * - Trim and normalize spaces
 */
function normalizeName(string $name): string {
    // Lowercase
    $name = mb_strtolower($name, 'UTF-8');

    // Remove accents
    $accents = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];
    $name = strtr($name, $accents);

    // Remove special characters (keep only letters, numbers, and spaces)
    $name = preg_replace('/[^a-z0-9\s]/u', '', $name);

    // Normalize spaces
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);

    return $name;
}

/**
 * Check if a similar name already exists
 */
function checkSimilarName(PDO $db, string $table, string $name, ?int $excludeId = null): ?array {
    $normalized = normalizeName($name);

    // Get all names from the table
    $query = "SELECT id, name FROM $table";
    if ($excludeId) {
        $query .= " WHERE id != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$excludeId]);
    } else {
        $stmt = $db->query($query);
    }

    $existing = $stmt->fetchAll();

    foreach ($existing as $item) {
        if (normalizeName($item['name']) === $normalized) {
            return $item;
        }
    }

    return null;
}
