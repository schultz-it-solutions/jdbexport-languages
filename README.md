# jDBexport language packs

Translations for [jDBexport](https://it-solutions.schultz.ch), the Joomla component that
exports database queries to Excel, PDF and other documents. The language packs are
released separately from the component and installed through Joomla's extension
installer.

## Status

Each language is a mix of translations by volunteers (**reviewed**) and translations made
by an AI agent that no native speaker has checked yet (**machine**). The installer
description of every language pack states the current percentages. Run
`php tools/diff.php --version=v4.1 --summary` for the full table.

**Help is very welcome**: if you speak one of the languages, reviewing machine
translations is the most valuable contribution, and it takes no technical setup.

## How to help

### Review or correct a translation (no tools needed)

1. Open the file of your language, e.g.
   [`v4.1/translations/de-DE/admin/com_jdbexport.ini`](v4.1/translations/de-DE/admin/com_jdbexport.ini),
   and use GitHub's edit button (pencil icon). GitHub creates a fork and a pull request
   for you.
2. Change only the text between the quotes. Keep the key on the left, all HTML tags,
   links and placeholders such as `%s` or `{CURRENTMODEL}` exactly as they are.
3. In the pull request description, say whether you changed individual strings or
   reviewed the whole file / language.

An automatic check validates every pull request. Your corrections are then marked as
reviewed and terms you chose go into the language's glossary in
[`i18n/glossary/`](i18n/glossary/), so future translations follow them. You can also
correct the glossary directly; that is often the quickest way to fix a recurring term.

### Report a problem

Open an issue with the language, the key (e.g. `COM_JDBEXPORT_WORD_WORKBOOK`) and what
is wrong.

### Request a new language

Open an issue. New languages start machine-translated and become "reviewed" as native
speakers check them.

## Repository layout

| Path | Content |
|---|---|
| `v4.1/`, `v5/` | One folder per jDBexport version line |
| `<version>/source/en-GB/` | English master texts from the component |
| `<version>/translations/<tag>/` | One folder per language |
| `i18n/languages.json` | Languages and package versions |
| `i18n/state/` | Translation status per string (maintained by the tools) |
| `i18n/glossary/` | Term decisions per language |
| `tools/` | PHP command-line tools (diff, apply, sync, validate, review, build) |

Untranslated strings carry the English text, so an incomplete language pack never shows
raw language keys.

## For maintainers

```
php tools/diff.php     --version=v4.1 --summary            # status table
php tools/validate.php                                      # checks (also run by CI)
php tools/sync.php     --version=v4.1                       # after updating source/en-GB
php tools/review.php   --version=v4.1 --lang=de-DE --base=<ref>   # record a volunteer review
php tools/build.php    --version=v4.1                       # build packages into dist/
```

Translation work by the agent follows
[`.claude/skills/translate-jdbexport/SKILL.md`](.claude/skills/translate-jdbexport/SKILL.md).

## License

GNU General Public License v3, see [LICENSE](LICENSE).
