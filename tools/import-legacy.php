<?php
/**
 * One-off import of the old Transifex-era language files.
 *
 *   php tools/import-legacy.php --version=v4.1 --dir=/path/to/languages [--lang=de-DE]
 *
 * Expects <dir>/<tag>/<area>/<tag>.<file>.ini (the old prefixed layout).
 * Non-empty, uncommented values for keys that exist in the English source are imported
 * as "reviewed" (volunteer work); values failing a check are kept but marked "needs-fix".
 * The state records the hash of the CURRENT English text: we cannot know which English
 * wording a volunteer translated from, so imported strings are assumed up to date.
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'dir:', 'lang:']);
$version = $o['version'] ?? fail('--version is required');
$dir = rtrim($o['dir'] ?? fail('--dir is required'), '/');
$langs = isset($o['lang']) ? [$o['lang']] : array_map('basename', glob("$dir/*", GLOB_ONLYDIR));

$state = loadState($version);
foreach ($langs as $lang) {
    $sum = ['reviewed' => 0, 'needs-fix' => 0, 'total' => 0];
    foreach (sourceFiles($version) as $rel) {
        $en = sourceStrings($version, $rel);
        $legacy = "$dir/$lang/" . dirname($rel) . "/$lang." . basename($rel);
        $old = parseIni($legacy);
        if ($old === null) { echo "  $lang: $rel not found or unparsable ($legacy) - skipped\n"; $old = []; }
        $values = [];
        foreach ($en as $k => $e) {
            if ($e === '') continue;
            $sum['total']++;
            if (!isset($old[$k]) || trim($old[$k]) === '') continue;
            $values[$k] = $old[$k];
            $status = checkTranslation($e, $old[$k]) ? 'needs-fix' : 'reviewed';
            $state[$lang][$rel][$k] = ['h' => srcHash($e), 's' => $status];
            $sum[$status]++;
        }
        writeFile(transPath($version, $lang, $rel), render($version, $rel, $values, $lang));
    }
    printf("%-6s imported %4d reviewed, %3d needs-fix, of %d strings (%d%%)\n", $lang,
        $sum['reviewed'], $sum['needs-fix'], $sum['total'], 100 * ($sum['reviewed'] + $sum['needs-fix']) / max(1, $sum['total']));
}
saveState($version, $state);
