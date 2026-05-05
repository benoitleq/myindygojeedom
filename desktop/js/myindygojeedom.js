"use strict";

/* Appelé par plugin.template.js pour chaque commande lors de l'affichage */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) _cmd = {configuration: {}};
    if (!isset(_cmd.configuration)) _cmd.configuration = {};

    var tr = '';
    tr += '<td style="min-width:50px;width:70px;">';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<div class="row">';
    tr += '<div class="col-sm-6">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> {{Icône}}</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left:10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-sm-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;display:inline-block;">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;display:inline-block;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;margin-left:2px;">';
    if (_cmd.type === 'action') {
        tr += '<br/><input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="display_name" placeholder="{{Nom section (widget)}}" title="{{Nom section (widget)}}" style="width:92%;margin-top:4px;">';
    }
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/> {{Historiser}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/> {{Inverser}}</label></span> ';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';

    var newRow = document.createElement('tr');
    newRow.className = 'cmd';
    newRow.setAttribute('data-cmd_id', init(_cmd.id));
    newRow.innerHTML = tr;
    document.querySelector('#table_cmd tbody').appendChild(newRow);
    newRow.setJeeValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType(newRow, init(_cmd.subType));
}

/* Exécuter une commande de mode et basculer l'état visuel du bouton actif */
function indygoSetMode(id, el) {
    $.post('core/ajax/cmd.ajax.php', {action: 'execCmd', id: id, options: '{}'}, null, 'json');
    var $el  = $(el);
    var $sec = $el.closest('[data-ind-sec]');
    $sec.find('[data-ind-btn]').each(function () {
        this.setAttribute('style', $(this).data('style-off'));
        $(this).find('i').attr('style', 'font-size:17px;color:#2e3d55;');
        $(this).find('span').attr('style', 'font-size:11px;font-weight:800;color:#2e3d55;');
    });
    el.setAttribute('style', $el.data('style-on'));
    $el.find('i').attr('style', 'font-size:17px;' + $el.data('ic-on'));
    $el.find('span').attr('style', 'font-size:11px;font-weight:800;' + $el.data('lc-on'));
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
