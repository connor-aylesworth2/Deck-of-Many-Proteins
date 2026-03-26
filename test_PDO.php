<?php
require_once "login.php";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();

    echo "<h1>Connected successfully</h1>";

    if (count($tables) > 0) {
        echo "<h2>Tables in database:</h2><ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars(array_values($table)[0]) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found yet.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
