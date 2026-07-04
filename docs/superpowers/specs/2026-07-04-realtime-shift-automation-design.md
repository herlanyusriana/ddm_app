# Realtime Shift Automation Design

## Goal

Automatically select the active production date and shift, and refresh dashboard Good/Reject totals without a manual page reload.

## Active Production Window

All calculations use the application timezone (`Asia/Jakarta`):

- 08:00–15:59: Shift 1, current calendar date.
- 16:00–23:59: Shift 2, current calendar date.
- 00:00–07:59: Shift 3, previous calendar date.

Input WIP, Input FG, and the dashboard use this window when the request does not contain a manual date or shift.

## Realtime Dashboard

- Add a JSON endpoint returning dashboard process summaries and totals for a requested production date and shift.
- The dashboard polls the endpoint every five seconds and updates totals, process cards, and the last-updated indicator without reloading.
- Polling reads existing production entries; the production-entry write flow remains unchanged.
- Polling pauses while the page is hidden and refreshes immediately when it becomes visible again.
- A failed poll keeps the last valid values and shows a disconnected status until the next successful response.

## Manual History Mode

The existing date and shift controls remain available. Submitting them opens a manual historical view and polling follows that selected production window. A visible **Kembali ke shift aktif** action restores automatic date and shift selection.

## Verification

- Test all three time ranges, including Shift 3 using the previous production date.
- Test explicit query parameters overriding the automatic window.
- Test the JSON summary response.
- Verify dashboard markup exposes update targets and polling configuration.
- Verify WIP and FG forms receive the automatic production window.
