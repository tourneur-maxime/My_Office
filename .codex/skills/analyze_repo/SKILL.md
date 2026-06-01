---
name: analyze_repo
description: Scan a repository and produce structured maps, dependency graphs, and module summaries.
inputs:
  - name: repo_path
    type: string
    required: true
    description: Absolute or relative path to the repository root
outputs:
  - name: /tmp/repo_map.json
    type: json
    description: Tree of all files with metadata (path, size, extension, last modified)
  - name: /tmp/dependency_graph.json
    type: json
    description: Module-level import/dependency graph
  - name: /tmp/summaries.json
    type: json
    description: Per-module plain-text summaries for downstream skills
depends_on: []
---

# analyze_repo

## Purpose
Ground truth step. Every other skill depends on this output.
Run once per repo; reuse `/tmp/` outputs if they already exist and repo has not changed.

---

## Freshness check (skip if already fresh)

```
if /tmp/repo_map.json exists:
    compare mtime of /tmp/repo_map.json with latest git commit timestamp
    if repo_map is newer → SKIP, reuse outputs
    else → recompute
```

---

## Step 1 — Map the repo

Traverse `repo_path` recursively. Exclude:
- `.git/`, `node_modules/`, `__pycache__/`, `dist/`, `build/`, `*.lock`

For each file, record:
```json
{
  "path": "src/core/parser.py",
  "extension": ".py",
  "size_bytes": 4200,
  "last_modified": "2025-04-10T14:22:00Z"
}
```

Write result to `/tmp/repo_map.json`.

---

## Step 2 — Build dependency graph

For each source file in repo_map:
- Extract imports/requires/includes using language-appropriate patterns:
  - Python: `import X`, `from X import Y`
  - JS/TS: `import`, `require()`
  - Go: `import "pkg"`
  - Java/Kotlin: `import pkg.Class`
- Resolve to internal modules vs external packages.

Output format:
```json
{
  "nodes": ["src/core/parser", "src/utils/logger"],
  "edges": [
    { "from": "src/core/parser", "to": "src/utils/logger", "type": "import" }
  ],
  "external_deps": ["requests", "lodash"]
}
```

Write to `/tmp/dependency_graph.json`.

---

## Step 3 — Summarize modules

For each module (group files by directory or package):
- Infer responsibility from file names, class names, function signatures.
- Write a 3–5 sentence plain-text summary.

Output format:
```json
[
  {
    "module": "src/core",
    "files": ["parser.py", "lexer.py"],
    "summary": "Handles tokenization and AST generation. Entry point is parse(). Depends on utils/logger for debug output.",
    "exports": ["parse", "tokenize"],
    "language": "python"
  }
]
```

Write to `/tmp/summaries.json`.

---

## Validation

Before exiting, verify:
- [ ] `/tmp/repo_map.json` — non-empty, valid JSON
- [ ] `/tmp/dependency_graph.json` — has `nodes` and `edges` keys
- [ ] `/tmp/summaries.json` — array with ≥1 item, each has `module` and `summary`

On failure: log the failing file and abort with error code `ERR_ANALYZE`.
