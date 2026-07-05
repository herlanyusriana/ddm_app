# Production Hourly Export Design

## Goal

Remove Item from custom production input and export production history as hourly Excel summaries.

## Custom Input

- Custom / Tanpa SPK requires Buyer and Size, but no Item.
- SPK-backed FG behavior retains its existing item handling.
- Binding requires an Operator selected from Operator Master.
- Other processes do not request or store an operator.

## Stored Data

- Add a nullable `operator_id` relationship to production entries.
- Server validation requires `operator_id` only when the selected process is named Binding.
- Binding entries retain Buyer, Size, Good, Reject, date, shift, and creation time for reporting.

## Export Scope

Export follows the current production date, shift, and selected process.

### Binding

One row per operator with:

`No | Nama | Target | Jam 1 | Jam 2 | Jam 3 | Jam 4 | Jam 5 | Jam 6 | Jam 7 | Total Good | Total Reject`

- No, Nama, and Target come from Operator Master.
- Each hourly cell groups Good/Reject by Buyer and Size.
- Target uses the operator's per-person `target_prod`.

### Other Processes

Rows are grouped by Buyer and Size. Operator name and operator target are omitted.

## Hour Buckets

- Shift 1 starts at 08:00.
- Shift 2 starts at 16:00.
- Shift 3 starts at 00:00.
- Jam 1 through Jam 7 are consecutive one-hour buckets from the shift start.
- Entry timestamps are interpreted in Asia/Jakarta.

## Verification

- Custom WIP/FG entries save without Item.
- Binding rejects input without an operator.
- Non-Binding input does not require an operator.
- Binding export uses Operator Master targets and hourly Buyer/Size totals.
- Other-process export contains no operator identity.
