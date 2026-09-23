---
paths:
  - app/Modules/Collections/Models/Entry.php
---

# Models

## Entry ownership comes from SEO meta_author, no creator column
Entry ownership ("other authors") is decided by the SEO tab's Meta Author field, NOT a creator column: Entry::ownedBy() returns true when seo.meta_author is empty (no author recorded = global/shared content, editable with just the base permission) and otherwise compares it to the logged-in user id. Only non-owner users need the "edit/publish/delete other authors {collection} entries" permissions. There is deliberately no created_by column.
