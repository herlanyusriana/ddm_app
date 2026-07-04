# Optional SPK Custom Input Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow WIP and FG entries without SPK using Buyer, Part, and Size master selections.

**Architecture:** Keep one production-entry endpoint, branch validation by nullable `spk_id`, and toggle master-data fields in the existing Blade form.

**Tech Stack:** Laravel 12, Blade, vanilla JavaScript, PHPUnit

---

- [ ] Add failing tests for custom WIP/FG forms, validation, persistence, and history.
- [ ] Make SPK nullable in validation and implement custom master-data fields.
- [ ] Preserve SPK-backed behavior and run the complete test suite.
