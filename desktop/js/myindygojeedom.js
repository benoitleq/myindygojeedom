"use strict";

/* Appelé par plugin.template.js pour chaque commande lors de l'affichage */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) _cmd = {configuration: {}};
    if (!isset(_cmd.configuration)) _cmd.configuration = {};

    var tr = '<td><span class="cmdAttr" data-l1key="id"></span></td>';
    tr += '<td><span class="cmdAttr" data-l1key="name"></span></td>';
    tr += '<td><span class="cmdAttr" data-l1key="type"></span></td>';
    tr += '<td><span class="cmdAttr" data-l1key="subType"></span></td>';
    tr += '<td><span class="cmdAttr" data-l1key="unite"></span></td>';
    tr += '<td><span class="cmdAttr" data-l1key="htmlstate"></span></td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</td>';

    var newRow = document.createElement('tr');
    newRow.className = 'cmd';
    newRow.setAttribute('data-cmd_id', init(_cmd.id));
    newRow.innerHTML = tr;
    document.querySelector('#table_cmd tbody').appendChild(newRow);
    newRow.setJeeValues(_cmd, '.cmdAttr');
}

/* Tester la connexion */
$(document).on('click', '#bt_testIndygo', function () {
    var id = $('.eqLogicAttr[data-l1key="id"]').val();
    if (!id) {
        jeedomUtils.showAlert({message: '{{Sauvegardez d\'abord l\'équipement}}', level: 'warning'});
        return;
    }
    $('#div_indygo_result').show();
    $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Test en cours…}}');
    $.ajax({
        type: 'POST',
        url: 'plugins/myindygojeedom/core/php/jeeIndygo.ajax.php',
        data: {action: 'testConnection', id: id},
        dataType: 'json',
        error: function (req) {
            var msg = (req.responseJSON && req.responseJSON.result) ? req.responseJSON.result
                    : (req.responseText ? req.responseText.substring(0, 200) : 'Erreur réseau');
            $('#span_indygo_result').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + msg + '</span>');
        },
        success: function (data) {
            $('#span_indygo_result').html(data.state === 'ok'
                ? '<span class="label label-success"><i class="fas fa-check"></i> {{Connexion réussie !}}</span>'
                : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
            );
        }
    });
});

/* Synchroniser les équipements */
$(document).on('click', '#bt_syncIndygo', function () {
    var id = $('.eqLogicAttr[data-l1key="id"]').val();
    if (!id) {
        jeedomUtils.showAlert({message: '{{Sauvegardez d\'abord l\'équipement}}', level: 'warning'});
        return;
    }
    $('#div_indygo_result').show();
    $('#span_indygo_result').html('<i class="fas fa-spinner fa-spin"></i> {{Synchronisation…}}');
    $.ajax({
        type: 'POST',
        url: 'plugins/myindygojeedom/core/php/jeeIndygo.ajax.php',
        data: {action: 'sync', id: id},
        dataType: 'json',
        error: function (req) {
            var msg = (req.responseJSON && req.responseJSON.result) ? req.responseJSON.result
                    : (req.responseText ? req.responseText.substring(0, 200) : 'Erreur réseau');
            $('#span_indygo_result').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + msg + '</span>');
        },
        success: function (data) {
            $('#span_indygo_result').html(data.state === 'ok'
                ? '<span class="label label-success"><i class="fas fa-check"></i> {{Synchronisation réussie !}}</span>'
                : '<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>'
            );
        }
    });
});
