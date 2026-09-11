<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.1                                                     |
// +---------------------------------------------------------------------------+
// | language/english.php                                                      |
// +---------------------------------------------------------------------------+

$LANG_configsections['indexnow'] = array(
    'label' => 'IndexNow',
    'title' => 'IndexNow Configuration'
);

$LANG_confignames['indexnow'] = array(
    'indexnow_key' => 'IndexNow key',
    'debug_mode' => 'Debug Mode',
    'history_retention_days' => 'Submission history retention'
);

$LANG_configsubgroups['indexnow'] = array('sg_0' => 'Main Settings');
$LANG_fs['indexnow'] = array('fs_01' => 'IndexNow plugin');
$LANG_tab['indexnow'] = array('tab_main' => 'General Settings');

$LANG_configselects['indexnow'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array(
        'Unlimited' => 0,
        '30 days' => 30,
        '90 days' => 90,
        '180 days' => 180,
        '365 days' => 365
    )
);

$LANG_indexnow = array(
    'plugin_name' => 'IndexNow',
    'submit_success' => 'Successfully submitted %d articles to IndexNow.',
    'submit_error' => 'An error occurred while submitting articles.',
    'submit_to_bing' => 'Submit Batch',
    'submit_first_batch' => 'Click the button below to submit the first batch of %d articles.',
    'submit_next_batch_message' => 'Click the button below to submit the next batch of %d articles. %d articles remaining.',
    'no_articles_to_submit' => 'No more articles to submit.',
    'articles_submitted' => 'Articles submitted: %d to %d.',
    'loading_message' => 'Submitting articles, please wait...',
    'total_articles' => 'Total articles available for submission: %d.',
    'no_articles_remaining' => 'All articles have been submitted. No articles remaining.',
    'plugin_denied_msg' => 'You do not have the required permissions to access the IndexNow administration.',
    'access_denied' => 'Access Denied',
    'key_missing' => 'IndexNow key is not configured.',
    'key_missing_help' => 'Add a key in the IndexNow configuration before submitting URLs.',
    'key_invalid' => 'The configured IndexNow key is invalid.',
    'key_invalid_help' => 'A key must contain 8 to 128 letters, digits, or hyphens.',
    'key_present' => 'The IndexNow key is configured.',
    'key_file_missing' => 'The verification file was not found in the site root.',
    'key_file_unreadable' => 'The verification file exists but cannot be read by PHP.',
    'key_file_mismatch' => 'The verification file does not match the configured key.',
    'key_file_mismatch_help' => 'The file must contain only the configured key.',
    'key_ready' => 'The IndexNow key and verification file are ready.',
    'configured_key' => 'Configured key',
    'expected_file' => 'Expected file on the server',
    'public_url' => 'Public verification URL',
    'open_configuration' => 'Open IndexNow configuration',
    'configuration_status' => 'Configuration status',
    'configuration_subtitle' => 'Key, verification file and plugin runtime settings.',
    'manual_submission' => 'Manual article submission',
    'manual_submission_subtitle' => 'Submit public articles in controlled batches of %d URLs.',
    'submission_not_ready' => 'Complete the key and verification-file configuration before submitting URLs.',
    'debug_status' => 'Debug mode:',
    'debug_enabled' => 'enabled — submissions are written to error.log.',
    'debug_disabled' => 'disabled — only errors are written to error.log.',
    'error_log' => 'Log file:',
    'history_retention' => 'History retention',
    'history_retention_days' => '%d days',
    'history_retention_unlimited' => 'Unlimited',
    'recent_submissions' => 'Recent submissions',
    'submission_history' => 'Submission history',
    'history_empty' => 'No IndexNow submission has been recorded yet.',
    'history_date' => 'Date',
    'history_item' => 'Item',
    'history_event' => 'Event',
    'history_status' => 'Status',
    'history_http' => 'HTTP',
    'history_url' => 'URL',
    'history_message' => 'Details',
    'filter_status' => 'Status',
    'filter_event' => 'Event',
    'filter_type' => 'Type',
    'filter_all_statuses' => 'All statuses',
    'filter_all_events' => 'All events',
    'filter_all_types' => 'All types',
    'status_success' => 'Success',
    'status_failed' => 'Failed',
    'status_skipped' => 'Skipped',
    'event_saved' => 'Saved',
    'event_deleted' => 'Deleted',
    'event_manual' => 'Manual',
    'event_scheduled' => 'Scheduled',
    'event_cleanup' => 'Cleanup',
    'summary_aria' => 'IndexNow administration summary',
    'summary_ready' => 'Ready',
    'summary_attention' => 'Attention',
    'summary_compatible' => 'Compatible',
    'summary_coexistence' => 'Geeklog coexistence',
    'summary_security_cleanup' => 'Security cleanup',
    'summary_articles' => 'Articles',
    'summary_pending_remediation' => 'Pending remediation',
    'summary_articles_available' => 'Available for manual submission',
    'summary_coexistence_conflict' => 'XMLSitemap IndexNow is also enabled',
    'summary_coexistence_ok' => 'No duplicate IndexNow provider detected',
    'cleanup_title' => 'Security cleanup',
    'cleanup_intro' => 'Audits URLs previously accepted by IndexNow and queues a recrawl when an URL is no longer anonymously accessible or its canonical URL changed.',
    'cleanup_subtitle' => 'Review previously submitted URLs that may no longer be public.',
    'cleanup_pending' => 'Pending remediation',
    'cleanup_completed' => 'Completed',
    'cleanup_failed' => 'Failed after retries',
    'cleanup_review' => 'Legacy review warnings',
    'cleanup_last_audit' => 'Last audit',
    'cleanup_never_audited' => 'No security audit has been recorded yet.',
    'cleanup_run' => 'Run security audit again',
    'cleanup_run_now' => 'Run cleanup now',
    'cleanup_run_success' => 'Security audit completed: %d historical URLs checked, %d remediation URLs queued.',
    'cleanup_run_result' => '%d remediation URL(s) processed: %d completed, %d failed, %d still pending.',
    'cleanup_legacy_warning' => 'This installation contains pre-1.2.0 history that cannot be reconstructed safely. Unknown private URLs are never submitted during remediation.',
    'cleanup_schedule_help' => 'Pending remediation URLs are processed automatically by the IndexNow scheduled task, in batches of up to 100.',
    'cleanup_review_required' => 'Administrator review required',
    'cleanup_failures_title' => 'Cleanup failures require attention',
    'cleanup_failures_help' => 'Failed remediation entries remain visible below and can be audited again.',
    'cleanup_pending_title' => 'Cleanup is pending',
    'cleanup_pending_help' => 'Pending remediation will be processed automatically by the next IndexNow scheduled task, or you can run one cleanup batch now.',
    'cleanup_none_title' => 'No pending remediation',
    'cleanup_none_help' => 'The cleanup queue currently requires no action.',
    'cleanup_key_required' => 'Complete the IndexNow key verification before running cleanup manually.',
    'cleanup_batch_help' => 'Runs one batch of up to 100 URLs. Remaining URLs stay queued for the next batch or scheduled task.',
    'coexistence_title' => 'Geeklog / XMLSitemap integration',
    'coexistence_subtitle' => 'Keep XMLSitemap for sitemap generation while assigning IndexNow notifications to a single provider.',
    'coexistence_no_overlap_title' => 'No XMLSitemap overlap detected',
    'coexistence_no_overlap_help' => 'XMLSitemap is not installed, so this plugin is the only detected IndexNow provider.',
    'coexistence_disabled_title' => 'XMLSitemap is disabled',
    'coexistence_disabled_help' => 'No duplicate IndexNow submission can occur while XMLSitemap is disabled.',
    'coexistence_compatible_title' => 'Compatible XMLSitemap configuration',
    'coexistence_compatible_help' => 'This XMLSitemap installation does not expose its own IndexNow setting. The dedicated IndexNow plugin can operate without overlap.',
    'coexistence_conflict_title' => 'Duplicate IndexNow provider detected',
    'coexistence_conflict_help' => 'XMLSitemap and the dedicated IndexNow plugin are both configured to submit changed URLs. Disable only XMLSitemap\'s IndexNow option to avoid duplicate requests and inconsistent submission history.',
    'coexistence_recommended_title' => 'Recommended coexistence is active',
    'coexistence_recommended_help' => 'XMLSitemap remains enabled for sitemap generation and its IndexNow option is disabled. The dedicated plugin is the single IndexNow notification provider.',
    'coexistence_installed' => 'Installed',
    'coexistence_not_installed' => 'Not installed',
    'coexistence_plugin_state' => 'Plugin state',
    'coexistence_enabled' => 'Enabled',
    'coexistence_disabled' => 'Disabled',
    'coexistence_native_support' => 'Native IndexNow support',
    'coexistence_available' => 'Available',
    'coexistence_not_detected' => 'Not detected',
    'coexistence_indexnow_label' => 'XMLSitemap IndexNow',
    'coexistence_split_title' => 'Recommended responsibility split',
    'coexistence_split_help' => 'XMLSitemap should continue generating XML and News sitemaps. This dedicated plugin should handle IndexNow notifications, history, permission checks and security remediation. The IndexNow plugin never changes XMLSitemap settings automatically.',
    'coexistence_open_configuration' => 'Open XMLSitemap configuration',
    'coexistence_disable_instruction' => 'Set <strong>Enable IndexNow</strong> to False. Do not disable XMLSitemap itself.',
    'documentation' => 'Documentation & Help',
    'documentation_content' => '<p><strong>Step 1: Generate an IndexNow Key</strong><br>Visit the IndexNow key creation page at <a href="https://www.bing.com/webmasters/indexnow" target="_blank" rel="noopener noreferrer">https://www.bing.com/webmasters/indexnow</a>.</p><p><strong>Step 2: Create and Host the Key File</strong><br>Create a text file containing only your key and upload it to the root directory of your website.</p><p><strong>Step 3: Configure the Plugin</strong><br>Open Geeklog Configuration, select IndexNow, and enter your key. Version 1.2.1 records submissions, enforces anonymous visibility before submission, and maintains a local security-remediation queue for previously submitted URLs that should be revisited.</p>'
);

?>
