# Changelog

## 1.2.1

- Validate all submitted URLs before contacting IndexNow and reject non-HTTP(S), foreign-host, credential-bearing, control-character and unexpected-port URLs.
- Validate IndexNow keys at runtime before any submission.
- Restrict submission-history retention configuration to 0, 30, 90, 180 or 365 days.
- Sanitize CR, LF and NUL characters before writing plugin messages to Geeklog's error log.
- Explicitly enable TLS peer and host verification for IndexNow cURL requests and restrict protocols to HTTPS where supported.
- Submit scheduled URLs in batches of up to 100 instead of one HTTP request per item.
- Preserve per-item type, ID and event metadata when manual and scheduled batch submissions are recorded in submission history.
- Prevent automatic, manual and scheduled disclosure of private Geeklog articles, static pages and topics by checking anonymous read permissions and topic permissions before submission.
- Resolve plugin-owned URLs through permission-aware `PLG_getItemInfo(..., uid=1)` calls and fail closed when anonymous accessibility cannot be verified.
- When previously public content becomes private, request a recrawl using only an URL that was already successfully submitted while public.
- Submit deleted URLs only when submission history proves that the URL had previously been successfully submitted while public.
- Add an `indexnow_cleanup` remediation queue for URLs that submission history proves were previously accepted by IndexNow but are now private, deleted or have a changed canonical URL.
- Run a local-only security audit automatically during the 1.2.1 upgrade; the upgrade itself performs no external HTTP request.
- Process pending remediation URLs automatically from the IndexNow scheduled task in batches of up to 100, retrying failures up to three times.
- Call cleanup processing directly from `plugin_runScheduledTask_indexnow()` instead of relying on call-stack detection.
- Record remediation requests in submission history with the `cleanup` event.
- Add a Security cleanup administration panel with queue statistics, last-audit information, a CSRF-protected manual re-audit action and a manual "Run cleanup now" batch action.
- Remove both `indexnow_submissions` and `indexnow_cleanup` tables during plugin uninstall.
- Mark upgrades from pre-1.2.0 releases as `legacy_unverifiable` when exact historical submissions cannot be reconstructed; unknown private URLs are never sent merely to guess what may have been submitted.
- Detect Geeklog XMLSitemap's optional native IndexNow support in the administration dashboard and warn when both providers are enabled, recommending that only XMLSitemap's IndexNow option be disabled while sitemap generation remains active.
- Never modify XMLSitemap configuration automatically; coexistence diagnostics are advisory and preserve clear plugin responsibility boundaries.
- Make the administration dashboard, submission-history status/event labels, cleanup messages and XMLSitemap coexistence guidance translatable through `language/english.php` instead of hardcoded display strings.
- Keep only internal debug/log wording and protocol/product names in code; administrator-facing text is language-file driven.
- Harden the release workflow and generate a SHA-256 checksum alongside the installable archive.

## 1.2.0

- Add generic Geeklog content lifecycle support through `PLG_itemSaved()` and `PLG_itemDeleted()`.
- Resolve plugin-owned canonical URLs through `PLG_getItemInfo()` with `plugin_idtourl_*()` fallback.
- Support namespaced plugin identifiers such as `maps / marker:123` while keeping Geeklog 2.1.1 compatibility.
- Submit generic plugin deletions after deterministic URL resolution.
- Add compatibility for one-argument and subtype-aware two-argument `plugin_idtourl_*()` callbacks.
- Add the `indexnow_submissions` history table.
- Record automatic saves, deletions, manual batches and scheduled submissions with URL, event, HTTP code, status, message and timestamp.
- Record `skipped` attempts when an URL is resolved but no usable IndexNow key is configured.
- Add a Recent submissions table to the administration dashboard.
- Add configurable submission-history retention: 30, 90, 180, 365 days or Unlimited; default 90 days.
- Add `indexnow_get_last_submission($type, $id)` for future consumers such as Hub without coupling IndexNow directly to Hub.
- Add an installable archive workflow and `dist/` package generation.
- Preserve Geeklog 2.1.1 through 2.2.2 and PHP 5.6 through 8.1 modernization compatibility.

## 1.1.6

- Stack the configuration and manual-submission cards vertically.

## 1.1.5

- Remove the temporary debug-log test button.
- Reorganize the administration page into configuration, submission, and help sections.
- Improve responsive layout, visual status hierarchy, and action clarity.
- Disable manual submission until the key and verification file are ready.

## 1.1.4

- Keep debug logging exclusively on Geeklog's native `COM_errorLog()` API.
- Document that a locally disabled `COM_errorLog()` must be fixed in Geeklog itself.

## 1.1.3

- Use Geeklog's native `COM_errorLog()` call consistently for debug entries.
- Report whether the administration-page logging function was reached.

## 1.1.2

- Open the IndexNow configuration with the POST request required by Geeklog 2.1.1-2.2.2.
- Make debug logging explicitly target `logs/error.log`.
- Display debug status, log path, and log writability on the administration page.
- Log receipt of article and static-page save callbacks when debug mode is enabled.
- Use Geeklog's supported validation array for the debug setting.

## 1.1.1

- Show the configured key and verification-file status on the administration page.
- Register the permission required to open the IndexNow configuration tab.
- Persist the new plugin version after a successful Geeklog upgrade.
- Add support for Geeklog 2.1.1 through 2.2.2 and PHP 5.6.
- Restore automatic submissions after item saves by loading the plugin configuration in the callback.
- Make the upgrade entry point available to Geeklog and support the Geeklog 2.1.1 configuration API.
- Generate valid static-page URLs in the scheduled task.
- URL-encode IndexNow GET submissions and report cURL transport errors.
- Add connection and request timeouts to IndexNow calls.
- Protect manual batch submissions with Geeklog's CSRF token.

## 1.1.0

- Add batch article submission, scheduled submissions, and debug logging.
