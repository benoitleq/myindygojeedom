"use strict";

var myindygojeedom = (function () {
    var _eqType = 'myindygojeedom';

    // ─── Ouvre le panneau de détail d'un équipement ──────────────────
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

    // ─── Charge et affiche la liste des piscines ─────────────────────
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

    return {
        init: function () {
            loadList();

            // ── Ajouter une piscine ──────────────────────────────────
            $(document).off('click', '#bt_addPiscine').on('click', '#bt_addPiscine', function () {
                bootbox.prompt('{{Nom de la piscine ?}}', function (result) {
                    if (result === null || result.trim() === '') return;
                    jeedom.eqLogic.save({
                        type: _eqType,
                        eqLogic: { name: result.trim(), eqType_name: _eqType, isEnable: 1, isVisible: 1 },
                        error: function (err) { notify('Erreur', err.message, 'danger'); },
                        success: function (eq) { openEqLogic(eq.id); }
                    });
                });
            });

            // ── Retour à la liste ────────────────────────────────────
            $(document).off('click', '#bt_backList').on('click', '#bt_backList', function () {
                $('.eqLogic').hide();
                $('.eqLogicThumbnailDisplay').show();
                loadList();
            });

            // ── Sauvegarder ──────────────────────────────────────────
            $(document).off('click', '#bt_saveEq').on('click', '#bt_saveEq', function () {
                var eq = $('.eqLogic').getValues('.eqLogicAttr');
                eq.eqLogic.eqType_name = _eqType;
                jeedom.eqLogic.save({
                    type: _eqType,
                    eqLogic: eq.eqLogic,
                    error: function (err) { notify('Erreur', err.message, 'danger'); },
                    success: function (data) {
                        modifyWithoutSave = false;
                        notify('Info', '{{Sauvegarde réussie}}', 'success');
                        openEqLogic(data.id);
                    }
                });
            });

            // ── Supprimer ────────────────────────────────────────────
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

            // ── Tester la connexion ──────────────────────────────────
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

            // ── Synchroniser les équipements ─────────────────────────
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
    };
})();

$(document).ready(function () {
    myindygojeedom.init();
});
