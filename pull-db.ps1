# SaaqiFolio - Pull Database from Hostinger
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Pull Hostinger Live Database -> Localhost" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$backupPath = "database/live_hostinger_backup.sql"
Write-Host "[1/2] Dumping live database from Hostinger (u195418993_SaaqiFolio)..." -ForegroundColor Yellow
& "g:\ct-xammps\mysql\bin\mysqldump.exe" -h srv578.hstgr.io -u u195418993_SaaqiFolio -p"Saydev@1234" u195418993_SaaqiFolio > $backupPath

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to dump live database from Hostinger." -ForegroundColor Red
    exit 1
}

Write-Host "[2/2] Importing into Localhost MySQL (folivo)..." -ForegroundColor Yellow
Get-Content $backupPath | & "g:\ct-xammps\mysql\bin\mysql.exe" -u root folivo

if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Database successfully pulled from Hostinger into Localhost!" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to import database locally." -ForegroundColor Red
}
