#!/usr/bin/env php
<?php
declare(strict_types=1);
// Lightweight PHPUnit JUnit XML timing summary
// Usage: php scripts/phpunit-timing-summary.php [path/to/phpunit-junit.xml]

$defaultPath = '.ai/out/phpunit-junit.xml';
$xmlPath = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : $defaultPath;

if(!file_exists($xmlPath) || !is_readable($xmlPath))
{
    fwrite(STDERR, "Error: JUnit XML file not found or not readable: $xmlPath\n");
    exit(2);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlPath);
if($xml === false)
{
    $errs = libxml_get_errors();
    $msg = "Error: failed to parse XML file: $xmlPath\n";
    foreach($errs as $e)
    {
        $msg .= trim($e->message) . " on line " . $e->line . "\n";
    }
    fwrite(STDERR, $msg);
    exit(3);
}

$cases = [];
$fileSums = [];
$totalTime = 0.0;

$nodes = $xml->xpath('//testcase');
if($nodes === false)
{
    fwrite(STDERR, "Error: XPath evaluation failed while searching for testcase elements.\n");
    exit(4);
}

foreach($nodes as $tc)
{
    $attrs = $tc->attributes();
    $classname = isset($attrs['classname']) ? (string)$attrs['classname'] : '';
    $name = isset($attrs['name']) ? (string)$attrs['name'] : '';
    $time = isset($attrs['time']) ? (float)$attrs['time'] : 0.0;
    // file attribute is not always present in junit from phpunit; fallback to classname
    $file = isset($attrs['file']) ? (string)$attrs['file'] : '';
    if($file === '')
    {
        $file = $classname !== '' ? $classname : '(unknown)';
    }

    $cases[] = ['classname' => $classname, 'name' => $name, 'file' => $file, 'time' => $time,];

    if(!isset($fileSums[$file]))
    {
        $fileSums[$file] = 0.0;
    }
    $fileSums[$file] += $time;

    $totalTime += $time;
}

$count = count($cases);

// sort cases by time desc
usort($cases, function ($a, $b) {
    if($a['time'] == $b['time'])
    {
        return 0;
    }

    return ($a['time'] < $b['time']) ? 1 : -1;
});

// sort files by summed time desc
arsort($fileSums);

// Print plain text summary
echo "PHPUnit JUnit timing summary for: $xmlPath\n";
echo "Testcase count: $count\n";
echo "Total testcase time (sum): " . number_format($totalTime, 6) . " seconds\n";
echo "\n";

$topCases = array_slice($cases, 0, 25);
echo "Top " . count($topCases) . " slowest test cases:\n";
$i = 1;
foreach($topCases as $c)
{
    $className = $c['classname'] !== '' ? $c['classname'] : '(no-class)';
    $testName = $c['name'] !== '' ? $c['name'] : '(no-name)';
    $file = $c['file'];
    $time = number_format($c['time'], 6);
    echo sprintf("%2d) %s::%s | %s | %s s\n", $i, $className, $testName, $file, $time);
    $i++;
}

echo "\nTop " . min(25, count($fileSums)) . " slowest test files (by summed testcase time):\n";
$i = 1;
foreach(array_slice($fileSums, 0, 25, true) as $file => $sum)
{
    $fileCount = 0;
    // count how many cases belong to this file
    foreach($cases as $c)
    {
        if($c['file'] === $file)
        {
            $fileCount++;
        }
    }
    echo sprintf("%2d) %s | %s s | %d test(s)\n", $i, $file, number_format($sum, 6), $fileCount);
    $i++;
}

exit(0);

