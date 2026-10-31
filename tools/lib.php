<?php
/**
 * Shared helpers for the jDBexport language tools.
 *
 * Repository model
 *   <version>/source/en-GB/<area>/<file>.ini       English master (copied from the component)
 *   <version>/translations/<tag>/<area>/<file>.ini  one folder per language, same layout
 *   i18n/state/<version>.json                       per key: hash of the English text it was
 *                                                   translated from + status
 *   i18n/languages.json                             language list and per-version targets
 *
 * Translation files are always rendered from the English template: same order, same
 * comments, every source key present. A key without a translation carries the English
 * text, so Joomla never shows a raw language key. Which keys are really translated is
 * recorded in the state file, not in the .ini.
 *
 * Statuses: reviewed (checked by a native speaker), machine (translated by the agent,
 * not yet reviewed), needs-fix (imported translation that fails a check).
 */

const ROOT = __DIR__ . '/..';

function fail(string $msg): never { fwrite(STDERR, "ERROR: $msg\n"); exit(1); }

function opts(array $spec): array
{
    $o = getopt('', $spec);
    return $o === false ? [] : $o;
}

function languages(): array
{
    $j = json_decode(file_get_contents(ROOT . '/i18n/languages.json'), true);
    if (!is_array($j)) fail('i18n/languages.json is not valid JSON');
    return $j;
}

function versions(): array
{
    return array_keys(languages()['versions']);
}

function versionLanguages(string $version): array
{
    $l = languages();
    if (!isset($l['versions'][$version])) fail("unknown version $version (see i18n/languages.json)");
    return $l['versions'][$version]['languages'];
}

/** Relative file paths of the English source, e.g. admin/com_jdbexport.ini */
function sourceFiles(string $version): array
{
    $base = ROOT . "/$version/source/en-GB";
    if (!is_dir($base)) fail("no source folder $version/source/en-GB");
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (str_ends_with($f->getFilename(), '.ini')) {
            $out[] = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($base))), '/');
        }
    }
    sort($out);
    return $out;
}

function sourcePath(string $version, string $rel): string { return ROOT . "/$version/source/en-GB/$rel"; }
function transPath(string $version, string $lang, string $rel): string { return ROOT . "/$version/translations/$lang/$rel"; }

/** Parse an ini file the way Joomla does (RAW scanner, \" unescaped). Returns null on parse error. */
function parseIni(string $path): ?array
{
    if (!is_file($path)) return null;
    $s = @parse_ini_string(file_get_contents($path), false, INI_SCANNER_RAW);
    if ($s === false) return null;
    foreach ($s as $k => $v) $s[$k] = str_replace('\\"', '"', (string) $v);
    return $s;
}

/** English strings of one source file, empty values included. */
function sourceStrings(string $version, string $rel): array
{
    $s = parseIni(sourcePath($version, $rel));
    if ($s === null) fail("source file $version/$rel does not parse");
    return $s;
}

function srcHash(string $en): string { return substr(sha1($en), 0, 12); }

function escapeValue(string $v): string
{
    // Inner double quotes must be escaped for Joomla; normalise line breaks away.
    $v = str_replace(["\r\n", "\r", "\n"], ' ', $v);
    $v = str_replace('\\"', '"', $v);
    return str_replace('"', '\\"', $v);
}

/**
 * Render a translation file from the English template.
 * $values: key => translated text. Missing keys get the English text.
 * Duplicate keys in the template are written once (first occurrence).
 */
function render(string $version, string $rel, array $values, string $lang): string
{
    $lines = preg_split('/\r\n|\r|\n/', file_get_contents(sourcePath($version, $rel)));
    $en = sourceStrings($version, $rel);
    $seen = [];
    $out = [];
    foreach ($lines as $line) {
        if (preg_match('/^([A-Z0-9_.\-]+)\s*=/', $line, $m)) {
            $k = $m[1];
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $v = array_key_exists($k, $values) ? $values[$k] : ($en[$k] ?? '');
            $out[] = $k . '="' . escapeValue($v) . '"';
        } else {
            if (stripos($line, 'transifex') !== false) {
                $line = '; Translations are maintained at https://github.com/schultz-it-solutions/jdbexport-languages';
            }
            $out[] = str_replace('en-GB', $lang, $line);
        }
    }
    while ($out && end($out) === '') array_pop($out);
    return implode("\n", $out) . "\n";
}

function writeFile(string $path, string $content): void
{
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    file_put_contents($path, $content);
}

