<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    // ─── sync : rafraîchit un équipement à la demande ─────────────────
    if (init('action') == 'sync') {
        $eqLogic = myindygojeedom::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception('{{Équipement introuvable}}');
        }
        $eqLogic->pull();
        ajax::success();
    }

    // ─── testConnection : vérifie email/mdp avant de sauvegarder ──────
    if (init('action') == 'testConnection') {
        $eqLogic = myindygojeedom::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception('{{Équipement introuvable}}');
        }
        // On force une re-authentification
        $eqLogic->setConfiguration('access_token', '');
        $eqLogic->setConfiguration('token_expiry', 0);
        $eqLogic->pull();
        ajax::success('{{Connexion réussie}}');
    }

    throw new Exception('{{Action non trouvée : }}' . init('action'));

} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
