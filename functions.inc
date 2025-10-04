<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin 1.0.1                                                     |
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
 * Add the IndexNow plugin to the Command and Control block on moderation.php.
 *
 * @return array Array containing (plugin name, admin url, url of plugin icon) or an empty string.
 */
function plugin_cclabel_indexnow() {
    global $_CONF, $LANG_indexnow;

    if (SEC_hasRights('indexnow.admin')) {
        return array(
            $LANG_indexnow['plugin_name'], // Nom du plugin
            $_CONF['site_admin_url'] . '/plugins/indexnow/index.php', // Lien vers la page admin
            $_CONF['site_admin_url'] . '/plugins/indexnow/indexnow.png' // Icône du plugin (optionnel, remplacez par le chemin correct)
        );
    }

    return ''; // Si l'utilisateur n'a pas les droits, ne rien afficher
}


/**
 * Add the IndexNow plugin to the Admin menu.
 *
 * @return array Array containing (plugin name, plugin admin url, # of items in plugin or empty string).
 */
function plugin_getadminoption_indexnow() {
    global $_CONF, $LANG_indexnow;

    if (SEC_hasRights('indexnow.admin')) {
        return array(
            $LANG_indexnow['plugin_name'], // Nom du plugin
            $_CONF['site_admin_url'] . '/plugins/indexnow/index.php', // URL de la page admin
            '' // Nombre d'éléments spécifiques ou vide si non applicable
        );
    }

    return ''; // Si l'utilisateur n'a pas les droits, ne rien afficher
}



/**
 * Handle the event when an article or static page is saved.
 * The function checks if the content is a draft or scheduled for future publication,
 * and sends the URL to the IndexNow API if it's eligible.
 *
 * @param string $id   The ID of the saved content.
 * @param string $type The type of content (article, staticpage or topic).
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
            $url = $_CONF['site_url'] . "/article.php/$id";
        } else {
            $url = $_CONF['site_url'] . "/article.php?story=$id";
        }

    } elseif ($type === 'staticpages') {
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

    } elseif ($type === 'topic') {
        // Topics never use rewritten URLs in Geeklog
        $url = $_CONF['site_url'] . "/index.php?topic=$id";

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

/**
 * Submit articles to IndexNow by descending date order.
 * This function retrieves articles sorted by date in descending order
 * and submits their URLs to the IndexNow API.
 *
 * @param int $limit  Number of articles to process in one batch.
 * @param int $offset Offset to start retrieving articles.
 * @return int        Number of articles submitted.
 */
function submit_articles_by_date_desc_to_indexnow($limit = 1, $offset = 0) {
    global $_CONF, $_TABLES;

    $urls = [];

    // Requête SQL : trie par date décroissante et utilise offset pour sélectionner le bon lot
    $sql = "SELECT sid, date 
            FROM {$_TABLES['stories']} 
            WHERE draft_flag = 0 
            AND UNIX_TIMESTAMP(date) <= UNIX_TIMESTAMP(NOW()) 
            ORDER BY date DESC 
            LIMIT $offset, $limit";
    $result = DB_query($sql);

    // Récupérer les URLs des articles
    while ($A = DB_fetchArray($result)) {
        // Construire l'URL de l'article
        if ($_CONF['url_rewrite']) {
            $urls[] = $_CONF['site_url'] . "/article.php/" . $A['sid'];
        } else {
            $urls[] = $_CONF['site_url'] . "/article.php?story=" . $A['sid'];
        }
    }

    // Soumettre chaque URL à IndexNow
    foreach ($urls as $url) {
        send_to_indexnow($url);
        usleep(500000); // Pause de 500 ms pour limiter les requêtes
    }

    // Enregistrer dans les logs
    $submitted_count = count($urls);
    COM_errorLog("IndexNow: Submitted $submitted_count articles (offset: $offset, limit: $limit).");

    return $submitted_count;
}

function get_total_articles_to_submit() {
    global $_TABLES;

    // Requête pour compter les articles non soumis (draft_flag = 0)
    $sql = "SELECT COUNT(*) AS total 
            FROM {$_TABLES['stories']} 
            WHERE draft_flag = 0 
            AND UNIX_TIMESTAMP(date) <= UNIX_TIMESTAMP(NOW())";
    $result = DB_query($sql);
    $A = DB_fetchArray($result);

    return intval($A['total']);
}

/**
 * Geeklog scheduled task for IndexNow.
 * Submits all new stories and staticpages created in the last 24 hours.
 */
function plugin_runScheduledTask_indexnow() {
    global $_TABLES, $_CONF;

    $urls = [];

    // ---- Stories published in the last 24 hours ----
    $sql = "SELECT sid FROM {$_TABLES['stories']}
            WHERE draft_flag = 0
            AND UNIX_TIMESTAMP(date) >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)";
    $result = DB_query($sql);

    while ($A = DB_fetchArray($result)) {
        if ($_CONF['url_rewrite']) {
            $urls[] = $_CONF['site_url'] . "/article.php/" . $A['sid'];
        } else {
            $urls[] = $_CONF['site_url'] . "/article.php?story=" . $A['sid'];
        }
    }

    // ---- Staticpages created in the last 24 hours ----
    $sql = "SELECT sp_id FROM {$_TABLES['staticpage']}
            WHERE draft_flag = 0
            AND UNIX_TIMESTAMP(created) >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)";
    $result = DB_query($sql);

    while ($A = DB_fetchArray($result)) {
        if ($_CONF['url_rewrite']) {
            $urls[] = $_CONF['site_url'] . "/page.php/" . $A['sp_id'];
        } else {
            $urls[] = $_CONF['site_url'] . "/page.php?page=" . $A['sp_id'];
        }
    }

    // ---- Submit each URL using the existing function ----
    foreach ($urls as $url) {
        send_to_indexnow($url);
        usleep(500000); // 0.5s pause to avoid flooding
    }

    COM_errorLog("IndexNow scheduled task: submitted " . count($urls) . " URLs from the last 24h.", 1);

    return true;
}

?>
