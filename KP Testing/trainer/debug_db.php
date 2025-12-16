<?php
require_once '../includes/config.php';

function show_columns($pdo, $table) {
    echo "<h3>$table</h3><ul>";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
}

try {
    show_columns($pdo, 'sessions');
    show_columns($pdo, 'activities');
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
