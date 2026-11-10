# Servers app - implementation plan and result

This is the final, spec-specific plan. The generated app is `Servers.zip`.

## 1. Overview

A custom Hubleto app (`Hubleto\App\External\WaiBlue\ServerReport`, slug `servers`) that helps IT teams track the state of servers they
manage. Key functions: server records (description, connection details without passwords, three documentation links,
tags, owner/manager/sharing, location, provider, customer, workflow, notes, PDF report), application stack with history,
health with history, typical activities and a planner, calendar tab, reports of performed activities, overall and detailed
dashboards, sidebar and application menu.

## 2. Decisions and assumptions

| # | Decision |
|---|---|
| A1 | `HealthStatus` has the requested JSON `status` and an extra `overall` enum (dashboards need something filterable) |
| A2 | Datacenter is a separate table; the server holds room, rack and position |
| A3 | `ActivityTemplate` holds typical activities with default recurrence; the Planner plans them on servers |
| A4 | Completing an activity creates a draft report (setting, on by default) |
| A5 | `id_owner` = responsible administrator, `id_manager` = manager |
| A6 | Secret guard rejects obvious passwords in connection details and stack configuration (setting) |
| A7 | Boards hide the desktop only when embedded in a panel (`panelUid` / `idPanel`) |
| A8 | `is_closed` is set when the server moves to the workflow step tagged `servers-decommissioned` |
| D-TBL | All SQL tables are prefixed `servers` (main table `servers`) |
| D-CMP | One `Table<Plural>.tsx` and one `Form<Singular>.tsx` per model |
| D-API | API controllers read through `prepareReadQuery()` before touching data of a server |
| D-PHP | PHP 8.2 syntax only (`(new X())->` with parentheses), 2-space indentation |
| D-SEED | Default data are created in install rounds 1 and 2 (idempotent) and on demand |

## 3. Risks

| Risk | Mitigation |
|---|---|
| App was never run against a live Hubleto instance | Static checks (below); shipped `Tests/RenderAllRoutesTest.php`; README lists limitations |
| `app install` runs only round 1 | Seeding in rounds 1 and 2 and on demand; README install sequence; core patch proposal |
| Foreign keys exist only after `php hubleto migrate` | README install sequence |
| Custom app cannot ship a dictionary | English in code; framework patch proposal |
| `InputJsonKeyValue` stores strings | Dashboards parse values with `parseFloat`; empty values ignored |
| Conditional `hideDefaultDesktop` is unverified (A7) | Documented; patch proposal for an explicit embedded flag |
| Events/listeners could slow every save | The app registers no listeners; logic is in model callbacks |
| Secret guard false positives | Setting to disable it |

## 4. Facts that shaped the design

`php hubleto app install` runs round 1 only; `generateDemoData()` runs only in `php hubleto init`; `Documents\Generator` needs
`id_template`, `id_document` and `pdf` and keeps one Document per record with versions; the base `getDocumentPreviewVars()`
loads all relations (overridden); `CalendarTab` needs `is_closed` and `ACTIVITIES` on the record; recurrence is a JSON with an
expanded `dates` list; boards are POSTed by panels with `idPanel`/`panelUid` and a saved configuration; `prepareReadQuery()` limits
non-administrators to owned/managed/shared records; FK name = `fk_` + md5(table_column) (verified against the framework);
indexes are created for Boolean, Date, DateTime, Time, Lookup and Integer columns.

## 5. Community functionality reused

Customers (Customer, tag pattern), Auth (User), Settings (Country, ActivityType, config), Calendar (Activity, Calendar,
CalendarTab, CalendarFormActivity), Workflow (Workflow, WorkflowStep, manager), Documents (Generator, Template, Document),
Dashboards (Controller, manager), Desktop (AppMenu extendible), Helper::deleteTags.

## 6. Integrations

Workflow group `servers`; calendar `servers`; two boards (configurable); Settings tiles; global search; AppMenu; form tab
"Servers" in `FormCustomer`; Documents PDF generation; Customers lookup.

## 7. Data structure

| Model | Table |
|---|---|
| Server | `servers` |
| Provider | `servers_providers` |
| Datacenter | `servers_datacenters` |
| Tag | `servers_tags` |
| ServerHasTag | `servers_has_tags` |
| StackItem | `servers_stack_items` |
| HealthStatus | `servers_health_statuses` |
| ActivityTemplate | `servers_activity_templates` |
| ServerActivity (extends Calendar Activity) | `servers_activities` |
| ActivityReport | `servers_activity_reports` |

Each has a model, a record manager and migration `<Model>_0001`.

## 8. Services

`Counter`, `Settings`, `DefaultData`, `Health`, `SecretGuard`, `Reporting`, `Planner`, `Overview`, `DemoData`.

## 9. Routes, controllers, views, components

CRUD routes: `servers`, `server-report/providers`, `server-report/datacenters`, `server-report/tags`, `server-report/server-has-tags`, `server-report/stack`,
`server-report/health`, `server-report/activity-templates`, `server-report/activities`, `server-report/reports`. Pages: `server-report/settings`,
`server-report/planner`. Boards: `server-report/board/health-overview`, `server-report/board/health-detail`. API: `server-report/api/{get-server-overview,
generate-report, get-planner-data, plan-activities, log-activity, create-report-from-activity}`.

Components: a `Table*.tsx` and a `Form*.tsx` for each of the 10 models, plus `HealthBadge`, `ServerOverview` (default tab of the
server form), `ServersPlanner`. `FormServerActivity` wraps the Calendar activity form and is also registered for the Calendar.

## 10. Loader

`init()` registers routes, search, settings tiles, workflow group, calendar and boards (no queries); `installApp(1|2)` upgrades
schema (round 1) and seeds default data; sidebar, alerts, badge, search and demo data as in the community apps.

## 11. Event listeners

None. Logic is in model callbacks (secret guard, link validation, current-version handling, report snapshot, decommission closing).

## 12. Demo data

Six servers in two datacenters with two providers, tags, application stack history, 14 days of health checks (mixed states),
recurring planned activities and performed activities with reports. Does nothing when servers already exist.

## 13. Folder structure

See the zip: `manifest.yaml`, `Loader.php`, `Loader.tsx`, `Calendar.php`, `Workflow.php`, `Components/FC`, `Controllers` (+ `Api`,
`Boards`), `DefaultTemplates`, `Extendibles`, `Models` (+ `Migrations`, `RecordManagers`), `Services`, `Tests`, `Views` (+ `Boards`),
`README.md` and three patch files.

## 14. Suggested patches

`community-apps-patches.md`, `community-core-patches.md`, `hubleto-framework-patches.md` (in the app folder).

## 15. Implementation status

| Phase | Result |
|---|---|
| P0 analysis | done (community code read from the cloned repositories) |
| P1 skeleton and data layer | done: 10 models, record managers, migrations |
| P2 backend logic | done: callbacks, services, controllers, API |
| P3 UI | done: 20 model components, overview, planner, Loader.tsx |
| P4 integrations and data | done: workflow, calendar, boards, documents, seeding, demo data |
| P5 docs and patches | done |
| P6 QA | static checks passed (php -l, tsc type-check, linter, class resolution, Twig structure); **not run in a live instance** |
