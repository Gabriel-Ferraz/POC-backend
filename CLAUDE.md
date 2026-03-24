# Project Guidelines

## Foundational Context

Stack: Laravel 11 | PHP 8.2 | Inertia v1 + React 18 | Tailwind v3 | Pest v3 | Boost MCP

> **Note:** Laravel core conventions, PHP style, testing basics, Inertia, Tailwind, and Pint rules
> are already provided by Laravel Boost guidelines. The files below extend them with
> project-specific architecture, patterns, and deeper conventions.

## Code Philosophy

- Write all code, comments, docblocks, and variable names in **English**
- Use early returns to reduce nesting
- Prefer immutability: `readonly` properties, value objects, `final` classes by default
- Max method length: ~20 lines — extract if longer
- Max class length: ~200 lines — split responsibilities if longer
- Never leave dead code, commented-out blocks, or TODO without a linked issue
- One class per file, always

## Laravel Boost

@import ./BOOST.md

## Global Decision Engine

@import ./.claude-collective/DECISION.md

## Task Master AI Instructions

@import ./.taskmaster/CLAUDE.md

## Development Learnings

- Store and read all development learnings in `./.claude/learnings/{doc_name}.md` — never write them directly into `CLAUDE.md`
- Group learnings by topic (e.g. `debugging.md`, `patterns.md`, `integrations.md`)
- Before starting a task, check relevant learning files to build on previous experience
- After solving a non-trivial problem, update or create the appropriate learning file

## Agent Usage

- Always prefer **parallel agents** for independent subtasks to keep the main context window focused and clean
- Delegate research, file exploration, and isolated implementations to subagents via the Agent tool
- Avoid duplicating work that a subagent is already performing
