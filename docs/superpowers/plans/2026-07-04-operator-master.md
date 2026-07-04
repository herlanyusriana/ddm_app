# Operator Master Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add list, create, and delete workflows for operator codes and names.

**Architecture:** Follow the existing Laravel Buyer Master pattern with an Operator model, migration, controller routes, and Blade views.

**Tech Stack:** Laravel 12, Blade, PHPUnit

---

- [ ] Add failing feature tests for navigation, creation, uniqueness, listing, and deletion.
- [ ] Add the operator table, model, routes, controller actions, and views.
- [ ] Run migrations/tests and complete verification.
