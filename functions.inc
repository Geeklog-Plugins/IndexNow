<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.1                                                     |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file cannot be used on its own.');
}

global $_CONF, $_TABLES, $_INDEXNOW_CONF, $_DB_table_prefix;

$plugin_path = $_CONF['path'] . 'plugins/indexnow/';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';
if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

if (!isset($_INDEXNOW_CONF)) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    if (class_exists('config')) {
        $indexnow_config = config::get_instance();
        $_INDEXNOW_CONF = $indexnow_config->get_config('indexnow');
    } else {
        $_INDEXNOW_CONF = array();
    }
}

$_TABLES['indexnow_submissions'] = $_DB_table_prefix . 'indexnow_submissions';
require_once $plugin_path . 'submission_history.php';

/**
 * Remove control characters that could forge additional log entries.
 */
function indexnow_sanitize_log_value($value)
{
    return str_replace(array("\r", "\n", "\0"), '', (string) $value);
}

function indexnow_log($message, $force = false)
{
    global $_INDEXNOW_CONF;

    $debug_mode = isset($_INDEXNOW_CONF['debug_mode']) ? (int) $_INDEXNOW_CONF['debug_mode'] : 0;
    if ($debug_mode === 1 || $force) {
        COM_errorLog('[IndexNow] ' . indexnow_sanitize_log_value($message));
        return true;
    }

    return false;
}

/**
 * Validate an IndexNow key without changing it.
 */
function indexnow_validate_key($key)
{
    $key = trim((string) $key);
    return ($key !== '' && preg_match('/^[A-Za-z0-9-]{8,128}$/D', $key)) ? $key : false;
}

/**
 * Only accept HTTP(S) URLs belonging to the configured Geeklog host.
 * This establishes a trust boundary between IndexNow and content plugins.
 */
function indexnow_validate_url($url)
{
    global $_CONF;

    $url = trim((string) $url);
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return false;
    }

    $parts = parse_url($url);
    $siteParts = parse_url($_CONF['site_url']);
    if (!is_array($parts) || !is_array($siteParts) ||
        empty($parts['scheme']) || empty($parts['host']) || empty($siteParts['host'])) {
        return false;
    }

    $scheme = strtolower($parts['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') {
        return false;
    }

    if (strcasecmp($parts['host'], $siteParts['host']) !== 0) {
        return false;
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
        return false;
    }

    $urlPort = isset($parts['port']) ? (int) $parts['port'] : null;
    $sitePort = isset($siteParts['port']) ? (int) $siteParts['port'] : null;
    if ($urlPort !== $sitePort) {
        return false;
    }

    return $url;
}

function indexnow_get_key_status()
{
    global $_CONF, $_INDEXNOW_CONF;

    $key = isset($_INDEXNOW_CONF['indexnow_key']) ? trim($_INDEXNOW_CONF['indexnow_key']) : '';
    $status = array(
        'key' => $key,
        'key_present' => ($key !== ''),
        'key_valid' => false,
        'file_path' => '',
        'file_url' => '',
        'file_exists' => false,
        'file_readable' => false,
        'file_matches' => false
    );

    if ($key === '') {
        return $status;
    }

    $validatedKey = indexnow_validate_key($key);
    $status['key_valid'] = ($validatedKey !== false);
    if (!$status['key_valid']) {
        return $status;
    }

    $key = $validatedKey;
    $status['file_path'] = rtrim($_CONF['path_html'], '/\\') . DIRECTORY_SEPARATOR . $key . '.txt';
    $status['file_url'] = rtrim($_CONF['site_url'], '/') . '/' . rawurlencode($key) . '.txt';
    $status['file_exists'] = is_file($status['file_path']);
    $status['file_readable'] = $status['file_exists'] && is_readable($status['file_path']);

    if ($status['file_readable']) {
        $contents = file_get_contents($status['file_path']);
        $status['file_matches'] = ($contents !== false && trim($contents) === $key);
    }

    return $status;
}

function plugin_autouninstall_indexnow()
{
    return array(
        'tables' => array('indexnow_submissions'),
        'groups' => array('IndexNow Admin'),
        'features' => array('indexnow.admin', 'config.indexnow.tab_main'),
        'php_blocks' => array(),
        'vars' => array()
    );
}

