# .ai/repo-metrics.ps1 -Since "30 hours ago" -Directories application,public

param(
    [string]$Since = "30 hours ago",

    [string[]]$Directories = @("application")
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Get-DisplayPathList {
    param(
        [string[]]$Paths
    )

    return ($Paths -join ", ")
}

function Get-GitPathspecArgs {
    param(
        [string[]]$Paths
    )

    $args = @("--")

    foreach ($path in $Paths) {
        $args += $path
    }

    return $args
}

function Get-ChurnMetrics {
    param(
        [string]$Title,
        [string[]]$GitArgs
    )

    $added = 0
    $removed = 0
    $changed = 0

    $lines = & git @GitArgs

    foreach ($line in $lines) {
        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }

        $parts = $line -split "`t"

        if ($parts.Count -lt 3) {
            continue
        }

        # Binary files show "-" instead of numbers in git numstat output.
        if ($parts[0] -eq "-" -or $parts[1] -eq "-") {
            continue
        }

        $lineAdded = [int]$parts[0]
        $lineRemoved = [int]$parts[1]

        $added += $lineAdded
        $removed += $lineRemoved
        $changed += [Math]::Min($lineAdded, $lineRemoved)
    }

    $pureAdded = $added - $changed
    $pureRemoved = $removed - $changed
    $churn = $added + $removed
    $net = $added - $removed

    Write-Host ""
    Write-Host $Title
    Write-Host ("Raw added:      {0}" -f $added)
    Write-Host ("Raw removed:    {0}" -f $removed)
    Write-Host ("Approx changed: {0}" -f $changed)
    Write-Host ("Pure added:     {0}" -f $pureAdded)
    Write-Host ("Pure removed:   {0}" -f $pureRemoved)
    Write-Host ("Total churn:    {0}" -f $churn)
    Write-Host ("Net LOC delta:  {0:+#;-#;0}" -f $net)
}

function Get-TrackedLoc {
    param(
        [string[]]$Paths
    )

    $extensions = @(
        ".php",
        ".phtml",
        ".inc",
        ".js",
        ".cs",
        ".cshtml",
        ".mjs",
        ".ts",
        ".css",
        ".html",
        ".htm",
        ".sql",
        ".xml",
        ".json",
        ".ini",
        ".conf",
        ".txt",
        ".md"
    )

    $gitArgs = @("ls-files")
    $gitArgs += Get-GitPathspecArgs -Paths $Paths

    $files = & git @gitArgs

    $total = 0

    foreach ($file in $files) {
        $extension = [System.IO.Path]::GetExtension($file)

        if ($extensions -notcontains $extension) {
            continue
        }

        if (-not (Test-Path -LiteralPath $file)) {
            continue
        }

        $total += (Get-Content -LiteralPath $file | Measure-Object -Line).Lines
    }

    return $total
}

function Get-DirectoryLocBreakdown {
    param(
        [string[]]$Paths
    )

    foreach ($path in $Paths) {
        $loc = Get-TrackedLoc -Paths @($path)

        [PSCustomObject]@{
            Path = $path
            TrackedLoc = $loc
        }
    }
}

$displayPaths = Get-DisplayPathList -Paths $Directories

$repoPathspec = Get-GitPathspecArgs -Paths @(".")
$selectedPathspec = Get-GitPathspecArgs -Paths $Directories

Get-ChurnMetrics `
    -Title "Committed repo churn since: $Since" `
    -GitArgs @("log", "--since=$Since", "--numstat", "--format=") + $repoPathspec

Get-ChurnMetrics `
    -Title "Committed selected-path churn since: $Since [$displayPaths]" `
    -GitArgs @("log", "--since=$Since", "--numstat", "--format=") + $selectedPathspec

Get-ChurnMetrics `
    -Title "Uncommitted repo churn vs HEAD" `
    -GitArgs @("diff", "--numstat", "HEAD") + $repoPathspec

Get-ChurnMetrics `
    -Title "Uncommitted selected-path churn vs HEAD [$displayPaths]" `
    -GitArgs @("diff", "--numstat", "HEAD") + $selectedPathspec

$totalLoc = Get-TrackedLoc -Paths $Directories
$breakdown = Get-DirectoryLocBreakdown -Paths $Directories

Write-Host ""
Write-Host "Current tracked LOC by selected path:"
$breakdown | Format-Table -AutoSize

Write-Host ("Current tracked LOC across selected paths [{0}]: {1}" -f $displayPaths, $totalLoc)