# Optional SPK Custom Input Design

## Goal

Allow WIP and FG production entries to be recorded without selecting an SPK.

## Form Behavior

- The SPK selector becomes optional and includes a **Custom / Tanpa SPK** option.
- Selecting an SPK preserves the existing autofill and lot-capacity behavior.
- Custom mode requires selections from existing master data:
  - Buyer from Buyer Master.
  - Item from Part Master.
  - Size from Size Master.
- Items are filtered to the selected buyer; generic items without a buyer remain available.
- Process, Good, Reject, notes, production date, and shift retain their existing behavior.

## Storage and Validation

- Custom entries store `spk_id` as `null`.
- Buyer, item (`part_id`), and size are required in custom mode.
- SPK lot-capacity checks and SPK status updates run only when an SPK is selected.
- SPK-backed entries continue deriving buyer/item/size from the selected SPK where applicable.

## Display

- History displays **Custom** when an entry has no SPK.
- Buyer, item, and size are shown for custom WIP and FG entries.

## Verification

- WIP and FG custom entries can be saved from master-data selections.
- Missing custom buyer, item, or size is rejected.
- Buyer-specific item filtering is present.
- Existing SPK-backed entry behavior remains unchanged.
