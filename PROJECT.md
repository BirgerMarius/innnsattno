# Project

Version: 1.0

---

# Project Information

## Name

innsatt.no

## Repository

git@github.com:BirgerMarius/innnsattno.git

## Technology

- Laravel
- PHP 8
- Livewire
- Blade
- MySQL-compatible Laravel database configuration
- Laravel Mix

---

# Purpose

innsatt.no is a Laravel web application. AI Software Factory should support small, reviewable improvements while preserving the existing application architecture.

---

# Scope

Included:

- Incremental Laravel application maintenance.
- Blade view, shared layout, partial, CSS, route, controller, and service changes when explicitly requested by a Work Package.
- Documentation updates when they support the requested Work Package.

Outside scope unless separately approved:

- Deployment.
- Large rewrites or framework migrations.
- Migration of older standalone modules.
- Unrelated cleanup.

---

# Architecture

The project is a Laravel application with Blade views under `resources/views`, routes under `routes`, public assets under `public`, and application services under `app`.

Shared layout:

- `resources/views/layouts/app.blade.php`

Shared partials:

- `resources/views/partials/header.blade.php`
- `resources/views/partials/footer.blade.php`

Global custom CSS:

- `public/css/custom/app.css`

---

# Coding Standards

Preserve the existing architecture and style. Make small incremental changes, one logical task at a time.

---

# Deployment

No deployment may be performed without explicit user approval.

---

# Testing

Use the smallest relevant verification for each Work Package. Prefer project-native Laravel, PHP, or frontend checks when available and applicable.

---

# AI Instructions

- Preserve existing architecture.
- Use `resources/views/layouts/app.blade.php` for shared layout work.
- Use shared header and footer partials where applicable.
- Use `public/css/custom/app.css` for global custom CSS.
- Make small incremental changes.
- Complete one logical task at a time.
- Do not deploy without user approval.
- Do not migrate older standalone modules except in separate Work Packages.

---

# References

- `project.yaml`
- `context/project-rules.md`
- `README.md`
