# IndexNow Plugin for Geeklog 2.1.1 to 2.2.2

Current development release: **1.2.1**

## Overview

The **IndexNow** plugin for Geeklog notifies IndexNow-compatible search engines when addressable content is created, updated, or deleted. Version 1.2.x also records submission attempts so administrators can see what was submitted, skipped, rejected or remediated.

Version 1.2.1 is a security and reliability maintenance release. It validates plugin-provided URLs before submission, enforces anonymous visibility for core and plugin-owned content, hardens key and configuration handling, protects log integrity, submits scheduled content in controlled batches and adds an automated remediation mechanism for URLs that should no longer have been exposed.

## Features

- **Generic Geeklog lifecycle listener**: listens to `PLG_itemSaved()` and `PLG_itemDeleted()` for core and plugin-owned content.
- **Permission-aware interoperability**: resolves plugin-owned canonical URLs through `PLG_getItemInfo(..., uid=1)` so automatic submissions fail closed when anonymous accessibility cannot be verified.
- **Anonymous visibility enforcement**: articles, static pages and topics must be readable by anonymous users, including their topic permissions, before automatic, scheduled or manual submission.
- **Same-host URL validation**: only HTTP(S) URLs belonging to the configured Geeklog host are accepted for IndexNow submission.
- **Namespaced item IDs**: supports identifiers such as `maps / marker:123` while remaining compatible with Geeklog 2.1.1 lifecycle events.
- **Automated submission**: submits created and updated public URLs when a valid IndexNow key is configured.
- **Safe deletion submission**: submits a deleted URL only when local history proves it had previously been submitted successfully while public.
- **Submission history**: records automatic, deleted, manual, scheduled and cleanup attempts with item identity, URL, HTTP code, status and timestamp.
- **Security remediation queue**: audits previously successful submissions and queues URLs that are now private, deleted or have a changed canonical URL.
- **Upgrade-time audit**: the 1.2.1 upgrade performs a local-only audit and never depends on an external HTTP request.
- **Automatic cleanup processing**: pending remediation URLs are submitted by the IndexNow scheduled task in batches of up to 100, with up to three attempts.
- **Legacy-safe migration**: pre-1.2.0 submissions that cannot be reconstructed are flagged for review rather than guessed and re-disclosed.
- **Configurable retention**: keep submission history for 30, 90, 180 or 365 days, or indefinitely. Default: 90 days.
- **Administration dashboard**: displays submission history, cleanup statistics and a CSRF-protected manual security re-audit action.
- **Manual batch submission**: submits batches of existing public Geeklog articles.
- **Scheduled batch submission**: submits recently created public articles and static pages in batches of up to 100 URLs through Geeklog's scheduled task API.
- **Debug Mode**: optional detailed logging to Geeklog's native `error.log`, with control characters removed before logging.
- **Installable archive workflow**: GitHub Actions builds the versioned Geeklog plugin archive and SHA-256 checksum in `dist/`.

## Interoperability model

A content plugin remains responsible for its own routing and permissions. IndexNow consumes Geeklog lifecycle events and permission-aware interoperability callbacks.

Example:

```text
PLG_itemSaved('marker:123', 'maps')
        ↓
IndexNow listener
        ↓
PLG_getItemInfo('maps', 'marker:123', 'url', uid=1, ...)
        ↓
anonymous access verified by owner plugin
        ↓
canonical URL
        ↓
same-host validation
        ↓
IndexNow submission + history record
```

Plugin-provided URLs are treated as untrusted input. They must use HTTP or HTTPS and match the host configured in Geeklog's `site_url` before they are submitted.

## Submission history

Version 1.2.0 introduced the Geeklog-prefixed table:

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

Events include `saved`, `deleted`, `manual`, `scheduled` and `cleanup`.

Statuses are currently:

- `success` — IndexNow accepted the request with HTTP 200 or 202;
- `failed` — URL resolution, URL validation or the HTTP request failed;
- `skipped` — the content was not eligible for submission or no usable IndexNow key was configured.

The helper `indexnow_get_last_submission($type, $id)` is intentionally kept independent from Hub so a future Hub integration can read the latest IndexNow state without parsing logs.

## Security cleanup

Version 1.2.1 adds the Geeklog-prefixed table:

```text
indexnow_cleanup
```

During upgrade, IndexNow audits the retained successful-submission history. If an URL is no longer anonymously accessible, no longer exists, or has been replaced by a different canonical URL, the old URL is placed in the cleanup queue. The upgrade itself does not contact IndexNow.

The normal IndexNow scheduled task later processes pending cleanup rows in batches. Failed cleanup requests remain pending until three attempts have been made, after which they are marked failed for administrator review.

For installations upgraded from releases older than 1.2.0, exact historical submissions cannot always be reconstructed because the submission-history table did not yet exist. Version 1.2.1 records this as a legacy review warning and deliberately does not submit private URLs merely because they might have been disclosed in the past.

## Requirements

- **Geeklog**: 2.1.1 through 2.2.2
- **PHP**: 5.6 through PHP 8.1 for the current modernization target
- **cURL** PHP extension
- MySQL/MariaDB database supported by the installed Geeklog release

### Security note for legacy PHP

PHP 5.6 compatibility is retained to support legacy Geeklog 2.1.1 installations. PHP 5.6 is end-of-life and should not be considered a secure production runtime. Administrators should upgrade to a currently supported PHP version whenever their Geeklog installation and hosting environment allow it.

## Installation and upgrade

See `INSTALL` for installation details. Upgrading to 1.2.1 creates or verifies both the submission-history and cleanup tables, then performs the local security audit before Geeklog records the new plugin version. Pending remediation is processed later by the scheduled task.

## Scope

IndexNow intentionally does **not** implement Google Search Console, Google Analytics, Bing Webmaster reporting, or Hub orchestration. Those plugins can share the common content identity (`type + id + canonical URL`) while keeping separate responsibilities.

## Credits

Copyright (C) 2024-2026  
Author: Ben (hostellerie.org AT gmail DOT com)
