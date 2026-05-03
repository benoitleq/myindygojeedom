<?php
/* Script d'installation / mise à jour / suppression du plugin MyIndygo */

function myindygojeedom_install() {
    // Plugin PHP pur, aucune dépendance système à installer
    log::add('myindygojeedom', 'info', 'Installation du plugin MyIndygo OK');
}

function myindygojeedom_update() {
    log::add('myindygojeedom', 'info', 'Mise à jour du plugin MyIndygo OK');
}

function myindygojeedom_remove() {
    // Supprime les équipements restants proprement
    foreach (myindygojeedom::byType('myindygojeedom') as $eqLogic) {
        $eqLogic->remove();
    }
    log::add('myindygojeedom', 'info', 'Suppression du plugin MyIndygo OK');
}
