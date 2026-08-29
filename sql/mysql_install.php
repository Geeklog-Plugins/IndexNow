<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.2.0                                                     |
// +---------------------------------------------------------------------------+
// | sql/mysql_install.php                                                     |
// |                                                                           |
// | Installation SQL for submission history.                                  |
// +---------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), 'mysql_install.php') !== false) {
    die('This file cannot be used on its own!');
}

$_SQL[] = "CREATE TABLE {$_TABLES['indexnow_submissions']} (
  `submission_id` int(10) unsigned NOT NULL auto_increment,
  `item_type` varchar(64) NOT NULL default '',
  `item_id` varchar(255) NOT NULL default '',
  `item_subtype` varchar(64) NOT NULL default '',
  `event` varchar(16) NOT NULL default '',
  `url` text NOT NULL,
  `submitted` tinyint(1) unsigned NOT NULL default '0',
  `http_code` smallint(5) unsigned NOT NULL default '0',
  `status` varchar(32) NOT NULL default '',
  `message` text NOT NULL,
  `submitted_at` datetime NOT NULL,
  PRIMARY KEY (`submission_id`),
  KEY `submitted_at` (`submitted_at`),
  KEY `status` (`status`),
  KEY `item_lookup` (`item_type`,`item_id`(100))
) ENGINE=MyISAM;";

?>
