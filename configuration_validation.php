<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.1                                                     |
// +---------------------------------------------------------------------------+
// | configuration_validation.php                                              |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'configuration_validation.php') !== false) {
    die('This file cannot be used on its own!');
}

$_CONF_VALIDATE['indexnow']['indexnow_key'] = array('rule' => 'stringOrEmpty');
$_CONF_VALIDATE['indexnow']['debug_mode'] = array('rule' => 'boolean');
$_CONF_VALIDATE['indexnow']['history_retention_days'] = array(
    'rule' => array('inList', array(0, 30, 90, 180, 365), false)
);

?>
