# Realtime Shift Automation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automatically select the active production window and refresh dashboard production totals every five seconds.

**Architecture:** Centralize production-window calculation in the controller, expose dashboard summaries through JSON, and update existing dashboard markup through lightweight browser polling.

**Tech Stack:** Laravel 12, Blade, vanilla JavaScript, PHPUnit

---

### Task 1: Active production window

- [ ] Add failing feature tests for Shift 1, Shift 2, overnight Shift 3, and manual query overrides.
- [ ] Implement a timezone-aware active-window helper and use it on dashboard, WIP, and FG pages.

### Task 2: Realtime dashboard summaries

- [ ] Add failing tests for the JSON summaries and polling markup.
- [ ] Add the summary endpoint and five-second visibility-aware dashboard polling.
- [ ] Run focused tests, the full suite, and whitespace checks.
