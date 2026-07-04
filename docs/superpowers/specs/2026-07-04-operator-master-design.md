# Operator Master Design

## Goal

Add an Operator Master for maintaining production operator codes and names.

## Data

Create an `operators` table with:

- `id`
- `operator_code`: required, unique, maximum 40 characters
- `name`: required, maximum 120 characters
- timestamps

## Interface

- Add **Operator Master** to the Master Data sidebar.
- `/masters/operators` shows operator code, name, and delete action.
- `/masters/operators/create` shows a separate create form.
- Successful creation and deletion return to the operator list with a status message.
- Deletion requires browser confirmation.

## Scope

This version supports list, create, and delete, matching the existing Buyer Master workflow. Editing and active/inactive status are outside scope.

## Verification

- Operator Master appears in sidebar navigation.
- Valid operators can be created and listed.
- Duplicate operator codes are rejected.
- Operators can be deleted.
