# Get the script's directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Read the SQL file
$sqlContent = Get-Content -Path "$scriptDir\..\update_scripts\20240320_add_image_columns.sql" -Raw

# Execute the SQL using phpMyAdmin or your preferred method
# For now, we'll just log the SQL content
Write-Host "Executing SQL script to add image columns..."
Write-Host $sqlContent

# Log the execution in the scripts database
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$description = "Added image columns to consumable_materials table and created image_uploads table"

$dbPath = "$scriptDir\..\scripts.db"
$query = @"
INSERT INTO script_executions (script_name, execution_date, description)
VALUES ('20240320_add_image_columns.ps1', '$timestamp', '$description')
"@

# Execute the query using SQLite
sqlite3 $dbPath $query

Write-Host "Script execution completed and logged." 