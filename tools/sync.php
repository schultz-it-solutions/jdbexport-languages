<?php
/**
 * Bring all translations in line with the English source. Run after updating
 * <version>/source/en-GB from a new component release.
 *
 *   php tools/sync.php --version=v4.1 [--no-carry]
 *
 * - creates folders for languages newly listed in i18n/languages.json
 * - re-renders every language file from the English template (new keys appear with
 *   English text, removed keys disappear, order/comments follow the source)
 * - drops state entries for keys that no longer exist
 * - carry-over: a missing key whose English text is identical to an already translated
 *   string in the same language (any file or version) gets that translation and its
 *   status. This is what preserves volunteer work when keys are renamed or moved
 *   (e.g. the jDBexport 5 reorganisation). Disable with --no-carry.
 * Changed English texts are NOT touched: they show up as "changed" in diff.php.
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'no-carry']);
$version = $o['version'] ?? fail('--version is required');
$carry = !isset($o['no-carry']);
$state = loadState($version);

foreach (versionLanguages($version) as $lang) {
    $tm = $carry ? translationMemory($lang) : [];
    $carried = 0; $dropped = 0;
    foreach (sourceFiles($version) as $rel) {
        $en = sourceStrings($version, $rel);
        $values = currentValues($version, $lang, $rel, $state);
        foreach (array_keys($state[$lang][$rel] ?? []) as $k) {
            if (!array_key_exists($k, $en) || $en[$k] === '') { unset($state[$lang][$rel][$k], $values[$k]); $dropped++; }
        }
        foreach ($en as $k => $e) {
            if ($e === '' || isset($state[$lang][$rel][$k]) || !isset($tm[$e])) continue;
            $values[$k] = $tm[$e]['translation'];
            $state[$lang][$rel][$k] = ['h' => srcHash($e), 's' => $tm[$e]['status']];
            $carried++;
        }
        writeFile(transPath($version, $lang, $rel), render($version, $rel, $values, $lang));
    }
    // state for files that no longer exist
    foreach (array_keys($state[$lang] ?? []) as $rel) {
        if (!in_array($rel, sourceFiles($version), true)) unset($state[$lang][$rel]);
    }
    printf("%-6s carried over %d, dropped %d obsolete\n", $lang, $carried, $dropped);
}
saveState($version, $state);
