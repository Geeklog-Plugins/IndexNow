<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
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
 * Render one field in the native Geeklog submission-history list.
 */
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

/**
 * Build native Geeklog filters for the submission history.
 */
function INDEXNOW_submissionFilters(&$defaultFilter, &$pageNavUrl)
{
    global $_TABLES, $LANG_indexnow;

    $status = isset($_REQUEST['ixn_status']) ? COM_applyFilter($_REQUEST['ixn_status']) : 'all';
    $event = isset($_REQUEST['ixn_event']) ? COM_applyFilter($_REQUEST['ixn_event']) : 'all';
    $type = isset($_REQUEST['ixn_type']) ? COM_applyFilter($_REQUEST['ixn_type']) : 'all';

    $allowedStatuses = array('all', 'success', 'failed', 'skipped');
    $allowedEvents = array('all', 'saved', 'deleted', 'manual', 'scheduled');
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
        'scheduled' => 'Scheduled'
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

/**
 * Render the full searchable, sortable, paginated submission history.
 */
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
$key_status = indexnow_get_key_status();
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

$display = '<style>
.ixn-card{box-sizing:border-box;width:100%;margin-bottom:18px;padding:18px 20px;border:1px solid #dfe3e8;border-radius:8px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.ixn-card h2{margin:0 0 14px;font-size:1.15em}.ixn-status{padding:11px 13px;border:1px solid;border-radius:6px;margin-bottom:14px}
.ixn-ok{color:#1b5e20;background:#e8f5e9;border-color:#a5d6a7}.ixn-warning{color:#7a4f00;background:#fff8e1;border-color:#ffe082}.ixn-error{color:#b71c1c;background:#ffebee;border-color:#ef9a9a}
.ixn-details{display:grid;grid-template-columns:minmax(150px,auto) 1fr;gap:7px 14px;margin:0}.ixn-details dt{font-weight:bold}.ixn-details dd{margin:0;min-width:0;overflow-wrap:anywhere}
.ixn-actions{margin-top:16px}.ixn-button{display:inline-block;padding:9px 16px;border:0;border-radius:4px;background:#1678c2;color:#fff;cursor:pointer}.ixn-button[disabled]{background:#aeb7bf;cursor:not-allowed}
.ixn-muted{color:#68737d}.ixn-success{color:#1b5e20;font-weight:bold}.ixn-help{margin-top:18px;padding:15px 18px;border:1px solid #dfe3e8;border-radius:8px;background:#fafbfc}
.ixn-config-form{display:inline}.ixn-config-button{padding:0;border:0;background:none;color:#1678c2;cursor:pointer;text-decoration:underline}
.ixn-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:.9em;white-space:nowrap}.ixn-badge-success{background:#e8f5e9;color:#1b5e20}.ixn-badge-failed{background:#ffebee;color:#b71c1c}.ixn-badge-skipped{background:#fff8e1;color:#7a4f00}
.ixn-native-filters{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center}.ixn-native-filters label{white-space:nowrap}.ixn-native-filters select{margin-left:4px;max-width:180px}
@media(max-width:640px){.ixn-details{grid-template-columns:1fr}.ixn-details dd{margin-bottom:7px}.ixn-native-filters{display:grid;grid-template-columns:1fr}.ixn-native-filters label{white-space:normal}.ixn-native-filters select{width:100%;max-width:none;margin:4px 0 0}}
</style>';

if ($feedback !== '') {
    $display .= $feedback;
}

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

$display .= '<section class="ixn-card"><h2>' . $LANG_indexnow['configuration_status'] . '</h2>';
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
$display .= '<dt>' . $LANG_indexnow['error_log'] . '</dt><dd><code>' . htmlspecialchars($error_log_path, ENT_QUOTES, 'UTF-8') . '</code></dd>';
$display .= '<dt>' . $LANG_indexnow['history_retention'] . '</dt><dd>' . ($retention_days > 0 ? (int) $retention_days . ' days' : 'Unlimited') . '</dd></dl>';
$config_url = $_CONF['site_admin_url'] . '/configuration.php';
$display .= '<div class="ixn-actions"><form class="ixn-config-form" method="post" action="' . htmlspecialchars($config_url, ENT_QUOTES, 'UTF-8') . '"><input type="hidden" name="conf_group" value="indexnow"><button type="submit" class="ixn-config-button">' . $LANG_indexnow['open_configuration'] . '</button></form></div></section>';

$display .= '<section class="ixn-card"><h2>' . $LANG_indexnow['manual_submission'] . '</h2>';
$display .= '<p class="ixn-muted">' . sprintf($LANG_indexnow['total_articles'], $total_articles) . '</p>';
if ($submitted_range !== '') {
    $display .= '<p class="ixn-success">' . $submitted_range . '</p>';
}
$display .= '<p>' . $next_action_message . '</p>';
if (!$submission_ready) {
    $display .= '<p class="ixn-warning ixn-status">' . $LANG_indexnow['submission_not_ready'] . '</p>';
}
$display .= '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" onsubmit="indexnowSubmissionLoading()"><input type="hidden" name="offset" value="' . $next_offset . '"><input type="hidden" name="' . CSRF_TOKEN . '" value="' . SEC_createToken() . '">';
$display .= '<button type="submit" id="submit-button" name="submit_articles" class="ixn-button"' . ($submission_ready ? '' : ' disabled="disabled"') . '>' . $LANG_indexnow['submit_to_bing'] . '</button></form>';
$display .= '<p id="loading-message" class="ixn-muted" style="display:none">' . $LANG_indexnow['loading_message'] . '</p></section>';

$display .= INDEXNOW_submissionHistoryList();
$display .= '<details class="ixn-help"><summary>' . $LANG_indexnow['documentation'] . '</summary><div class="ixn-help-body">' . $LANG_indexnow['documentation_content'] . '</div></details>';
$display .= '<script>function indexnowSubmissionLoading(){var b=document.getElementById("submit-button"),m=document.getElementById("loading-message");if(b){b.disabled=true;}if(m){m.style.display="block";}}</script>';

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
