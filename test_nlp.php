<?php
require_once 'vendor/autoload.php';
require_once 'db_connection.php';
require_once 'natural_language_processor.php';

// Test commands with various scenarios
$test_commands = [
    "Phil took 1 12 x 12 Tee, and returned 2 12 inch, 90", // Valid command
    "Phil took 1 12 inch tee", // Should show suggestions
    "Phil took 1 12 inch ninety" // Should show suggestions
];

$processor = new NaturalLanguageProcessor($pdo);

foreach ($test_commands as $command) {
    echo "\nTesting command: " . $command . "\n";
    try {
        $result = $processor->processCommand($command);
        if (!$result['success']) {
            echo "Message: " . $result['message'] . "\n";
            echo "Suggestions:\n";
            foreach ($result['suggestions'] as $suggestion) {
                echo "- " . $suggestion . "\n";
            }
        } else {
            echo "Successfully parsed commands:\n";
            print_r($result['commands']);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} 