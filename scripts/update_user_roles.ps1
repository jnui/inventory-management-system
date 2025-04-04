# Update user roles script
$scriptName = "update_user_roles.ps1"
$description = "Updates the users table to add the read-only role and modifies existing users if needed"

# Log function
function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp] $Message"
}

try {
    # Connect to MySQL database
    $env:DB_HOST = "localhost"
    $env:DB_NAME = "smccontr_inventory"
    $env:DB_USER = "root"
    $env:DB_PASS = ""

    # Create PHP script to update the database
    $phpScript = @"
<?php
require_once 'db_connection.php';

try {
    // Update the users table to add the read-only role
    \$pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'readonly') NOT NULL DEFAULT 'user'");
    echo "Successfully updated users table schema\n";
} catch (PDOException \$e) {
    echo "Error: " . \$e->getMessage() . "\n";
    exit(1);
}
"@

    # Save the PHP script
    $phpScriptPath = Join-Path $PSScriptRoot ".." "update_user_roles.php"
    $phpScript | Out-File -FilePath $phpScriptPath -Encoding UTF8

    # Execute the PHP script using the full PHP path
    Write-Log "Updating database schema..."
    & php.exe $phpScriptPath

    # Clean up the temporary PHP script
    Remove-Item $phpScriptPath

    Write-Log "Script completed successfully"
} catch {
    Write-Log "Error: $_"
    exit 1
} 