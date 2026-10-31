<?php
/**
 * Build installable Joomla language packages.
 *
 *   php tools/build.php --version=v4.1 [--lang=de-DE] [--out=dist]
 *
 * Per language: dist/<version>/file_jdbexport_lang_<tag>-<pkgversion>.zip, a Joomla
 * "file" extension that installs
 *   site/*   -> language/<tag>/
 *   admin/*  -> administrator/language/<tag>/
 * with unprefixed file names (com_jdbexport.ini), the form Joomla documents since 4.0.
 * Without --lang an all-languages package pkg_jdbexport_languages-<pkgversion>.zip is
 * built as well. Package version and Joomla target come from i18n/languages.json.
 */
require __DIR__ . '/lib.php';

$o = opts(['version:', 'lang:', 'out:']);
$version = $o['version'] ?? fail('--version is required');
$out = ROOT . '/' . trim($o['out'] ?? 'dist', '/') . "/$version";
$meta = languages();
$vmeta = $meta['versions'][$version];
$pkgVersion = $vmeta['packageVersion'] ?? fail("packageVersion missing for $version in languages.json");
$state = loadState($version);
$langs = isset($o['lang']) ? [$o['lang']] : $vmeta['languages'];
if (!is_dir($out)) mkdir($out, 0777, true);

$targets = ['site' => 'language', 'admin' => 'administrator/language'];
$date = date('Y-m-d');
$built = [];

foreach ($langs as $lang) {
    $element = 'jdbexport_lang_' . strtolower(str_replace('-', '_', $lang));
    $name = $meta['names'][$lang]['english'] ?? $lang;
    // coverage for the description
    $n = 0; $tr = 0; $rev = 0;
    foreach (sourceFiles($version) as $rel) {
        foreach (sourceStrings($version, $rel) as $k => $e) {
            if ($e === '') continue;
            $n++;
            $st = $state[$lang][$rel][$k] ?? null;
            if ($st && $st['h'] === srcHash($e) && $st['s'] !== 'needs-fix') { $tr++; if ($st['s'] === 'reviewed') $rev++; }
        }
    }
    $pct = intdiv(100 * $tr, max(1, $n)); $revPct = intdiv(100 * $rev, max(1, $n));
    $quality = $revPct >= 90 ? 'reviewed by native speakers'
        : ($revPct > 0 ? "$revPct% reviewed by native speakers, the rest machine-translated" : 'machine-translated, not yet reviewed');

    $filesets = '';
    $zipName = "file_{$element}-{$pkgVersion}.zip";
    $zip = new ZipArchive();
    $zip->open("$out/$zipName", ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($targets as $area => $target) {
        $list = array_values(array_filter(sourceFiles($version), fn($r) => str_starts_with($r, "$area/")));
        if (!$list) continue;
        $filesets .= "\t\t<files folder=\"$area\" target=\"$target/$lang\">\n";
        foreach ($list as $rel) {
            $zip->addFile(transPath($version, $lang, $rel), $rel);
            $filesets .= "\t\t\t<filename>" . basename($rel) . "</filename>\n";
        }
        $filesets .= "\t\t</files>\n";
    }
    $desc = htmlspecialchars("jDBexport $version language pack: $name ($lang). $pct% translated, $quality. "
        . "Help improve it at https://github.com/schultz-it-solutions/jdbexport-languages", ENT_XML1);
    $manifest = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="file" method="upgrade">
	<name>jDBexport language pack $lang</name>
	<element>$element</element>
	<author>Schultz IT Solutions and jDBexport translators</author>
	<authorUrl>https://it-solutions.schultz.ch</authorUrl>
	<creationDate>$date</creationDate>
	<copyright>Copyright (C) Schultz IT Solutions</copyright>
	<license>GNU General Public License version 3 or later</license>
	<version>$pkgVersion</version>
	<description>$desc</description>
	<fileset>
$filesets	</fileset>
</extension>

XML;
    $zip->addFromString("file_$element.xml", $manifest);
    $zip->close();
    $built[] = [$element, $zipName];
    printf("%-6s %3d%% translated (%3d%% reviewed)  %s\n", $lang, $pct, $revPct, $zipName);
}

if (!isset($o['lang'])) {
    $files = implode("\n", array_map(fn($b) => "\t\t<file type=\"file\" id=\"{$b[0]}\">{$b[1]}</file>", $built));
    $manifest = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="package" method="upgrade">
	<name>jDBexport language packs (all languages)</name>
	<packagename>jdbexport_languages</packagename>
	<author>Schultz IT Solutions and jDBexport translators</author>
	<authorUrl>https://it-solutions.schultz.ch</authorUrl>
	<creationDate>$date</creationDate>
	<copyright>Copyright (C) Schultz IT Solutions</copyright>
	<license>GNU General Public License version 3 or later</license>
	<version>$pkgVersion</version>
	<description>All jDBexport $version language packs. Translations are maintained at https://github.com/schultz-it-solutions/jdbexport-languages</description>
	<files>
$files
	</files>
</extension>

XML;
    $pkg = "pkg_jdbexport_languages-$pkgVersion.zip";
    $zip = new ZipArchive();
    $zip->open("$out/$pkg", ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($built as $b) $zip->addFile("$out/{$b[1]}", $b[1]);
    $zip->addFromString('pkg_jdbexport_languages.xml', $manifest);
    $zip->close();
    echo "package: $pkg\n";
}
