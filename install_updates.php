<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_updates.php') !== false) {
    die('This file cannot be used on its own!');
}

function indexnow_update_ConfValues_1_1_0()
{
    global $_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $c = config::get_instance();

    if ($c->group_exists('indexnow')) {
        $configuration = $c->get_config('indexnow');
        if (!array_key_exists('debug_mode', $configuration)) {
            $c->add('debug_mode', 0, 'select', 0, 0, 0, 20, true, 'indexnow', 0);
        }
    }

    return true;
}

function indexnow_update_ConfigSecurity_1_1_1()
{
    global $_TABLES;

    $feature_name = 'config.indexnow.tab_main';
    $feature_id = DB_getItem($_TABLES['features'], 'ft_id',
        "ft_name = '" . DB_escapeString($feature_name) . "'");

    if (empty($feature_id)) {
        DB_query("INSERT INTO {$_TABLES['features']} " .
            "(ft_name, ft_descr, ft_gl_core) VALUES (" .
            "'" . DB_escapeString($feature_name) . "', " .
            "'Access to configure the IndexNow plugin', 0)");
        if (DB_error()) {
            return false;
        }
        $feature_id = DB_getItem($_TABLES['features'], 'ft_id',
            "ft_name = '" . DB_escapeString($feature_name) . "'");
    }

    $group_id = DB_getItem($_TABLES['groups'], 'grp_id', "grp_name = 'IndexNow Admin'");
    if (empty($feature_id) || empty($group_id)) {
        return false;
    }

    $mapping = DB_getItem($_TABLES['access'], 'acc_ft_id',
        'acc_ft_id = ' . (int) $feature_id . ' AND acc_grp_id = ' . (int) $group_id);
    if (empty($mapping)) {
        DB_query("INSERT INTO {$_TABLES['access']} (acc_ft_id, acc_grp_id) VALUES (" .
            (int) $feature_id . ', ' . (int) $group_id . ')');
        if (DB_error()) {
            return false;
        }
    }

    return true;
}

/**
 * Return whether the IndexNow submission history table exists.
 *
 * @return bool
 */
function indexnow_submission_table_exists()
{
    global $_TABLES;

    if (!isset($_TABLES['indexnow_submissions']) || $_TABLES['indexnow_submissions'] === '') {
        return false;
    }

    $table = $_TABLES['indexnow_submissions'];
    $result = DB_query("SHOW TABLES LIKE '" . DB_escapeString($table) . "'");

    return ($result && DB_numRows($result) > 0);
}

/**
 * Add the submission history table and retention configuration.
 * Safe to call more than once and suitable as a repair step.
 */
function indexnow_update_1_2_0()
{
    global $_CONF, $_TABLES;

    if (!isset($_TABLES['indexnow_submissions']) || $_TABLES['indexnow_submissions'] === '') {
        return false;
    }

    $table = $_TABLES['indexnow_submissions'];

    if (!indexnow_submission_table_exists()) {
        DB_query("CREATE TABLE IF NOT EXISTS {$table} (
          submission_id int(10) unsigned NOT NULL auto_increment,
          item_type varchar(64) NOT NULL default '',
          item_id varchar(255) NOT NULL default '',
          item_subtype varchar(64) NOT NULL default '',
          event varchar(16) NOT NULL default '',
          url text NOT NULL,
          submitted tinyint(1) unsigned NOT NULL default '0',
          http_code smallint(5) unsigned NOT NULL default '0',
          status varchar(32) NOT NULL default '',
          message text NOT NULL,
          submitted_at datetime NOT NULL,
          PRIMARY KEY (submission_id),
          KEY submitted_at (submitted_at),
          KEY status (status),
          KEY item_lookup (item_type,item_id(100))
        ) ENGINE=MyISAM");
        if (DB_error() || !indexnow_submission_table_exists()) {
            return false;
        }
    }

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $c = config::get_instance();
    if ($c->group_exists('indexnow')) {
        $configuration = $c->get_config('indexnow');
        if (!array_key_exists('history_retention_days', $configuration)) {
            $c->add('history_retention_days', 90, 'select', 0, 0, 1, 30, true, 'indexnow', 0);
        }
    }

    return true;
}

function plugin_upgrade_indexnow()
{
    global $_TABLES;

    $installed_version = DB_getItem($_TABLES['plugins'], 'pi_version', "pi_name = 'indexnow'");
    $code_version = plugin_chkVersion_indexnow();

    // 1.2.0 introduced a database table. Always verify it, even when the
    // plugin version is already recorded as 1.2.0 (for example after files
    // were replaced before Geeklog's upgrade routine was run).
    if (version_compare($code_version, '1.2.0', '>=')) {
        if (!indexnow_update_1_2_0()) {
            return false;
        }
    }

    if ($installed_version == $code_version) {
        return true;
    }

    if ($installed_version == '1.0.0' || $installed_version == '1.0.1') {
        if (!indexnow_update_ConfValues_1_1_0()) {
            return false;
        }
    }

    if (!indexnow_update_ConfigSecurity_1_1_1()) {
        return false;
    }

    DB_query("UPDATE {$_TABLES['plugins']} SET " .
        "pi_version = '" . DB_escapeString($code_version) . "', " .
        "pi_gl_version = '2.1.1' WHERE pi_name = 'indexnow'");

    return !DB_error();
}

?>
