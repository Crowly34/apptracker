# Comments

Default to none. Rename the variable or method before you reach for a comment.

Write one only if it passes this test: it explains *why* — a non-obvious
constraint, a rejected alternative, a remote side effect, a deliberate gap —
something not recoverable from the code. A comment that restates *what* a line
does is noise. Delete it.

- No pointers to PRDs, tickets, or planning docs. The repo is the source of truth.
- Don't document code that isn't written yet.
- Plain technical English, one or two lines.
- PHPDoc: keep real type hints (`@param`/`@return` generics); drop prose that
  repeats the signature.
- Config files are the exception — one short comment per key is fine.
