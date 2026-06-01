---
name: qa_repo
description: Answer a question about the repository using focused context from build_context.
inputs:
  - name: question
    type: string
    required: true
    description: The user's question (verbatim)
  - name: /tmp/context.md
    type: markdown
    required: true
    description: Output of build_context
outputs:
  - name: answer.md
    type: markdown
    description: Grounded answer with module references
depends_on:
  - analyze_repo
  - build_context
---

# qa_repo

## Purpose
Produce a factual, grounded answer to the user's question.
Every claim must be traceable to a module in `/tmp/context.md`.

---

## Steps

### 1 — Load context
Read `/tmp/context.md` in full.

### 2 — Reason over context
For each sentence in the answer:
- Map it to a specific module or export from context.
- Do not invent modules, functions, or behaviors not present in the context.

### 3 — Write answer

Format `answer.md`:

```markdown
# Answer

**Question:** <question>

## Response

<Clear answer in plain language. 3–10 sentences.>

## Evidence

| Claim | Source module |
|-------|--------------|
| <claim 1> | src/core |
| <claim 2> | src/utils |

## Confidence
HIGH | MEDIUM | LOW

> HIGH: all claims grounded in context.
> MEDIUM: some inference required.
> LOW: context insufficient; suggest running analyze_repo again.
```

---

## Grounding rules

- If the answer requires knowledge not in context → state "Information not found in current summaries."
- Never extrapolate behavior from external package names alone.
- If confidence is LOW → append: "Suggestion: run `analyze_repo` with `--deep` flag."

---

## Validation

- [ ] `answer.md` exists and is non-empty
- [ ] Evidence table has at least one row (or explicit "not found" note)
- [ ] Confidence field is set
