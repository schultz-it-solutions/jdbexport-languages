<?php
/**
 * Validate language files. Used by the agent before finishing and by CI on every PR.
 *
 *   php tools/validate.php [--version=v4.1] [--lang=de-DE] [--strict]
 *
 * Errors (exit 1):
 *   - file missing, not UTF-8, has a BOM, or does not parse like Joomla parses it
 *   - a line that is not a comment, blank or KEY="value"; unescaped " inside a value
 *   - keys missing compared to the English source, or keys that are not in the source
 *   - a translation marked reviewed/machine that fails a check (placeholders, HTML, links)
 * Warnings (exit 0 unless --strict):
 *   - needs-fix translations (imported, failing a check) and outdated translations
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'lang:', 'strict']);
$errors = 0; $warnings = 0;
$err = function (string $m) use (&$errors) { $errors++; echo "ERROR   $m\n"; };
$warn = function (string $m) use (&$warnings) { $warnings++; echo "WARNING $m\n"; };

foreach (isset($o['version']) ? [$o['version']] : versions() as $version) {
    if (!is_dir(ROOT . "/$version/source/en-GB")) { $warn("$version: no source yet"); continue; }
    $state = loadState($version);
    $langs = isset($o['lang']) ? [$o['lang']] : versionLanguages($version);
    foreach (sourceFiles($version) as $rel) {
        if (parseIni(sourcePath($version, $rel)) === null) $err("$version/source/en-GB/$rel does not parse");
    }
    foreach ($langs as $lang) {
        $warnFix = 0; $warnOld = 0;
        foreach (sourceFiles($version) as $rel) {
            $where = "$version/translations/$lang/$rel";
            $path = transPath($version, $lang, $rel);
            if (!is_file($path)) { $err("$where: missing"); continue; }
            $raw = file_get_contents($path);
            if (str_starts_with($raw, "\xEF\xBB\xBF")) $err("$where: has a UTF-8 BOM");
            if (!mb_check_encoding($raw, 'UTF-8')) $err("$where: not valid UTF-8");
            foreach (preg_split('/\r\n|\n/', $raw) as $n => $line) {
                if ($line === '' || $line[0] === ';') continue;
                if (!preg_match('/^[A-Z0-9_.\-]+="((?:[^"\\\\]|\\\\.)*)"$/', $line)) {
                    $err("$where:" . ($n + 1) . ": malformed line (unescaped quote or not KEY=\"value\")");
                }
            }
            $t = parseIni($path);
            if ($t === null) { $err("$where: does not parse"); continue; }
            $en = sourceStrings($version, $rel);
            foreach (array_diff_key($en, $t) as $k => $_) $err("$where: missing key $k");
            foreach (array_diff_key($t, $en) as $k => $_) $err("$where: unknown key $k");
            foreach ($state[$lang][$rel] ?? [] as $k => $st) {
                if (!isset($en[$k], $t[$k])) { $err("$where: state lists $k, which is not in the file/source"); continue; }
                if ($st['h'] !== srcHash($en[$k])) { $warnOld++; continue; }
                $p = checkTranslation($en[$k], $t[$k]);
                if ($st['s'] === 'needs-fix') { $warnFix++; continue; }
                if ($p) $err("$where: $k: " . implode('; ', $p));
            }
        }
        if ($warnFix) $warn("$version/$lang: $warnFix imported translations need fixing (see diff.php)");
        if ($warnOld) $warn("$version/$lang: $warnOld translations are outdated (English changed)");
    }
}

echo "\n$errors error(s), $warnings warning(s)\n";
exit($errors || (isset($o['strict']) && $warnings) ? 1 : 0);
