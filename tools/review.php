<?php
/**
 * Record a native speaker's review.
 *
 * Volunteers review by editing the .ini files directly in a pull request (or by
 * approving one). Afterwards, run one of:
 *
 *   php tools/review.php --version=v4.1 --lang=de-DE --base=origin/main
 *       every string whose value differs from <base> becomes "reviewed"
 *       (the volunteer's own edits)
 *
 *   php tools/review.php --version=v4.1 --lang=de-DE --approve-all
 *       every translated string of the language becomes "reviewed"
 *       (the volunteer read the whole language and approved it)
 *
 * Edited values are checked like any translation; failing ones are reported and stay
 * as they are in the state.
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'lang:', 'base:', 'approve-all']);
$version = $o['version'] ?? fail('--version is required');
$lang = $o['lang'] ?? fail('--lang is required');
if (!isset($o['base']) && !isset($o['approve-all'])) fail('use --base=<git ref> or --approve-all');
$state = loadState($version);
$marked = 0; $problems = [];

foreach (sourceFiles($version) as $rel) {
    $en = sourceStrings($version, $rel);
    $now = parseIni(transPath($version, $lang, $rel)) ?? [];
    $before = [];
    if (isset($o['base'])) {
        $gitPath = "$version/translations/$lang/$rel";
        $raw = shell_exec('git -C ' . escapeshellarg(ROOT) . ' show ' . escapeshellarg($o['base'] . ':' . $gitPath) . ' 2>/dev/null');
        if ($raw) $before = @parse_ini_string($raw, false, INI_SCANNER_RAW) ?: [];
        foreach ($before as $k => $v) $before[$k] = str_replace('\\"', '"', (string) $v);
    }
    foreach ($en as $k => $e) {
        if ($e === '' || !isset($now[$k])) continue;
        $edited = isset($o['base']) && ($before[$k] ?? null) !== $now[$k];
        $approve = isset($o['approve-all']) && isset($state[$lang][$rel][$k]);
        if (!$edited && !$approve) continue;
        if ($p = checkTranslation($e, $now[$k])) { $problems[] = "$rel:$k: " . implode('; ', $p); continue; }
        $state[$lang][$rel][$k] = ['h' => srcHash($e), 's' => 'reviewed'];
        $marked++;
    }
}
saveState($version, $state);
echo "marked $marked strings as reviewed\n";
foreach ($problems as $p) echo "  NOT MARKED $p\n";
exit($problems ? 1 : 0);
