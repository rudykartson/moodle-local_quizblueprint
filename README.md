# Quiz Blueprint Builder (local_quizblueprint)

Build a quiz's section / random-question structure from an Excel spreadsheet
instead of clicking through the Quiz → Questions editor by hand.

Tested target: **Moodle 4.3+** (written against `MOODLE_403_STABLE` quiz
internals; intended to also run on 4.4 / 5.x).

---

## What it does

On a quiz's **Questions** page a new **Blueprint Import** button appears next to
*Repaginate* / *Select multiple items*. From there a teacher can:

1. **Download Template** – a 3-sheet `.xlsx`:
   - *Blueprint* – the rows you fill in (one section per row).
   - *Category Reference* – every question category in scope, its **Category ID**,
     and how many usable questions it currently holds (auto-generated).
   - *Question Reference* – category → question-name listing (optional aid).
2. **Upload Blueprint** – upload the filled-in sheet.
3. **Preview** – see exactly what will be created (per-section breakdown plus
   totals). **Nothing is written until you confirm.**
4. **Create Quiz Structure** – the structure is built inside a single DB
   transaction; any error rolls the whole thing back (no half-built quiz).

### Blueprint columns

| Column | Required | Notes |
|---|---|---|
| Section Name | yes | Heading for the section. |
| Category | yes | Category **ID** (preferred, unambiguous) or exact name. |
| Random | yes | `Yes/No/Y/N/TRUE/FALSE/1/0`. **v1 builds random sections only** (must be Yes). |
| Question Count | yes | Positive integer, ≤ available questions in that category. |
| Mark Per Question | yes | Numeric > 0, decimals allowed. |
| Shuffle | no | `Yes/No/...` – shuffles questions within the section. |
| Page Break | no | See *Known limitations* below. |

Validation is all-or-nothing: if any row is invalid you get a list of every
problem and nothing is imported.

---

## Install

1. Copy this folder to `MOODLE/local/quizblueprint`.
2. Visit **Site administration → Notifications** and complete the upgrade.
3. The capability `local/quizblueprint:manage` is granted to **Editing Teacher**
   and **Manager** by default (cloned from `mod/quiz:manage`).

Requires the `mod_quiz` module (core) and PhpSpreadsheet (bundled with Moodle —
no Composer step needed).

---

## Architecture (no core hacks)

- **Button injection** – `lib.php` uses the
  `before_standard_top_of_body_html` callback, scoped to pagetype
  `mod-quiz-edit` and gated on the capability, to append the toolbar button via
  a small dependency-free inline script. Core files are never modified.
- **Settings-nav node** – also added under the module settings menu as an
  official, JS-independent entry point.
- **Structure writes** – go exclusively through the public
  `\mod_quiz\quiz_settings` / `\mod_quiz\structure` APIs
  (`add_random_questions`, `add_section_heading`, `set_section_shuffle`,
  `update_slot_maxmark`, grade recompute via `get_grade_calculator()`),
  so random slots land correctly in `question_set_references`.

---

## Known limitations (v1)

These are deliberate v1 scope choices, called out so there are no surprises:

1. **Random-only sections.** Each row creates a section of *random* questions
   drawn from one category. Hand-picked fixed questions are not part of v1
   (the `Random` column must be Yes).
2. **One section per page.** Each blueprint row becomes its own section starting
   on its own page, so section boundaries and page breaks coincide. The
   `Page Break` column is therefore structurally always-honored between
   sections rather than a free-standing mid-section break.

---

## ⚠️ Please verify on your instance before production use

This plugin was written and **statically** verified (all files lint-clean) and
the quiz-API calls were checked against the Moodle 4.3 source — but it has **not**
been run against a live Moodle instance here. Before trusting it on a real
course, smoke-test on a throwaway quiz and confirm these two highest-risk areas
in particular:

1. **Section / page sequencing** (`classes/local/blueprint_builder.php`).
   The builder assumes page 1 already has a default section (`firstslot = 1`)
   created with the quiz, updates that one for the first row, and adds new
   section headings for subsequent rows on incrementing pages. Verify the first
   section isn't duplicated and that later sections land on the right pages.
2. **Question-count SQL** (`classes/local/category_helper.php`).
   The available-questions count walks the 4.x bank schema
   (`question_bank_entries` → latest `ready` `question_versions` → `question`,
   excluding `qtype = 'random'`). Confirm the counts shown in the template match
   what you see in the question bank for a few categories, including ones with
   draft/old versions and subcategories.

After confirming, treat this zip as the known-good baseline and apply
incremental patches on top of it.

---

## Running the automated tests

The plugin ships PHPUnit tests covering the two highest-risk areas:

- `tests/category_helper_test.php` — question counting against the 4.x bank
  schema (ready vs draft versions, multiple versions per entry, `random` qtype
  exclusion, subcategories, in-scope category resolution).
- `tests/blueprint_builder_test.php` — structure creation (sections, pages,
  per-slot marks, shuffle, recomputed total grade), the default-first-section
  reuse (no duplicate section), and the attempt guard (build refuses once the
  quiz has a non-preview attempt).

From the Moodle root, initialise the test environment once, then run just this
plugin's suite:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter local_quizblueprint
# or by path:
vendor/bin/phpunit local/quizblueprint/tests
```

These require a configured PHPUnit test database (`$CFG->phpunit_prefix` and
`$CFG->phpunit_dataroot` in `config.php`). They were written against the
verified 4.3 APIs and schema but, like the rest of the plugin, should be run on
your instance to confirm behaviour for your Moodle/DB version.
