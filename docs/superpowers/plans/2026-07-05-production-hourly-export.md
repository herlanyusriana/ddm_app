# Production Hourly Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Binding operator capture and hourly production Excel exports while removing Item from custom input.

**Architecture:** Store an optional operator relation on production entries, branch validation by process, and aggregate entries into Jakarta-time shift-hour buckets for XLSX export.

**Tech Stack:** Laravel 12, Blade, ZipArchive, PHPUnit

---

- [ ] Add failing tests for custom input, Binding operator validation, and both export formats.
- [ ] Add schema/model/form/controller support for Binding operators.
- [ ] Add hourly aggregation endpoint and export controls.
- [ ] Run migration and full verification.
