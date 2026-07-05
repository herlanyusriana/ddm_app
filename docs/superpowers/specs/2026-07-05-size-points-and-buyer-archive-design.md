# Size Points and Buyer Archive Design

## Goal

Support selectable A/B size-point combinations for Binding production and safely remove buyers that are already referenced by historical data.

## Size Master

Each selectable size variant contains:

- `production_code`: A or B.
- `type`: the existing size code, such as 8T or 12Q.
- `point`: decimal value, such as 0.8 or 1.4.

The combination of production code and type is unique. Existing size rows remain valid during migration.

Size Master create, list, export, and import use:

`Code | Type | Point`

## Production Input and Binding Points

- Size selection displays values such as `A - 12Q (1 point)` and `B - 12Q (0.5 point)`.
- The selected size variant stores the chosen Code, Type, and Point through its existing `size_variant_id`.
- Binding Excel calculates each entry's points as `Good Qty × Size Point`.
- Binding hourly cells identify Code and Type.
- Binding export adds Total Point per operator.

## Buyer Removal

- Deleting an unused buyer performs a normal hard delete.
- Deleting a buyer referenced by Parts, SPKs, or Production Entries changes `is_active` to false instead.
- Archived buyers remain available to historical relations but are hidden from new production/SPK selections.
- Buyer Master clearly labels archived rows and allows the operator to understand that history was preserved.

## Verification

- A/B variants of the same Type can coexist with different points.
- Size import/export preserves decimal points and leading type text.
- Production input shows Code, Type, and Point.
- Binding export calculates Total Point correctly.
- Referenced buyers archive without database errors; unused buyers still delete.
