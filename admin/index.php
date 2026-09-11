<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.1                                                     |
// +---------------------------------------------------------------------------+
// | admin/index.php                                                           |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('indexnow.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the IndexNow plugin administration screen.");
    $display = COM_siteHeader('menu', $LANG_indexnow['access_denied'])
             . COM_startBlock($LANG_indexnow['access_denied'])
             . COM_showMessageText($LANG_indexnow['plugin_denied_msg'], $LANG_indexnow['access_denied'])
             . COM_endBlock()
             . COM_siteFooter();
    echo $display;
    exit;
}

require_once $_CONF['path'] . 'plugins/indexnow/functions.inc';
require_once $_CONF['path_system'] . 'lib-admin.php';

/**
 * Detect coexistence with Geeklog's bundled XMLSitemap IndexNow support.
 * Detection is based on the actual installed plugin and configuration, not on
 * the Geeklog version number, so separately upgraded XMLSitemap installations
 * are handled correctly.
 */
function INDEXNOW_detectXmlSitemapIntegration()
{
    global $_CONF, $_TABLES;

    $state = array(
        'installed' => false,
        'enabled' => false,
        'version' => '',
        'indexnow_supported' => false,
        'indexnow_enabled' => false,
        'conflict' => false
    );

    $result = DB_query(
        "SELECT pi_version,pi_enabled FROM {$_TABLES['plugins']} " .
        "WHERE pi_name='xmlsitemap' LIMIT 1"
    );
    if (!$result || DB_numRows($result) === 0) {
        return $state;
    }

    $row = DB_fetchArray($result);
    $state['installed'] = true;
    $state['enabled'] = isset($row['pi_enabled']) && (int) $row['pi_enabled'] === 1;
    $state['version'] = isset($row['pi_version']) ? (string) $row['pi_version'] : '';

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    if (!class_exists('config')) {
        return $state;
    }

    $config = config::get_instance();
    if (!$config->group_exists('xmlsitemap')) {
        return $state;
    }

    $xmlConfig = $config->get_config('xmlsitemap');
    if (!is_array($xmlConfig) || !array_key_exists('indexnow', $xmlConfig)) {
        return $state;
    }

    $state['indexnow_supported'] = true;
    $state['indexnow_enabled'] = !empty($xmlConfig['indexnow']);
    $state['conflict'] = $state['enabled'] && $state['indexnow_enabled'];

    return $state;
}

function INDEXNOW_getSubmissionListField($fieldName, $fieldValue, $A, $iconArray)
{
    global $LANG_indexnow;

    switch ($fieldName) {
        case 'submitted_at':
            $timestamp = strtotime($fieldValue);
            if ($timestamp !== false && $timestamp > 0) {
                $date = COM_getUserDateTimeFormat($timestamp);
                return htmlspecialchars($date[0], ENT_QUOTES, 'UTF-8');
            }
            return '&mdash;';

        case 'item_type':
            $item = trim((string) $A['item_type']);
            if (isset($A['item_id']) && $A['item_id'] !== '') {
                $item .= ' / ' . $A['item_id'];
            }
            if (isset($A['item_subtype']) && $A['item_subtype'] !== '') {
                $item .= ' (' . $A['item_subtype'] . ')';
            }
            return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');

        case 'event':
            return htmlspecialchars(ucfirst((string) $fieldValue), ENT_QUOTES, 'UTF-8');

        case 'status':
            $status = strtolower(trim((string) $fieldValue));
            $class = 'ixn-badge ixn-badge-skipped';
            if ($status === 'success') {
                $class = 'ixn-badge ixn-badge-success';
            } elseif ($status === 'failed') {
                $class = 'ixn-badge ixn-badge-failed';
            }
            return '<span class="' . $class . '">' .
                htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';

        case 'http_code':
            return ((int) $fieldValue > 0) ? (string) (int) $fieldValue : '&mdash;';

        case 'url':
            $url = trim((string) $fieldValue);
            if ($url === '') {
                return '&mdash;';
            }
            $label = COM_truncate($url, 70, '...');
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') .
                '" target="_blank" rel="noopener noreferrer" title="' .
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' .
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';

        case 'message':
            $message = trim((string) $fieldValue);
            if ($message === '') {
                return '&mdash;';
            }
            return '<span title="' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '">' .
                htmlspecialchars(COM_truncate($message, 90, '...'), ENT_QUOTES, 'UTF-8') . '</span>';

        default:
            return htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8');
    }
}

