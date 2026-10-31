---
name: translate-jdbexport
description: Translate, update or fix jDBexport language files in this repository (missing strings, changed English texts, imported translations that fail checks), or sync a new English source from a component release. Use for any request like "translate de-DE", "update the translations", "add Polish", "new 4.1.4 strings".
---

# Translating jDBexport

The tools in `tools/` own the `.ini` files and `i18n/state/*.json`. **Never edit
translation files or state by hand**: always go through `diff.php` → `apply.php`, so
every string is checked and tracked. Never change `*/source/en-GB/` except when syncing a
new component release (workflow B).

## A. Translate one language

1. Overview: `php tools/diff.php --version=v4.1 --summary`
2. Read `i18n/glossary/_shared.md` and `i18n/glossary/<tag>.md` completely. The language
   glossary wins over everything else. If it has no style decisions yet (form of
   address, tone), decide following the official Joomla translation for that language
   and write the decision into its "Notes" section, marked "(agent decision, please
   review)".
3. Get a batch: `php tools/diff.php --version=v4.1 --lang=<tag> --limit=150 > work/<tag>-batch.json`
4. For every item write a `value` and save the items as `work/<tag>-out.json`
   (a JSON array of `{file, key, value}`; you may keep the other fields).
   - **missing**: translate `en`.
   - **changed**: the English text changed; `current` is the old translation. Adapt it
     to the new English, keeping its wording where the meaning is unchanged.
   - **needs-fix**: an imported volunteer translation fails a check (`problems`). Fix
     only what the problem names (restore HTML tags or the link, replace `"_QQ_"` by
     single quotes, re-insert a placeholder); keep the volunteer's wording. If the
     English text is clearly longer than the translation (the English was extended
     after it was translated), translate the missing part too.
   - **tm** present: the identical English text is already translated elsewhere. Reuse
     it unless the key context clearly needs something different.
   - **Context**: look at the key name and its neighbours in
     `<version>/source/en-GB/<file>`: `_LABEL` is a short form label, `_DESC` its
     tooltip, `_OPTn` the choices of a dropdown, `COM_JDBEXPORT_WORD_*` are short labels
     used in many places, `*_XML_DESCRIPTION` is HTML shown in the extension manager,
     `COM_JDBEXPORT_SITE_*` and `site/` strings are seen by website visitors.
5. Apply: `php tools/apply.php --version=v4.1 --lang=<tag> --input=work/<tag>-out.json`
   - Agent translations use the default status `machine`.
   - For **needs-fix** items where you only repaired markup and kept the volunteer's text,
     apply them in a separate file with `--status=reviewed`.
   - Exit code 1 lists REJECTED items: fix exactly those and apply again.
6. Repeat 3–5 until `remaining` is 0.
7. Add every new product term you decided on to the language glossary table.
8. `php tools/validate.php --version=v4.1 --lang=<tag>` must report 0 errors.
9. Commit on a branch `translate/<version>-<tag>` and open a PR titled
   `<tag>: <n> strings translated (<version>)`. In the description list: counts by
   reason, glossary terms added or decided, and **English source problems** you noticed
   (typos, broken HTML, unclear strings, strings concatenated in code) so the maintainer
   can fix them in the component. Ask for review by a native speaker.

Work in batches; do not load an entire language at once. Keep `work/` out of commits
(it is git-ignored).

## B. New English source from a component release

1. Copy the component's `en-GB` files into `<version>/source/en-GB/` using unprefixed
   names: `admin/com_jdbexport.ini`, `admin/com_jdbexport.sys.ini`,
   `site/com_jdbexport.ini`. Convert line endings to LF.
2. `php tools/sync.php --version=<version>`. New keys appear with English text, removed
   keys disappear, and keys whose English text is identical to an existing translation
   are carried over automatically (this preserves work when keys are renamed or moved).
3. `php tools/diff.php --version=<version> --summary` shows what is left; continue with
   workflow A per language.
4. Bump `packageVersion` for that version in `i18n/languages.json`.

## C. Add a language

1. Add the tag to `versions.<version>.languages` and to `names` in `i18n/languages.json`
   (Joomla tags: `pl-PL`, `pt-BR`, `zh-TW`, `sr-YU` (Latin) / `sr-RS` (Cyrillic) ...).
2. `php tools/sync.php --version=<version>` creates the files (English placeholders).
3. `php tools/glossary-seed.php --version=<version> --lang=<tag>` creates the glossary.
4. Translate with workflow A.

## D. A volunteer reviewed a language

After merging their PR: `php tools/review.php --version=<v> --lang=<tag> --base=<commit before the PR>`
(only their edits become reviewed) or `--approve-all` (they approved the whole language).
Then add any term decisions from their review to the glossary.

## Build packages

`php tools/build.php --version=v4.1` → `dist/v4.1/` with one installable zip per language
plus `pkg_jdbexport_languages-<version>.zip`.
