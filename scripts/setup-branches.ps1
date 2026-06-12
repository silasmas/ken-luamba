# Script de création des branches de déploiement
# Usage : powershell -ExecutionPolicy Bypass -File scripts/setup-branches.ps1

$ErrorActionPreference = "Continue"
$repoRoot = Split-Path -Parent $PSScriptRoot
$tempBackend = Join-Path $env:TEMP "ken-luamba-backend"
$tempFrontend = Join-Path $env:TEMP "ken-luamba-frontend"

Write-Host "Copie des projets vers le dossier temporaire..."
if (Test-Path $tempBackend) {
  Remove-Item $tempBackend -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path $tempFrontend) {
  Remove-Item $tempFrontend -Recurse -Force -ErrorAction SilentlyContinue
}
Copy-Item (Join-Path $repoRoot "backend") $tempBackend -Recurse -Force
Copy-Item (Join-Path $repoRoot "frontend") $tempFrontend -Recurse -Force

Set-Location $repoRoot

function Reset-WorkingTree {
  Get-ChildItem -Force | Where-Object { $_.Name -ne ".git" } | ForEach-Object {
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
  }
}

function Switch-OrphanBranch {
  param([string]$BranchName)
  git checkout $BranchName 2>$null
  if ($LASTEXITCODE -ne 0) {
    git checkout --orphan $BranchName 2>$null
  }
  git rm -rf . 2>$null
  Reset-WorkingTree
}

Write-Host "Mise a jour de la branche backend-filament-api..."
Switch-OrphanBranch "backend-filament-api"
Copy-Item (Join-Path $tempBackend "*") $repoRoot -Recurse -Force
git add .
git commit -m "feat: synchronisation backend Laravel, Filament 5 et API Sanctum"

Write-Host "Mise a jour de la branche frontend-nextjs..."
Switch-OrphanBranch "frontend-nextjs"
Copy-Item (Join-Path $tempFrontend "*") $repoRoot -Recurse -Force
if (Test-Path "frontend") {
  git rm -r --cached frontend 2>$null
}
git add .
git commit -m "feat: synchronisation frontend Next.js 16 pour la boutique"

Write-Host "Retour sur main et restauration du monorepo local..."
git checkout main
if (-not (Test-Path (Join-Path $repoRoot "backend"))) {
  New-Item -ItemType Directory -Force -Path (Join-Path $repoRoot "backend") | Out-Null
}
if (-not (Test-Path (Join-Path $repoRoot "frontend"))) {
  New-Item -ItemType Directory -Force -Path (Join-Path $repoRoot "frontend") | Out-Null
}
Copy-Item (Join-Path $tempBackend "*") (Join-Path $repoRoot "backend") -Recurse -Force
Copy-Item (Join-Path $tempFrontend "*") (Join-Path $repoRoot "frontend") -Recurse -Force

Write-Host ""
Write-Host "Branches deploy pretes :"
Write-Host "  - backend-filament-api"
Write-Host "  - frontend-nextjs"
