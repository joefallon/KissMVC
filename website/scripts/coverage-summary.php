<?php
declare(strict_types=1);

$cloverFile = $argv[1] ?? 'build/coverage/clover.xml';
$basePath = realpath(dirname(__DIR__));

if(!is_file($cloverFile))
{
    fwrite(STDERR, "Coverage file not found: {$cloverFile}\n");
    exit(1);
}

$xml = simplexml_load_file($cloverFile);

if($xml === false)
{
    fwrite(STDERR, "Failed to parse Clover XML: {$cloverFile}\n");
    exit(1);
}

$rows = [];
$totalStatements = 0;
$totalCovered = 0;

$project = $xml->project;
if(!$project)
{
    fwrite(STDERR, "Invalid Clover XML: missing <project>\n");
    exit(1);
}

foreach($project->xpath('.//file') as $file)
{
    $name = (string)$file['name'];

    if($basePath !== false)
    {
        $prefix = $basePath . DIRECTORY_SEPARATOR;
        if(strpos($name, $prefix) === 0)
        {
            $name = substr($name, strlen($prefix));
        }
    }

    $statements = 0;
    $covered = 0;

    foreach($file->metrics as $metrics)
    {
        $statements += (int)$metrics['statements'];
        $covered += (int)$metrics['coveredstatements'];
    }

    if($statements === 0)
    {
        continue;
    }

    $rows[] = ['file'    => str_replace('\\', '/', $name), 'statements' => $statements, 'covered' => $covered,
               'percent' => ($covered / $statements) * 100,
    ];

    $totalStatements += $statements;
    $totalCovered += $covered;
}

usort($rows, static function (array $a, array $b): int {
    return strcmp($a['file'], $b['file']);
});

$fileHeader = 'FILE';
$fileWidth = strlen($fileHeader);

foreach($rows as $row)
{
    $fileWidth = max($fileWidth, strlen($row['file']));
}

$fileWidth = min($fileWidth, 90);

$isTty = function_exists('stream_isatty') && stream_isatty(STDOUT);

const ANSI_RED = "\033[31m";
const ANSI_YELLOW = "\033[33m";
const ANSI_GREEN = "\033[32m";
const ANSI_RESET = "\033[0m";

function colorize(string $text, float $percent, bool $isTty): string
{
    if(!$isTty)
    {
        return $text;
    }

    if($percent < 50.0)
    {
        return ANSI_RED . $text . ANSI_RESET;
    }

    if($percent < 80.0)
    {
        return ANSI_YELLOW . $text . ANSI_RESET;
    }

    return ANSI_GREEN . $text . ANSI_RESET;
}

printf("%-{$fileWidth}s %8s %8s %8s\n", $fileHeader, 'STMT', 'COVER', 'LINE%');

foreach($rows as $row)
{
    $linePercent = sprintf('%7.2f', $row['percent']);

    printf("%-{$fileWidth}s %8d %8d %s\n", $row['file'], $row['statements'], $row['covered'],
           colorize($linePercent, $row['percent'], $isTty));
}

$totalPercent = $totalStatements > 0 ? ($totalCovered / $totalStatements) * 100 : 0.0;
$totalPercentText = sprintf('%7.2f', $totalPercent);

printf("%-{$fileWidth}s %8d %8d %s\n", 'TOTAL', $totalStatements, $totalCovered,
       colorize($totalPercentText, $totalPercent, $isTty));
