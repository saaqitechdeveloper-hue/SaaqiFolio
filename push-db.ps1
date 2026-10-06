# SaaqiFolio - Push Database to Hostinger
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Push Local Database -> Hostinger Live" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$dumpPath = "database/saaqifolio.sql"
Write-Host "[1/2] Exporting local database (folivo)..." -ForegroundColor Yellow
& "g:\ct-xammps\mysql\bin\mysqldump.exe" -u root folivo > $dumpPath

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to dump local database." -ForegroundColor Red
    exit 1
}

Write-Host "[2/2] Importing into Hostinger Live MySQL (u195418993_SaaqiFolio)..." -ForegroundColor Yellow
Get-Content $dumpPath | & "g:\ct-xammps\mysql\bin\mysql.exe" -h srv578.hstgr.io -u u195418993_SaaqiFolio -p"Saydev@1234" u195418993_SaaqiFolio

if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Database successfully pushed to Hostinger live!" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to import database to Hostinger." -ForegroundColor Red
}
