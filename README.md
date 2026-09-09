# Quiz Blueprint Builder —

## What is this?

Quiz Blueprint Builder lets you set up a quiz's structure — its sections and random-question rules — by filling in a spreadsheet instead of clicking through Moodle's question editor row by row.

If you've ever needed to build a quiz with, say, 5 sections each pulling a random set of questions from a different category, you know how repetitive that is by hand. This plugin turns it into: download a template, fill in one row per section, upload it, review the preview, confirm.

## Where to find it

Open any quiz and go to its **Questions** page (the same screen where you normally add or reorder questions). You'll see a new **Blueprint Import** button next to the existing *Repaginate* and *Select multiple items* options. It's also available as a link in the quiz's settings menu.

You'll only see this button if you have teacher/manager-level permissions on the course — students never see it.

## How to use it

**Step 1 — Download the template.**
Click **Download template**. You'll get an Excel file with three tabs:
- **Blueprint** — the sheet you actually fill in, one row per section you want to create.
- **Category Reference** — every question category available to this quiz, its ID, and how many usable questions it currently holds. Use this to look up which Category ID to type into your Blueprint rows.
- **Question Reference** — a listing of which questions live in which category, if you want to double-check a category's contents before using it.

**Step 2 — Fill in the Blueprint sheet.**
Each row becomes one section of your quiz. The columns are:

| Column | Required? | What to put there |
|---|---|---|
| Section Name | Yes | The heading students will see for this section. |
| Category | Yes | The category's numeric **ID** from the Category Reference sheet (safest), or its exact name if it's unique. |
| Random | Yes | `Yes` — this version only builds random-question sections, so this column must be Yes. |
| Question Count | Yes | How many random questions to pull from that category. Must be a whole number, and can't be more than the "Available Questions" shown for that category. |
| Mark Per Question | Yes | How many marks each question in the section is worth. |
| Shuffle | No | `Yes`/`No` — whether questions within the section are shuffled for each student. |
| Page Break | No | Informational only — see the note below. |

A quick note on page breaks: in Moodle, every section always starts on its own new page, so a page break between your sections happens automatically regardless of what you put in this column.

**Step 3 — Upload it and preview.**
Upload your completed spreadsheet. Before anything is created, you'll see a full preview: every section, its category, question count, marks, and a running total of questions and marks for the whole quiz. **Nothing is written to your quiz at this point** — it's just a preview.

If anything in your spreadsheet has a problem (a missing category, too many questions requested for a category, a non-numeric mark, etc.), you'll get a clear list of exactly which rows need fixing instead of a partial import.

**Step 4 — Confirm and create.**
Once the preview looks right, click **Confirm and create**. The sections and random-question slots are added to your quiz, and the quiz's total grade is recalculated automatically. You'll see a summary of how many sections and questions were created.

## Things to know before you use it

- **It adds, it doesn't replace.** If your quiz already has questions or sections, the blueprint's sections are added on top of what's there — existing content is left alone.
- **Random questions only, for now.** Every row builds a section of randomly-drawn questions from one category. Hand-picking specific fixed questions isn't supported in this version.
- **Locked once a student has attempted the quiz.** If the quiz already has a real (non-preview) attempt, blueprint import is disabled — Moodle doesn't allow restructuring a quiz once students have started it, and this plugin respects that.
- **All-or-nothing validation.** If any row in your spreadsheet is invalid, nothing is imported — you'll get the full list of what to fix and can just re-upload the corrected file.
- **No personal data is stored.** The plugin doesn't collect student information; each import is simply recorded in Moodle's normal activity log.

## New to this plugin? Try it safely first

Before relying on it for a real course, run through it once on a spare or practice quiz — download the template, fill in one or two simple sections, and confirm the structure comes out the way you expect. Once you're comfortable with it, it's a big time-saver for building out larger, category-based quizzes.
