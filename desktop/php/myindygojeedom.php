<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
/* Inclusion explicite du JS du plugin */
include_file('desktop', 'myindygojeedom', 'js', 'myindygojeedom');
?>

<div class="row row-overflow">

    <!-- ─── Liste des piscines ─────────────────────────────────────── -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <div class="row">
            <div class="col-xs-12">
                <a class="btn btn-default" id="bt_addPiscine">
                    <i class="fas fa-plus-circle"></i> {{Ajouter une piscine}}
                </a>
            </div>
        </div>
        <div class="row" id="div_resumeEqLogic"></div>
    </div>

    <!-- ─── Détail d'une piscine ──────────────────────────────────── -->
    <div class="col-xs-12 eqLogic" style="display:none;">

        <div class="input-group pull-right" style="display:inline-flex">
            <a class="btn btn-sm btn-default roundedLeft" id="bt_backList">
                <i class="fas fa-arrow-left"></i>
            </a>
            <a class="btn btn-sm btn-success" id="bt_saveEq">
                <i class="fas fa-check-circle"></i> {{Sauvegarder}}
            </a>
            <a class="btn btn-sm btn-danger roundedRight" id="bt_removeEq">
                <i class="fas fa-minus-circle"></i> {{Supprimer}}
            </a>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#" data-toggle="tab" data-target="#eqlogictab">
                    <i class="fas fa-tachometer-alt"></i> {{Équipement}}
                </a>
            </li>
            <li role="presentation">
                <a href="#" data-toggle="tab" data-target="#commandtab">
                    <i class="fas fa-list"></i> {{Commandes}}
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- Onglet Équipement -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <div class="col-sm-8">
                    <form class="form-horizontal">
                        <fieldset>
                            <?php include_file('desktop', 'eqLogic', 'inc', 'core'); ?>

                            <hr/>
                            <legend><i class="fas fa-lock"></i> {{Connexion MyIndygo}}</legend>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Email}} <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="email"
                                           type="email" placeholder="votre.email@exemple.com"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Mot de passe}} <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="password"
                                           type="password" placeholder="●●●●●●●●"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Pool ID}} <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="pool_id"
                                           placeholder="ID visible dans l'URL myindygo.com/pools/&lt;ID&gt;"/>
                                </div>
                            </div>

                            <hr/>

                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <a class="btn btn-info" id="bt_testIndygo">
                                        <i class="fas fa-plug"></i> {{Tester la connexion}}
                                    </a>
                                    &nbsp;
                                    <a class="btn btn-default" id="bt_syncIndygo">
                                        <i class="fas fa-sync"></i> {{Synchroniser}}
                                    </a>
                                </div>
                            </div>

                            <div class="form-group" id="div_indygo_result" style="display:none;">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <span id="span_indygo_result"></span>
                                </div>
                            </div>

                        </fieldset>
                    </form>
                </div>
            </div>

            <!-- Onglet Commandes -->
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <?php include_file('desktop', 'cmd', 'inc', 'core'); ?>
            </div>

        </div>
    </div>
</div>

<script>
$(function () {
    if (typeof myindygojeedom !== 'undefined') {
        myindygojeedom.init();
    }
    /* Fallback direct si le JS externe n'est pas encore chargé */
    $('#bt_addPiscine').off('click').on('click', function () {
        bootbox.prompt('{{Nom de la piscine ?}}', function (result) {
            if (!result || result.trim() === '') return;
            jeedom.eqLogic.save({
                type: 'myindygojeedom',
                eqLogics: [{name: result.trim(), eqType_name: 'myindygojeedom', isEnable: 1, isVisible: 1}],
                error: function (err) { notify('Erreur', err.message, 'danger'); },
                success: function (data) {
                    var eq = Array.isArray(data) ? data[0] : data;
                    jeedom.eqLogic.get({
                        id: eq.id,
                        success: function (eqLogic) {
                            $('.eqLogic').setValues(eqLogic, '.eqLogicAttr');
                            modifyWithoutSave = false;
                            $('.eqLogicThumbnailDisplay').hide();
                            $('.eqLogic').show();
                        }
                    });
                }
            });
        });
    });
});
</script>
