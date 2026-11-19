# Employees Org Chart

Interactive org chart at `/org-chart`, built from each user's Manager field
(`field_managers`) and drawn with [d3-org-chart](https://github.com/bumbeishvili/org-chart).

## How it works

- `views.view.employees_org_chart` lists active users. Its style plugin
  (`OrgChart`, "Org chart (d3)") turns the rows into a tree and passes it to
  `js/org-chart.js` in `drupalSettings`.
- Each person hangs under the first manager in `field_managers` who is also in
  the results. Reporting loops are broken so the tree stays valid.
- Only logged-in users can open the chart (view access: Authenticated user
  role). Anonymous visitors are sent to the login page.
- Only active users are listed (Status filter). Someone whose manager is
  blocked moves to their next active manager, or to the unconnected group.
- People with no manager and no reports are hidden (style option).
- Cards show direct reports and, when different, everyone under the person at
  any level ("6 direct · 48 in org"). Both are computed server-side, because
  d3-org-chart's own counts only cover the currently expanded nodes.
- Search suggestions show name, job title and bureau
  (`field_group_names`).
- When there are several top-level people, they go under a "City of
  Portland" box: the one with the largest team (the Mayor) is the main chart,
  in the middle of the top row. People whose job title is listed in the
  style's "Top-level job titles" option sit beside it, collapsed: the City
  Auditor directly, and councilors in a "City Council" box (configured as
  `Councilor|City Council`). The rest are grouped under "Not connected to the
  main chart";
  these are usually managers whose own Manager field is empty in Entra ID.
- The chart opens centered on `?focus=<uid>`, or else on the current user.
  Link to someone's position with `/org-chart?focus=123`.
- d3 7.9.0, d3-flextree 2.1.2 and d3-org-chart 3.1.1 are bundled in `js/`,
  copied unmodified from their npm packages (checked against the registry's
  integrity hashes). To upgrade, replace the files and update the versions in
  `employees_org_chart.libraries.yml`.

## Caching

Building the chart data for ~8,000 users takes several seconds, so there are
two caches, both lasting the view's cache lifespan (4 hours) and both cleared
after each user sync from Entra ID finishes:

- The chart data (the `nodes` array) is cached in the `cache.data` bin by the
  style plugin, per permission set and language. This works even where the
  render cache is disabled (e.g. `settings.local.php` on Lando). Rebuilding
  it reads each field for all users with one SQL query (field access is
  checked once per field), which takes under a second for ~6,000 people.
- The view output uses this module's "Time-based, ignoring entity changes"
  cache plugin. Core's time cache would add a `user:N` tag per row, which
  makes the cache-tag headers too large (nginx returns 502), and its
  `user_list` tag is cleared by every user save.

portland_openid_connect invalidates the `portland_openid_connect:user_sync`
cache tag on the first cron run after a sync's queue is processed, so the
chart refreshes once the synced data is complete. The 4-hour lifespan covers
changes in between: users saved part-way through a sync (which spreads over
most of a day), manual edits and photo updates on login. Editing the view, or
`drush cr`, also clears both caches.

## Removing

1. `drush pmu employees_org_chart` (also deletes the view, which depends on
   this module)
2. `drush cex`
3. Delete this directory.
