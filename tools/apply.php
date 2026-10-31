<?php
/**
 * Write translations into the language files and record them in the state.
 *
 *   php tools/apply.php --version=v4.1 --lang=de-DE --input=work/de-DE.json [--status=machine]
 *
 * Input: JSON array of {"file": "admin/com_jdbexport.ini", "key": "...", "value": "..."}
 * (the "items" array of diff.php output with a "value" added per item also works).
 *
 * Each value is checked against its English source (placeholders, HTML tags, links).
 * Failing values are rejected and listed; everything else is written. Exit code 1 if
 * anything was rejected, so the agent knows to fix and re-apply those items.
 *
 * --status: machine (default, agent translations) or reviewed (a native speaker's
 * corrections, e.g. when merging a volunteer's review).
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'lang:', 'input:', 'status:']);
$version = $o['version'] ?? fail('--version is required');
$lang = $o['lang'] ?? fail('--lang is required');
$status = $o['status'] ?? 'machine';
if (!in_array($status, ['machine', 'reviewed'], true)) fail('--status must be machine or reviewed');
if (!in_array($lang, versionLanguages($version), true)) fail("$lang is not listed for $version in i18n/languages.json");

$in = json_decode(file_get_contents($o['input'] ?? fail('--input is required')), true);
if (isset($in['items'])) $in = $in['items'];
if (!is_array($in)) fail('input is not a JSON array');

$state = loadState($version);
$files = sourceFiles($version);
$byFile = [];
$rejected = [];
$accepted = 0;

foreach ($in as $i => $it) {
    $rel = $it['file'] ?? null; $k = $it['key'] ?? null; $v = $it['value'] ?? null;
    if (!in_array($rel, $files, true) || $k === null || !is_string($v)) { $rejected[] = "#$i: missing/unknown file, key or value"; continue; }
    $en = sourceStrings($version, $rel);
    if (!array_key_exists($k, $en)) { $rejected[] = "$rel:$k: key not in English source"; continue; }
    $v = trim($v);
    $problems = checkTranslation($en[$k], $v);
    if ($problems) { $rejected[] = "$rel:$k: " . implode('; ', $problems); continue; }
    $byFile[$rel][$k] = $v;
    $state[$lang][$rel][$k] = ['h' => srcHash($en[$k]), 's' => $status];
    $accepted++;
}

foreach ($byFile as $rel => $new) {
    $values = currentValues($version, $lang, $rel, $state);
    foreach ($new as $k => $v) $values[$k] = $v;
    writeFile(transPath($version, $lang, $rel), render($version, $rel, $values, $lang));
}
saveState($version, $state);

echo "applied $accepted, rejected " . count($rejected) . "\n";
foreach ($rejected as $r) echo "  REJECTED $r\n";
exit($rejected ? 1 : 0);
