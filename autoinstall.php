<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.0                                                       |
// +---------------------------------------------------------------------------+
// | autoinstall.php                                                           |
// |                                                                           |
// | This file provides helper functions for the automatic plugin install.     |
// +---------------------------------------------------------------------------+

/**
* @package indexnow
*/

require_once('functions.inc');

/**
 * Plugin autoinstall function
 *
 * This function sets up the necessary information for the automatic installation
 * of the IndexNow plugin, including groups, features, and mappings.
 *
 * @param string $pi_name Plugin name
 * @return array          Plugin information needed for installation
 */
function plugin_autoinstall_indexnow($pi_name)
{
    $pi_name         = 'indexnow';
    $pi_display_name = 'IndexNow';
    $pi_admin        = $pi_display_name . ' Admin';

    $info = array(
        'pi_name'         => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version'      => '1.0.0',
        'pi_gl_version'   => '1.8.0',  // Minimum Geeklog version required
        'pi_homepage'     => 'https://geeklog.fr'
    );

    $groups = array(
        $pi_admin => 'Users in this group can administer the ' . $pi_display_name . ' plugin'
    );

    $features = array(
        $pi_name . '.admin'   => 'Full access to ' . $pi_display_name . ' plugin'
    );

    $mappings = array(
        $pi_name . '.admin'     => array($pi_admin)
    );

    $tables = array();  // No additional database tables are created for this plugin

    $inst_parms = array(
        'info'      => $info,
        'groups'    => $groups,
        'features'  => $features,
        'mappings'  => $mappings,
        'tables'    => $tables
    );

    return $inst_parms;
}

/**
 * Load the plugin configuration during installation
 *
 * This function loads the default configuration values for the plugin during installation.
 *
 * @param string $pi_name Plugin name
 * @return bool           True on successful loading of configuration
 */
function plugin_load_configuration_indexnow($pi_name)
{
    global $_CONF;

    // Load the configuration file
    $base_path = $_CONF['path'] . 'plugins/' . $pi_name . '/';
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    
    require_once $base_path . 'install_defaults.php';

    return plugin_initconfig_indexnow();
}

/**
 * Post-installation function for the plugin
 *
 * This function is called after the plugin has been installed, allowing for additional
 * setup tasks if necessary.
 *
 * @param string $pi_name Plugin name
 * @return boolean        True to continue installation, false if an error occurs
 */
function plugin_postinstall_indexnow($pi_name) {
    global $_CONF, $_TABLES;

    // Additional post-install tasks can be added here

    return true;
}

/**
 * Check if the plugin is compatible with the current Geeklog version
 *
 * This function ensures that the plugin is compatible with the installed
 * version of Geeklog.
 *
 * @param string $pi_name Plugin name
 * @return boolean        True if compatible, false if not
 */
function plugin_compatible_with_this_version_indexnow($pi_name)
{
    if (!function_exists('COM_newTemplate')) return false;

    // Additional compatibility checks can be added here if necessary

    return true;
}

?>
