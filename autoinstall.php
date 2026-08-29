<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | autoinstall.php                                                           |
// |                                                                           |
// | This file provides helper functions for the automatic plugin install.     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2024-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben (hostellerie.org AT gmail DOT com)                           |
// +---------------------------------------------------------------------------+

require_once('functions.inc');

function plugin_autoinstall_indexnow($pi_name)
{
    $pi_name         = 'indexnow';
    $pi_display_name = 'IndexNow';
    $pi_admin        = $pi_display_name . ' Admin';

    $info = array(
        'pi_name'         => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version'      => '1.2.0',
        'pi_gl_version'   => '2.1.1',
        'pi_homepage'     => 'https://geeklog.fr'
    );

    $groups = array(
        $pi_admin => 'Users in this group can administer the ' . $pi_display_name . ' plugin'
    );

    $features = array(
        $pi_name . '.admin' => 'Full access to ' . $pi_display_name . ' plugin',
        'config.' . $pi_name . '.tab_main' => 'Access to configure the ' . $pi_display_name . ' plugin'
    );

    $mappings = array(
        $pi_name . '.admin' => array($pi_admin),
        'config.' . $pi_name . '.tab_main' => array($pi_admin)
    );

    return array(
        'info' => $info,
        'groups' => $groups,
        'features' => $features,
        'mappings' => $mappings,
        'tables' => array('indexnow_submissions')
    );
}

function plugin_load_configuration_indexnow($pi_name)
{
    global $_CONF;

    $base_path = $_CONF['path'] . 'plugins/' . $pi_name . '/';
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $base_path . 'install_defaults.php';

    return plugin_initconfig_indexnow();
}

function plugin_postinstall_indexnow($pi_name)
{
    return function_exists('indexnow_update_1_2_0')
        ? indexnow_update_1_2_0()
        : true;
}

function plugin_compatible_with_this_version_indexnow($pi_name)
{
    global $_CONF, $_DB_dbms;

    if (!function_exists('COM_newTemplate') ||
        !function_exists('PLG_itemSaved') ||
        !function_exists('SEC_createToken') ||
        !function_exists('curl_init')) {
        return false;
    }

    if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        return false;
    }

    if (defined('VERSION') && version_compare(VERSION, '2.1.1', '<')) {
        return false;
    }

    $dbFile = $_CONF['path'] . 'plugins/' . $pi_name . '/sql/' . $_DB_dbms . '_install.php';
    if (!file_exists($dbFile)) {
        return false;
    }

    return true;
}

?>
