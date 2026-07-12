# Configuration
$BackupFolder = "D:\ssglsatpalmama\dataBackup"
$ContainerName = "company_1_mysql"
$DbUser = "root"
$DbPass = "root"
$DbName = "ssgls"

# Ensure the backup directory exists
if (-not (Test-Path -Path $BackupFolder)) {
    New-Item -ItemType Directory -Force -Path $BackupFolder | Out-Null
}

# Generate filename with date, month, year, and time
$DateString = Get-Date -Format "dd-MM-yyyy_HH-mm"
$BackupFile = Join-Path $BackupFolder "${DbName}_backup_${DateString}.sql"

Write-Host "Starting backup for database '$DbName' from container '$ContainerName'..."

try {
    # Note: We use -i instead of -t to avoid TTY output formatting issues (like carriage return duplication)
    # We execute via cmd.exe to ensure clean stdout redirection to the file
    cmd.exe /c "docker exec -i $ContainerName mysqldump -u$DbUser -p$DbPass $DbName > `"$BackupFile`""
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Backup completed successfully! Saved to: $BackupFile"
    } else {
        Write-Error "Backup failed with exit code $LASTEXITCODE"
    }
} catch {
    Write-Error "An unexpected error occurred during backup: $_"
}
