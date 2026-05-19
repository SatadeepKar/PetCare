# PetCare - start PHP app + Vet Locator API (Windows)
# Usage: .\start-petcare.ps1
#        .\start-petcare.ps1 -NoBrowser

param(
    [int]$PhpPort = 8888,
    [int]$ApiPort = 3002,
    [switch]$NoBrowser
)

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot
$PhpRoot = Join-Path $Root "PetCare"
$ApiRoot = Join-Path $PhpRoot "VetShopsLocator"

function Write-Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Write-Ok($msg)   { Write-Host "    $msg" -ForegroundColor Green }
function Write-Warn($msg) { Write-Host "    $msg" -ForegroundColor Yellow }
function Write-Err($msg)  { Write-Host "    $msg" -ForegroundColor Red }

function Test-Command($name) {
    return [bool](Get-Command $name -ErrorAction SilentlyContinue)
}

function Test-PortListening($port) {
    try {
        $c = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction Stop | Select-Object -First 1
        return [bool]$c
    } catch {
        return $false
    }
}

Write-Host ""
Write-Host "  PetCare - Run Everything" -ForegroundColor Magenta
Write-Host "  ========================" -ForegroundColor Magenta

# --- Prerequisites ---
Write-Step "Checking prerequisites"

if (-not (Test-Path $PhpRoot)) {
    Write-Err "PHP app folder not found: $PhpRoot"
    exit 1
}
if (-not (Test-Path $ApiRoot)) {
    Write-Err "Vet Locator folder not found: $ApiRoot"
    exit 1
}

if (-not (Test-Command "php")) {
    Write-Err "PHP not found in PATH. Install PHP or add XAMPP php to PATH."
    Write-Warn "Example: C:\xampp\php"
    exit 1
}
Write-Ok "PHP: $(php -v | Select-Object -First 1)"

if (-not (Test-Command "node")) {
    Write-Err "Node.js not found in PATH."
    exit 1
}
Write-Ok "Node: $(node -v)"

if (-not (Test-Command "npm")) {
    Write-Err "npm not found in PATH."
    exit 1
}
Write-Ok "npm: $(npm -v)"

if (Test-PortListening 3306) {
    Write-Ok "MySQL appears to be running (port 3306)."
} else {
    Write-Warn "MySQL not detected on port 3306."
    Write-Warn "Start MySQL in XAMPP before using login/dashboard features."
    Write-Warn "Import PetCare/schema.sql if the database is not set up yet."
}

# --- Node dependencies ---
Write-Step "Vet Locator API dependencies"
if (-not (Test-Path (Join-Path $ApiRoot "node_modules"))) {
    Write-Warn "Installing npm packages (first run)..."
    Push-Location $ApiRoot
    npm install
    if ($LASTEXITCODE -ne 0) { Pop-Location; exit 1 }
    Pop-Location
    Write-Ok "npm install complete."
} else {
    Write-Ok "node_modules present."
}

if (-not (Test-Path (Join-Path $ApiRoot ".env"))) {
    Write-Warn ".env missing in VetShopsLocator. API may exit without FOURSQUARE_API_KEY."
}

# --- Ports ---
Write-Step "Checking ports"
if (Test-PortListening $PhpPort) {
    Write-Warn "Port $PhpPort already in use (PHP may already be running)."
}
if (Test-PortListening $ApiPort) {
    Write-Warn "Port $ApiPort already in use (Vet Locator API may already be running)."
}

# --- Start servers in new windows ---
Write-Step "Starting servers (separate windows)"

$phpCmd = "Set-Location -LiteralPath '$PhpRoot'; Write-Host 'PetCare PHP app - http://localhost:$PhpPort' -ForegroundColor Green; php -S localhost:$PhpPort"
Start-Process powershell -ArgumentList @("-NoExit", "-Command", $phpCmd) | Out-Null
Write-Ok "PHP app -> http://localhost:$PhpPort"

$apiCmd = "Set-Location -LiteralPath '$ApiRoot'; Write-Host 'Vet Locator API - http://localhost:$ApiPort' -ForegroundColor Green; npm start"
Start-Process powershell -ArgumentList @("-NoExit", "-Command", $apiCmd) | Out-Null
Write-Ok "Vet Locator API -> http://localhost:$ApiPort"
Write-Ok "Vet Locator UI -> http://localhost:$ApiPort/ (or from app: Vet Locator link)"

Start-Sleep -Seconds 2

# --- Summary ---
Write-Host ""
Write-Host "  Ready" -ForegroundColor Green
Write-Host "  -----" -ForegroundColor Green
Write-Host "  Main app (landing):  http://localhost:$PhpPort/index.php"
Write-Host "  Dashboard (login):   http://localhost:$PhpPort/login.php"
Write-Host "  Vet map search:      http://localhost:$ApiPort/"
Write-Host ""
Write-Host "  Close the two PowerShell windows to stop the servers."
Write-Host ""

if (-not $NoBrowser) {
    Start-Process "http://localhost:$PhpPort/index.php"
}
