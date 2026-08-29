<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file cannot be used on its own!');
}

global $_INDEXNOW_DEFAULT;
$_INDEXNOW_DEFAULT = array();
$_INDEXNOW_DEFAULT['indexnow_key'] = '';
$_INDEXNOW_DEFAULT['debug_mode'] = 0;
$_INDEXNOW_DEFAULT['history_retention_days'] = 90;

function plugin_initconfig_indexnow()
{
    global $_INDEXNOW_DEFAULT;

    $c = config::get_instance();

    if (!$c->group_exists('indexnow')) {
        $c->add('sg_0', NULL, 'subgroup', 0, 0, NULL, 0, true, 'indexnow', 0);
        $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, 'indexnow', 0);
        $c->add('fs_01', NULL, 'fieldset', 0, 0, NULL, 0, true, 'indexnow', 0);

        $c->add('indexnow_key', $_INDEXNOW_DEFAULT['indexnow_key'], 'text', 0, 0, 0, 10, true, 'indexnow', 0);
        $c->add('debug_mode', $_INDEXNOW_DEFAULT['debug_mode'], 'select', 0, 0, 0, 20, true, 'indexnow', 0);
        $c->add('history_retention_days', $_INDEXNOW_DEFAULT['history_retention_days'], 'select', 0, 0, 1, 30, true, 'indexnow', 0);
    } else {
        COM_errorLog("Group 'indexnow' already exists.");
    }

    return true;
}

?>
