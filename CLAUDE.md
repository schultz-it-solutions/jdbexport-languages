# jdbexport-languages

Translations of the Joomla component jDBexport (and its modules and plugins), released
as separate language packages.

- `v4.1/`, `v5/`: one folder per jDBexport version line. `source/en-GB/` is the English
  master copied from the component; `translations/<tag>/` mirror it.
- Every jDBexport string lives in three files per language:
  `admin/com_jdbexport.ini`, `admin/com_jdbexport.sys.ini` (also holds the module,
  plugin and package names and descriptions), `site/com_jdbexport.ini`.
- `i18n/languages.json`: languages per version and package versions.
- `i18n/state/<version>.json`: per string the hash of the English text it was
  translated from and its status (`reviewed`, `machine`, `needs-fix`).
- `i18n/glossary/`: `_shared.md` for all languages plus one file per language.
- `tools/`: plain PHP 8.1+ CLI scripts, no dependencies.

For any translation work use the `translate-jdbexport` skill
(`.claude/skills/translate-jdbexport/SKILL.md`). Never hand-edit `.ini` files under
`translations/` or the state file; use the tools. Run `php tools/validate.php` before
committing; CI runs it on every pull request.
