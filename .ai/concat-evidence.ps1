[CmdletBinding()]
param(
    [string]$OutputPath = (Join-Path '.ai' 'concatenated.txt'),

    [switch]$KeepSourceFiles
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Write-Section {
    param(
        [System.IO.StreamWriter]$Writer,
        [System.IO.FileInfo]$File
    )

    $relativePath = Resolve-Path -Relative $File.FullName

    $Writer.WriteLine('')
    $Writer.WriteLine('================================================================================')
    $Writer.WriteLine("FILE: $relativePath")
    $Writer.WriteLine("LAST WRITE: $($File.LastWriteTime.ToString('o'))")
    $Writer.WriteLine("SIZE: $($File.Length) bytes")
    $Writer.WriteLine('================================================================================')
    $Writer.WriteLine('')

    $content = Get-Content -LiteralPath $File.FullName -Raw -ErrorAction Stop

    if ($null -ne $content -and $content.Length -gt 0) {
        $Writer.Write($content)

        if (-not $content.EndsWith("`n")) {
            $Writer.WriteLine('')
        }
    }

    $Writer.WriteLine('')
}

$sourceDirs = @(
    (Join-Path '.ai' 'out'),
    (Join-Path (Join-Path (Join-Path '.ai' 'scale') 'state') 'queries')
)

$outputFullPath = [System.IO.Path]::GetFullPath($OutputPath)

$files = foreach ($dir in $sourceDirs) {
    if (Test-Path -LiteralPath $dir) {
        Get-ChildItem -LiteralPath $dir -File -Recurse |
            Where-Object {
                [System.IO.Path]::GetFullPath($_.FullName) -ne $outputFullPath
            }
    }
}

$files = @($files | Sort-Object FullName)

if ($files.Count -eq 0) {
    Write-Host 'No source files found to concatenate.'
    exit 0
}

$outputDir = Split-Path -Parent $OutputPath

if (-not [string]::IsNullOrWhiteSpace($outputDir)) {
    New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
}

$tempPath = "$OutputPath.tmp"

if (Test-Path -LiteralPath $tempPath) {
    Remove-Item -LiteralPath $tempPath -Force
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$writer = New-Object System.IO.StreamWriter($tempPath, $false, $utf8NoBom)

try {
    $writer.WriteLine('AI EVIDENCE CONCATENATION')
    $writer.WriteLine("CREATED: $((Get-Date).ToString('o'))")
    $writer.WriteLine("SOURCE COUNT: $($files.Count)")
    $writer.WriteLine('')

    foreach ($file in $files) {
        Write-Section -Writer $writer -File $file
    }
}
finally {
    $writer.Dispose()
}

Move-Item -LiteralPath $tempPath -Destination $OutputPath -Force

if (-not $KeepSourceFiles) {
    foreach ($file in $files) {
        Remove-Item -LiteralPath $file.FullName -Force
    }
}

Write-Host "WROTE: $OutputPath"
Write-Host "SOURCE FILES: $($files.Count)"

if ($KeepSourceFiles) {
    Write-Host 'SOURCE FILES KEPT'
}
else {
    Write-Host 'SOURCE FILES DELETED'
}