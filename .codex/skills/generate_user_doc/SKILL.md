---
name: generate_user_doc
description: Generate end-user documentation (guides, feature list, quickstart) from module summaries.
inputs:
  - name: /tmp/summaries.json
    type: json
    required: true
    description: Output of analyze_repo
outputs:
  - name: docs/user/user.md
    type: markdown
    description: Non-technical user guide
depends_on:
  - analyze_repo
---

# generate_user_doc

## Purpose
Produce accessible documentation for users of the project —
not contributors. Avoid internal module names and technical jargon.

---

## Steps

### 1 — Identify user-facing modules
From `summaries.json`, filter modules whose names or summaries suggest user-facing behavior:
- Keywords: `api`, `cli`, `ui`, `interface`, `endpoint`, `handler`, `route`, `command`
- Exclude: `test`, `utils`, `config`, `internal`, `core` (unless only option)

### 2 — Synthesize features
Map each user-facing module to a plain-language feature:
- `src/api/auth` → "User authentication (login, logout, token refresh)"
- `src/cli/export` → "Export data to CSV or JSON from the command line"

### 3 — Generate document

`docs/user/user.md` structure:

```markdown
# User Guide

> This guide explains how to use <project name>. No programming knowledge required.

## What is this project?
<2–3 sentence plain-language description of what the project does, derived from module summaries.>

## Features

- **<Feature 1>:** <One sentence description.>
- **<Feature 2>:** <One sentence description.>
<!-- one bullet per user-facing module/feature -->

## Quickstart

### 1. Install
<Installation steps if found in repo_map (package.json, requirements.txt, etc). Otherwise: "See README.md.">

### 2. Configure
<Configuration steps if a config file or env vars pattern detected. Otherwise: omit section.>

### 3. Run
<Main entry point command if detectable. Otherwise: "See README.md for usage instructions.">

## FAQ

**Q: Where do I report bugs?**
A: Open an issue in the repository's issue tracker.

**Q: Is there an API?**
A: <Yes, documented in docs/dev/dev.md / No API detected.>

---
_Generated: <ISO timestamp>_
```

---

## Rules

- Write for a non-technical audience: no module paths, no import names.
- If no user-facing modules detected → write a generic overview using all summaries.
- Never copy internal terminology (e.g., `dependency_graph`, `repo_map`).

---

## Validation

- [ ] `docs/user/user.md` exists and is non-empty
- [ ] At least 2 features listed
- [ ] No internal module paths visible in the output
- [ ] Timestamp present
