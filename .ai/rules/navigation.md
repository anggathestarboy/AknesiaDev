---
paths:
  - 'app/Modules/Navigation/**'
---

# Navigation

## Navigation tree editor: no cross-group wire:sort — use the "Move to..." menu instead
The tree editor originally shared one `wire:sort:group` name between the root list and every parent's children list, so SortableJS would let you drag an item between them. In practice this was unreliable: children were only rendered as a drop target while their parent was both expanded AND already had at least one child (so you often couldn't drop into a target at all), and dragging a child directly onto the root list could visually corrupt an existing root row's displayed data due to how the client-side DOM move interacted with Livewire's morph before the server round-trip resolved.

The fix: each `wire:sort` list is now independent (`sortRoot($id, $position)` for the root `<ul>`, `sortChildren($id, $position, $parentId)` per parent's children `<ul>`, using `wire:sort:group-id` only to pass the parent id — no `wire:sort:group` is set anywhere, so SortableJS never considers these lists a shared drag pool). Reordering only ever happens within one list.

Moving an item between levels/parents is instead an explicit action: the "…" context menu's "Move to..." opens a modal (`askMove`/`confirmMove` in `NavigationTreeEditor`) where you pick a target parent (or "Level 1"). If the item being moved already has children of its own and is going under another parent, the modal makes you choose `promote` (lift its children to level 1) or `delete` them first — via `NavigationTreeService::withPromotedChildren()` / `withClearedChildren()` — since a parent-with-children becoming a level-2 item would otherwise push its children to level 3, past the max depth. Do not reintroduce a shared `wire:sort:group` between the root and children lists to "fix" drag-and-drop — build any further cross-level move UX through this explicit menu/service path instead.