function INDEXNOW_submissionFilters(&$defaultFilter, &$pageNavUrl)
{
    global $_TABLES, $LANG_indexnow;

    $status = isset($_REQUEST['ixn_status']) ? COM_applyFilter($_REQUEST['ixn_status']) : 'all';
    $event = isset($_REQUEST['ixn_event']) ? COM_applyFilter($_REQUEST['ixn_event']) : 'all';
    $type = isset($_REQUEST['ixn_type']) ? COM_applyFilter($_REQUEST['ixn_type']) : 'all';

    $allowedStatuses = array('all', 'success', 'failed', 'skipped');
    $allowedEvents = array('all', 'saved', 'deleted', 'manual', 'scheduled', 'cleanup');
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }
    if (!in_array($event, $allowedEvents, true)) {
        $event = 'all';
    }

    if ($status !== 'all') {
        $defaultFilter .= " AND status='" . DB_escapeString($status) . "'";
        $pageNavUrl .= '&amp;ixn_status=' . rawurlencode($status);
    }
    if ($event !== 'all') {
        $defaultFilter .= " AND event='" . DB_escapeString($event) . "'";
        $pageNavUrl .= '&amp;ixn_event=' . rawurlencode($event);
    }
    if ($type !== 'all' && $type !== '') {
        $defaultFilter .= " AND item_type='" . DB_escapeString($type) . "'";
        $pageNavUrl .= '&amp;ixn_type=' . rawurlencode($type);
    }

    $typeOptions = '<option value="all">' . $LANG_indexnow['filter_all_types'] . '</option>';
    $result = DB_query("SELECT DISTINCT item_type FROM {$_TABLES['indexnow_submissions']} WHERE item_type <> '' ORDER BY item_type");
    while ($row = DB_fetchArray($result)) {
        $value = (string) $row['item_type'];
        $selected = ($type === $value) ? ' selected="selected"' : '';
        $typeOptions .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' .
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    $statusOptions = array(
        'all' => $LANG_indexnow['filter_all_statuses'],
        'success' => 'Success',
        'failed' => 'Failed',
        'skipped' => 'Skipped'
    );
    $eventOptions = array(
        'all' => $LANG_indexnow['filter_all_events'],
        'saved' => 'Saved',
        'deleted' => 'Deleted',
        'manual' => 'Manual',
        'scheduled' => 'Scheduled',
        'cleanup' => 'Cleanup'
    );

    $filter = '<div class="ixn-native-filters">';
    $filter .= '<label>' . $LANG_indexnow['filter_status'] . ' <select name="ixn_status" onchange="this.form.submit()">';
    foreach ($statusOptions as $value => $label) {
        $filter .= '<option value="' . $value . '"' . (($status === $value) ? ' selected="selected"' : '') . '>' . $label . '</option>';
    }
    $filter .= '</select></label>';
    $filter .= '<label>' . $LANG_indexnow['filter_event'] . ' <select name="ixn_event" onchange="this.form.submit()">';
    foreach ($eventOptions as $value => $label) {
        $filter .= '<option value="' . $value . '"' . (($event === $value) ? ' selected="selected"' : '') . '>' . $label . '</option>';
    }
    $filter .= '</select></label>';
    $filter .= '<label>' . $LANG_indexnow['filter_type'] . ' <select name="ixn_type" onchange="this.form.submit()">' . $typeOptions . '</select></label>';
    $filter .= '</div>';

    return $filter;
}

