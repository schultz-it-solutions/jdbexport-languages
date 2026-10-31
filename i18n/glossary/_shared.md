# Shared glossary and rules (all languages)

## What jDBexport is

jDBexport is a Joomla component that runs administrator-defined SQL queries and delivers
the results as Excel, PDF, CSV or other documents. Most strings are backend
configuration labels (`_LABEL`), their help texts (`_DESC`) and option lists (`_OPTn`),
read by Joomla site administrators. Strings under `COM_JDBEXPORT_SITE_*` and in
`site/com_jdbexport.ini` are shown to front-end visitors.

## Core concepts (translate consistently, see the language glossary)

| English | Meaning |
|---|---|
| Workbook | One exportable document definition (like an Excel workbook); has one or more worksheets |
| Worksheet | One sheet of a workbook, defined by one SQL query |
| Runtime parameter | A value the user enters before a document is created, substituted into the SQL query |
| Schedule | A timed job that creates workbooks and e-mails them |
| Extra cell | A fixed cell added to a worksheet besides the query result |
| Conditional format | Cell formatting applied when a condition is met (as in Excel) |
| Autofilter | Excel's autofilter |
| Cache | Stored, already created documents |
| Database (connection) | An external database jDBexport can query |

The language glossary (`<tag>.md`) always takes precedence over this file. Where it has
no entry and Excel or LibreOffice has an established term in the target language
(worksheet, cell range, autofilter, conditional formatting, freeze panes, print area),
use Excel's localized term, then add it to the language glossary.

## Never translate

- Product and library names: jDBexport, Joomla, Schultz IT Solutions, mPDF, PhpSpreadsheet,
  OpenSpout, ChatGPT, GPT-3.5 Turbo, Google DocViewer, Microsoft Excel Online, LibreOffice
- Technical tokens: SQL, URL, CSS, HTML, PDF, CSV, XLS/XLSX, ODS, JSON, PHP, API, ID, CRON
- SQL keywords and code: `SELECT`, `WHERE`, `UNION`, `date()`, `Y-m-d`, `*.xls`
- Placeholders and substitution variables: `%s`, `%1$s`, `%d`, `{CURRENTMODEL}`,
  `{USER_NAME}`, `{QUERY_STRING}`, `{EVEN}`, `{/ODD}`, `{jdbexportgrid}` ... in short,
  anything in `{...}` or starting with `%`
- Plugin tag syntax such as `{jdbexportfield}Workbook_ID:Worksheet_ID:Cell_COORDINATE{/jdbexportfield}`
- URLs, `href` targets, CSS class names, HTML tag names and attributes
- Option names quoted from the UI in square brackets, e.g. `[Show SQL errors in worksheet]`:
  translate them **only** with the exact translation used for that option's own label

## Format rules

- Keep every HTML tag; translate only the visible text between tags.
- Attribute quotes in HTML are single quotes (`href='...'`). Never use `"_QQ_"` (removed in
  Joomla 4). A literal double quote in text is written as `\"` (the tools do this).
- Keep leading/trailing spaces and punctuation that the English string has (strings are
  sometimes concatenated in code, e.g. a message followed by a value).
- Keep the length roughly similar for `_LABEL` strings (they are form labels and buttons).
- Option values in UPPER CASE (e.g. paper sizes `LETTER EXTRA PAPER`) are names, keep them.
- If the English source looks wrong (typo, broken HTML, unclear), translate the intended
  meaning and report the source problem; do not copy the mistake.