function plugin_cclabel_indexnow()
{
    global $_CONF, $LANG_indexnow;

    if (SEC_hasRights('indexnow.admin')) {
        return array(
            $LANG_indexnow['plugin_name'],
            $_CONF['site_admin_url'] . '/plugins/indexnow/index.php',
            $_CONF['site_admin_url'] . '/plugins/indexnow/indexnow.png'
        );
    }

    return '';
}

function plugin_getadminoption_indexnow()
{
    global $_CONF, $LANG_indexnow;

    if (SEC_hasRights('indexnow.admin')) {
        return array($LANG_indexnow['plugin_name'], $_CONF['site_admin_url'] . '/plugins/indexnow/index.php', '');
    }

    return '';
}

function indexnow_extract_item_url($value)
{
    if (is_string($value)) {
        return trim($value);
    }
    if (!is_array($value)) {
        return '';
    }
    if (isset($value['url']) && is_string($value['url'])) {
        return trim($value['url']);
    }
    if (isset($value[0]) && is_string($value[0])) {
        return trim($value[0]);
    }
    return '';
}

/**
 * Call plugin_idtourl_* while tolerating both the legacy one-argument form and
 * the subtype-aware two-argument form used by modernized plugins.
 */
function indexnow_call_idtourl($function, $sub_type, $id)
{
    if (!function_exists($function)) {
        return '';
    }

    try {
        $reflection = new ReflectionFunction($function);
        if ($reflection->getNumberOfParameters() >= 2) {
            return trim((string) $function($sub_type, $id));
        }
    } catch (Exception $e) {
        // Fall through to the safest legacy call.
    }

    return trim((string) $function($id));
}

function indexnow_resolve_saved_plugin_url($type, $id, $sub_type = '')
{
    $url = '';

    if (function_exists('PLG_getItemInfo')) {
        $options = array();
        if ($sub_type !== '') {
            $options['sub_type'] = $sub_type;
        }
        $url = indexnow_extract_item_url(PLG_getItemInfo($type, $id, 'url', 0, $options));
    }

    if ($url !== '') {
        return $url;
    }

    return indexnow_call_idtourl('plugin_idtourl_' . $type, $sub_type, $id);
}

function indexnow_resolve_deleted_plugin_url($type, $id, $sub_type = '')
{
    $url = indexnow_call_idtourl('plugin_idtourl_' . $type, $sub_type, $id);
    if ($url !== '') {
        return $url;
    }

    if (function_exists('PLG_getItemInfo')) {
        $options = array();
        if ($sub_type !== '') {
            $options['sub_type'] = $sub_type;
        }
        return indexnow_extract_item_url(PLG_getItemInfo($type, $id, 'url', 0, $options));
    }

    return '';
}

function indexnow_submission_context($type, $id, $sub_type, $event)
{
    return array(
        'item_type' => (string) $type,
        'item_id' => (string) $id,
        'item_subtype' => (string) $sub_type,
        'event' => (string) $event
    );
}

