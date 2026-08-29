<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
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
    'manual_submission' => 'Manual article submission',
    'submission_not_ready' => 'Complete the key and verification-file configuration before submitting URLs.',
    'debug_status' => 'Debug mode:',
    'debug_enabled' => 'enabled — submissions are written to error.log.',
    'debug_disabled' => 'disabled — only errors are written to error.log.',
    'error_log' => 'Log file:',
    'history_retention' => 'History retention',
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
    'documentation' => 'Documentation & Help',
    'documentation_content' => '<p><strong>Step 1: Generate an IndexNow Key</strong><br>Visit the IndexNow key creation page at <a href="https://www.bing.com/webmasters/indexnow" target="_blank" rel="noopener noreferrer">https://www.bing.com/webmasters/indexnow</a>.</p><p><strong>Step 2: Create and Host the Key File</strong><br>Create a text file containing only your key and upload it to the root directory of your website.</p><p><strong>Step 3: Configure the Plugin</strong><br>Open Geeklog Configuration, select IndexNow, and enter your key. Version 1.2.0 records automatic, deleted, scheduled and manual submission attempts in its own history table.</p>'
);

?>
