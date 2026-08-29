<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | submission_history.php                                                    |
// |                                                                           |
// | Persistence helpers for IndexNow submission attempts.                     |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'submission_history.php') !== false) {
    die('This file cannot be used on its own!');
}

/**
 * Check whether the submission history table can be used safely.
 * If 1.2.0 code is present but the migration did not create the table, try to
 * repair the installation once through the idempotent 1.2.0 updater.
 *
 * @return bool
 */
function indexnow_history_table_ready()
{
    global $_TABLES;

    if (!isset($_TABLES['indexnow_submissions']) || $_TABLES['indexnow_submissions'] === '') {
        return false;
    }

    if (function_exists('indexnow_submission_table_exists')) {
        if (indexnow_submission_table_exists()) {
            return true;
        }

        if (function_exists('indexnow_update_1_2_0') && indexnow_update_1_2_0()) {
            return indexnow_submission_table_exists();
        }

        return false;
    }

    $table = $_TABLES['indexnow_submissions'];
    $result = DB_query("SHOW TABLES LIKE '" . DB_escapeString($table) . "'");

    return ($result && DB_numRows($result) > 0);
}

/**
 * Record one IndexNow submission attempt.
 *
 * @param array  $context   item_type, item_id, item_subtype and event
 * @param string $url
 * @param bool   $submitted Whether an HTTP request was attempted
 * @param int    $httpCode
 * @param string $status    success, failed or skipped
 * @param string $message
 * @return bool
 */
function indexnow_record_submission($context, $url, $submitted, $httpCode, $status, $message)
{
    global $_TABLES;

    if (!indexnow_history_table_ready()) {
        return false;
    }

    if (!is_array($context)) {
        $context = array();
    }

    $itemType = isset($context['item_type']) ? trim((string) $context['item_type']) : '';
    $itemId = isset($context['item_id']) ? trim((string) $context['item_id']) : '';
    $itemSubtype = isset($context['item_subtype']) ? trim((string) $context['item_subtype']) : '';
    $event = isset($context['event']) ? trim((string) $context['event']) : '';

    $sql = "INSERT INTO {$_TABLES['indexnow_submissions']} " .
        "(item_type,item_id,item_subtype,event,url,submitted,http_code,status,message,submitted_at) VALUES (" .
        "'" . DB_escapeString($itemType) . "'," .
        "'" . DB_escapeString($itemId) . "'," .
        "'" . DB_escapeString($itemSubtype) . "'," .
        "'" . DB_escapeString($event) . "'," .
        "'" . DB_escapeString((string) $url) . "'," .
        ((bool) $submitted ? '1' : '0') . ',' .
        (int) $httpCode . ',' .
        "'" . DB_escapeString((string) $status) . "'," .
        "'" . DB_escapeString((string) $message) . "',NOW())";

    DB_query($sql);
    return !DB_error();
}

/**
 * Return recent submission attempts for the administration dashboard.
 *
 * @param int $limit
 * @return array
 */
function indexnow_get_recent_submissions($limit = 25)
{
    global $_TABLES;

    $rows = array();
    if (!indexnow_history_table_ready()) {
        return $rows;
    }

    $limit = max(1, min(100, (int) $limit));
    $result = DB_query(
        "SELECT submission_id,item_type,item_id,item_subtype,event,url,submitted,http_code,status,message,submitted_at " .
        "FROM {$_TABLES['indexnow_submissions']} ORDER BY submission_id DESC LIMIT " . $limit
    );

    if (!$result) {
        return $rows;
    }

    while ($row = DB_fetchArray($result)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Return the latest recorded attempt for one Geeklog item.
 * This intentionally stays provider-neutral so Hub can consume it later.
 *
 * @param string $type
 * @param string $id
 * @return array
 */
function indexnow_get_last_submission($type, $id)
{
    global $_TABLES;

    if (!indexnow_history_table_ready()) {
        return array();
    }

    $sql = "SELECT submission_id,item_type,item_id,item_subtype,event,url,submitted,http_code,status,message,submitted_at " .
        "FROM {$_TABLES['indexnow_submissions']} WHERE item_type='" . DB_escapeString((string) $type) .
        "' AND item_id='" . DB_escapeString((string) $id) . "' ORDER BY submission_id DESC LIMIT 1";
    $result = DB_query($sql);
    if (!$result || DB_numRows($result) === 0) {
        return array();
    }

    return DB_fetchArray($result);
}

/**
 * Purge history according to the configured retention period.
 * A value of 0 means unlimited retention.
 *
 * @param int|null $days
 * @return int Number of rows deleted when available
 */
function indexnow_purge_submission_history($days = null)
{
    global $_INDEXNOW_CONF, $_TABLES;

    if (!indexnow_history_table_ready()) {
        return 0;
    }

    if ($days === null) {
        $days = isset($_INDEXNOW_CONF['history_retention_days'])
            ? (int) $_INDEXNOW_CONF['history_retention_days'] : 90;
    }

    $days = (int) $days;
    if ($days <= 0) {
        return 0;
    }

    $result = DB_query(
        "DELETE FROM {$_TABLES['indexnow_submissions']} " .
        "WHERE submitted_at < DATE_SUB(NOW(), INTERVAL " . $days . " DAY)"
    );

    if (DB_error()) {
        return 0;
    }

    // Geeklog 2.1.1 requires the query result/connection argument here.
    // Passing it is also compatible with later Geeklog database wrappers.
    return function_exists('DB_affectedRows') ? (int) DB_affectedRows($result) : 0;
}

?>
