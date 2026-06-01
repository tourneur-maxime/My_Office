---
name: update_docs_on_change
description: Patch existing documentation based on a git diff, without rewriting unchanged sections.
inputs:
  - name: git_diff
    type: string
    required: true
    description: Raw output of `git diff` or path to a .diff file
  - name: docs/dev/dev.md
    type: markdown
    required: false
    description: Existing developer doc (patched if affected modules changed)
  - name: docs/user/user.md
    type: markdown
    required: false
    description: Existing user doc (patched if user-facing modules changed)
outputs:
  - name: docs/dev/dev.md
    type: markdown
    description: Patched developer doc (only changed sections)
  - name: docs/user/user.md
    type: markdown
    description: Patched user doc (only if user-facing change detected)
  - name: /tmp/change_report.md
    type: markdown
    description: Summary of what changed and what was updated
depends_on: []
---

# update_docs_on_change

## Purpose
Surgically update only the doc sections affected by a code change.
Do not rewrite docs from scratch; preserve existing human edits.

---

## Steps

### 1 — Parse git diff
Extract from `git_diff`:
- Changed files: list of `+++ b/<path>` headers
- Added lines: lines starting with `+` (excluding `+++`)
- Removed lines: lines starting with `-` (excluding `---`)
- Changed modules: map file paths to module names (strip filename, keep directory)

### 2 — Classify changes

| Change type                        | Action                                      |
|------------------------------------|---------------------------------------------|
| New file added                     | Add new module section to dev.md            |
| File deleted                       | Remove module section from dev.md           |
| Function/class signature changed   | Update Public API entry in dev.md           |
| New user-facing endpoint/command   | Add feature bullet to user.md               |
| Config file changed                | Update Setup section in dev.md and user.md  |
| Test file changed                  | Update Running Tests section in dev.md      |
| Internal-only change               | Update module summary in dev.md only        |

### 3 — Apply patches

For each affected section:
1. Locate section by heading (`### <module_name>` in dev.md).
2. Replace only the changed fields (Responsibility, Public API, Dependencies).
3. Preserve all other content in the section.
4. Append `_Updated: <ISO timestamp>_` below the changed section.

### 4 — Write change report

`/tmp/change_report.md`:

```markdown
# Change Report

**Diff processed:** <timestamp>
**Files changed:** 3
**Modules affected:** src/core, src/api

## Actions taken

| Module    | Action                        | Doc updated       |
|-----------|-------------------------------|-------------------|
| src/core  | Function signature changed    | docs/dev/dev.md   |
| src/api   | New endpoint added            | docs/dev/dev.md, docs/user/user.md |

## Sections NOT updated (unaffected)
- src/utils — no changes detected
- docs/user FAQ section — preserved as-is
```

---

## Rules

- Never overwrite a section unless the diff directly affects that module.
- If `docs/dev/dev.md` or `docs/user/user.md` do not exist → skip that output and warn.
- If diff is empty or unparseable → abort with `ERR_DIFF_PARSE` and explain.

---

## Validation

- [ ] `/tmp/change_report.md` exists
- [ ] Each action in the report corresponds to a real change in the diff
- [ ] Unchanged doc sections are byte-for-byte identical to input
- [ ] Updated sections contain the new `_Updated:` timestamp
