# Get the script's directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Read the SQL file
$sqlContent = Get-Content -Path "$scriptDir\..\add_last_updated_to_order_history.sql" -Raw

# Execute the SQL
try {
    # Connect to the database and execute the SQL
    $connectionString = "Server=localhost;Database=smccontr_inventory;User=root;Password=;"
    $connection = New-Object System.Data.SqlClient.SqlConnection
    $connection.ConnectionString = $connectionString
    $connection.Open()

    $command = New-Object System.Data.SqlClient.SqlCommand($sqlContent, $connection)
    $command.ExecuteNonQuery()

    # Log the execution
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "$timestamp - Added last_updated column to order_history table and created update trigger"
    
    # Insert into scripts_log table
    $logSql = "INSERT INTO scripts_log (script_name, execution_date, description) VALUES ('update_order_history_timestamp.ps1', NOW(), '$logMessage')"
    $logCommand = New-Object System.Data.SqlClient.SqlCommand($logSql, $connection)
    $logCommand.ExecuteNonQuery()

    Write-Host "Successfully added last_updated column and created trigger"
} catch {
    Write-Host "Error: $_"
} finally {
    if ($connection.State -eq 'Open') {
        $connection.Close()
    }
} 