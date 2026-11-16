# .ai\guest-test.ps1

$ErrorActionPreference = 'Stop'

$RunScript = Join-Path $PSScriptRoot 'guest-run.ps1'

& $RunScript 'composer test'

exit $LASTEXITCODE