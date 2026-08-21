<?php
/**
 * Database Setup Script
 * This script helps set up the database tables required for the Supply Chain Management System.
 * Run this file once to create all necessary tables.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

echo "<h1>Supply Chain Management System - Database Setup</h1>";
echo "<p>This script will create all necessary database tables for the system.</p>";

if (!$conn->connect_error) {
    echo "<p style='color: green;'>✓ Database connection successful.</p>";
    
    // Read the schema file
    $schema_file = __DIR__ . '/schema_updates.sql';
    if (file_exists($schema_file)) {
        $schema_sql = file_get_contents($schema_file);
        
        // Split by individual statements
        $statements = explode(';', $schema_sql);
        
        $success_count = 0;
        $error_count = 0;
        
        echo "<h2>Creating Tables...</h2>";
        echo "<ul>";
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Skip comments
            if (strpos($statement, '--') === 0) continue;
            
            if ($conn->query($statement)) {
                $success_count++;
                echo "<li style='color: green;'>✓ Table created/updated successfully</li>";
            } else {
                $error_count++;
                echo "<li style='color: red;'>✗ Error: " . $conn->error . "</li>";
            }
        }
        
        echo "</ul>";
        
        echo "<h2>Setup Summary</h2>";
        echo "<p><strong>Successful operations:</strong> $success_count</p>";
        echo "<p><strong>Failed operations:</strong> $error_count</p>";
        
        if ($error_count === 0) {
            echo "<p style='color: green; font-weight: bold;'>✓ Database setup completed successfully!</p>";
            echo "<p>You can now use the Supply Chain Management System.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>⚠ Some tables could not be created. Please check the errors above.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Schema file not found: $schema_file</p>";
    }
    
    $conn->close();
} else {
    echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    echo "<p>Please check your database credentials in db_connection.php</p>";
}
?>