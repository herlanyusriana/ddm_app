# Size Points and Buyer Archive Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add A/B point-bearing size variants and archive referenced buyers safely.

**Architecture:** Extend SizeVariant while retaining its existing foreign key identity, update size CRUD/XLSX/input labels, calculate Binding points from selected sizes, and branch Buyer deletion by dependency usage.

**Tech Stack:** Laravel 12, Blade, SQLite/MySQL migrations, PHPUnit

---

- [ ] Add failing tests for A/B size variants, Binding points, and Buyer archive behavior.
- [ ] Add Size schema/model/CRUD/XLSX/input changes.
- [ ] Add Binding Total Point calculation.
- [ ] Add safe Buyer archive behavior and active-only selectors.
- [ ] Run migration and full verification.
