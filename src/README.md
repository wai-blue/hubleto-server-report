# Servers - a custom app for Hubleto

Keeps track of the servers an IT team manages: description and connection details (no passwords), tags, location in a
datacenter, hardware provider, customer, the application stack with history, health with history, a workflow of the
server's lifecycle, planned (recurring) activities in the calendar, reports of what was done, dashboards and a PDF
server report.

Namespace `Hubleto\App\External\WaiBlue\ServerReport`, URL slug `servers`. Requires: Settings, Auth, Customers, Documents, Workflow,
Calendar, Dashboards.

## Installation

1. Unpack the zip into `src/apps` of your Hubleto project, so that `src/apps/Servers/manifest.yaml` exists.
2. Install the app: `php hubleto app install Hubleto\App\External\WaiBlue\ServerReport`
3. Create foreign keys: `php hubleto migrate`
4. Build the JS: `npm run build`

`php hubleto app install` runs only round 1 of the installation (tables). Round 3 (default permissions for roles and foreign
keys) is run by `php hubleto init` and by *Settings -> Apps*, so for a full installation prefer to (re)install the app
from *Settings -> Apps*. Default data (workflow of a server with its steps, the template of the PDF report, activity types
and typical activities) are created in **both** round 1 and round 2, are idempotent, and are also created on demand when
the first server is saved, so the app is usable after any of these paths.

**Never delete the installed-migrations record of a table in the config and reinstall:** the first migration of each table
starts with `drop table if exists` (as in the community apps) and would delete the data.

## What is where

| Area | Where |
|---|---|
| Servers (main model) | `server-report` - `Models/Server.php`, `Components/FC/TableServers.tsx`, `FormServer.tsx` |
| Hardware providers | `server-report/providers` |
| Datacenters | `server-report/datacenters` |
| Server tags | `server-report/tags` (also in Settings) |
| Application stack (with history) | `server-report/stack` and the tab "Application stack" of a server |
| Health history | `server-report/health` and the tab "Health history" of a server |
| Typical activities (templates) | `server-report/activity-templates` |
| Planner (plan typical activities on servers) | `server-report/planner` |
| Planned activities / calendar | `server-report/activities`, calendar `servers`, tab "Calendar" of a server |
| Activity reports | `server-report/reports` and the tab "Reports" of a server |
| Dashboards | `server-report/board/health-overview`, `server-report/board/health-detail` (also available as panels in the Dashboards app) |
| Settings | `server-report/settings` |

## Tables

All tables have the prefix `servers`: `servers`, `servers_providers`, `servers_datacenters`, `servers_tags`,
`servers_has_tags`, `servers_stack_items`, `servers_health_statuses`, `servers_activity_templates`, `servers_activities`,
`servers_activity_reports`.

## Behaviour worth knowing

* **Responsible administrator / manager** are `id_owner` / `id_manager`. Visibility follows Hubleto rules: administrators see
  all servers, managers see servers they own or manage, other users see servers they own (or that are shared with them).
* **Health status** has an overall status (OK, Warning, Critical, Down, Unknown) used by dashboards, filters and alerts and
  a free JSON `status` with details. Recommended keys: `cpu_percent`, `memory_percent`, `disk_percent`, `load_avg`,
  `uptime_days`. Keys ending with `_percent` are drawn as bars. The key-value editor stores values as strings, the dashboards
  parse them as numbers and ignore empty values.
* **Application stack** is append-only history: a new *current* record of an application on a server turns the older
  records of that application into history. The table shows only current records by default (filter "Show").
* **Secrets:** connection details and stack configuration are rejected when they look like they contain a password
  (e.g. `password: x`, `token=...`, a private key, `user:pass@host`). This is a heuristic and can be disabled in the
  settings. It does not replace a secret manager.
* **Documentation links** must start with `http://` or `https://`.
* **Closing:** moving a server to the workflow step with the tag `servers-decommissioned` sets `is_closed`.
* **Reports:** a report keeps a copy (date, time, description, server, worker) of the activity, so it stays correct when the
  activity is changed or deleted. When an activity is completed, a draft of its report is created (setting).
* **Recurrence:** typical activities are expanded to recurrence dates server-side from the chosen start date, in the format
  used by the Calendar app.
* **PDF report:** `server-report/api/generate-report` uses `Documents\Generator` with the template whose `used_for` is
  `servers-server-report` (created during installation from `DefaultTemplates/ServerReport.twig`). The report contains details
  of the server, the time of generation, the current health with its history, the application stack and activities of
  the last N days (setting, 90 by default). You can edit the template in the Documents app or select another template in
  the server (`id_template`).
* **Dashboards:** *Dashboard* shows the overall health of servers managed by a user, *Detailed dashboard* adds metrics and
  activities of the last X days (setting, 14 by default). As panels of the Dashboards app both accept the options
  `userEmail` (empty = the current user) and, for the detailed one, `days`.

## Public API for other apps

* Services (`Hubleto\App\External\WaiBlue\ServerReport\Services`): `Health` (labels, colors, latest status of servers), `Counter`,
  `Planner` (`plan()`, `expandDates()`, `nextOccurrence()`), `Overview`, `Reporting`, `Settings`, `DefaultData`, `SecretGuard`.
* API endpoints (all return JSON with `status`): `server-report/api/get-server-overview?idServer=`,
  `server-report/api/generate-report?idServer=`, `server-report/api/get-planner-data`,
  `server-report/api/plan-activities?idTemplate=&idServers=1,2&dateStart=YYYY-MM-DD&timeStart=HH:MM`,
  `server-report/api/log-activity?idServer=&activity=`, `server-report/api/create-report-from-activity?idActivity=`.
* Calendar key: `servers`. Workflow group: `servers`. Form tab "Servers" is added to the form of a customer.

## Localization

All strings in PHP, Twig and TSX are in English and go through the translation functions. The stock translator reads
dictionaries only from the folder `lang` of the `erp` package, so a custom app cannot ship its own dictionary until the
framework patch in `hubleto-framework-patches.md` is applied. Until then the app is displayed in English.

## Verification status

The app was generated and checked **statically** (no running Hubleto instance was available): `php -l` on all PHP
files, a TypeScript type-check of all TSX files against the real `react-ui` and community sources (0 errors in the app;
the check was verified to catch injected errors), resolution of all PHP class references and TSX imports against the
community sources, a convention linter (naming, table prefix, foreign key names = `fk_` + md5(table_column), relations,
registrations, tags in views, PHP 8.2 compatibility) and a structural check of the Twig templates. It has **not** been
installed, built (`npm run build`) or run. Expect small integration fixes on first run; `Tests/RenderAllRoutesTest.php`
(in the style of the community apps) renders all routes and is the quickest way to find them.

## Known limitations

* `server-report/settings` has no administrator check (as the community Settings pages).
* The default recommended keys of the health status JSON are not pre-filled in new records.
* Recurring activities do not track completion of single occurrences (the Calendar app does not either).
* `php hubleto app test` is listed in the CLI help of the community code, but no implementing command was found; the test
  can be run with PHPUnit using the bootstrap of the project.
