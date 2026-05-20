<?php
declare(strict_types=1);

$cloverFile = $argv[1] ?? 'build/coverage/clover.xml';
$crapFile = $argv[2] ?? null;
$basePath = realpath(dirname(__DIR__));

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function normalizeClassName(string $className): string
{
    return ltrim(str_replace('/', '\\', trim($className)), '\\');
}

function stripBasePath(string $path, string|false $basePath): string
{
    $path = normalizePath($path);

    if($basePath === false)
    {
        return $path;
    }

    $basePath = rtrim(normalizePath($basePath), '/') . '/';

    if(str_starts_with($path, $basePath))
    {
        return substr($path, strlen($basePath));
    }

    return $path;
}

function pathSuffixMatches(string $candidate, string $target): bool
{
    $candidate = normalizePath($candidate);
    $target = normalizePath($target);

    return $candidate === $target || str_ends_with($candidate, $target);
}

function classNameMatches(string $candidate, string $target): bool
{
    $candidate = normalizeClassName($candidate);
    $target = normalizeClassName($target);

    return $candidate === $target
        || str_ends_with($candidate, '\\' . $target)
        || str_ends_with($target, '\\' . $candidate);
}

function textNodeValue(SimpleXMLElement $node, string $name): ?string
{
    if(!isset($node->{$name}))
    {
        return null;
    }

    $value = trim((string)$node->{$name});

    return $value === '' ? null : $value;
}

function extractCloverClassName(SimpleXMLElement $classNode): ?string
{
    $name = trim((string)$classNode['name']);
    if($name === '')
    {
        return null;
    }

    $namespace = trim((string)$classNode['namespace']);
    $name = normalizeClassName($name);

    if($namespace !== '' && !str_contains($name, '\\'))
    {
        return normalizeClassName($namespace) . '\\' . $name;
    }

    return $name;
}

function buildCloverClassMap(SimpleXMLElement $project, string|false $basePath): array
{
    $classToFile = [];

    foreach($project->xpath('.//file') ?: [] as $fileNode)
    {
        $fileName = stripBasePath((string)$fileNode['name'], $basePath);
        $classes = $fileNode->xpath('.//class');
        if($classes === false)
        {
            continue;
        }

        foreach($classes as $classNode)
        {
            $className = extractCloverClassName($classNode);
            if($className === null)
            {
                continue;
            }

            $classToFile[normalizeClassName($className)] = $fileName;
        }
    }

    return $classToFile;
}

function resolveClassToFile(string $className, array $classToFile): ?string
{
    $className = normalizeClassName($className);

    if(isset($classToFile[$className]))
    {
        return $classToFile[$className];
    }

    foreach($classToFile as $candidateClass => $fileName)
    {
        if(classNameMatches($candidateClass, $className))
        {
            return $fileName;
        }
    }

    return null;
}

function formatNumber(float $value, int $precision = 2): string
{
    return number_format($value, $precision, '.', '');
}

function formatCompactNumber(float $value): string
{
    $formatted = formatNumber($value, 2);

    return rtrim(rtrim($formatted, '0'), '.');
}

function parseCrap4j(SimpleXMLElement $crapXml, array $classToFile): array
{
    $fileStats = [];
    $methodCount = 0;
    $mappedMethodCount = 0;
    $maxMethod = [
        'score' => 0.0,
        'className' => '',
        'methodName' => '',
    ];

    $methods = $crapXml->xpath('/crap_result/methods/method');
    if($methods === false)
    {
        $methods = $crapXml->xpath('//methods/method') ?: [];
    }

    foreach($methods as $methodNode)
    {
        $methodCount++;

        $className = textNodeValue($methodNode, 'className');
        $methodName = textNodeValue($methodNode, 'methodName');
        $crapValue = textNodeValue($methodNode, 'crap');

        if($className === null || $methodName === null || $crapValue === null)
        {
            continue;
        }

        $score = (float)$crapValue;
        if($score > $maxMethod['score'])
        {
            $maxMethod = [
                'score' => $score,
                'className' => $className,
                'methodName' => $methodName,
            ];
        }

        $fileName = resolveClassToFile($className, $classToFile);
        if($fileName === null)
        {
            continue;
        }

        if(!isset($fileStats[$fileName]))
        {
            $fileStats[$fileName] = [
                'sum' => 0.0,
                'max' => 0.0,
                'methods' => 0,
            ];
        }

        $fileStats[$fileName]['sum'] += $score;
        $fileStats[$fileName]['max'] = max($fileStats[$fileName]['max'], $score);
        if($score >= 30.0)
        {
            $fileStats[$fileName]['methods']++;
        }

        $mappedMethodCount++;
    }

    $statsNode = null;
    $statsNodes = $crapXml->xpath('/crap_result/stats');
    if($statsNodes !== false && $statsNodes !== [])
    {
        $statsNode = $statsNodes[0];
    }
    else
    {
        $fallbackStats = $crapXml->xpath('//stats');
        if($fallbackStats !== false && $fallbackStats !== [])
        {
            $statsNode = $fallbackStats[0];
        }
    }

    $methodCountValue = 0;
    $crapMethodCount = 0;
    $crapLoad = 0.0;
    $totalCrap = 0.0;

    if($statsNode instanceof SimpleXMLElement)
    {
        $methodCountValue = (int)(textNodeValue($statsNode, 'methodCount') ?? '0');
        $crapMethodCount = (int)(textNodeValue($statsNode, 'crapMethodCount') ?? '0');
        $crapLoad = (float)(textNodeValue($statsNode, 'crapLoad') ?? '0');
        $totalCrap = (float)(textNodeValue($statsNode, 'totalCrap') ?? '0');
    }

    return [
        'fileStats' => $fileStats,
        'methodCount' => $methodCount,
        'methodCountValue' => $methodCountValue,
        'mappedMethodCount' => $mappedMethodCount,
        'crapMethodCount' => $crapMethodCount,
        'crapLoad' => $crapLoad,
        'totalCrap' => $totalCrap,
        'maxMethod' => $maxMethod,
    ];
}

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

