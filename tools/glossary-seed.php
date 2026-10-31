<?php
/**
 * Create a starting glossary for a language from its existing translations of the
 * core product terms. Existing glossary files are never overwritten.
 *
 *   php tools/glossary-seed.php --version=v4.1 [--lang=de-DE]
 */
require __DIR__ . '/lib.php';

const TERM_KEYS = [
    'COM_JDBEXPORT_WORD_WORKBOOK', 'COM_JDBEXPORT_WORD_WORKBOOKS', 'COM_JDBEXPORT_WORD_WORKSHEET',
    'COM_JDBEXPORT_WORD_WORKSHEETS', 'COM_JDBEXPORT_WORD_RUNTIMEPARAMETER', 'COM_JDBEXPORT_WORD_RUNTIMEPARAMETERS',
    'COM_JDBEXPORT_WORD_SCHEDULE', 'COM_JDBEXPORT_WORD_SCHEDULES', 'COM_JDBEXPORT_WORD_DATABASE',
    'COM_JDBEXPORT_WORD_DATABASES', 'COM_JDBEXPORT_WORD_CATEGORY', 'COM_JDBEXPORT_WORD_CATEGORIES',
    'COM_JDBEXPORT_WORD_QUERY', 'COM_JDBEXPORT_WORD_CELL', 'COM_JDBEXPORT_WORD_CELLRANGE',
    'COM_JDBEXPORT_WORD_CHARTS', 'COM_JDBEXPORT_WORD_AUTOFILTERS', 'COM_JDBEXPORT_WORD_EXTRACELLS',
    'COM_JDBEXPORT_WORD_HEADER', 'COM_JDBEXPORT_WORD_FOOTER', 'COM_JDBEXPORT_WORD_CLEARCACHE',
    'COM_JDBEXPORT_WORD_CONTROLPANEL', 'COM_JDBEXPORT_WORD_DOWNLOAD', 'COM_JDBEXPORT_WORD_PREVIEW',
];

$o = opts(['version:', 'lang:']);
$version = $o['version'] ?? fail('--version is required');
$state = loadState($version);
$rel = 'admin/com_jdbexport.ini';
$en = sourceStrings($version, $rel);
$names = languages()['names'];

foreach (isset($o['lang']) ? [$o['lang']] : versionLanguages($version) as $lang) {
    $path = ROOT . "/i18n/glossary/$lang.md";
    if (is_file($path)) { echo "$lang: glossary exists, skipped\n"; continue; }
    $vals = currentValues($version, $lang, $rel, $state);
    $rows = '';
    $found = 0;
    foreach (TERM_KEYS as $k) {
        if (!isset($en[$k])) continue;
        $st = $state[$lang][$rel][$k]['s'] ?? null;
        $tr = ($st === 'reviewed' || $st === 'machine') ? $vals[$k] : '';
        if ($tr !== '') $found++;
        $rows .= "| {$en[$k]} | " . ($tr === '' ? '_(not yet decided)_' : $tr) . " | " . ($st ?? '') . " |\n";
    }
    $name = $names[$lang]['english'] ?? $lang;
    $native = $names[$lang]['native'] ?? '';
    $md = <<<MD
# Glossary: $name ($native, $lang)

Read together with [`_shared.md`](_shared.md). Binding for every translation into this
language: use these terms consistently. Reviewers: correct this file when a term is
wrong, the agent will follow it from then on.

## Style

- Form of address: _(to be decided by a reviewer, e.g. formal/informal)_
- Tone: concise UI language, as in the Joomla core translation for this language.
- Joomla's own terms (Article, Category, Module, Plugin, Access level, Options, ...):
  use the wording of the official Joomla $lang translation.

## Product terms

Seeded from the existing translations of jDBexport $version ("reviewed" = volunteer
translation). A term marked "not yet decided" is chosen on first use and added here.

| English | $lang | source |
|---|---|---|
$rows
## Notes

_(add decisions, e.g. "Workbook: always X, never Y")_

MD;
    writeFile($path, $md);
    echo "$lang: glossary created ($found terms seeded)\n";
}
