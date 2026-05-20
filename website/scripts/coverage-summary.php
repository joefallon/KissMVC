<?php
declare(strict_types=1);

if($argc !== 2)
{
    fwrite(STDERR, "Usage: php scripts/coverage-summary.php <clover.xml>\n");
    exit(1);
}

$cloverFile = $argv[1];

if(!is_file($cloverFile) || !is_readable($cloverFile))
{
    fwrite(STDERR, "Clover file not found or unreadable: {$cloverFile}\n");
    exit(1);
}

$cloverXml = simplexml_load_file($cloverFile);
if($cloverXml === false)
{
    fwrite(STDERR, "Failed to parse Clover XML: {$cloverFile}\n");
    exit(1);
}

$project = $cloverXml->project;
if(!$project instanceof SimpleXMLElement)
{
    fwrite(STDERR, "Invalid Clover XML: missing <project>\n");
    exit(1);
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function stripBasePath(string $path, string|false $basePath): string
{
    $path = normalizePath($path);

    if($basePath === false)
    {
        return $path;
    }

    $basePath = normalizePath($basePath);
    $basePath = rtrim($basePath, '/');

    if($basePath === '')
    {
        return $path;
    }

    if(str_starts_with($path, $basePath . '/'))
    {
        return substr($path, strlen($basePath) + 1);
    }

    return $path;
}

function colorize(string $text, float $percent, bool $isTty): string
{
    if(!$isTty)
    {
        return $text;
    }

    if($percent < 50.0)
    {
        return "\033[31m{$text}\033[0m";
    }

    if($percent < 80.0)
    {
        return "\033[33m{$text}\033[0m";
    }

    return "\033[32m{$text}\033[0m";
}

$basePath = realpath(dirname(__DIR__));
$rows = [];
$totalStatements = 0;
$totalCovered = 0;

foreach($project->xpath('.//file') ?: [] as $fileNode)
{
    $fileName = stripBasePath((string)$fileNode['name'], $basePath);
    $statements = 0;
    $covered = 0;

    foreach($fileNode->metrics as $metrics)
    {
        $statements += (int)$metrics['statements'];
        $covered += (int)$metrics['coveredstatements'];
    }

    if($statements === 0)
    {
        continue;
    }

    $rows[] = [
        'file' => $fileName,
        'statements' => $statements,
        'covered' => $covered,
        'percent' => ($covered / $statements) * 100,
    ];

    $totalStatements += $statements;
    $totalCovered += $covered;
}

usort($rows, static function(array $left, array $right): int {
    return strcmp($left['file'], $right['file']);
});

$fileWidth = strlen('FILE');
foreach($rows as $row)
{
    $fileWidth = max($fileWidth, strlen($row['file']));
}

$fileWidth = min($fileWidth, 90);
$isTty = function_exists('stream_isatty') && stream_isatty(STDOUT);

printf('%-' . $fileWidth . 's %8s %8s %8s' . "\n", 'FILE', 'STMT', 'COVER', 'LINE%');

foreach($rows as $row)
{
    $linePercent = sprintf('%7.2f', $row['percent']);
    printf(
        '%-' . $fileWidth . 's %8d %8d %s' . "\n",
        $row['file'],
        $row['statements'],
        $row['covered'],
        colorize($linePercent, $row['percent'], $isTty)
    );
}

$totalPercent = $totalStatements > 0 ? ($totalCovered / $totalStatements) * 100 : 0.0;
$totalPercentText = sprintf('%7.2f', $totalPercent);

printf(
    '%-' . $fileWidth . 's %8d %8d %s' . "\n",
    'TOTAL',
    $totalStatements,
    $totalCovered,
    colorize($totalPercentText, $totalPercent, $isTty)
);
