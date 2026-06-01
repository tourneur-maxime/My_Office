---
name: plan_execution
description: Parse user request and produce an ordered skill execution plan.
inputs:
  - name: user_request
    type: string
    required: true
    description: Raw user instruction
outputs:
  - name: execution_plan.json
    type: json
    schema: |
      {
        "pipeline": "doc | qa | update",
        "skills": ["skill_name", ...],
        "params": { "repo_path": "...", "question": "...", "git_diff": "..." }
      }
---

# plan_execution

## Purpose
Detect the user's intent and build the ordered list of skills to run.

---

## Detection Rules

Scan `user_request` for keywords:

| Keywords detected                          | Pipeline    | Skills to run (in order)                                          |
|--------------------------------------------|-------------|-------------------------------------------------------------------|
| doc / document / readme / generate / write | `doc`       | analyze_repo → generate_diagrams → generate_dev_doc → generate_user_doc |
| how / what / explain / why / where / query / question | `qa` | analyze_repo → build_context → qa_repo                       |
| update / change / diff / patch / modified  | `update`    | update_docs_on_change                                             |
| mixed (e.g. "update and explain")          | `doc+qa`    | analyze_repo → generate_dev_doc → generate_user_doc → build_context → qa_repo |

If no keyword matches → ask user to clarify.

---

## Steps

1. Tokenize `user_request` (lowercase).
2. Apply Detection Rules table (first match wins; mixed check last).
3. Extract parameters:
   - `repo_path`: from request or default to `.`
   - `question`: full user_request if pipeline is `qa`
   - `git_diff`: path or raw diff string if pipeline is `update`
4. Validate extracted params (non-empty, path accessible).
5. Serialize to `execution_plan.json`.

---

## Output example

```json
{
  "pipeline": "doc",
  "skills": [
    "analyze_repo",
    "generate_diagrams",
    "generate_dev_doc",
    "generate_user_doc"
  ],
  "params": {
    "repo_path": "./my-project"
  }
}
```

---

## Error handling

- Missing `repo_path` → set to `.` and emit warning.
- Ambiguous intent → output `pipeline: "unknown"` and stop; ask user.
