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

                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Nom}} <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control" data-l1key="name"
                                           placeholder="Ma piscine" id="input_eqName"/>
                                </div>
                            </div>

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
    var _currentId = null;
    var _currentName = null;

    function _notify(title, msg, type) {
        var cls = type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger';
        var $n = $('<div class="alert alert-' + cls + '" style="position:fixed;top:60px;right:20px;z-index:9999;min-width:280px;max-width:420px;box-shadow:0 2px 8px rgba(0,0,0,.3)">' +
            '<strong>' + title + '</strong> ' + msg + '</div>');
        $('body').append($n);
        setTimeout(function () { $n.fadeOut(400, function () { $n.remove(); }); }, 4000);
    }

    function ajaxErr(req) {
        var msg = (req && req.responseJSON && req.responseJSON.result) ? req.responseJSON.result
                : (req && req.responseText) ? req.responseText.substring(0, 200) : 'Erreur réseau';
        _notify('Erreur', msg, 'danger');
    }

    function openEqLogic(id) {
        _currentId = id;
        jeedom.eqLogic.byId({
            id: id,
            error: function (err) { _notify('Erreur', err.message || JSON.stringify(err), 'danger'); },
            success: function (eq) {
                _currentName = eq.name || '';
                $('.eqLogic').setValues(eq, '.eqLogicAttr');
                $('#input_eqName').val(_currentName);
                modifyWithoutSave = false;
                $('.eqLogicThumbnailDisplay').hide();
                $('.eqLogic').show();
            }
        });
    }

    function loadList() {
        $.ajax({
            type: 'POST', url: 'plugins/' + _eqType + '/core/php/jeeIndygo.ajax.php',
            data: {action: 'listAll'}, dataType: 'json',
            error: ajaxErr,
            success: function (data) {
                if (data.state !== 'ok') { _notify('Erreur', data.result, 'danger'); return; }
                var eqLogics = data.result;
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
                $.ajax({
                    type: 'POST', url: 'core/ajax/eqLogic.ajax.php',
                    data: {action: 'save', eqLogic: JSON.stringify({name: result.trim(), eqType_name: _eqType, isEnable: 1, isVisible: 1})},
                    dataType: 'json', error: ajaxErr,
                    success: function (data) {
                        if (data.state !== 'ok') { _notify('Erreur', data.result, 'danger'); return; }
                        openEqLogic(data.result.id || data.result);
                    }
                });
            });
        });

        $(document).off('click', '#bt_backList').on('click', '#bt_backList', function () {
            _currentId = null;
            $('.eqLogic').hide();
            $('.eqLogicThumbnailDisplay').show();
            loadList();
        });

        $(document).off('click', '#bt_saveEq').on('click', '#bt_saveEq', function () {
            var vals = $('.eqLogic').getValues('.eqLogicAttr');
            var firstVal = Array.isArray(vals) ? (vals[0] || {}) : vals;
            var name = $('#input_eqName').val() || $('[data-l1key="name"].eqLogicAttr').val() || _currentName || '';
            if (!name.trim()) { _notify('Erreur', '{{Le nom ne peut pas être vide}}', 'danger'); return; }
            var eq = {
                id: _currentId || '',
                name: name,
                eqType_name: _eqType,
                isEnable: $('[data-l1key="isEnable"].eqLogicAttr').val() || 1,
                isVisible: $('[data-l1key="isVisible"].eqLogicAttr').val() || 1,
                configuration: JSON.stringify(firstVal.configuration || {})
            };
            $.ajax({
                type: 'POST', url: 'core/ajax/eqLogic.ajax.php',
                data: {action: 'save', eqLogic: JSON.stringify(eq)},
                dataType: 'json', error: ajaxErr,
                success: function (data) {
                    if (data.state !== 'ok') { _notify('Erreur', data.result, 'danger'); return; }
                    modifyWithoutSave = false;
                    _notify('OK', '{{Sauvegarde réussie}}', 'success');
                    var savedId = (data.result && data.result.id) ? data.result.id : (data.result || _currentId);
                    openEqLogic(savedId);
                }
            });
        });

        $(document).off('click', '#bt_removeEq').on('click', '#bt_removeEq', function () {
            bootbox.confirm('{{Supprimer cet équipement ?}}', function (result) {
                if (!result) return;
                jeedom.eqLogic.remove({
                    id: _currentId,
                    error: function (err) { _notify('Erreur', err.message || JSON.stringify(err), 'danger'); },
                    success: function () {
                        _currentId = null;
                        _currentName = null;
                        $('.eqLogic').hide();
                        $('.eqLogicThumbnailDisplay').show();
                        loadList();
                    }
                });
            });
        });

        $(document).off('click', '#bt_testIndygo').on('click', '#bt_testIndygo', function () {
            if (!_currentId) { _notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }
            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Test en cours…}}');
            $.ajax({
                type: 'POST', url: 'plugins/' + _eqType + '/core/php/jeeIndygo.ajax.php',
                data: {action: 'testConnection', id: _currentId}, dataType: 'json',
                error: ajaxErr,
                success: function (data) {
                    $('#span_indygo_result').html(data.state === 'ok'
                        ? '<span class="label label-success"><i class="fas fa-check"></i> {{Connexion réussie !}}</span>'
                        : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                    );
                }
            });
        });

        $(document).off('click', '#bt_syncIndygo').on('click', '#bt_syncIndygo', function () {
            if (!_currentId) { _notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }
            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Synchronisation…}}');
            $.ajax({
                type: 'POST', url: 'plugins/' + _eqType + '/core/php/jeeIndygo.ajax.php',
                data: {action: 'sync', id: _currentId}, dataType: 'json',
                error: ajaxErr,
                success: function (data) {
                    $('#span_indygo_result').html(data.state === 'ok'
                        ? '<span class="label label-success"><i class="fas fa-check"></i> {{Synchronisation réussie !}}</span>'
                        : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                    );
                    if (data.state === 'ok') openEqLogic(_currentId);
                }
            });
        });
    }

    $(document).ready(function () { init(); });
})();
</script>
