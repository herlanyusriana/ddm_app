# Operator Production Fields Design

## Goal

Expand Operator Master to match the five-column production sheet: No, Nama, QC Label, Group, and Target Prod.

## Fields

- **No** maps to `operator_code`. It accepts digits only, remains unique, and is stored as text to preserve leading zeroes.
- **Nama** maps to the existing operator name.
- **QC Label** accepts digits only and is stored as text to preserve leading zeroes.
- **Group** stores the leader name as text.
- **Target Prod** stores a non-negative integer target per operator.

## Interface

- The create form displays the five fields in one row on desktop and stacks them on mobile.
- The operator list uses the same five-column order, followed by the delete action.
- Existing operators remain valid; the three new fields are nullable for backward compatibility.

## Verification

- Numeric-only validation applies to No and QC Label.
- Target Prod rejects negative and non-integer values.
- All five values are saved and displayed in the expected order.
- Existing operator creation data remains readable after migration.
