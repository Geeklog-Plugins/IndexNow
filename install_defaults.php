<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | indexnow plugin 1.0.0                                                     |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | Initial Installation Defaults used when loading the online configuration  |
// | records. These settings are only used during the initial installation     |
// | and not referenced any more once the plugin is installed.                 |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2024 by the following authors:                              |
// |                                                                           |
// | Authors: Ben        - ben AT geeklog DOT fr                               |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

// Prevent this file from being accessed directly
if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file cannot be used on its own!');
}

/*
 * IndexNow default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and are not referenced anymore once the plugin is installed.
 */

// Initialize default values
global $_INDEXNOW_DEFAULT;
$_INDEXNOW_DEFAULT = array();
$_INDEXNOW_DEFAULT['indexnow_key'] = '';  // Default empty key

/**
 * Initialize IndexNow plugin configuration
 * This function checks if the configuration group exists, and if not,
 * it creates the necessary configuration group and fields for the plugin.
 *
 * @return bool True if the configuration is initialized, false otherwise.
 */
function plugin_initconfig_indexnow()
{
    global $_INDEXNOW_DEFAULT;

    $c = config::get_instance();

    // Check if the 'indexnow' group exists
    if (!$c->group_exists('indexnow')) {

        // Create the main subgroup #0
        $c->add('sg_0', NULL, 'subgroup', 0, 0, NULL, 0, true, 'indexnow');

        // Create the fieldset #1 within subgroup #0
        $c->add('fs_01', NULL, 'fieldset', 0, 0, NULL, 0, true, 'indexnow');

        // Add the IndexNow key field in the configuration
        $c->add('indexnow_key', $_INDEXNOW_DEFAULT['indexnow_key'], 'text', 0, 0, 0, 10, true, 'indexnow');
    } else {
        // Log if the configuration group already exists
        COM_errorLog("Group 'indexnow' already exists.");
    }

    return true;
}


?>