function INDEXNOW_submissionHistoryList()
{
    global $_CONF, $_TABLES, $LANG_indexnow;

    $headerArray = array(
        array('text' => $LANG_indexnow['history_date'], 'field' => 'submitted_at', 'sort' => true),
        array('text' => $LANG_indexnow['history_item'], 'field' => 'item_type', 'sort' => true),
        array('text' => $LANG_indexnow['history_event'], 'field' => 'event', 'sort' => true),
        array('text' => $LANG_indexnow['history_status'], 'field' => 'status', 'sort' => true),
        array('text' => $LANG_indexnow['history_http'], 'field' => 'http_code', 'sort' => true),
        array('text' => $LANG_indexnow['history_url'], 'field' => 'url', 'sort' => true),
        array('text' => $LANG_indexnow['history_message'], 'field' => 'message', 'sort' => false)
    );

    $textArray = array(
        'has_extras' => true,
        'title' => $LANG_indexnow['submission_history'],
        'form_url' => $_CONF['site_admin_url'] . '/plugins/indexnow/index.php',
        'no_data' => $LANG_indexnow['history_empty']
    );

    $defaultFilter = '';
    $pageNavUrl = '';
    $filter = INDEXNOW_submissionFilters($defaultFilter, $pageNavUrl);

    $queryArray = array(
        'sql' => "SELECT submission_id,item_type,item_id,item_subtype,event,url,submitted,http_code,status,message,submitted_at " .
            "FROM {$_TABLES['indexnow_submissions']} WHERE 1=1",
        'query_fields' => array('item_type', 'item_id', 'item_subtype', 'event', 'url', 'status', 'message', 'http_code'),
        'default_filter' => $defaultFilter
    );

    $defaultSortArray = array('field' => 'submitted_at', 'direction' => 'desc');

    return ADMIN_list(
        'indexnowsubmissions',
        'INDEXNOW_getSubmissionListField',
        $headerArray,
        $textArray,
        $queryArray,
        $defaultSortArray,
        $filter,
        '',
        array(),
        array(),
        true,
        $pageNavUrl
    );
}

indexnow_purge_submission_history();

if (function_exists('indexnow_cleanup_table_exists') && !indexnow_cleanup_table_exists() &&
    function_exists('indexnow_update_1_2_1')) {
    indexnow_update_1_2_1(false);
}

$key_status = indexnow_get_key_status();
$xmlsitemap_state = INDEXNOW_detectXmlSitemapIntegration();
$debug_enabled = isset($_INDEXNOW_CONF['debug_mode']) && (int) $_INDEXNOW_CONF['debug_mode'] === 1;
$retention_days = isset($_INDEXNOW_CONF['history_retention_days']) ? (int) $_INDEXNOW_CONF['history_retention_days'] : 90;
$error_log_path = rtrim($_CONF['path_log'], '/\\') . DIRECTORY_SEPARATOR . 'error.log';
$submission_ready = $key_status['key_valid'] && $key_status['file_matches'];

$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
$batch_size = 100;
$total_articles = get_total_articles_to_submit();
$articles_remaining = $total_articles - $offset;
$feedback = '';
$submitted_range = '';
$next_action_message = '';
$next_offset = $offset;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_security_audit']) && SEC_checkToken()) {
    try {
        $audit = indexnow_audit_submitted_urls(false);
        $feedback = COM_showMessageText(
            sprintf($LANG_indexnow['cleanup_run_success'], $audit['audited'], $audit['queued']),
            $LANG_indexnow['cleanup_title']
        );
    } catch (Exception $e) {
        $feedback = COM_showMessageText(
            $LANG_indexnow['submit_error'] . ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            $LANG_indexnow['cleanup_title']
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_articles']) && SEC_checkToken()) {
    try {
        $submitted_count = submit_articles_by_date_desc_to_indexnow($batch_size, $offset);
        if ($submitted_count > 0) {
            $start_range = $offset + 1;
            $end_range = $offset + $submitted_count;
            $feedback = COM_showMessageText(sprintf($LANG_indexnow['submit_success'], $submitted_count), $LANG_indexnow['plugin_name']);
            $submitted_range = sprintf($LANG_indexnow['articles_submitted'], $start_range, $end_range);
            $next_offset = $offset + $submitted_count;
            $articles_remaining = $total_articles - $next_offset;
        } else {
            $feedback = COM_showMessageText($LANG_indexnow['no_articles_to_submit'], $LANG_indexnow['plugin_name']);
        }
    } catch (Exception $e) {
        $feedback = COM_showMessageText($LANG_indexnow['submit_error'] . ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), $LANG_indexnow['plugin_name']);
    }
}

if ($offset === 0 && empty($feedback)) {
    $next_action_message = sprintf($LANG_indexnow['submit_first_batch'], $batch_size);
} elseif ($articles_remaining > 0) {
    $next_action_message = sprintf($LANG_indexnow['submit_next_batch_message'], $batch_size, $articles_remaining);
} else {
    $next_action_message = $LANG_indexnow['no_articles_remaining'];
}

