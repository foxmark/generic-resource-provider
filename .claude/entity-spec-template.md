# Entity Specification: Book (example)

---

## Conventions & Defaults

These rules apply to every field unless explicitly overridden in the tables below.

- **All DTO fields are nullable (`?type`) and optional** unless marked **required** in the Validation column or application logic mandates a value (e.g. `available` has a hardcoded default so it never needs to be sent by the client).
- **Bool fields are never nullable** — they always carry a value; document their default explicitly.
- **Default maximum length for string fields: 128 characters** — `varchar(128)` in the DB column, `Length(max:128)` in DTO validation. Any field that deviates must say so in the Notes column.
- `id` is always auto-generated, read-only, and never accepted on write operations.
- `createdAt` is always set by `PrePersist`, never exposed on the API, and never accepted as input.

---

## 1. Domain Context

**What it represents:** A physical or digital book in a library catalogue.
**Who uses it:** Librarians (manage catalogue), members (browse & check availability).
**Domain:** Library management.

---

## 2. API Resource Fields

Fields exposed on the DTO (`BookResource`). All fields are nullable and optional unless the Validation column says **required**.

| Field           | PHP type | Nullable | Validation                              | Notes                                           |
|-----------------|----------|----------|-----------------------------------------|-------------------------------------------------|
| id              | int      | yes      | none — read-only, set by DB             |                                                 |
| title           | string   | yes      | **required** — NotBlank, Length(min:2, max:255) | max 255 — overrides the 128 default    |
| isbn            | string   | yes      | **required** — NotBlank, Isbn           | max 20 — stored as varchar(20) in DB            |
| authorName      | string   | yes      | **required** — NotBlank, Length(max:255)| max 255 — overrides the 128 default             |
| publicationYear | int      | yes      | Range(min:1000, max:2100)               | Exposed as a year integer; maps to `publishedAt` in entity |
| available       | bool     | **no**   | none                                    | Defaults to `true`; bool fields are never nullable |

---

## 3. Entity Fields

Fields stored in the database (`Book` entity). Fields not listed here map directly from the DTO with no transformation.

| Field       | PHP type          | Nullable | DB type            | Unique | Default | Notes                                                 |
|-------------|-------------------|----------|--------------------|--------|---------|-------------------------------------------------------|
| id          | int               | yes      | integer, auto-incr | yes    | —       | Auto-generated                                        |
| title       | string            | no       | varchar(255)       | no     | `''`    | Overrides 128-char default                            |
| isbn        | string            | no       | varchar(20)        | yes    | `''`    | Unique constraint; well below 128-char default        |
| authorName  | string            | no       | varchar(255)       | no     | `''`    | Overrides 128-char default                            |
| publishedAt | DateTimeImmutable | yes      | datetime_immutable | no     | null    | Stores full date; DTO exposes only the year as an int |
| available   | bool              | no       | boolean            | no     | true    |                                                       |
| createdAt   | DateTimeImmutable | no       | datetime_immutable | no     | —       | Set by `PrePersist`; never exposed on the API         |

---

## 4. Field Mappings (DTO ↔ Entity differences)

Only fields where the name or type differs between DTO and entity.

| DTO field       | Entity field | Direction | Transformation                                                              |
|-----------------|--------------|-----------|-----------------------------------------------------------------------------|
| publicationYear | publishedAt  | both      | DTO `int` year → `{year}-01-01` `DateTimeImmutable`; entity → extract `Y` as `int`. Null in either direction if absent. |

---

## 5. API Operations

| Operation     | Enabled | Notes      |
|---------------|---------|------------|
| GetCollection | yes     | paginated  |
| Get           | yes     |            |
| Post          | yes     |            |
| Put           | yes     |            |
| Patch         | yes     |            |
| Delete        | yes     |            |

**Pagination:** enabled, default 10 per page, client-configurable, max 30.

---

## 6. Default Collection Ordering

Implement `DefaultOrderRepositoryInterface` on the repository.

| Field | Direction |
|-------|-----------|
| id    | ASC       |

---

## 7. Events

| Interface                      | Implement | Effect                                      |
|--------------------------------|-----------|---------------------------------------------|
| `NotifiableInsertInterface`    | **yes**   | fires `book.created` after `postPersist`    |
| `NotifiableUpdatedInterface`   | **yes**   | fires `book.updated` after `postUpdate`     |

**Event class:** `BookChangeEvent`
**Event constants:** `book.created`, `book.updated`

**Subscriber logs these fields on both create and update:**
- `entity_class`
- `entity_id`
- `isbn`
- `title`

---

## 8. Custom Repository Methods

| Method          | Returns  | Description                                                    |
|-----------------|----------|----------------------------------------------------------------|
| findAvailable() | `Book[]` | All books where `available = true`, ordered by `title ASC`    |

---

## 9. Business Rules

- `id` is auto-generated and read-only; it cannot be set or changed by the client.
- `isbn` must be unique across all books.
- `createdAt` is set once on first persist and never updated or exposed.
- `available` defaults to `true` on creation if the client omits it.
- `publicationYear` is optional; if absent, `publishedAt` is stored as `null`.
- `title`, `isbn`, and `authorName` are required — a book cannot be created without them.

---

## 10. User Stories

**As a librarian:**
- I want to add a new book (POST) with title, ISBN, author, and publication year so it appears in the catalogue.
- I want to update a book's availability (PATCH) so members know if it can be borrowed.
- I want to replace all book details at once (PUT) when correcting a mis-catalogued entry.
- I want to delete a book (DELETE) when it is permanently removed from the collection.

**As a member:**
- I want to list all books (GET collection, paginated) so I can browse the catalogue.
- I want to fetch a single book by ID (GET) to see its full details.

---

## 11. Test Scenarios

### Happy path
- POST with all valid fields → 201, returns new book with `id`
- GET collection → 200, paginated list, ordered by `id ASC`
- GET single → 200, correct fields returned
- PUT with updated `title` → 200, title changed
- PATCH `available: false` → 200, `available` is `false`
- DELETE → 204, subsequent GET on same `id` returns 404

### Validation / edge cases
- POST missing `title` → 422 Unprocessable Entity
- POST missing `isbn` → 422
- POST missing `authorName` → 422
- POST invalid ISBN format → 422
- POST `publicationYear` below 1000 → 422
- POST `publicationYear` above 2100 → 422
- POST duplicate `isbn` → 422 (unique constraint violation)
- GET non-existent `id` → 404
- PATCH `id` field → ignored, value unchanged

### Events
- POST a book → `book.created` event fired, log entry written with `isbn` and `title`
- PUT/PATCH a book → `book.updated` event fired, log entry written with `isbn` and `title`
