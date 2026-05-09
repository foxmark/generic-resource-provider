# Domain Glossary

## Title

A movie title — a motion picture release in the catalog. Entirely separate from the Book domain.

**Fields:** `title` (string), `director` (string), `releaseYear` (int, earliest valid: 1888), `durationMinutes` (int, optional)

**Operations:** GET, GET collection, POST, PUT, PATCH. No DELETE — titles are never removed, only updated.

**Events:** Fires `TitleChangeEvent::CREATED` on insert only (`NotifiableInsertInterface`). No update events.

**Collection ordering:** `title ASC` by default (alphabetical).

**Pagination:** 10 per page default, client-configurable up to 30 (same as Book).

**Validation:**
- `title`: NotBlank, Length(min:1, max:255)
- `director`: NotBlank, Length(min:2, max:255)
- `releaseYear`: NotBlank, Range(min:1888, max:2100)
- `durationMinutes`: Range(min:1, max:600), nullable

## Book

A physical library item. Has `title` (string field, not a Title entity), `isbn`, `authorName`, `publicationYear`, `available`. Fires events on both insert and update.
