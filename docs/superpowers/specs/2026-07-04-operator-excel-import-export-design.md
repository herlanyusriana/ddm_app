# Operator Excel Import/Export Design

## Goal

Add XLSX export and import actions to Operator Master.

## File Format

The worksheet uses these columns in order:

1. `No`
2. `Nama`
3. `QC LABEL`
4. `Group`
5. `Target Prod`

No and QC Label are written as text so leading zeroes are preserved.

## Export

- Export all operators ordered by No.
- Download an `.xlsx` file named with the current timestamp.

## Import

- Accept `.xlsx` files using the five-column format.
- Match existing operators by No.
- Update matched operators and create new operators.
- Skip rows missing No/Nama, rows with non-numeric No/QC Label, and rows with invalid Target Prod.
- Return the number of successfully imported rows.

## Interface and Verification

- Add **Export Excel** and **Import Excel** buttons to Operator Master.
- Provide a dedicated upload page with the expected column descriptions.
- Test exported headers/data, create/update imports, invalid-row skipping, and upload validation.