$project = $xml->project;
if(!$project)
{
    fwrite(STDERR, "Invalid Clover XML: missing <project>\n");
    exit(1);
}

$classToFile = buildCloverClassMap($project, $basePath);

$crapScores = [];
$crapFileStats = [];
$showCrap = false;
$crapMethodCountReported = 0;
$crapMethodCount = 0;
$crapLoad = 0.0;
$totalCrap = 0.0;
$maxMethod = [
    'score' => 0.0,
    'className' => '',
    'methodName' => '',
];

if($crapFile !== null)
{
    if(is_file($crapFile) && is_readable($crapFile))
    {
        $crapXml = simplexml_load_file($crapFile);
        if($crapXml === false)
        {
            fwrite(STDERR, "Failed to parse Crap4J XML: {$crapFile}\n");
            exit(1);
        }

        $crapData = parseCrap4j($crapXml, $classToFile);
        $crapFileStats = $crapData['fileStats'];
        $crapScores = [];
        foreach($crapFileStats as $fileName => $stats)
        {
            $crapScores[$fileName] = $stats['sum'];
        }
        $totalCrap = $crapData['totalCrap'];
        $crapMethodCountReported = $crapData['methodCountValue'];
        $crapMethodCount = $crapData['crapMethodCount'];
        $crapLoad = $crapData['crapLoad'];
        $maxMethod = $crapData['maxMethod'];
        $showCrap = true;

        if($crapData['methodCount'] > 0 && $crapData['mappedMethodCount'] === 0)
        {
            fwrite(STDERR, "Warning: Crap4J XML contained {$crapData['methodCount']} method entries, but none could be mapped to Clover files.\n");
        }
    }
    else
    {
        fwrite(STDERR, "Crap4J file not found or unreadable: {$crapFile}\n");
    }
}

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
        'crapSum' => $showCrap ? ($crapScores[$fileName] ?? 0.0) : 0.0,
        'crapMax' => $showCrap ? ($crapFileStats[$fileName]['max'] ?? 0.0) : 0.0,
        'crapMethods' => $showCrap ? ($crapFileStats[$fileName]['methods'] ?? 0) : 0,
    ];

    $totalStatements += $statements;
    $totalCovered += $covered;
}

usort($rows, static function(array $a, array $b): int {
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

if($showCrap)
{
    printf(
        '%-' . $fileWidth . 's %8s %8s %8s %10s %10s %13s' . "\n",
        $fileHeader,
        'STMT',
        'COVER',
        'LINE%',
        'CRAP_SUM',
        'MAX_CRAP',
        'CRAP_METHODS'
    );
}
else
{
    printf('%-' . $fileWidth . 's %8s %8s %8s' . "\n", $fileHeader, 'STMT', 'COVER', 'LINE%');
}

foreach($rows as $row)
{
    $linePercent = sprintf('%7.2f', $row['percent']);

    if($showCrap)
    {
        printf(
            '%-' . $fileWidth . 's %8d %8d %s %10.2f %10.2f %13d' . "\n",
            $row['file'],
            $row['statements'],
            $row['covered'],
            colorize($linePercent, $row['percent'], $isTty),
            $row['crapSum'],
            $row['crapMax'],
            $row['crapMethods']
        );
        continue;
    }

    printf('%-' . $fileWidth . 's %8d %8d %s' . "\n", $row['file'], $row['statements'], $row['covered'],
           colorize($linePercent, $row['percent'], $isTty));
}

$totalPercent = $totalStatements > 0 ? ($totalCovered / $totalStatements) * 100 : 0.0;
$totalPercentText = sprintf('%7.2f', $totalPercent);

if($showCrap)
{
    printf(
        '%-' . $fileWidth . 's %8d %8d %s %10.2f %10.2f %13d' . "\n",
        'TOTAL',
        $totalStatements,
        $totalCovered,
        colorize($totalPercentText, $totalPercent, $isTty),
        $totalCrap,
        $maxMethod['score'],
        $crapMethodCount
    );

    $maxMethodLabel = $maxMethod['className'] !== '' && $maxMethod['methodName'] !== ''
        ? $maxMethod['className'] . '::' . $maxMethod['methodName']
        : 'n/a';

    $interpretation = ($crapMethodCount > 0 || $maxMethod['score'] >= 30.0)
        ? 'REVIEW - one or more methods have high CRAP scores.'
        : 'PASS - no individually risky methods reported by Crap4J.';

    printf("\nCRAP SUMMARY\n");
    printf("Method count:       %d\n", $crapMethodCountReported);
    printf("Total CRAP:         %s\n", formatNumber($totalCrap));
    printf("Max method CRAP:    %s  %s\n", formatNumber($maxMethod['score']), $maxMethodLabel);
    printf("CRAP methods:       %d\n", $crapMethodCount);
    printf("CRAP load:          %s\n", formatCompactNumber($crapLoad));
    printf("Interpretation:     %s\n", $interpretation);
}
else
{
    printf('%-' . $fileWidth . 's %8d %8d %s' . "\n", 'TOTAL', $totalStatements, $totalCovered,
           colorize($totalPercentText, $totalPercent, $isTty));
}
