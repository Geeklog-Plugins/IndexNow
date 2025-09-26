<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | IndexNow Plugin                                                           |
// +---------------------------------------------------------------------------+
// | admin/index.php                                                           |
// |                                                                           |
// | Plugin administration page                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2024                                                       |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

// Vérifiez les droits d'administration
if (!SEC_hasRights('indexnow.admin')) {
    $display = COM_siteHeader('menu', $LANG_ACCESS['accessdenied'])
             . COM_showMessageText($LANG_ACCESS['plugin_denied_msg'], $LANG_ACCESS['accessdenied'])
             . COM_siteFooter();

    COM_accessLog("User {$_USER['username']} tried to illegally access the IndexNow plugin administration screen.");
    echo $display;
    exit;
}

// Initialisation de l'offset
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
$batch_size = 20; // Taille d'un lot (1 pour les tests)
$total_articles = 0; // Initialisation pour le total d'articles
$articles_remaining = 0; // Initialisation pour les articles restants

// Calculer le total d'articles disponibles pour soumission
require_once $_CONF['path'] . 'plugins/indexnow/functions.inc';
$total_articles = get_total_articles_to_submit();
$articles_remaining = $total_articles - $offset;

// Gestion du formulaire
$feedback = '';
$submitted_range = ''; // Variable pour afficher la plage d'articles soumis
$next_action_message = ''; // Message pour indiquer ce que le bouton va faire
$next_offset = $offset; // Calcul dynamique de l'offset suivant après soumission

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_articles'])) {
    try {
        // Soumission des articles du lot actuel
        $submitted_count = submit_articles_by_date_desc_to_indexnow($batch_size, $offset);

        if ($submitted_count > 0) {
            // Calcul de la plage soumise
            $start_range = $offset + 1;
            $end_range = $offset + $submitted_count;

            // Feedback utilisateur
            $feedback = '<p style="color: green;">' . sprintf($LANG_indexnow['submit_success'], $submitted_count) . '</p>';
            $submitted_range = sprintf($LANG_indexnow['articles_submitted'], $start_range, $end_range);

            // Mise à jour de l'offset et du nombre d'articles restants
            $next_offset = $offset + $submitted_count;
            $articles_remaining = $total_articles - $next_offset;
        } else {
            $feedback = '<p style="color: red;">' . $LANG_indexnow['no_articles_to_submit'] . '</p>';
        }
    } catch (Exception $e) {
        $feedback = '<p style="color: red;">' . $LANG_indexnow['submit_error'] . ' ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// Préparer le message pour le prochain lot
if ($offset === 0 && empty($feedback)) {
    $next_action_message = sprintf($LANG_indexnow['submit_first_batch'], $batch_size);
} else if ($articles_remaining > 0) {
    $next_action_message = sprintf($LANG_indexnow['submit_next_batch_message'], $batch_size, $articles_remaining);
} else {
    $next_action_message = $LANG_indexnow['no_articles_remaining'];
}

// Génération de la page
$display = COM_siteHeader('menu', $LANG_indexnow['plugin_name']);
$display .= '<h1>' . $LANG_indexnow['plugin_name'] . '</h1>';
$display .= '<p>' . sprintf($LANG_indexnow['total_articles'], $total_articles) . '</p>';

if (!empty($feedback)) {
    $display .= $feedback;
}
if (!empty($submitted_range)) {
    $display .= '<p>' . $submitted_range . '</p>';
}

$display .= '<p>' . $next_action_message . '</p>';
$display .= <<<HTML
<form method="post" action="" onsubmit="submitLoadingMessage()" class="uk-form">
    <input type="hidden" name="offset" value="{$next_offset}">
    <input type="submit" id="submit-button" name="submit_articles" value="{$LANG_indexnow['submit_to_bing']}">
</form>
<p id="loading-message" style="display:none;">{$LANG_indexnow['loading_message']}</p>
<script>
    function submitLoadingMessage() {
        document.getElementById('submit-button').style.display = 'none';
        document.getElementById('loading-message').style.display = 'block';
    }
</script>
HTML;

$display .= COM_siteFooter();

echo $display;

?>