function plugin_itemsaved_indexnow($id, $type, $old_id = '', $sub_type = '')
{
    global $_CONF, $_TABLES, $_INDEXNOW_CONF;

    $url = '';
    $key = isset($_INDEXNOW_CONF['indexnow_key']) ? trim($_INDEXNOW_CONF['indexnow_key']) : '';
    $context = indexnow_submission_context($type, $id, $sub_type, 'saved');

    if ($type === 'article') {
        $escaped_id = DB_escapeString($id);
        $result = DB_query("SELECT draft_flag, date FROM {$_TABLES['stories']} WHERE sid = '$escaped_id'");
        $A = DB_fetchArray($result);
        if (!is_array($A) || $A['draft_flag'] == 1 || strtotime($A['date']) > time()) {
            return;
        }
        $url = $_CONF['url_rewrite']
            ? $_CONF['site_url'] . "/article.php/$id"
            : $_CONF['site_url'] . "/article.php?story=$id";
    } elseif ($type === 'staticpages') {
        $escaped_id = DB_escapeString($id);
        $result = DB_query("SELECT draft_flag FROM {$_TABLES['staticpage']} WHERE sp_id = '$escaped_id'");
        $A = DB_fetchArray($result);
        if (!is_array($A) || $A['draft_flag'] == 1) {
            return;
        }
        $url = $_CONF['url_rewrite']
            ? $_CONF['site_url'] . "/staticpages/index.php/$id"
            : $_CONF['site_url'] . "/staticpages/index.php?page=$id";
    } elseif ($type === 'topic') {
        $url = $_CONF['site_url'] . "/index.php?topic=$id";
    } else {
        $url = indexnow_resolve_saved_plugin_url($type, $id, $sub_type);
        if ($url === '') {
            indexnow_log("Unable to resolve plugin item URL (type: $type, ID: $id).", true);
            indexnow_record_submission($context, '', false, 0, 'failed', 'Unable to resolve canonical URL.');
            return;
        }
    }

    if (indexnow_validate_url($url) === false) {
        indexnow_log("Rejected unsafe or foreign URL (type: $type, ID: $id, URL: $url).", true);
        indexnow_record_submission($context, $url, false, 0, 'failed', 'URL rejected: URL must use HTTP(S) and belong to the Geeklog site host.');
        return;
    }

    if ($key === '') {
        indexnow_log("Save resolved but not submitted: no key configured (type: $type, ID: $id, URL: $url).", true);
        indexnow_record_submission($context, $url, false, 0, 'skipped', 'No IndexNow key configured.');
        return;
    }

    send_to_indexnow($url, $context);
}

function plugin_itemdeleted_indexnow($id, $type, $sub_type = '')
{
    global $_CONF, $_INDEXNOW_CONF;

    $key = isset($_INDEXNOW_CONF['indexnow_key']) ? trim($_INDEXNOW_CONF['indexnow_key']) : '';
    $url = '';
    $context = indexnow_submission_context($type, $id, $sub_type, 'deleted');

    if ($type === 'article') {
        $url = $_CONF['url_rewrite']
            ? $_CONF['site_url'] . "/article.php/$id"
            : $_CONF['site_url'] . "/article.php?story=$id";
    } elseif ($type === 'staticpages') {
        $url = $_CONF['url_rewrite']
            ? $_CONF['site_url'] . "/staticpages/index.php/$id"
            : $_CONF['site_url'] . "/staticpages/index.php?page=$id";
    } elseif ($type === 'topic') {
        $url = $_CONF['site_url'] . "/index.php?topic=$id";
    } else {
        $url = indexnow_resolve_deleted_plugin_url($type, $id, $sub_type);
        if ($url === '') {
            indexnow_log("Generic delete received but URL could not be resolved (type: $type, ID: $id).", true);
            indexnow_record_submission($context, '', false, 0, 'failed', 'Unable to resolve canonical URL after deletion.');
            return;
        }
    }

    if (indexnow_validate_url($url) === false) {
        indexnow_log("Rejected unsafe or foreign deleted-item URL (type: $type, ID: $id, URL: $url).", true);
        indexnow_record_submission($context, $url, false, 0, 'failed', 'URL rejected: URL must use HTTP(S) and belong to the Geeklog site host.');
        return;
    }

    if ($key === '') {
        indexnow_log("Delete resolved but not submitted: no key configured (type: $type, ID: $id, URL: $url).", true);
        indexnow_record_submission($context, $url, false, 0, 'skipped', 'No IndexNow key configured.');
        return;
    }

    send_to_indexnow($url, $context);
}

/**
 * Apply common secure cURL options for IndexNow HTTPS requests.
 */
function indexnow_apply_curl_security($ch)
{
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    }
}

/**
 * Send one or multiple URLs to IndexNow and persist the result.
 * For batches, $context['batch_items'] may contain one context per URL.
 *
 * @param string|array $url
 * @param array        $context
 * @return mixed
 */
