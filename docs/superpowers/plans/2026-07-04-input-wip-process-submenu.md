# Input WIP Process Submenu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show WIP processes as sidebar submenu links and bind the selected link to the WIP input form.

**Architecture:** Share the ordered WIP process collection with the production layout through a view composer. The input controller validates `process_id`, defaults safely, and the Blade view submits the WIP selection as a hidden value while leaving FG unchanged.

**Tech Stack:** Laravel 12, Blade, PHPUnit feature tests

---

### Task 1: Specify navigation and selection behavior

**Files:**
- Modify: `tests/Feature/ProductionAdminTest.php`

- [ ] Add feature tests asserting ordered WIP-only submenu links, active process selection, invalid-ID fallback, hidden WIP process input, and unchanged FG radio selection.
- [ ] Run `php artisan test --filter=ProductionAdminTest` and confirm the new assertions fail because submenu and hidden selection are absent.

### Task 2: Implement the submenu and selected process

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Controllers/ProductionAdminController.php`
- Modify: `resources/views/production/layout.blade.php`
- Modify: `resources/views/production/index.blade.php`

- [ ] Register a `production.layout` view composer that loads input processes excluding FG/Packing, ordered by `sort_order`.
- [ ] Resolve the WIP `process_id` against the allowed collection and default to its first item.
- [ ] Render nested sidebar links with preserved date/shift parameters and active styling.
- [ ] Replace the WIP radio list with a hidden `process_id` and visible selected-process label; retain the existing FG selector.
- [ ] Show a non-submittable empty state when no WIP processes exist.
- [ ] Run `php artisan test --filter=ProductionAdminTest`, then the complete `php artisan test` suite.
