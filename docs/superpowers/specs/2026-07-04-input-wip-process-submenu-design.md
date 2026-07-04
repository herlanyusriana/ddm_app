# Input WIP Process Submenu Design

## Goal

Move the WIP process selector from the production input form into the main sidebar. A user chooses a process from the submenu under **Input Proses (WIP)**, then lands on the WIP form with that process already selected.

## Navigation

- **Input Proses (WIP)** remains the parent navigation item.
- Its submenu is populated dynamically from processes that are valid WIP input processes, using the same filtering and ordering rules as the WIP input page.
- Each submenu link points to `/input-proses?process_id={id}`.
- The selected process submenu and its parent are visually active.
- Existing production date and shift query parameters are preserved when navigating between process submenu items when they are available.
- **Input Hasil (FG)** and **Rework** remain unchanged.

## Input Page

- The controller reads `process_id` from the query string.
- A process is accepted only if it belongs to the WIP process collection available to the page.
- When no valid process is selected, the first available WIP process is used as the default.
- The visible radio-button section titled **Pilih Proses** is removed from the WIP form.
- The form submits the selected WIP process through a hidden `process_id` input.
- The FG input page retains its existing process selector because this change is scoped to Input WIP.
- The selected process name is shown in the WIP form so the operator can verify the active context before saving.

## Data and Validation

The existing server-side production-entry validation remains authoritative. Sidebar selection only supplies the process ID; it does not bypass validation. Invalid or stale query-string IDs fall back to the first valid WIP process.

## Empty State

If no WIP processes are configured, the submenu contains no process links and the WIP form shows a clear message instead of presenting a submit-ready form without a process.

## Verification

- Confirm every configured WIP process appears in the submenu in `sort_order`.
- Confirm clicking a process highlights it and preselects it in the form.
- Confirm saving creates an entry for the selected process.
- Confirm invalid `process_id` values safely fall back.
- Confirm FG input and Rework navigation continue to behave as before.
- Confirm the sidebar remains usable on desktop and mobile.
