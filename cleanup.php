<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.1                                                     |
// +---------------------------------------------------------------------------+
// | cleanup.php                                                               |
// |                                                                           |
// | Security remediation queue for URLs submitted before privacy hardening.   |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'cleanup.php') !== false) {
    die('This file cannot be used on its own!');
}

function indexnow_cleanup_table_exists()
{
    global $_TABLES;

    if (!isset($_TABLES['indexnow_cleanup']) || $_TABLES['indexnow_cleanup'] === '') {
        return false;
    }

    $table = $_TABLES['indexnow_cleanup'];
    $result = DB_query("SHOW TABLES LIKE '" . DB_escapeString($table) . "'");

    return ($result && DB_numRows($result) > 0);
}

function indexnow_cleanup_core_url($type, $id)
{
    global $_CONF;

    $id = (string) $id;
    if ($type === 'article') {
        return $_CONF['url_rewrite']
            ? $_CONF['site_url'] . '/article.php/' . $id
            : $_CONF['site_url'] . '/article.php?story=' . $id;
    }
    if ($type === 'staticpages') {
        return $_CONF['url_rewrite']
            ? $_CONF['site_url'] . '/staticpages/index.php/' . $id
            : $_CONF['site_url'] . '/staticpages/index.php?page=' . $id;
    }
    if ($type === 'topic') {
        return $_CONF['site_url'] . '/index.php?topic=' . $id;
    }

    return '';
}

function indexnow_cleanup_visibility($type, $id, $subType = '')
{
    if ($type === 'article' || $type === 'staticpages' || $type === 'topic') {
        $public = indexnow_core_item_is_public($type, $id);
        return array(
            'public' => $public,
            'url' => $public ? indexnow_cleanup_core_url($type, $id) : ''
        );
    }

    $url = indexnow_resolve_saved_plugin_url($type, $id, $subType);
    return array(
        'public' => ($url !== ''),
        'url' => $url
    );
}

function indexnow_cleanup_enqueue($type, $id, $subType, $url, $reason)
{
    global $_TABLES;

    if (!indexnow_cleanup_table_exists()) {
        return false;
    }

    $url = trim((string) $url);
    if ($url === '' || indexnow_validate_url($url) === false) {
        return false;
    }

    $type = (string) $type;
    $id = (string) $id;
    $subType = (string) $subType;
    $reason = (string) $reason;
    $cleanupKey = sha1($type . "\0" . $id . "\0" . $subType . "\0" . $url);

    $existing = DB_getItem(
        $_TABLES['indexnow_cleanup'],
        'cleanup_id',
        "cleanup_key='" . DB_escapeString($cleanupKey) . "'"
    );
    if (!empty($existing)) {
        return false;
    }

    DB_query(
        "INSERT INTO {$_TABLES['indexnow_cleanup']} " .
        "(cleanup_key,item_type,item_id,item_subtype,url,reason,status,attempts,last_http_code,message,created_at,updated_at,processed_at) VALUES (" .
        "'" . DB_escapeString($cleanupKey) . "'," .
        "'" . DB_escapeString($type) . "'," .
        "'" . DB_escapeString($id) . "'," .
        "'" . DB_escapeString($subType) . "'," .
        "'" . DB_escapeString($url) . "'," .
        "'" . DB_escapeString($reason) . "'," .
        "'pending',0,0,'',NOW(),NOW(),NULL)"
    );

    return !DB_error();
}

function indexnow_cleanup_record_audit($audited, $queued, $legacyUnverifiable = false)
{
    global $_TABLES, $LANG_indexnow;

    if (!indexnow_cleanup_table_exists()) {
        return false;
    }

    $message = sprintf($LANG_indexnow['cleanup_audit_record'], (int) $audited, (int) $queued);
    if ($legacyUnverifiable) {
        $message .= $LANG_indexnow['cleanup_audit_legacy_append'];
    }

    $key = sha1('audit' . microtime(true) . mt_rand());
    DB_query(
        "INSERT INTO {$_TABLES['indexnow_cleanup']} " .
        "(cleanup_key,item_type,item_id,item_subtype,url,reason,status,attempts,last_http_code,message,created_at,updated_at,processed_at) VALUES (" .
        "'" . DB_escapeString($key) . "','__audit__','','','','security_audit','audit',0,0," .
        "'" . DB_escapeString($message) . "',NOW(),NOW(),NOW())"
    );

    if ($legacyUnverifiable) {
        $legacyKey = sha1('legacy-unverifiable-1.2.1');
        $existing = DB_getItem(
            $_TABLES['indexnow_cleanup'],
            'cleanup_id',
            "cleanup_key='" . DB_escapeString($legacyKey) . "'"
        );
        if (empty($existing)) {
            DB_query(
                "INSERT INTO {$_TABLES['indexnow_cleanup']} " .
                "(cleanup_key,item_type,item_id,item_subtype,url,reason,status,attempts,last_http_code,message,created_at,updated_at,processed_at) VALUES (" .
                "'" . DB_escapeString($legacyKey) . "','__legacy__','','','','legacy_unverifiable','review',0,0," .
                "'" . DB_escapeString($LANG_indexnow['cleanup_legacy_record']) . "',NOW(),NOW(),NULL)"
            );
        }
    }

    return !DB_error();
}

