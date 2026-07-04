# Operator Production Fields Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add QC Label, leader group, and per-person production target to Operator Master.

**Architecture:** Extend the existing operator table with nullable columns and update the current CRUD form/list.

**Tech Stack:** Laravel 12, Blade, PHPUnit

---

- [ ] Add failing persistence and validation tests.
- [ ] Add migration/model/controller fields and five-column UI.
- [ ] Run migration and complete regression tests.
