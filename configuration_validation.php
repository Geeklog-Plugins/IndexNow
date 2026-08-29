<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | configuration_validation.php                                              |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'configuration_validation.php') !== false) {
    die('This file cannot be used on its own!');
}

$_CONF_VALIDATE['indexnow']['debug_mode'] = array('rule' => 'boolean');

?>
