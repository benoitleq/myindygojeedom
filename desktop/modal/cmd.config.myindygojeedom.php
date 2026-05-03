<?php
/* Modal de configuration d'une commande — affiché dans l'onglet Commandes */
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<!-- Aucune configuration spécifique requise pour les commandes de ce plugin.
     Les commandes sont générées automatiquement par pull(). -->
<div class="form-group">
    <label class="col-sm-4 control-label">{{Note}}</label>
    <div class="col-sm-6">
        <span class="text-info">
            <i class="fas fa-info-circle"></i>
            {{Les commandes sont créées automatiquement lors de la synchronisation.}}
        </span>
    </div>
</div>
