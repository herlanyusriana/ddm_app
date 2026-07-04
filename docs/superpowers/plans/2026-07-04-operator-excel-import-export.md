# Operator Excel Import/Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Export and import Operator Master data through the agreed five-column XLSX format.

**Architecture:** Reuse the controller’s existing XLSX writer/parser and add operator-specific routes, validation, and upload UI.

**Tech Stack:** Laravel 12, Blade, ZipArchive, PHPUnit

---

- [ ] Add failing export/import feature tests.
- [ ] Add routes, controller actions, buttons, and upload page.
- [ ] Run focused and full verification.
