# .ai\guest-run.ps1

param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string] $Command,

    [switch] $NoSync
)

$ErrorActionPreference = 'Stop'

$Guest = 'root@dev.joefallon.net'
$RemoteRoot = '/var/www/kissmvc/website'
$IdentityFile = Join-Path $env:USERPROFILE '.ssh\homestory-dev-ed25519'

$SshArgs = @(
    '-i', $IdentityFile,
    '-o', 'IdentitiesOnly=yes',
    '-o', 'BatchMode=yes',
    '-o', 'StrictHostKeyChecking=accept-new'
)

function Quote-Bash {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Value
    )

    return "'" + ($Value -replace "'", "'\''") + "'"
}

if (-not (Test-Path $IdentityFile -PathType Leaf)) {
    throw "SSH identity file not found: $IdentityFile"
}

if ([string]::IsNullOrWhiteSpace($Command)) {
    throw 'Usage: .\.ai\guest-run.ps1 "composer test"'
}

if ($Command.Contains("`n") -or $Command.Contains("`r")) {
    throw 'Remote command must be a single-line command.'
}

if (-not $NoSync) {
    $SyncScript = Join-Path $PSScriptRoot 'guest-sync.ps1'
    & $SyncScript

    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

$RemoteRootQuoted = Quote-Bash $RemoteRoot
$CommandBase64 = [Convert]::ToBase64String(
    [System.Text.Encoding]::UTF8.GetBytes($Command)
)
$RemoteCommand = "cd $RemoteRootQuoted && export COMPOSER_ALLOW_SUPERUSER=1 && export XDEBUG_MODE=off && printf '%s' $CommandBase64 | base64 --decode | bash 2>&1"

Write-Host "REMOTE $Command"

ssh @SshArgs $Guest $RemoteCommand

exit $LASTEXITCODE
