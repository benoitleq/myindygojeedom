"use strict";

/* Plugin MyIndygo — JavaScript côté desktop Jeedom */

// var (pas let) : nécessaire pour que Jeedom puisse appeler window.myindygojeedom.init()
var myindygojeedom = (function () {
    const _eqType = 'myindygojeedom';

    // ─── Affiche la liste des équipements sous forme de tuiles ────────
    function displayEqLogics(eqLogics) {
        let html = '';
        for (const eq of eqLogics) {
            const enabled = parseInt(eq.isEnable) === 1;
            html += `
            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="eqLogicDisplayCard cursor" data-eqLogic_id="${eq.id}">
                    <img src="plugins/${_eqType}/desktop/img/${_eqType}_icon.png"
                         onerror="this.src='core/img/eqlogic.png'" />
                    <br/>
                    <span class="name">${eq.name}</span>
                    <span class="hidden eq_id">${eq.id}</span>
                    <span class="hidden eq_isEnable">${eq.isEnable}</span>
                    <span class="hidden eq_isVisible">${eq.isVisible}</span>
                    ${!enabled ? '<span class="label label-danger">{{Désactivé}}</span>' : ''}
                </div>
            </div>`;
        }
        $('#div_resumeEqLogic').html(html);

        // Clic sur une tuile → ouvre le panneau de détail
        $('.eqLogicDisplayCard').on('click', function () {
            const id = $(this).data('eqlogic_id');
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
        });
    }

    // ─── Bouton "Tester la connexion" ─────────────────────────────────
    function bindTestButton() {
        $('#bt_testIndygo').on('click', function () {
            const id = $('.eqLogic .eqLogicAttr[data-l1key=id]').val();
            if (!id) { notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }

            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Test en cours…}}');

            $.ajax({
                type: 'POST',
                url: `plugins/${_eqType}/core/php/jeeIndygo.ajax.php`,
                data: { action: 'testConnection', id: id },
                dataType: 'json',
                error: function (req, status, err) { handleAjaxError(req, status, err); },
                success: function (data) {
                    if (data.state !== 'ok') {
                        $('#span_indygo_result').html(
                            '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                        );
                    } else {
                        $('#span_indygo_result').html(
                            '<span class="label label-success"><i class="fas fa-check"></i> {{Connexion réussie !}}</span>'
                        );
                    }
                }
            });
        });
    }

    // ─── Bouton "Synchroniser les équipements" ────────────────────────
    function bindSyncButton() {
        $('#bt_syncIndygo').on('click', function () {
            const id = $('.eqLogic .eqLogicAttr[data-l1key=id]').val();
            if (!id) { notify('Attention', '{{Sauvegardez d\'abord l\'équipement}}', 'warning'); return; }

            $('#div_indygo_result').show();
            $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Synchronisation…}}');

            $.ajax({
                type: 'POST',
                url: `plugins/${_eqType}/core/php/jeeIndygo.ajax.php`,
                data: { action: 'sync', id: id },
                dataType: 'json',
                error: function (req, status, err) { handleAjaxError(req, status, err); },
                success: function (data) {
                    if (data.state !== 'ok') {
                        $('#span_indygo_result').html(
                            '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
                        );
                    } else {
                        $('#span_indygo_result').html(
                            '<span class="label label-success"><i class="fas fa-check"></i> {{Synchronisation réussie !}}</span>'
                        );
                        // Recharge la liste des commandes
                        jeedom.eqLogic.get({
                            id: id,
                            error: function (err) { notify('Erreur', err.message, 'danger'); },
                            success: function (eq) {
                                $('.eqLogic').setValues(eq, '.eqLogicAttr');
                            }
                        });
                    }
                }
            });
        });
    }

    // ─── Point d'entrée appelé par Jeedom au chargement de la page ───
    return {
        init: function () {
            jeedom.eqLogic.getAll({
                type: _eqType,
                error: function (err) { notify('Erreur', err.message, 'danger'); },
                success: function (eqLogics) { displayEqLogics(eqLogics); }
            });

            bindTestButton();
            bindSyncButton();
        }
    };
})();
