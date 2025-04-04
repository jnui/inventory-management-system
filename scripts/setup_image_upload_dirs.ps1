# Get the script's directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptDir

# Define upload directories
$uploadDir = Join-Path $projectRoot "uploads"
$imagesDir = Join-Path $uploadDir "images"
$thumb50Dir = Join-Path $imagesDir "thumb_50"
$thumb150Dir = Join-Path $imagesDir "thumb_150"

# Create directories if they don't exist
Write-Host "Creating upload directories..."
New-Item -ItemType Directory -Force -Path $uploadDir | Out-Null
New-Item -ItemType Directory -Force -Path $imagesDir | Out-Null
New-Item -ItemType Directory -Force -Path $thumb50Dir | Out-Null
New-Item -ItemType Directory -Force -Path $thumb150Dir | Out-Null

# Set permissions (read/write for everyone)
Write-Host "Setting directory permissions..."
$acl = Get-Acl $uploadDir
$accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone","FullControl","ContainerInherit,ObjectInherit","None","Allow")
$acl.SetAccessRule($accessRule)
Set-Acl $uploadDir $acl

# Log the execution in the scripts database
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$description = "Created image upload directories and set permissions"

$dbPath = Join-Path $scriptDir "scripts.db"
$query = @"
INSERT INTO script_executions (script_name, execution_date, description)
VALUES ('setup_image_upload_dirs.ps1', '$timestamp', '$description')
"@

# Execute the query using SQLite
sqlite3 $dbPath $query

Write-Host "Setup completed successfully." 