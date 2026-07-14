# Sincroniza o JACAD -> banco Neon (produção).
# Rode SEMPRE desta máquina/rede (o IP precisa estar liberado no JACAD).
# Uso manual:  botão direito -> "Executar com o PowerShell"  (ou:  ./scripts/sync-neon.ps1)
# Uso agendado: ver instruções no final (Agendador de Tarefas do Windows).

$ErrorActionPreference = 'Stop'

# Raiz do projeto (pasta acima de /scripts)
$proj = Split-Path -Parent $PSScriptRoot
Set-Location $proj

# Lê a URL da Neon de .env.neon (linha: DB_URL=postgresql://...). Arquivo NÃO versionado.
$neonFile = Join-Path $proj '.env.neon'
if (-not (Test-Path $neonFile)) {
    Write-Error "Arquivo .env.neon nao encontrado. Copie .env.neon.example para .env.neon e preencha a DB_URL."
    exit 1
}
$linha = Get-Content $neonFile | Where-Object { $_ -match '^\s*DB_URL\s*=' } | Select-Object -First 1
$dbUrl = ($linha -replace '^\s*DB_URL\s*=\s*', '').Trim().Trim('"').Trim("'")
if ([string]::IsNullOrWhiteSpace($dbUrl)) { Write-Error 'DB_URL vazia em .env.neon'; exit 1 }

# Aponta a conexao para a Neon (sobrepoe o .env local, que continua no 127.0.0.1)
$env:DB_CONNECTION = 'pgsql'
$env:DB_URL = $dbUrl

# PHP via scoop shims
$env:PATH = "$env:USERPROFILE\scoop\shims;$env:PATH"

# Log
$logDir = Join-Path $proj 'storage\logs'
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Force -Path $logDir | Out-Null }
$log = Join-Path $logDir 'sync-neon.log'

$inicio = Get-Date
"[$($inicio.ToString('yyyy-MM-dd HH:mm:ss'))] === Iniciando sync JACAD -> Neon ===" | Tee-Object -FilePath $log -Append

php artisan jacad:sync-matriculas 2>&1 | Tee-Object -FilePath $log -Append

$fim = Get-Date
$dur = [int]($fim - $inicio).TotalSeconds
"[$($fim.ToString('yyyy-MM-dd HH:mm:ss'))] === Fim (${dur}s), exit=$LASTEXITCODE ===`n" | Tee-Object -FilePath $log -Append

exit $LASTEXITCODE
