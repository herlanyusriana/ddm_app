# Realtime Hourly Production History Design

## Goal

Make the production History table hourly and ensure its values always match the Excel export for the selected production date, shift, and process.

## Scope

- Replace the raw-entry History table with an hourly summary.
- Keep the existing production input flow and stored production entries.
- Use one server-side aggregation path for both the History table and Excel export.
- Refresh the summary after a successful production input through the existing redirect response.
- Do not add background polling or WebSockets.

## Data Selection

The hourly summary uses production entries matching:

- the selected production date;
- the selected shift; and
- the selected process.

The WIP page uses its selected process. On the FG page, the summary and export use the process selected by the page request. If no process is explicitly selected, the first available input process for that page is used consistently.

## Hour Buckets

- Shift 1 starts at 08:00 Asia/Jakarta.
- Shift 2 starts at 16:00 Asia/Jakarta.
- Shift 3 starts at 00:00 Asia/Jakarta on the following calendar day.
- `Jam 1` through `Jam 7` are consecutive one-hour buckets from the shift start.
- Entry `created_at` values are converted from the application timezone to Asia/Jakarta before bucketing.
- Entries outside the seven-hour window remain included in total Good and Reject, matching the existing export behavior, but do not appear in an hourly cell.

## Binding Summary

Binding displays one row per operator:

`No | Nama Operator | Target Operator | Jam 1 ... Jam 7 | Total Good | Total Reject | Total Point`

Each hourly cell groups entries by Buyer and Size and shows Good and Reject quantities. Total Point is the sum of Good quantity multiplied by the Size point.

## Other Process Summary

Other processes display one row per Buyer and Size:

`Buyer | Size | Jam 1 ... Jam 7 | Total Good | Total Reject`

Each hourly cell shows its Good and Reject quantities.

## Shared Aggregation

The controller builds a single hourly report structure containing:

- process metadata;
- table headers;
- summarized rows; and
- record count.

The Blade view renders this structure, while the XLSX endpoint passes the same headers and rows to the spreadsheet writer. No hourly aggregation is duplicated in Blade or JavaScript.

## Empty and Error States

- If no entries match the filter, History shows an empty-state row and Excel contains headers with no data rows.
- Existing request validation remains responsible for invalid dates, shifts, and process IDs.
- Missing related Buyer, Size, or Operator values retain the existing fallback marker.

## Verification

- A newly saved entry appears in the correct hourly cell after the redirect.
- History and Excel contain identical hourly values and totals.
- Binding groups by operator and retains target and point values.
- Non-Binding processes group by Buyer and Size.
- Shift boundaries and Asia/Jakarta conversion are covered by automated tests.
- Existing production input and export tests continue to pass.
