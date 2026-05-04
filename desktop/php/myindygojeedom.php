<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
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
"use strict";

(function () {
    var _eqType = 'myindygojeedom';

    function openEqLogic(id) {
        jeedom.eqLogic.get({
            id: id,
            error: function (err) { notify('Erreur', err.message, 'danger'); },
            success: function (eq) {
                $('.eqLogic').setValues(eq, '.eqLogicAttr');
                modifyWithoutSave = false;
                $('.eqLogicThumbnailDisplay').hide();
                $('.eqLogic').show();
            }
        });
    }

    function loadList() {
        jeedom.eqLogic.getAll({
            type: _eqType,
            error: function (err) { notify('Erreur', err.message, 'danger'); },
            success: function (eqLogics) {
                if (!eqLogics || eqLogics.length === 0) {
                    $('#div_resumeEqLogic').html(
                        '<br/><br/><center><i class="fas fa-swimming-pool" style="font-size:3em;color:#aaa"></i><br/><br/><span style="color:#aaa">{{Aucune piscine. Cliquez sur Ajouter.}}</span></center>'
                    );
                    return;
                }
                var html = '';
                for (var i = 0; i < eqLogics.length; i++) {
                    var eq = eqLogics[i];
                    html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">';
                    html += '<div class="eqLogicDisplayCard cursor" data-eqlogic_id="' + eq.id + '">';
                    html += '<img src="plugins/' + _eqType + '/desktop/img/' + _eqType + '_icon.png" onerror="this.src=\'core/img/eqlogic.png\'" style="max-width:80px"/>';
                    html += '<br/><span class="name">' + eq.name + '</span>';
                    if (eq.isEnable == 0) html += '<br/><span class="label label-danger">{{Désactivé}}</span>';
                    html += '</div></div>';
                }
                $('#div_resumeEqLogic').html(html);
                $('.eqLogicDisplayCard').off('click').on('click', function () {
                    openEqLogic($(this).data('eqlogic_id'));
                });
            }
        });
    }

    function init() {
        loadList();

        $(document).off('click', '#bt_addPiscine').on('click', '#bt_addPiscine', function () {
            bootbox.prompt('{{Nom de la piscine ?}}', function (result) {
                if (result === null || result.trim() === '') return;
                jeedom.eqLogic.save({
                    type: _eqType,
                    eqLogics: [{ name: result.trim(), eqType_name: _eqType, isEnable: 1, isVisible: 1 }],
                    error: function (err) { notify('Erreur', err.message, 'danger'); },
                    success: function (data) {
                        var eq = Array.isArray(data) ? data[0] : data;
                        openEqLogic(eq.id);
                    }
                });
            });
        });

        $(document).off('click', '#bt_backList').on('click', '#bt_backList', function () {
            $('.eqLogic').hide();
            $('.eqLogicThumbnailDisplay').show();
            loadList();
        });

        $(document).off('click', '#bt_saveEq').on('click', '#bt_saveEq', function () {
            var eq = $('.eqLogic').getValues('.eqLogicAttr');
            eq.eqLogic.eqType_name = _eqType;
            jeedom.eqLogic.save({
                type: _eqType,
                eqLogics: [eq.eqLogic],
                error: function (err) { notify('Erreur', err.message, 'danger'); },
                success: function (data) {
                    modifyWithoutSave = false;
                    notify('Info', '{{Sauvegarde réussie}}', 'success');
                    var saved = Array.isArray(data) ? data[0] : data;
                    openEqLogic(saved.id);
                }
            });
        });

        $(document).off('click', '#bt_removeEq').on('click', '#bt_removeEq', function () {
            var id = $('.eqLogic .eqLogicAttr[data-l1key=id]').val();
            bootbox.confirm('{{Supprimer cet équipement ?}}', function (result) {
                if (!result) return;
                jeedom.eqLogic.remove({
                    id: id,
                    error: function (err) { notify('Erreur', err.message, 'danger'); },
                    success: function () {
                        $('.eqLogic').hide();
                        $('.eqLogicThumbnailDisplay').show();
                        loadList();
                    }
                });
            });
        });

        $(document).off('click', '#bt_testIndygo').on('click', '#bt_testIndygo', function () {
            var id = $('.eqLogic .eqLogicAttr[data-l1key=id]').val();
            if (!id) { notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }
            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Test en cours…}}');
            $.ajax({
                type: 'POST', url: 'plugins/' + _eqType + '/core/php/jeeIndygo.ajax.php',
                data: { action: 'testConnection', id: id }, dataType: 'json',
                error: function (req, status, err) { handleAjaxError(req, status, err); },
                success: function (data) {
                    $('#span_indygo_result').html(data.state === 'ok'
                        ? '<span class="label label-success"><i class="fas fa-check"></i> {{Connexion réussie !}}</span>'
                        : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                    );
                }
            });
        });

        $(document).off('click', '#bt_syncIndygo').on('click', '#bt_syncIndygo', function () {
            var id = $('.eqLogic .eqLogicAttr[data-l1key=id]').val();
            if (!id) { notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }
            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Synchronisation…}}');
            $.ajax({
                type: 'POST', url: 'plugins/' + _eqType + '/core/php/jeeIndygo.ajax.php',
                data: { action: 'sync', id: id }, dataType: 'json',
                error: function (req, status, err) { handleAjaxError(req, status, err); },
                success: function (data) {
                    $('#span_indygo_result').html(data.state === 'ok'
                        ? '<span class="label label-success"><i class="fas fa-check"></i> {{Synchronisation réussie !}}</span>'
                        : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                    );
                    if (data.state === 'ok') openEqLogic(id);
                }
            });
        });
    }

    $(document).ready(function () { init(); });
})();
</script>
