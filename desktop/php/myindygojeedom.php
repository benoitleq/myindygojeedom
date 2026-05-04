<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('myindygojeedom');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">

    <!-- ─── Liste des piscines ─────────────────────────────────────── -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
        </div>
        <legend><i class="fas fa-swimming-pool"></i> {{Mes piscines}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '" onerror="this.src=\'core/img/eqlogic.png\'"/>';
                echo '<br/>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            } ?>
        </div>
    </div>

    <!-- ─── Détail d'une piscine ──────────────────────────────────── -->
    <div class="col-xs-12 eqLogic" style="display:none;">

        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure">
                    <i class="fa fa-cogs"></i> {{Configuration avancée}}
                </a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save">
                    <i class="fas fa-check-circle"></i> {{Sauvegarder}}
                </a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove">
                    <i class="fas fa-minus-circle"></i> {{Supprimer}}
                </a>
            </span>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation">
                <a href="#" class="eqLogicAction" role="tab" data-action="returnToThumbnailDisplay">
                    <i class="fa fa-arrow-circle-left"></i>
                </a>
            </li>
            <li role="presentation" class="active">
                <a href="#eqlogictab" role="tab" data-toggle="tab">
                    <i class="fas fa-tachometer-alt"></i> {{Équipement}}
                </a>
            </li>
            <li role="presentation">
                <a href="#commandtab" role="tab" data-toggle="tab">
                    <i class="fas fa-list-alt"></i> {{Commandes}}
                </a>
            </li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x:hidden;">

            <!-- Onglet Équipement -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>

                        <legend><i class="fas fa-wrench"></i> {{Général}}</legend>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;"/>
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Ma piscine}}"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Catégorie}}</label>
                            <div class="col-sm-9">
                                <?php foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '"/>' . $value['name'];
                                    echo '</label>';
                                } ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/> {{Activer}}
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/> {{Visible}}
                                </label>
                            </div>
                        </div>

                        <legend><i class="fas fa-lock"></i> {{Connexion MyIndygo}}</legend>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Email}} <span class="text-danger">*</span></label>
                            <div class="col-sm-3">
                                <input type="email" class="eqLogicAttr form-control"
                                       data-l1key="configuration" data-l2key="email"
                                       placeholder="votre.email@exemple.com"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Mot de passe}} <span class="text-danger">*</span></label>
                            <div class="col-sm-3">
                                <input type="password" class="eqLogicAttr form-control"
                                       data-l1key="configuration" data-l2key="password"
                                       placeholder="●●●●●●●●"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Pool ID}} <span class="text-danger">*</span></label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control"
                                       data-l1key="configuration" data-l2key="pool_id"
                                       placeholder="ID visible dans l'URL myindygo.com/pools/&lt;ID&gt;"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
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
                            <div class="col-sm-offset-3 col-sm-9">
                                <span id="span_indygo_result"></span>
                            </div>
                        </div>

                    </fieldset>
                </form>
            </div>

            <!-- Onglet Commandes -->
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Id}}</th>
                            <th>{{Nom}}</th>
                            <th>{{Type}}</th>
                            <th>{{Sous-type}}</th>
                            <th>{{Unité}}</th>
                            <th>{{Etat}}</th>
                            <th>{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include_file('desktop', 'myindygojeedom', 'js', 'myindygojeedom'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
