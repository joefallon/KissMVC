<?php
declare(strict_types=1);

if($argc !== 2)
{
    fwrite(STDERR, "Usage: php scripts/crap-summary.php <crap4j.xml>\n");
    exit(1);
}

$crapFile = $argv[1];

if(!is_file($crapFile) || !is_readable($crapFile))
{
    fwrite(STDERR, "Crap4J file not found or unreadable: {$crapFile}\n");
    exit(1);
}

$crapXml = simplexml_load_file($crapFile);
if($crapXml === false)
{
    fwrite(STDERR, "Failed to parse Crap4J XML: {$crapFile}\n");
    exit(1);
}

function normalizeClassName(string $className): string
{
    return ltrim(str_replace('/', '\\', trim($className)), '\\');
}

function classNameToGroup(string $className): string
{
    $className = normalizeClassName($className);

    if(str_starts_with($className, 'KissMVC\\'))
    {
        $relative = substr($className, strlen('KissMVC\\'));
        return 'lib/KissMVC/' . str_replace('\\', '/', $relative) . '.php';
    }

    if(str_starts_with($className, 'Controllers\\'))
    {
        $relative = substr($className, strlen('Controllers\\'));
        return 'src/Controllers/' . str_replace('\\', '/', $relative) . '.php';
    }

    return $className;
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

function numericNodeValue(SimpleXMLElement $node, string $name): float
{
    $value = textNodeValue($node, $name);
    return $value === null ? 0.0 : (float)$value;
}

function methodLabel(string $className, string $methodName): string
{
    $className = normalizeClassName($className);
    $methodName = trim($methodName);

    if($className === '')
    {
        return $methodName;
    }

    return $className . '::' . $methodName;
}

function formatNumber(float $value, int $precision = 2): string
{
    return number_format($value, $precision, '.', '');
}

function colorize(string $text, string $color, bool $isTty): string
{
    if(!$isTty)
    {
        return $text;
    }

    return match($color)
    {
        'red' => "\033[31m{$text}\033[0m",
        'yellow' => "\033[33m{$text}\033[0m",
        'green' => "\033[32m{$text}\033[0m",
        default => $text,
    };
}

function clipText(string $text, int $maxLength): string
{
    if($maxLength < 4 || strlen($text) <= $maxLength)
    {
        return $text;
    }

    return substr($text, 0, $maxLength - 3) . '...';
}

$methods = $crapXml->xpath('/crap_result/methods/method');
if($methods === false)
{
    $methods = $crapXml->xpath('//methods/method') ?: [];
}

$groups = [];
$totalMethods = 0;
$totalCrap = 0.0;
$totalCrapLoad = 0.0;
$totalCrapMethods = 0;
$maxMethod = [
    'score' => 0.0,
    'label' => 'n/a',
];

foreach($methods as $methodNode)
{
    $className = textNodeValue($methodNode, 'className');
    $methodName = textNodeValue($methodNode, 'methodName');

    if($className === null || $methodName === null)
    {
        continue;
    }

    $crap = numericNodeValue($methodNode, 'crap');
    $complexity = numericNodeValue($methodNode, 'complexity');
    $coverage = numericNodeValue($methodNode, 'coverage');
    $crapLoad = numericNodeValue($methodNode, 'crapLoad');

    $groupKey = classNameToGroup($className);
    if(!isset($groups[$groupKey]))
    {
        $groups[$groupKey] = [
            'methods' => 0,
            'crapSum' => 0.0,
            'maxCrap' => 0.0,
            'crapMethods' => 0,
            'worstMethod' => 'n/a',
        ];
    }

    $label = methodLabel($className, $methodName);
    $groups[$groupKey]['methods']++;
    $groups[$groupKey]['crapSum'] += $crap;
    $groups[$groupKey]['crapMethods'] += $crapLoad > 0.0 ? 1 : 0;

    if($crap > $groups[$groupKey]['maxCrap'])
    {
        $groups[$groupKey]['maxCrap'] = $crap;
        $groups[$groupKey]['worstMethod'] = $label;
    }

    if($crap > $maxMethod['score'])
    {
        $maxMethod = [
            'score' => $crap,
            'label' => $label,
        ];
    }

    $totalMethods++;
    $totalCrap += $crap;
    $totalCrapLoad += $crapLoad;
    $totalCrapMethods += $crapLoad > 0.0 ? 1 : 0;
}

ksort($groups);

$rows = [];
foreach($groups as $groupKey => $stats)
{
    $rows[] = [
        'file' => $groupKey,
        'methods' => $stats['methods'],
        'crapSum' => $stats['crapSum'],
        'maxCrap' => $stats['maxCrap'],
        'crapMethods' => $stats['crapMethods'],
        'worstMethod' => $stats['worstMethod'],
    ];
}

$fileWidth = strlen('FILE');
$methodsWidth = strlen('METHODS');
$crapSumWidth = strlen('CRAP_SUM');
$maxCrapWidth = strlen('MAX_CRAP');
$crapMethodsWidth = strlen('CRAP_METHODS');

foreach($rows as $row)
{
    $fileWidth = max($fileWidth, strlen(clipText($row['file'], 42)));
    $methodsWidth = max($methodsWidth, strlen((string)$row['methods']));
    $crapSumWidth = max($crapSumWidth, strlen(formatNumber($row['crapSum'])));
    $maxCrapWidth = max($maxCrapWidth, strlen(formatNumber($row['maxCrap'])));
    $crapMethodsWidth = max($crapMethodsWidth, strlen((string)$row['crapMethods']));
}

$fileWidth = min($fileWidth, 42);
$methodsWidth = max($methodsWidth, strlen((string)$totalMethods));
$crapSumWidth = max($crapSumWidth, strlen(formatNumber($totalCrap)));
$maxCrapWidth = max($maxCrapWidth, strlen(formatNumber($maxMethod['score'])));
$crapMethodsWidth = max($crapMethodsWidth, strlen((string)$totalCrapMethods));

printf(
    '%-' . $fileWidth . 's %' . $methodsWidth . 's %' . $crapSumWidth . 's %' . $maxCrapWidth . 's %' . $crapMethodsWidth . 's' . "\n",
    'FILE',
    'METHODS',
    'CRAP_SUM',
    'MAX_CRAP',
    'CRAP_METHODS'
);

foreach($rows as $row)
{
    printf(
        '%-' . $fileWidth . 's %' . $methodsWidth . 'd %' . $crapSumWidth . 's %' . $maxCrapWidth . 's %' . $crapMethodsWidth . 'd' . "\n",
        clipText($row['file'], $fileWidth),
        $row['methods'],
        formatNumber($row['crapSum']),
        formatNumber($row['maxCrap']),
        $row['crapMethods']
    );
}

printf(
    '%-' . $fileWidth . 's %' . $methodsWidth . 'd %' . $crapSumWidth . 's %' . $maxCrapWidth . 's %' . $crapMethodsWidth . 'd' . "\n",
    'TOTAL',
    $totalMethods,
    formatNumber($totalCrap),
    formatNumber($maxMethod['score']),
    $totalCrapMethods
);

$totalWorstMethod = $maxMethod['label'];
$isTty = function_exists('stream_isatty') && stream_isatty(STDOUT);
$totalCrapColor = $totalCrap > 0.0 ? 'yellow' : 'green';
$maxCrapColor = $maxMethod['score'] >= 30.0 ? 'red' : ($maxMethod['score'] > 0.0 ? 'yellow' : 'green');
$crapMethodsColor = $totalCrapMethods > 0 ? 'red' : 'green';
$crapLoadColor = $totalCrapLoad > 0.0 ? 'yellow' : 'green';
$interpretation = $totalCrapMethods === 0
    ? 'PASS - no methods with CRAP load > 0.'
    : 'REVIEW - one or more methods have CRAP load > 0.';

printf("\nCRAP SUMMARY\n");
printf("Method count:       %d\n", $totalMethods);
printf("Total CRAP:         %s\n", colorize(formatNumber($totalCrap), $totalCrapColor, $isTty));
printf(
    "Max method CRAP:    %s  %s\n",
    colorize(formatNumber($maxMethod['score']), $maxCrapColor, $isTty),
    colorize($totalWorstMethod, $maxCrapColor, $isTty)
);
printf("CRAP methods:       %s\n", colorize((string)$totalCrapMethods, $crapMethodsColor, $isTty));
printf("CRAP load:          %s\n", colorize(formatNumber($totalCrapLoad), $crapLoadColor, $isTty));
printf("Interpretation:     %s\n", colorize($interpretation, $totalCrapMethods === 0 ? 'green' : 'red', $isTty));
