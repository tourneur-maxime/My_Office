---
name: build_context
description: Filter and rank summaries relevant to a user question, producing a focused context for QA.
inputs:
  - name: question
    type: string
    required: true
    description: The user's question or query
  - name: /tmp/summaries.json
    type: json
    required: true
    description: Output of analyze_repo
outputs:
  - name: /tmp/context.md
    type: markdown
    description: Focused context block for qa_repo to reason over
depends_on:
  - analyze_repo
---

# build_context

## Purpose
Avoid passing the entire repo to the QA skill. Select only the modules
most relevant to the question to stay within context limits and improve answer precision.

---

## Steps

### 1 — Keyword extraction
Extract 3–8 keywords from `question`:
- Remove stop words (the, is, how, what, does, in, of…)
- Keep nouns, verbs, module names, function names

### 2 — Relevance scoring
For each entry in `summaries.json`, compute score:
```
score = count of keywords found in (module name + summary + exports)
```

### 3 — Selection
Keep the top N entries where score > 0, capped at:
- N = 5 for short questions (≤10 words)
- N = 10 for longer questions

If zero matches → include all entries (fallback).

### 4 — Format context

Write `/tmp/context.md`:

```markdown
# Context for: "<question>"

## Relevant Modules

### src/core
**Files:** parser.py, lexer.py
**Summary:** Handles tokenization and AST generation. Entry point is parse(). Depends on utils/logger.
**Exports:** parse, tokenize

### src/utils
**Files:** logger.py
**Summary:** Centralized logging utility. Used by all modules.
**Exports:** log, debug, warn

## Full Question
<question>
```

---

## Validation

- [ ] `/tmp/context.md` exists and is non-empty
- [ ] At least one module section present
- [ ] Question is echoed verbatim at the bottom

On failure: emit warning and pass full `summaries.json` as raw fallback.