function indexnow_audit_submitted_urls($legacyUnverifiable = false)
{
    global $_TABLES;

    $stats = array('audited' => 0, 'queued' => 0, 'legacy_unverifiable' => $legacyUnverifiable ? 1 : 0);
    if (!indexnow_cleanup_table_exists() || !indexnow_submission_table_exists()) {
        return $stats;
    }

    $sql = "SELECT item_type,item_id,item_subtype,url,MAX(submission_id) AS last_id " .
        "FROM {$_TABLES['indexnow_submissions']} " .
        "WHERE submitted=1 AND status='success' AND url<>'' " .
        "GROUP BY item_type,item_id,item_subtype,url ORDER BY last_id ASC";
    $result = DB_query($sql);

    while ($A = DB_fetchArray($result)) {
        $type = (string) $A['item_type'];
        $id = (string) $A['item_id'];
        $subType = isset($A['item_subtype']) ? (string) $A['item_subtype'] : '';
        $oldUrl = trim((string) $A['url']);

        if ($type === '' || $id === '' || $oldUrl === '' || indexnow_validate_url($oldUrl) === false) {
            continue;
        }

        $stats['audited']++;
        $visibility = indexnow_cleanup_visibility($type, $id, $subType);

        if (!$visibility['public']) {
            if (indexnow_cleanup_enqueue($type, $id, $subType, $oldUrl, 'private_or_deleted')) {
                $stats['queued']++;
            }
            continue;
        }

        $currentUrl = trim((string) $visibility['url']);
        if ($currentUrl !== '' && $currentUrl !== $oldUrl) {
            if (indexnow_cleanup_enqueue($type, $id, $subType, $oldUrl, 'canonical_changed')) {
                $stats['queued']++;
            }
        }
    }

    indexnow_cleanup_record_audit($stats['audited'], $stats['queued'], $legacyUnverifiable);
    indexnow_log('Security cleanup audit: ' . $stats['audited'] . ' historical URL(s) checked, ' .
        $stats['queued'] . ' queued for remediation.');

    return $stats;
}

function indexnow_cleanup_get_stats()
{
    global $_TABLES;

    $stats = array(
        'pending' => 0,
        'completed' => 0,
        'failed' => 0,
        'review' => 0,
        'last_audit' => '',
        'last_audit_message' => ''
    );

    if (!indexnow_cleanup_table_exists()) {
        return $stats;
    }

    foreach (array('pending', 'completed', 'failed', 'review') as $status) {
        $stats[$status] = (int) DB_count($_TABLES['indexnow_cleanup'], 'status', $status);
    }

    $result = DB_query(
        "SELECT created_at,message FROM {$_TABLES['indexnow_cleanup']} " .
        "WHERE status='audit' ORDER BY cleanup_id DESC LIMIT 1"
    );
    if ($result && DB_numRows($result) > 0) {
        $A = DB_fetchArray($result);
        $stats['last_audit'] = isset($A['created_at']) ? (string) $A['created_at'] : '';
        $stats['last_audit_message'] = isset($A['message']) ? (string) $A['message'] : '';
    }

    return $stats;
}

function indexnow_process_cleanup_queue($limit = 100)
{
    global $_TABLES, $LANG_indexnow;

    if (!indexnow_cleanup_table_exists()) {
        return 0;
    }

    $limit = max(1, min(100, (int) $limit));
    $result = DB_query(
        "SELECT cleanup_id,item_type,item_id,item_subtype,url,attempts " .
        "FROM {$_TABLES['indexnow_cleanup']} WHERE status='pending' " .
        "ORDER BY cleanup_id ASC LIMIT $limit"
    );

    $rows = array();
    $urls = array();
    $contexts = array();
    while ($A = DB_fetchArray($result)) {
        $rows[] = $A;
        $urls[] = $A['url'];
        $contexts[] = indexnow_submission_context($A['item_type'], $A['item_id'], $A['item_subtype'], 'cleanup');
    }

    if (empty($rows)) {
        return 0;
    }

    send_to_indexnow($urls, array('event' => 'cleanup', 'batch_items' => $contexts));

    foreach ($rows as $A) {
        $url = (string) $A['url'];
        $history = DB_query(
            "SELECT status,http_code,message FROM {$_TABLES['indexnow_submissions']} " .
            "WHERE event='cleanup' AND url='" . DB_escapeString($url) . "' " .
            "ORDER BY submission_id DESC LIMIT 1"
        );

        $status = 'pending';
        $httpCode = 0;
        $message = $LANG_indexnow['cleanup_result_missing'];
        if ($history && DB_numRows($history) > 0) {
            $H = DB_fetchArray($history);
            $httpCode = (int) $H['http_code'];
            $message = (string) $H['message'];
            if ($H['status'] === 'success') {
                $status = 'completed';
            }
        }

        $attempts = (int) $A['attempts'] + 1;
        if ($status !== 'completed' && $attempts >= 3) {
            $status = 'failed';
        }

        $processed = ($status === 'completed' || $status === 'failed') ? 'NOW()' : 'NULL';
        DB_query(
            "UPDATE {$_TABLES['indexnow_cleanup']} SET " .
            "status='" . DB_escapeString($status) . "'," .
            "attempts=$attempts," .
            "last_http_code=$httpCode," .
            "message='" . DB_escapeString($message) . "'," .
            "updated_at=NOW(),processed_at=$processed " .
            "WHERE cleanup_id=" . (int) $A['cleanup_id']
        );
    }

    indexnow_log('Security cleanup queue: processed ' . count($rows) . ' remediation URL(s).');
    return count($rows);
}

?>
