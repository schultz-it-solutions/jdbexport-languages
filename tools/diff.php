<?php
/**
 * List the translation work for one language as JSON (for the agent or a human).
 *
 *   php tools/diff.php --version=v4.1 --lang=de-DE [--limit=150] [--file=admin/com_jdbexport.ini]
 *   php tools/diff.php --version=v4.1 --summary          (table for all languages)
 *
 * Work item reasons:
 *   missing    no translation yet
 *   changed    the English text changed since it was translated ("current" = old translation)
 *   needs-fix  imported translation that fails a check ("problems" says why)
 * Each item carries "tm": an existing translation of the identical English text from
 * elsewhere in this language (any file or version), to reuse for consistency.
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'lang:', 'limit:', 'file:', 'summary']);
$version = $o['version'] ?? fail('--version is required');
$state = loadState($version);

function workItems(string $version, string $lang, array $state, ?string $onlyFile): array
{
    $tm = translationMemory($lang);
    $items = [];
    foreach (sourceFiles($version) as $rel) {
        if ($onlyFile !== null && $rel !== $onlyFile) continue;
        $en = sourceStrings($version, $rel);
        $cur = currentValues($version, $lang, $rel, $state);
        foreach ($en as $k => $e) {
            if ($e === '') continue;
            $st = $state[$lang][$rel][$k] ?? null;
            $reason = null;
            if ($st === null || !array_key_exists($k, $cur)) $reason = 'missing';
            elseif ($st['h'] !== srcHash($e)) $reason = 'changed';
            elseif ($st['s'] === 'needs-fix') $reason = 'needs-fix';
            if ($reason === null) continue;
            $item = ['file' => $rel, 'key' => $k, 'en' => $e, 'reason' => $reason];
            if ($reason !== 'missing') $item['current'] = $cur[$k];
            if ($reason === 'needs-fix') $item['problems'] = checkTranslation($e, $cur[$k]);
            if (isset($tm[$e]) && $tm[$e]['where'] !== "$version/$rel:$k") $item['tm'] = $tm[$e];
            $items[] = $item;
        }
    }
    return $items;
}

if (isset($o['summary'])) {
    printf("%-7s %8s %8s %8s %8s %8s %8s\n", 'lang', 'strings', 'reviewed', 'machine', 'needsfix', 'changed', 'missing');
    foreach (versionLanguages($version) as $lang) {
        $c = ['reviewed' => 0, 'machine' => 0, 'needs-fix' => 0, 'changed' => 0, 'missing' => 0, 'total' => 0];
        foreach (sourceFiles($version) as $rel) {
            foreach (sourceStrings($version, $rel) as $k => $e) {
                if ($e === '') continue;
                $c['total']++;
                $st = $state[$lang][$rel][$k] ?? null;
                if ($st === null) $c['missing']++;
                elseif ($st['h'] !== srcHash($e)) $c['changed']++;
                else $c[$st['s']]++;
            }
        }
        printf("%-7s %8d %8d %8d %8d %8d %8d\n", $lang, $c['total'], $c['reviewed'], $c['machine'], $c['needs-fix'], $c['changed'], $c['missing']);
    }
    exit(0);
}

$lang = $o['lang'] ?? fail('--lang is required (or use --summary)');
$items = workItems($version, $lang, $state, $o['file'] ?? null);
$total = count($items);
if (isset($o['limit'])) $items = array_slice($items, 0, (int) $o['limit']);

echo json_encode([
    'version' => $version,
    'lang' => $lang,
    'glossary' => "i18n/glossary/$lang.md",
    'remaining' => $total,
    'items' => $items,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
