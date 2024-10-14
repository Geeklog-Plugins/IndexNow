<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | hello Plugin 1.0.0                                                        |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// |                                                                           |
// | This file does two things: 1) it implements the necessary Geeklog Plugin  |
// | API method and 2) implements all the common code needed by the hello      |
// | plugin' PHP files.                                                        |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2024 by the following authors:                              |
// |                                                                           |
// | Authors: ::Ben - ben AT geeklog DOT fr                                    |
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
if (strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die ('This file cannot be used on its own.');
}

global $_CONF, $_TABLES;

// Path to the plugin
$plugin_path = $_CONF['path'] . 'plugins/indexnow/';

// Set language file or fallback to English by default
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

// Load plugin configuration if not already loaded
if (!isset($_INDEXNOW_CONF['indexnow_key'])) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    
    $indexnow_config = config::get_instance();
    $_INDEXNOW_CONF = $indexnow_config->get_config('indexnow');
}

/**
 * Automatic uninstall function for the IndexNow plugin.
 * This function returns the configuration necessary for removing the plugin.
 *
 * @return array Plugin information for uninstallation.
 */
function plugin_autouninstall_indexnow()
{
    $out = array (
        'tables' => array(),
        'groups' => array('IndexNow Admin'),
        'features' => array('indexnow.admin'),
        'php_blocks' => array(),
        'vars' => array()
    );

    return $out;
}

/**
 * Handle the event when an article or static page is saved.
 * The function checks if the content is a draft or scheduled for future publication,
 * and sends the URL to the IndexNow API if it's eligible.
 *
 * @param string $id   The ID of the saved content.
 * @param string $type The type of content (article or staticpage).
 */
function plugin_itemsaved_indexnow($id, $type) {
    global $_CONF, $_TABLES;

    $url = '';  // Initialize the variable to store the URL

    // Check the type of saved content
    if ($type === 'article') {
        // Query to check if the article is in draft mode or scheduled for future publication
        $sql = "SELECT draft_flag, date FROM {$_TABLES['stories']} WHERE sid = '$id'";
        $result = DB_query($sql);
        $A = DB_fetchArray($result);

        if ($A['draft_flag'] == 1) {
            // If the article is in draft mode, do not submit the URL
            return;
        }

        // Check if the publication date is in the future
        if (strtotime($A['date']) > time()) {
            // If the publication date is in the future, do not submit the URL
            return;
        }

        // If the article is not a draft and the date is valid, construct the URL
        if ($_CONF['url_rewrite']) {
            $url = $_CONF['site_url'] . "/article/$id";
        } else {
            $url = $_CONF['site_url'] . "/article.php?story=$id";
        }

    } elseif ($type === 'staticpage') {
        // Query to check if the static page is in draft mode
        $sql = "SELECT draft_flag FROM {$_TABLES['staticpage']} WHERE sp_id = '$id'";
        $result = DB_query($sql);
        $A = DB_fetchArray($result);

        if ($A['draft_flag'] == 1) {
            // If the static page is in draft mode, do not submit the URL
            return;
        }

        // If the static page is not a draft, construct the URL
        if ($_CONF['url_rewrite']) {
            $url = $_CONF['site_url'] . "/staticpages/index.php/$id";
        } else {
            $url = $_CONF['site_url'] . "/staticpages/index.php?page=$id";
        }

    } else {
        // If the content type is not supported, log an error
        COM_errorLog("IndexNow Plugin - Unsupported content type: $type for ID $id");
        return;
    }

    // Send the URL to the IndexNow API
    send_to_indexnow($url);
}

/**
 * Send a URL to the IndexNow API.
 * This function submits the URL using the IndexNow protocol and logs the result.
 *
 * @param string $url The URL to submit to IndexNow.
 * @return mixed The response from the IndexNow API.
 */
function send_to_indexnow($url) {
    global $_CONF, $_INDEXNOW_CONF;

    // Retrieve the key from the configuration
    $key = $_INDEXNOW_CONF['indexnow_key'];
    $keyLocation = $_CONF['site_url'] . '/' . $key . '.txt';

    // If the key is empty or not defined, stop the function
    if (empty($key)) {
        COM_errorLog("IndexNow: Key not defined or empty. Submission aborted.");
        return false;
    }

    // Encode the URL before submitting it
    $encodedUrl = rawurlencode($url);

    // Construct the endpoint with the key and the encoded URL, including keyLocation
    $endpoint = "https://www.bing.com/indexnow?url=$encodedUrl&key=$key&keyLocation=$keyLocation";

    // Perform the GET request with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request and retrieve the response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log only in case of failure
    if ($httpCode != 200) {
        COM_errorLog("IndexNow: Failed to submit URL $url - HTTP Code: $httpCode | Key: $key | keyLocation: $keyLocation");
    } else {
        COM_errorLog("IndexNow: Successfully submitted URL $url");
    }

    return $response;
}

?>