$cleanup_stats = function_exists('indexnow_cleanup_get_stats')
    ? indexnow_cleanup_get_stats()
    : array('pending' => 0, 'completed' => 0, 'failed' => 0, 'review' => 0, 'last_audit' => '', 'last_audit_message' => '');

$status_class = 'ixn-error';
$status_title = $LANG_indexnow['key_missing'];
$status_help = $LANG_indexnow['key_missing_help'];
if ($key_status['key_present'] && !$key_status['key_valid']) {
    $status_title = $LANG_indexnow['key_invalid'];
    $status_help = $LANG_indexnow['key_invalid_help'];
} elseif ($key_status['key_valid'] && !$key_status['file_exists']) {
    $status_class = 'ixn-warning';
    $status_title = $LANG_indexnow['key_present'];
    $status_help = $LANG_indexnow['key_file_missing'];
} elseif ($key_status['file_exists'] && !$key_status['file_readable']) {
    $status_class = 'ixn-warning';
    $status_title = $LANG_indexnow['key_present'];
    $status_help = $LANG_indexnow['key_file_unreadable'];
} elseif ($key_status['file_readable'] && !$key_status['file_matches']) {
    $status_title = $LANG_indexnow['key_file_mismatch'];
    $status_help = $LANG_indexnow['key_file_mismatch_help'];
} elseif ($submission_ready) {
    $status_class = 'ixn-ok';
    $status_title = $LANG_indexnow['key_ready'];
    $status_help = '';
}

$cleanup_attention = ((int) $cleanup_stats['pending'] > 0 || (int) $cleanup_stats['failed'] > 0 || (int) $cleanup_stats['review'] > 0);
$cleanup_summary_class = $cleanup_attention ? 'ixn-summary-warning' : 'ixn-summary-ok';
$cleanup_summary_value = (int) $cleanup_stats['pending'];
$config_summary_class = $submission_ready ? 'ixn-summary-ok' : 'ixn-summary-error';
$config_summary_value = $submission_ready ? 'Ready' : 'Attention';
$coexistence_summary_class = $xmlsitemap_state['conflict'] ? 'ixn-summary-warning' : 'ixn-summary-ok';
$coexistence_summary_value = $xmlsitemap_state['conflict'] ? 'Attention' : 'Compatible';
$coexistence_summary_note = $xmlsitemap_state['conflict']
    ? 'XMLSitemap IndexNow is also enabled'
    : 'No duplicate IndexNow provider detected';