function send_to_indexnow($url, $context = array())
{
    global $_CONF, $_INDEXNOW_CONF;

    $key = isset($_INDEXNOW_CONF['indexnow_key']) ? trim($_INDEXNOW_CONF['indexnow_key']) : '';
    $validatedKey = indexnow_validate_key($key);

    if (is_string($url)) {
        $validatedUrl = indexnow_validate_url($url);
        if ($validatedUrl === false) {
            indexnow_log('IndexNow: Rejected unsafe or foreign URL: ' . $url, true);
            indexnow_record_submission($context, $url, false, 0, 'failed',
                'URL rejected: URL must use HTTP(S) and belong to the Geeklog site host.');
            return false;
        }
        $url = $validatedUrl;

        if ($validatedKey === false) {
            $message = ($key === '') ? 'No IndexNow key configured.' : 'Invalid IndexNow key configured.';
            indexnow_log('IndexNow: ' . $message, true);
            indexnow_record_submission($context, $url, false, 0, 'skipped', $message);
            return false;
        }

        $key = $validatedKey;
        $keyLocation = rtrim($_CONF['site_url'], '/') . '/' . rawurlencode($key) . '.txt';
        $endpoint = 'https://api.indexnow.org/indexnow?' . http_build_query(array(
            'url' => $url,
            'key' => $key,
            'keyLocation' => $keyLocation
        ), '', '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        indexnow_apply_curl_security($ch);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = ($response !== false && ($httpCode === 200 || $httpCode === 202));
        $message = $success ? 'IndexNow accepted the URL.' :
            ('IndexNow request failed.' . ($curlError !== '' ? ' cURL: ' . $curlError : ''));
        indexnow_record_submission($context, $url, true, $httpCode, $success ? 'success' : 'failed', $message);

        if ($success) {
            indexnow_log("IndexNow GET OK [$url] - HTTP $httpCode");
        } else {
            indexnow_log("IndexNow GET failed [$url] - HTTP $httpCode" .
                ($curlError !== '' ? " - cURL: $curlError" : ''), true);
        }

        return $response;
    }

    if (is_array($url) && !empty($url)) {
        $validUrls = array();
        $batchContexts = array();
        $providedContexts = isset($context['batch_items']) && is_array($context['batch_items'])
            ? $context['batch_items'] : array();

        foreach ($url as $index => $batchUrl) {
            $rowContext = isset($providedContexts[$index]) && is_array($providedContexts[$index])
                ? $providedContexts[$index] : $context;
            unset($rowContext['batch_items']);

            $validatedUrl = indexnow_validate_url($batchUrl);
            if ($validatedUrl === false) {
                indexnow_log('IndexNow: Rejected unsafe or foreign batch URL: ' . $batchUrl, true);
                indexnow_record_submission($rowContext, $batchUrl, false, 0, 'failed',
                    'URL rejected: URL must use HTTP(S) and belong to the Geeklog site host.');
                continue;
            }

            $validUrls[] = $validatedUrl;
            $batchContexts[] = $rowContext;
        }

        if (empty($validUrls)) {
            return false;
        }

        if ($validatedKey === false) {
            $message = ($key === '') ? 'No IndexNow key configured.' : 'Invalid IndexNow key configured.';
            foreach ($validUrls as $index => $batchUrl) {
                indexnow_record_submission($batchContexts[$index], $batchUrl, false, 0, 'skipped', $message);
            }
            indexnow_log('IndexNow: ' . $message, true);
            return false;
        }

        $key = $validatedKey;
        $keyLocation = rtrim($_CONF['site_url'], '/') . '/' . rawurlencode($key) . '.txt';
        $payload = array(
            'host' => parse_url($_CONF['site_url'], PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => $validUrls
        );

        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        indexnow_apply_curl_security($ch);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = ($response !== false && ($httpCode === 200 || $httpCode === 202));
        $message = $success ? 'IndexNow accepted the batch.' :
            ('IndexNow batch request failed.' . ($curlError !== '' ? ' cURL: ' . $curlError : ''));

        foreach ($validUrls as $index => $batchUrl) {
            $rowContext = $batchContexts[$index];
            if (!isset($rowContext['event'])) {
                $rowContext['event'] = 'manual';
            }
            indexnow_record_submission($rowContext, $batchUrl, true, $httpCode,
                $success ? 'success' : 'failed', $message);
        }

        if ($success) {
            indexnow_log('IndexNow BATCH OK (' . count($validUrls) . " URLs) - HTTP $httpCode");
        } else {
            indexnow_log('IndexNow BATCH failed (' . count($validUrls) . ") - HTTP $httpCode" .
                ($curlError !== '' ? " - cURL: $curlError" : ''), true);
        }

        return $response;
    }

    indexnow_log('IndexNow: Invalid data passed to send_to_indexnow()', true);
    return false;
}

function submit_articles_by_date_desc_to_indexnow($limit = 1, $offset = 0)
{
    global $_CONF, $_TABLES;

    $urls = array();
    $contexts = array();
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $sql = "SELECT sid, date FROM {$_TABLES['stories']} WHERE draft_flag = 0 " .
        "AND UNIX_TIMESTAMP(date) <= UNIX_TIMESTAMP(NOW()) ORDER BY date DESC LIMIT $offset, $limit";
    $result = DB_query($sql);

    while ($A = DB_fetchArray($result)) {
        $urls[] = $_CONF['url_rewrite']
            ? $_CONF['site_url'] . '/article.php/' . $A['sid']
            : $_CONF['site_url'] . '/article.php?story=' . $A['sid'];
        $contexts[] = indexnow_submission_context('article', $A['sid'], '', 'manual');
    }

    if (!empty($urls)) {
        send_to_indexnow($urls, array('event' => 'manual', 'batch_items' => $contexts));
    }
    $submitted_count = count($urls);
    indexnow_log("IndexNow: Submitted $submitted_count articles (offset: $offset, limit: $limit).");

    return $submitted_count;
}

function get_total_articles_to_submit()
{
    global $_TABLES;

    $result = DB_query("SELECT COUNT(*) AS total FROM {$_TABLES['stories']} WHERE draft_flag = 0 " .
        "AND UNIX_TIMESTAMP(date) <= UNIX_TIMESTAMP(NOW())");
    $A = DB_fetchArray($result);
    return intval($A['total']);
}

function plugin_runScheduledTask_indexnow()
{
    global $_TABLES, $_CONF;

    indexnow_purge_submission_history();
    $items = array();

    $result = DB_query("SELECT sid FROM {$_TABLES['stories']} WHERE draft_flag = 0 " .
        "AND UNIX_TIMESTAMP(date) >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)");
    while ($A = DB_fetchArray($result)) {
        $items[] = array(
            'type' => 'article',
            'id' => $A['sid'],
            'url' => $_CONF['url_rewrite']
                ? $_CONF['site_url'] . '/article.php/' . $A['sid']
                : $_CONF['site_url'] . '/article.php?story=' . $A['sid']
        );
    }

    $result = DB_query("SELECT sp_id FROM {$_TABLES['staticpage']} WHERE draft_flag = 0 " .
        "AND UNIX_TIMESTAMP(created) >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)");
    while ($A = DB_fetchArray($result)) {
        $items[] = array(
            'type' => 'staticpages',
            'id' => $A['sp_id'],
            'url' => $_CONF['url_rewrite']
                ? $_CONF['site_url'] . '/staticpages/index.php/' . $A['sp_id']
                : $_CONF['site_url'] . '/staticpages/index.php?page=' . $A['sp_id']
        );
    }

    $batchSize = 100;
    for ($offset = 0; $offset < count($items); $offset += $batchSize) {
        $chunk = array_slice($items, $offset, $batchSize);
        $urls = array();
        $contexts = array();

        foreach ($chunk as $item) {
            $urls[] = $item['url'];
            $contexts[] = indexnow_submission_context($item['type'], $item['id'], '', 'scheduled');
        }

        if (!empty($urls)) {
            send_to_indexnow($urls, array('event' => 'scheduled', 'batch_items' => $contexts));
        }
    }

    indexnow_log('IndexNow scheduled task: processed ' . count($items) . ' URLs from the last 24h in batches.');
    return true;
}

function plugin_chkVersion_indexnow()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/indexnow/autoinstall.php';
    $install = plugin_autoinstall_indexnow('indexnow');
    return $install['info']['pi_version'];
}

require_once $_CONF['path'] . 'plugins/indexnow/install_updates.php';

?>
