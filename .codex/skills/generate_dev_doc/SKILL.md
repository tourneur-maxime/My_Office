---
name: generate_dev_doc
description: Generate comprehensive developer documentation from module summaries.
inputs:
  - name: /tmp/summaries.json
    type: json
    required: true
    description: Output of analyze_repo
  - name: docs/dev/diagrams.md
    type: markdown
    required: false
    description: Output of generate_diagrams (linked if available)
outputs:
  - name: docs/dev/dev.md
    type: markdown
    description: Full developer reference documentation
depends_on:
  - analyze_repo
  - generate_diagrams   # soft dependency — run if available
---

# generate_dev_doc

## Purpose
Produce a reference document for developers contributing to or extending the codebase.

---

## Steps

### 1 — Load summaries
Read `/tmp/summaries.json`. Sort modules by directory depth (root first).

### 2 — Generate document

`docs/dev/dev.md` structure:

```markdown
# Developer Documentation

> Auto-generated — do not edit manually. Re-run `generate_dev_doc` after changes.

## Overview
<2–3 sentence synthesis of the project's purpose, derived from all module summaries.>

## Architecture
> See [Architecture Diagrams](./diagrams.md) for visual representation.

## Modules

### `<module_name>`
**Files:** `file1.py`, `file2.py`
**Language:** Python
**Responsibility:** <1–2 sentences from summary>
**Public API:**
- `function_or_class(args)` — <one-line description>
**Internal dependencies:** `src/utils`, `src/config`
**External dependencies:** `requests`, `pydantic`

---

<!-- repeat for each module -->

## Setup & Installation
<If a setup file (requirements.txt, package.json, go.mod, Cargo.toml) was found in repo_map, list install commands here. Otherwise: "See repository root for setup instructions.">

## Running Tests
<If test directories found (tests/, __tests__/, spec/), include example test command. Otherwise: "No test directory detected.">

## Contributing
- Follow the module structure: one responsibility per package.
- Add/update summaries when creating new modules.
- Re-run the agent after significant changes.

---
_Generated: <ISO timestamp>_
```

---

## Rules

- One `###` section per entry in `summaries.json`.
- Public API listed only if `exports` array is non-empty.
- Link to `diagrams.md` only if the file exists.
- Do not invent setup or test commands not found in repo_map.

---

## Validation

- [ ] `docs/dev/dev.md` exists and is non-empty
- [ ] One section per module in summaries.json
- [ ] No broken links (diagrams.md referenced only if it exists)
- [ ] Timestamp present
