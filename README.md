# IndexNow Plugin for Geeklog 2.1.1 to 2.2.2

Current development release: **1.2.0**

## Overview

The **IndexNow** plugin for Geeklog notifies IndexNow-compatible search engines when addressable content is created, updated, or deleted. Version 1.2.0 also records submission attempts so administrators can see what was submitted, skipped, or rejected.

## Features

- **Generic Geeklog lifecycle listener**: listens to `PLG_itemSaved()` and `PLG_itemDeleted()` for core and plugin-owned content.
- **Content plugin interoperability**: resolves canonical URLs through `PLG_getItemInfo()` and the optional `plugin_idtourl_*()` callback instead of querying another plugin's SQL tables.
- **Namespaced item IDs**: supports identifiers such as `maps / marker:123` while remaining compatible with Geeklog 2.1.1 lifecycle events.
- **Automated submission**: submits created and updated URLs when an IndexNow key is configured.
- **Deletion submission**: resolves deterministic deleted-item URLs and submits them to IndexNow.
- **Submission history**: records automatic, deleted, manual and scheduled attempts with item identity, URL, HTTP code, status and timestamp.
- **Configurable retention**: keep history for 30, 90, 180 or 365 days, or indefinitely. Default: 90 days.
- **Recent submissions dashboard**: displays the latest attempts directly in the IndexNow administration page.
- **Manual batch submission**: submits batches of existing Geeklog articles.
- **Scheduled submission**: can submit recently created articles and static pages through Geeklog's scheduled task API.
- **Debug Mode**: optional detailed logging to Geeklog's native `error.log`.
- **Installable archive workflow**: GitHub Actions builds the versioned Geeklog plugin archive in `dist/`.

## Interoperability model

A content plugin remains responsible for its own routing and permissions. IndexNow only consumes Geeklog lifecycle events and public interoperability callbacks.

Example:

```text
PLG_itemSaved('marker:123', 'maps')
        ↓
IndexNow listener
        ↓
PLG_getItemInfo('maps', 'marker:123', 'url', ...)
        ↓
canonical URL
        ↓
IndexNow submission + history record
```

After deletion, `plugin_idtourl_maps()` or an equivalent deterministic callback can resolve the canonical URL without requiring the deleted database row to exist.

## Submission history

Version 1.2.0 creates the Geeklog-prefixed table:

```text
indexnow_submissions
```

Each row may contain:

```text
item_type
item_id
item_subtype
event
url
submitted
http_code
status
message
submitted_at
```

Statuses are currently:

- `success` — IndexNow accepted the request with HTTP 200 or 202;
- `failed` — URL resolution or the HTTP request failed;
- `skipped` — the URL was resolved but no IndexNow key was configured.

The helper `indexnow_get_last_submission($type, $id)` is intentionally kept independent from Hub so a future Hub integration can read the latest IndexNow state without parsing logs.

## Requirements

- **Geeklog**: 2.1.1 through 2.2.2
- **PHP**: 5.6 through PHP 8.1 for the current modernization target
- **cURL** PHP extension
- MySQL/MariaDB database supported by the installed Geeklog release

## Installation and upgrade

See `INSTALL` for installation details. Existing installations upgrading from 1.1.x receive the submission-history table and the 90-day retention setting through the normal Geeklog plugin upgrade process.

## Scope

IndexNow intentionally does **not** implement Google Search Console, Google Analytics, Bing Webmaster reporting, or Hub orchestration. Those plugins can share the common content identity (`type + id + canonical URL`) while keeping separate responsibilities.

## Credits

Copyright (C) 2024-2026  
Author: Ben (hostellerie.org AT gmail DOT com)