$display = '<style>
.ixn-admin{max-width:1500px;margin:0 auto}
.ixn-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 18px}
.ixn-summary{position:relative;overflow:hidden;padding:16px 18px;border:1px solid #dfe3e8;border-radius:10px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ixn-summary-label{display:block;margin-bottom:5px;color:#68737d;font-size:.9em;font-weight:600}.ixn-summary-value{display:block;font-size:1.6em;line-height:1.15;font-weight:700}.ixn-summary-note{display:block;margin-top:5px;color:#68737d;font-size:.86em}
.ixn-summary:before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:#b9c2ca}.ixn-summary-ok:before{background:#2e7d32}.ixn-summary-warning:before{background:#d08a00}.ixn-summary-error:before{background:#c62828}
.ixn-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px;align-items:start;margin-bottom:18px}
.ixn-card{box-sizing:border-box;width:100%;padding:20px;border:1px solid #dfe3e8;border-radius:10px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ixn-card-full{margin-bottom:18px}.ixn-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:14px}.ixn-card h2{margin:0;font-size:1.16em;line-height:1.3}.ixn-card-subtitle{margin:4px 0 0;color:#68737d;font-size:.92em}
.ixn-status{padding:11px 13px;border:1px solid;border-radius:7px;margin-bottom:15px}.ixn-status strong{display:block}.ixn-status div{margin-top:4px}.ixn-ok{color:#1b5e20;background:#edf7ee;border-color:#b7d9bb}.ixn-warning{color:#755000;background:#fff8e6;border-color:#edd18a}.ixn-error{color:#a71919;background:#fff0f0;border-color:#e7abab}
.ixn-details{display:grid;grid-template-columns:minmax(135px,auto) minmax(0,1fr);gap:8px 14px;margin:0}.ixn-details dt{font-weight:600;color:#4d5963}.ixn-details dd{margin:0;min-width:0;overflow-wrap:anywhere}.ixn-details code{white-space:normal;overflow-wrap:anywhere}
.ixn-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:18px;padding-top:15px;border-top:1px solid #edf0f2}.ixn-button{display:inline-block;padding:9px 15px;border:1px solid #1269a9;border-radius:5px;background:#1678c2;color:#fff;cursor:pointer;font-weight:600;text-decoration:none}.ixn-button:hover{filter:brightness(.96)}.ixn-button-secondary{background:#fff;color:#1678c2}.ixn-button[disabled]{border-color:#b7bec4;background:#b7bec4;color:#f7f7f7;cursor:not-allowed;filter:none}
.ixn-config-form{display:inline}.ixn-config-button{padding:9px 15px;border:1px solid #1269a9;border-radius:5px;background:#fff;color:#1678c2;cursor:pointer;font-weight:600}.ixn-muted{color:#68737d}.ixn-success{color:#1b5e20;font-weight:600}
.ixn-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:14px 0}.ixn-metric{padding:12px;border:1px solid #e2e6e9;border-radius:7px;background:#fafbfc;text-align:center}.ixn-metric strong{display:block;font-size:1.35em;line-height:1.2}.ixn-metric span{display:block;margin-top:4px;color:#68737d;font-size:.84em}
.ixn-manual-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.ixn-manual-copy p{margin:5px 0}.ixn-manual-action{text-align:right}.ixn-manual-action form{margin:0}
.ixn-coexistence{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(260px,.6fr);gap:24px;align-items:start}.ixn-coexistence-note{padding:14px 16px;border-radius:8px;background:#f7f9fa;color:#4d5963}.ixn-coexistence-note strong{display:block;margin-bottom:5px;color:#24313a}
.ixn-history-wrap{margin-top:18px}.ixn-help{margin-top:18px;padding:15px 18px;border:1px solid #dfe3e8;border-radius:8px;background:#fafbfc}.ixn-help summary{cursor:pointer;font-weight:600}
.ixn-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:.9em;white-space:nowrap}.ixn-badge-success{background:#e8f5e9;color:#1b5e20}.ixn-badge-failed{background:#ffebee;color:#b71c1c}.ixn-badge-skipped{background:#fff8e1;color:#7a4f00}
.ixn-native-filters{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center}.ixn-native-filters label{white-space:nowrap}.ixn-native-filters select{margin-left:4px;max-width:180px}
@media(max-width:1100px){.ixn-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:900px){.ixn-summary-grid{grid-template-columns:1fr}.ixn-layout,.ixn-coexistence{grid-template-columns:1fr}.ixn-manual-row{grid-template-columns:1fr}.ixn-manual-action{text-align:left}.ixn-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.ixn-card{padding:16px}.ixn-details{grid-template-columns:1fr;gap:3px}.ixn-details dd{margin-bottom:9px}.ixn-metrics{grid-template-columns:1fr 1fr}.ixn-native-filters{display:grid;grid-template-columns:1fr}.ixn-native-filters label{white-space:normal}.ixn-native-filters select{width:100%;max-width:none;margin:4px 0 0}.ixn-actions{align-items:stretch}.ixn-button,.ixn-config-button{width:100%;box-sizing:border-box;text-align:center}}
</style>';

$display .= '<div class="ixn-admin">';
if ($feedback !== '') {
    $display .= $feedback;
}

$display .= '<div class="ixn-summary-grid" aria-label="IndexNow administration summary">';
$display .= '<div class="ixn-summary ' . $config_summary_class . '"><span class="ixn-summary-label">IndexNow</span><span class="ixn-summary-value">' . $config_summary_value . '</span><span class="ixn-summary-note">' . htmlspecialchars($status_title, ENT_QUOTES, 'UTF-8') . '</span></div>';
$display .= '<div class="ixn-summary ' . $coexistence_summary_class . '"><span class="ixn-summary-label">Geeklog coexistence</span><span class="ixn-summary-value">' . $coexistence_summary_value . '</span><span class="ixn-summary-note">' . htmlspecialchars($coexistence_summary_note, ENT_QUOTES, 'UTF-8') . '</span></div>';
$display .= '<div class="ixn-summary ' . $cleanup_summary_class . '"><span class="ixn-summary-label">Security cleanup</span><span class="ixn-summary-value">' . $cleanup_summary_value . '</span><span class="ixn-summary-note">Pending remediation</span></div>';
$display .= '<div class="ixn-summary"><span class="ixn-summary-label">Articles</span><span class="ixn-summary-value">' . (int) $total_articles . '</span><span class="ixn-summary-note">Available for manual submission</span></div>';
$display .= '</div>';

$display .= '<div class="ixn-layout">';

$display .= '<section class="ixn-card"><div class="ixn-card-head"><div><h2>' . $LANG_indexnow['configuration_status'] . '</h2><p class="ixn-card-subtitle">Key, verification file and plugin runtime settings.</p></div></div>';
$display .= '<div class="ixn-status ' . $status_class . '"><strong>' . $status_title . '</strong>';
if ($status_help !== '') {
    $display .= '<div>' . $status_help . '</div>';
}
$display .= '</div><dl class="ixn-details">';
if ($key_status['key_present']) {
    $display .= '<dt>' . $LANG_indexnow['configured_key'] . '</dt><dd><code>' . htmlspecialchars($key_status['key'], ENT_QUOTES, 'UTF-8') . '</code></dd>';
}
if ($key_status['key_valid']) {
    $display .= '<dt>' . $LANG_indexnow['expected_file'] . '</dt><dd><code>' . htmlspecialchars($key_status['file_path'], ENT_QUOTES, 'UTF-8') . '</code></dd>';
    $display .= '<dt>' . $LANG_indexnow['public_url'] . '</dt><dd><a href="' . htmlspecialchars($key_status['file_url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($key_status['file_url'], ENT_QUOTES, 'UTF-8') . '</a></dd>';
}
$display .= '<dt>' . $LANG_indexnow['debug_status'] . '</dt><dd>' . ($debug_enabled ? $LANG_indexnow['debug_enabled'] : $LANG_indexnow['debug_disabled']) . '</dd>';
$display .= '<dt>' . $LANG_indexnow['history_retention'] . '</dt><dd>' . ($retention_days > 0 ? (int) $retention_days . ' days' : 'Unlimited') . '</dd>';
$display .= '<dt>' . $LANG_indexnow['error_log'] . '</dt><dd><code>' . htmlspecialchars($error_log_path, ENT_QUOTES, 'UTF-8') . '</code></dd></dl>';
$config_url = $_CONF['site_admin_url'] . '/configuration.php';
$display .= '<div class="ixn-actions"><form class="ixn-config-form" method="post" action="' . htmlspecialchars($config_url, ENT_QUOTES, 'UTF-8') . '"><input type="hidden" name="conf_group" value="indexnow"><button type="submit" class="ixn-config-button">' . $LANG_indexnow['open_configuration'] . '</button></form></div></section>';

$display .= '<section class="ixn-card"><div class="ixn-card-head"><div><h2>' . $LANG_indexnow['cleanup_title'] . '</h2><p class="ixn-card-subtitle">Review previously submitted URLs that may no longer be public.</p></div></div>';
if ((int) $cleanup_stats['review'] > 0) {
    $display .= '<div class="ixn-status ixn-warning"><strong>Administrator review required</strong><div>' . $LANG_indexnow['cleanup_legacy_warning'] . '</div></div>';
} elseif ((int) $cleanup_stats['failed'] > 0) {
    $display .= '<div class="ixn-status ixn-error"><strong>Cleanup failures require attention</strong><div>Failed remediation entries remain visible below and can be audited again.</div></div>';
} elseif ((int) $cleanup_stats['pending'] > 0) {
    $display .= '<div class="ixn-status ixn-warning"><strong>Cleanup is pending</strong><div>' . $LANG_indexnow['cleanup_schedule_help'] . '</div></div>';
} else {
    $display .= '<div class="ixn-status ixn-ok"><strong>No pending remediation</strong><div>The cleanup queue currently requires no action.</div></div>';
}
$display .= '<div class="ixn-metrics">';
$display .= '<div class="ixn-metric"><strong>' . (int) $cleanup_stats['pending'] . '</strong><span>' . $LANG_indexnow['cleanup_pending'] . '</span></div>';
$display .= '<div class="ixn-metric"><strong>' . (int) $cleanup_stats['completed'] . '</strong><span>' . $LANG_indexnow['cleanup_completed'] . '</span></div>';
$display .= '<div class="ixn-metric"><strong>' . (int) $cleanup_stats['failed'] . '</strong><span>' . $LANG_indexnow['cleanup_failed'] . '</span></div>';
$display .= '<div class="ixn-metric"><strong>' . (int) $cleanup_stats['review'] . '</strong><span>' . $LANG_indexnow['cleanup_review'] . '</span></div>';
$display .= '</div><dl class="ixn-details"><dt>' . $LANG_indexnow['cleanup_last_audit'] . '</dt><dd>';
if ($cleanup_stats['last_audit'] !== '') {
    $auditTimestamp = strtotime($cleanup_stats['last_audit']);
    if ($auditTimestamp !== false) {
        $auditDate = COM_getUserDateTimeFormat($auditTimestamp);
        $display .= htmlspecialchars($auditDate[0], ENT_QUOTES, 'UTF-8');
    } else {
        $display .= htmlspecialchars($cleanup_stats['last_audit'], ENT_QUOTES, 'UTF-8');
    }
    if ($cleanup_stats['last_audit_message'] !== '') {
        $display .= '<br><span class="ixn-muted">' . htmlspecialchars($cleanup_stats['last_audit_message'], ENT_QUOTES, 'UTF-8') . '</span>';
    }
} else {
    $display .= $LANG_indexnow['cleanup_never_audited'];
}
$display .= '</dd></dl>';
$display .= '<div class="ixn-actions"><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '"><input type="hidden" name="' . CSRF_TOKEN . '" value="' . SEC_createToken() . '"><button type="submit" name="run_security_audit" class="ixn-button">' . $LANG_indexnow['cleanup_run'] . '</button></form></div></section>';

$display .= '</div>';

$display .= '<section class="ixn-card ixn-card-full"><div class="ixn-card-head"><div><h2>Geeklog / XMLSitemap integration</h2><p class="ixn-card-subtitle">Keep XMLSitemap for sitemap generation while assigning IndexNow notifications to a single provider.</p></div></div>';
$display .= '<div class="ixn-coexistence"><div>';
if (!$xmlsitemap_state['installed']) {
    $display .= '<div class="ixn-status ixn-ok"><strong>No XMLSitemap overlap detected</strong><div>XMLSitemap is not installed, so this plugin is the only detected IndexNow provider.</div></div>';
} elseif (!$xmlsitemap_state['enabled']) {
    $display .= '<div class="ixn-status ixn-ok"><strong>XMLSitemap is disabled</strong><div>No duplicate IndexNow submission can occur while XMLSitemap is disabled.</div></div>';
} elseif (!$xmlsitemap_state['indexnow_supported']) {
    $display .= '<div class="ixn-status ixn-ok"><strong>Compatible XMLSitemap configuration</strong><div>This XMLSitemap installation does not expose its own IndexNow setting. The dedicated IndexNow plugin can operate without overlap.</div></div>';
} elseif ($xmlsitemap_state['conflict']) {
    $display .= '<div class="ixn-status ixn-warning"><strong>Duplicate IndexNow provider detected</strong><div>XMLSitemap and the dedicated IndexNow plugin are both configured to submit changed URLs. Disable only XMLSitemap\'s IndexNow option to avoid duplicate requests and inconsistent submission history.</div></div>';
} else {
    $display .= '<div class="ixn-status ixn-ok"><strong>Recommended coexistence is active</strong><div>XMLSitemap remains enabled for sitemap generation and its IndexNow option is disabled. The dedicated plugin is the single IndexNow notification provider.</div></div>';
}
$display .= '<dl class="ixn-details">';
$display .= '<dt>XMLSitemap</dt><dd>' . ($xmlsitemap_state['installed'] ? 'Installed' : 'Not installed') . ($xmlsitemap_state['version'] !== '' ? ' &mdash; v' . htmlspecialchars($xmlsitemap_state['version'], ENT_QUOTES, 'UTF-8') : '') . '</dd>';
if ($xmlsitemap_state['installed']) {
    $display .= '<dt>Plugin state</dt><dd>' . ($xmlsitemap_state['enabled'] ? 'Enabled' : 'Disabled') . '</dd>';
    $display .= '<dt>Native IndexNow support</dt><dd>' . ($xmlsitemap_state['indexnow_supported'] ? 'Available' : 'Not detected') . '</dd>';
    if ($xmlsitemap_state['indexnow_supported']) {
        $display .= '<dt>XMLSitemap IndexNow</dt><dd>' . ($xmlsitemap_state['indexnow_enabled'] ? '<strong>Enabled</strong>' : 'Disabled') . '</dd>';
    }
}
$display .= '</dl></div>';
$display .= '<aside class="ixn-coexistence-note"><strong>Recommended responsibility split</strong>XMLSitemap should continue generating XML and News sitemaps. This dedicated plugin should handle IndexNow notifications, history, permission checks and security remediation. The IndexNow plugin never changes XMLSitemap settings automatically.</aside></div>';
if ($xmlsitemap_state['installed'] && $xmlsitemap_state['indexnow_supported']) {
    $display .= '<div class="ixn-actions"><form class="ixn-config-form" method="post" action="' . htmlspecialchars($config_url, ENT_QUOTES, 'UTF-8') . '"><input type="hidden" name="conf_group" value="xmlsitemap"><button type="submit" class="ixn-config-button">Open XMLSitemap configuration</button></form>';
    if ($xmlsitemap_state['conflict']) {
        $display .= '<span class="ixn-muted">Set <strong>Enable IndexNow</strong> to False. Do not disable XMLSitemap itself.</span>';
    }
    $display .= '</div>';
}
$display .= '</section>';

$display .= '<section class="ixn-card ixn-card-full"><div class="ixn-card-head"><div><h2>' . $LANG_indexnow['manual_submission'] . '</h2><p class="ixn-card-subtitle">Submit public articles in controlled batches of ' . (int) $batch_size . ' URLs.</p></div></div>';
$display .= '<div class="ixn-manual-row"><div class="ixn-manual-copy"><p><strong>' . sprintf($LANG_indexnow['total_articles'], $total_articles) . '</strong></p>';
if ($submitted_range !== '') {
    $display .= '<p class="ixn-success">' . $submitted_range . '</p>';
}
$display .= '<p class="ixn-muted">' . $next_action_message . '</p></div><div class="ixn-manual-action">';
if (!$submission_ready) {
    $display .= '<div class="ixn-status ixn-warning">' . $LANG_indexnow['submission_not_ready'] . '</div>';
}
$display .= '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" onsubmit="indexnowSubmissionLoading()"><input type="hidden" name="offset" value="' . $next_offset . '"><input type="hidden" name="' . CSRF_TOKEN . '" value="' . SEC_createToken() . '"><button type="submit" id="submit-button" name="submit_articles" class="ixn-button"' . ($submission_ready ? '' : ' disabled="disabled"') . '>' . $LANG_indexnow['submit_to_bing'] . '</button></form><p id="loading-message" class="ixn-muted" style="display:none">' . $LANG_indexnow['loading_message'] . '</p></div></div></section>';

$display .= '<div class="ixn-history-wrap">' . INDEXNOW_submissionHistoryList() . '</div>';
$display .= '<details class="ixn-help"><summary>' . $LANG_indexnow['documentation'] . '</summary><div class="ixn-help-body">' . $LANG_indexnow['documentation_content'] . '</div></details>';
$display .= '<script>function indexnowSubmissionLoading(){var b=document.getElementById("submit-button"),m=document.getElementById("loading-message");if(b){b.disabled=true;}if(m){m.style.display="block";}}</script>';
$display .= '</div>';

if (function_exists('COM_createHTMLDocument')) {
    $html = COM_startBlock($LANG_indexnow['plugin_name']) . $display . COM_endBlock();
    echo COM_createHTMLDocument($html, array('pagetitle' => $LANG_indexnow['plugin_name']));
} else {
    echo COM_siteHeader('menu', $LANG_indexnow['plugin_name'])
       . COM_startBlock($LANG_indexnow['plugin_name'])
       . $display
       . COM_endBlock()
       . COM_siteFooter();
}

?>