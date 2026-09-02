---
name: commit
description: |
  Turn the working tree into a series of meaningful commits using Conventional
  Commits format.
  Use when: Gabriel says "commit this", "commit", "make commits", "/commit",
  "split into commits", or asks to stage and commit finished work.
user-invocable: true
argument-hint: "[optional: scope hint or which files]"
allowed-tools:
  - Bash
  - Read
---

# Commit

Split the current changes into commits and write them. Do not push unless asked.

## How to split

- **One logical concern per commit.** Auth mechanism, the endpoints that use it,
  a model rule, and its tests are four concerns, not one.
- **Small and concise — but function beats convention.** If splitting a file's
  hunks would leave a commit that doesn't build or boot, don't split it. A
  coherent working commit is worth more than a tidy diff.
- **Order so every commit stands on its own.** Model before controller,
  mechanism before wiring, code before the test that needs it. `git log` should
  read as a build sequence.
- Don't invent separation that isn't there — unrelated one-liners can share a
  `chore:` commit.

## Message format

Conventional Commits, when it fits:

```
type(scope): imperative summary, lower case, no period

Optional body: why, not what. Wrap at ~72. Note anything non-obvious —
a deferred edge case, a decision, a follow-up.
```

- Types: `feat`, `fix`, `refactor`, `test`, `chore`, `docs`, `perf`, `build`, `ci`.
- Scope is optional — use it when there's an obvious one (`api`, `applications`).
- If a change genuinely doesn't fit a type, write a plain imperative summary
  rather than forcing a label.
- Subject under ~70 chars. Body only when it adds something the diff doesn't.

## Rules

- **Never** add a `Claude-Session:` trailer, a `https://claude.ai/code/...`
  line, or any AI-attribution trailer. Strip them if a template adds them.
- Commit only what was asked for. If unrelated changes are staged, unstage them.
- Run `git status` and `git diff` first; use `git add <path>` or `git add -p`
  for precise staging, never a blind `git add -A`.
- If already on the default branch and the change is a feature, branch first
  unless Gabriel said to commit straight to it.
- Push only when Gabriel says to.
- Report the resulting `git log --oneline` and confirm the tree is clean.
