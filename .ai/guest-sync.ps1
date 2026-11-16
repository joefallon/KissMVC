# .ai\guest-sync.ps1

$ErrorActionPreference = 'Stop'

$Guest = 'root@dev.joefallon.net'
$RemoteRoot = '/var/www/kissmvc'
$IdentityFile = Join-Path $env:USERPROFILE '.ssh\homestory-dev-ed25519'

$SshArgs = @(
    '-i', $IdentityFile,
    '-o', 'IdentitiesOnly=yes',
    '-o', 'BatchMode=yes',
    '-o', 'StrictHostKeyChecking=accept-new'
)

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

function Convert-ToRemotePath {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path
    )

    return ($Path -replace '\\', '/')
}

function Quote-Bash {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Value
    )

    return "'" + ($Value -replace "'", "'\''") + "'"
}

function Assert-SafeRelativePath {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path
    )

    $RemotePath = Convert-ToRemotePath $Path

    if (
        $RemotePath.StartsWith('/') -or
        $RemotePath -eq '..' -or
        $RemotePath.StartsWith('../') -or
        $RemotePath.Contains('/../') -or
        $RemotePath.Contains("`0")
    ) {
        throw "Unsafe relative path from git status: $Path"
    }

    return $RemotePath
}

function Test-IsExcludedPath {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path
    )

    $RemotePath = Convert-ToRemotePath $Path

    return (
        $RemotePath.StartsWith('vendor/') -or
        $RemotePath.StartsWith('.ai/out/') -or
        $RemotePath.StartsWith('.ai/scale/state/')
    )
}

function Invoke-Target {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Command
    )

    ssh @SshArgs $Guest $Command

    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

if (-not (Test-Path $IdentityFile -PathType Leaf)) {
    throw "SSH identity file not found: $IdentityFile"
}

Push-Location $Root

try {
    $StatusLines = git -c core.quotepath=false status --porcelain=v1 --untracked-files=all

    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }

    $FilesToCopy = New-Object System.Collections.Generic.List[string]
    $FilesToDelete = New-Object System.Collections.Generic.List[string]

    foreach ($Line in $StatusLines) {
        if ([string]::IsNullOrWhiteSpace($Line)) {
            continue
        }

        if ($Line.Length -lt 4) {
            continue
        }

        $Status = $Line.Substring(0, 2)
        $PathPart = $Line.Substring(3)

        if ($PathPart -like '* -> *') {
            $Parts = $PathPart -split ' -> ', 2

            if ($Parts.Count -eq 2) {
                $OldPath = $Parts[0]
                $NewPath = $Parts[1]

                if (
                    -not [string]::IsNullOrWhiteSpace($OldPath) -and
                    -not (Test-IsExcludedPath $OldPath)
                ) {
                    $FilesToDelete.Add($OldPath)
                }

                if (
                    -not [string]::IsNullOrWhiteSpace($NewPath) -and
                    -not (Test-IsExcludedPath $NewPath)
                ) {
                    $FilesToCopy.Add($NewPath)
                }

                continue
            }
        }

        if (Test-IsExcludedPath $PathPart) {
            continue
        }

        $LocalPath = Join-Path $Root $PathPart

        if ($Status.Contains('D') -and -not (Test-Path $LocalPath)) {
            $FilesToDelete.Add($PathPart)
            continue
        }

        if (Test-Path $LocalPath -PathType Leaf) {
            $FilesToCopy.Add($PathPart)
        }
    }

    $FilesToCopy = @($FilesToCopy | Sort-Object -Unique)
    $FilesToDelete = @($FilesToDelete | Sort-Object -Unique)

    Write-Host "Files to copy:   $($FilesToCopy.Count)"
    Write-Host "Files to delete: $($FilesToDelete.Count)"

    foreach ($RelPath in $FilesToDelete) {
        $RemoteRelPath = Assert-SafeRelativePath $RelPath
        $RemoteFile = "$RemoteRoot/$RemoteRelPath"

        Write-Host "DELETE $RemoteRelPath"

        Invoke-Target "rm -f $(Quote-Bash $RemoteFile)"
    }

    foreach ($RelPath in $FilesToCopy) {
        $RemoteRelPath = Assert-SafeRelativePath $RelPath
        $LocalFile = Join-Path $Root $RelPath
        $RemoteFile = "$RemoteRoot/$RemoteRelPath"
        $RemoteDir = Convert-ToRemotePath (Split-Path $RemoteFile -Parent)

        Write-Host "COPY   $RemoteRelPath"

        Invoke-Target "mkdir -p $(Quote-Bash $RemoteDir)"

        scp @SshArgs $LocalFile "${Guest}:$RemoteFile"

        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
    }

    Write-Host 'Guest sync complete.'
}
finally {
    Pop-Location
}
