<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div class="row row-overflow">

    <!-- ─── Liste des équipements ──────────────────────────────────── -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <div class="row">
            <div class="col-xs-12">
                <a class="btn btn-default eqLogicAction" data-action="add">
                    <i class="fas fa-plus-circle"></i> {{Ajouter une piscine}}
                </a>
            </div>
        </div>
        <div class="row" id="div_resumeEqLogic"></div>
    </div>

    <!-- ─── Détail d'un équipement ────────────────────────────────── -->
    <div class="col-xs-12 eqLogic" style="display:none;">

        <!-- Boutons d'action -->
        <div class="input-group pull-right" style="display:inline-flex">
            <a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure">
                <i class="fas fa-cogs"></i>
            </a>
            <a class="btn btn-sm btn-default eqLogicAction" data-action="copy">
                <i class="fas fa-copy"></i>
            </a>
            <a class="btn btn-sm btn-success eqLogicAction" data-action="save">
                <i class="fas fa-check-circle"></i> {{Sauvegarder}}
            </a>
            <a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove">
                <i class="fas fa-minus-circle"></i> {{Supprimer}}
            </a>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#" class="nav-link" data-toggle="tab" data-target="#eqlogictab">
                    <i class="fas fa-tachometer-alt"></i> {{Équipement}}
                </a>
            </li>
            <li role="presentation">
                <a href="#" class="nav-link" data-toggle="tab" data-target="#commandtab">
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
                            <!-- Champs Jeedom standards (nom, objet, activé, visible…) -->
                            <?php include_file('desktop', 'eqLogic', 'inc', 'core'); ?>

                            <hr/>
                            <legend><i class="fas fa-lock"></i> {{Connexion MyIndygo}}</legend>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">
                                    {{Email}} <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="email"
                                           type="email"
                                           placeholder="votre.email@exemple.com"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">
                                    {{Mot de passe}} <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="password"
                                           type="password"
                                           placeholder="●●●●●●●●"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">
                                    {{Pool ID}} <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-6">
                                    <input class="eqLogicAttr form-control"
                                           data-l1key="configuration" data-l2key="pool_id"
                                           placeholder="ID visible dans l'URL myindygo.com/pools/&lt;ID&gt;"/>
                                </div>
                                <div class="col-sm-2">
                                    <span class="help-block">
                                        <i class="fas fa-info-circle text-info"></i>
                                        <a href="https://myindygo.com" target="_blank">myindygo.com</a>
                                    </span>
                                </div>
                            </div>

                            <hr/>
                            <legend><i class="fas fa-sync-alt"></i> {{Synchronisation}}</legend>

                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <a class="btn btn-info" id="bt_testIndygo">
                                        <i class="fas fa-plug"></i> {{Tester la connexion}}
                                    </a>
                                    &nbsp;
                                    <a class="btn btn-default" id="bt_syncIndygo">
                                        <i class="fas fa-sync"></i> {{Synchroniser les équipements}}
                                    </a>
                                </div>
                            </div>

                            <div class="form-group" id="div_indygo_result" style="display:none;">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <span id="span_indygo_result"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Statut connexion}}</label>
                                <div class="col-sm-6">
                                    <span class="eqLogicAttr label label-default"
                                          data-l1key="configuration" data-l2key="access_token"
                                          style="display:inline-block; max-width:200px; overflow:hidden; text-overflow:ellipsis;">
                                    </span>
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

        </div><!-- tab-content -->
    </div><!-- eqLogic -->
</div><!-- row -->