function loadState(string $version): array
{
    $p = ROOT . "/i18n/state/$version.json";
    if (!is_file($p)) return [];
    $j = json_decode(file_get_contents($p), true);
    if (!is_array($j)) fail("i18n/state/$version.json is not valid JSON");
    // stored compactly as "hash status" per key (one line per key, diff-friendly)
    foreach ($j as $l => $files) foreach ($files as $rel => $keys) foreach ($keys as $k => $v) {
        [$h, $st] = explode(' ', $v, 2);
        $j[$l][$rel][$k] = ['h' => $h, 's' => $st];
    }
    return $j;
}

function saveState(string $version, array $state): void
{
    ksort($state);
    foreach ($state as &$files) {
        ksort($files);
        foreach ($files as &$keys) { ksort($keys); foreach ($keys as &$e) $e = $e['h'] . ' ' . $e['s']; unset($e); }
        unset($keys);
    }
    unset($files);
    writeFile(ROOT . "/i18n/state/$version.json",
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
}

/* ---------- content checks (translation vs. English) ---------- */

function placeholders(string $s): array
{
    preg_match_all('/%(\d+\$)?[-+ 0#]*\d*(\.\d+)?[sdfuxXcbeEgGo%]|\{[A-Za-z0-9_\/]+\}/', $s, $m);
    $p = $m[0];
    sort($p);
    return $p;
}

function htmlTags(string $s): array
{
    preg_match_all('/<\s*(\/?)\s*([a-zA-Z][a-zA-Z0-9]*)/', $s, $m, PREG_SET_ORDER);
    $t = array_map(fn($x) => $x[1] . strtolower($x[2]), $m);
    sort($t);
    return $t;
}

function hrefs(string $s): array
{
    preg_match_all('/href\s*=\s*([\'"])(.*?)\1/i', $s, $m);
    $h = $m[2];
    sort($h);
    return $h;
}

/** Returns a list of problems (empty = OK). */
function checkTranslation(string $en, string $tr): array
{
    $p = [];
    if (trim($tr) === '' && trim($en) !== '') $p[] = 'empty translation';
    if (placeholders($en) !== placeholders($tr))
        $p[] = 'placeholders differ: [' . implode(' ', placeholders($en)) . '] vs [' . implode(' ', placeholders($tr)) . ']';
    if (htmlTags($en) !== htmlTags($tr))
        $p[] = 'HTML tags differ: [' . implode(' ', htmlTags($en)) . '] vs [' . implode(' ', htmlTags($tr)) . ']';
    if (hrefs($en) !== hrefs($tr)) $p[] = 'link targets differ';
    if (str_contains($tr, '_QQ_')) $p[] = 'uses the old "_QQ_" quote constant (removed in Joomla 4) - use single quotes or \\"';
    if (preg_match('/[\r\n]/', $tr)) $p[] = 'contains a line break';
    if (!mb_check_encoding($tr, 'UTF-8')) $p[] = 'invalid UTF-8';
    return $p;
}

/** Current translated values of one language file, taken from the .ini, for keys in state only. */
function currentValues(string $version, string $lang, string $rel, array $state): array
{
    $t = parseIni(transPath($version, $lang, $rel)) ?? [];
    $out = [];
    foreach ($state[$lang][$rel] ?? [] as $k => $_) {
        if (array_key_exists($k, $t)) $out[$k] = $t[$k];
    }
    return $out;
}

/** Translation memory for one language: English text => [translation, status, where]. Reviewed beats machine. */
function translationMemory(string $lang): array
{
    $tm = [];
    $rank = ['reviewed' => 2, 'machine' => 1];
    foreach (versions() as $v) {
        if (!is_dir(ROOT . "/$v/source/en-GB")) continue;
        $state = loadState($v);
        foreach (sourceFiles($v) as $rel) {
            $en = sourceStrings($v, $rel);
            $vals = currentValues($v, $lang, $rel, $state);
            foreach ($vals as $k => $tr) {
                $st = $state[$lang][$rel][$k]['s'] ?? '';
                if (!isset($rank[$st]) || !isset($en[$k]) || $en[$k] === '') continue;
                if (($state[$lang][$rel][$k]['h'] ?? '') !== srcHash($en[$k])) continue; // outdated
                $cur = $tm[$en[$k]] ?? null;
                if ($cur === null || $rank[$st] > $rank[$cur['status']]) {
                    $tm[$en[$k]] = ['translation' => $tr, 'status' => $st, 'where' => "$v/$rel:$k"];
                }
            }
        }
    }
    return $tm;
}
