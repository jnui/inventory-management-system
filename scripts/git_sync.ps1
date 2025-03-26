# git_sync.ps1
# This script handles git operations with logging

# Set up logging
$logFile = "git_sync.log"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

# Function to write to log
function Write-Log {
    param($Message)
    $logMessage = "$timestamp - $Message"
    Add-Content -Path $logFile -Value $logMessage
    Write-Host $logMessage
}

# Function to execute git command and log result
function Invoke-GitCommand {
    param(
        [string]$Command,
        [string]$Description
    )
    Write-Log "Executing: $Description"
    try {
        $result = Invoke-Expression "git $Command"
        Write-Log "Success: $Description"
        return $result
    }
    catch {
        Write-Log "Error: $Description - $_"
        throw
    }
}

# Main execution
try {
    # Check if we're in a git repository
    if (-not (Test-Path .git)) {
        Write-Log "Initializing git repository..."
        Invoke-GitCommand "init" "Initialize git repository"
    }

    # Add all files
    Invoke-GitCommand "add ." "Add all files to staging"

    # Get status
    $status = Invoke-GitCommand "status" "Get git status"
    Write-Log "Current status:"
    Write-Log $status

    # If there are changes, commit them
    if ($status -match "Changes to be committed") {
        $commitMessage = "Auto-sync: $timestamp"
        Invoke-GitCommand "commit -m '$commitMessage'" "Commit changes"
        Write-Log "Changes committed successfully"
    } else {
        Write-Log "No changes to commit"
    }

    # If remote exists, push changes
    $remotes = Invoke-GitCommand "remote -v" "Get remote repositories"
    if ($remotes) {
        Invoke-GitCommand "push" "Push changes to remote"
        Write-Log "Changes pushed successfully"
    } else {
        Write-Log "No remote repository configured"
    }

    Write-Log "Git sync completed successfully"
}
catch {
    Write-Log "Error during git sync: $_"
    exit 1
} 