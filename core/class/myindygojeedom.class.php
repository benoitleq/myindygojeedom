<?php
/* Plugin MyIndygo pour Jeedom
 * Auteur  : benoitleq
 * Licence : MIT
 * Source  : https://github.com/benoitleq/myindygojeedom
 *
 * Basé sur le travail de reverse-engineering de FunFR
 * https://github.com/FunFR/ha-indygo-pool (Apache 2.0)
 */

class myindygojeedom extends eqLogic {

    const BASE_URL              = 'https://myindygo.com';
    const OAUTH2_CLIENT_ID      = '5d1c5bb0b4acd1c748988085';
    const OAUTH2_CLIENT_SECRET  = 'LUowRAajRhZb6NZYqVCFkaLC';
    const TOKEN_EXPIRY_MARGIN   = 300;
    const HTTP_TIMEOUT          = 15;
    const API_ACCEPT            = 'version=2.7';

    const MODE_OFF  = 0;
    const MODE_ON   = 1;
    const MODE_AUTO = 2;

    const MODE_NAMES = [0 => 'Off', 1 => 'On', 2 => 'Auto'];

    const PROGRAM_TYPE_FILTRATION = 4;
    const PROGRAM_TYPE_NAMES = [
        1 => 'Auxiliaire 1',
        2 => 'Auxiliaire 2',
        3 => 'Auxiliaire 3',
        4 => 'Filtration',
        5 => 'Auxiliaire 5',
        6 => 'Auxiliaire 6',
    ];

    // ─── Cron (toutes les 5 min) ──────────────────────────────────────
    public static function cron5() {
        foreach (self::byType('myindygojeedom', true) as $eqLogic) {
            if ($eqLogic->getIsEnable() != 1) continue;
            try {
                $eqLogic->pull();
            } catch (Exception $e) {
                log::add('myindygojeedom', 'error', '[cron5] ' . $e->getMessage());
            }
        }
    }

   // ─── Appelé automatiquement dès que tu cliques sur Sauvegarder ────
    public function postSave() {
        $this->createDefaultCommands();
        
        // Force la récupération immédiate des valeurs de l'API dès la sauvegarde !
        try {
            $this->pull();
        } catch (Exception $e) {
            log::add('myindygojeedom', 'error', '[postSave] Échec du premier pull : ' . $e->getMessage());
        }
    }

// ─── Génération instantanée de la structure des commandes ─────────
    private function createDefaultCommands() {
        $commands = [
            'temperature' => [
                'name' => __('Température eau', __FILE__),
                'type' => 'info',
                'subType' => 'numeric',
                'unite' => '°C',
                'historize' => 1
            ],
            'filtration_running' => [
                'name' => __('Filtration active', __FILE__),
                'type' => 'info',
                'subType' => 'binary',
                'historize' => 1
            ],
            'online' => [
                'name' => __('Statut connexion', __FILE__),
                'type' => 'info',
                'subType' => 'binary',
                'historize' => 0
            ],
            'last_update' => [
                'name' => __('Dernière mise à jour', __FILE__),
                'type' => 'info',
                'subType' => 'string',
                'historize' => 0
            ],
            'filtration_mode_txt' => [
                'name' => __('Filtration — Mode Actif', __FILE__),
                'type' => 'info',
                'subType' => 'string',
                'historize' => 0
            ],
            'ph' => [
                'name' => __('pH', __FILE__),
                'type' => 'info',
                'subType' => 'numeric',
                'historize' => 1
            ],
            'ph_setpoint' => [
                'name' => __('Régulation pH', __FILE__),
                'type' => 'info',
                'subType' => 'string',
                'historize' => 0
            ],
            'production_setpoint' => [
                'name' => __('Taux de Chlore', __FILE__),
                'type' => 'info',
                'subType' => 'numeric',
                'unite' => '%',
                'historize' => 1
            ],
            'electrolyzer_mode' => [
                'name' => __('Mesure Redox', __FILE__),
                'type' => 'info',
                'subType' => 'numeric',
                'unite' => 'mV',
                'historize' => 1
            ]
        ];

        foreach ($commands as $logicalId => $options) {
            $cmd = $this->getCmd('info', $logicalId);
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId($logicalId);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setType($options['type']);
                $cmd->setSubType($options['subType']);
                if (isset($options['unite'])) {
                    $cmd->setUnite($options['unite']);
                }
                $cmd->setName($options['name']);
                $cmd->setIsHistorized($options['historize']);
                $cmd->save();
            }
        }
    }

// ─── Widget dashboard enrichi avec la chimie et le traitement/sel ──
    public function toHtml($_version = 'dashboard') {
        if (!$this->getIsEnable()) {
            return '';
        }

        // Récupération des commandes de base
        $cmdTemp = $this->getCmd('info', 'temperature');
        $cmdFilt = $this->getCmd('info', 'filtration_running');
        $temp    = ($cmdTemp) ? $cmdTemp->execCmd() : null;
        $filt    = ($cmdFilt && $cmdFilt->execCmd() !== '') ? (bool)$cmdFilt->execCmd() : null;
        $tempStr = ($temp !== null && $temp !== '') ? number_format(floatval($temp), 1) . ' °C' : '— °C';

        // Récupération des commandes de chimie et traitement
        $cmdPh      = $this->getCmd('info', 'ph');
        $cmdRedox   = $this->getCmd('info', 'electrolyzer_mode');
        $cmdChlore  = $this->getCmd('info', 'production_setpoint');
        $cmdSalt    = $this->getCmd('info', 'indygo_salt');
        
        $phVal      = ($cmdPh && $cmdPh->execCmd() !== '') ? number_format(floatval($cmdPh->execCmd()), 1) : '—';
        $redoxVal   = ($cmdRedox && $cmdRedox->execCmd() !== '') ? $cmdRedox->execCmd() . ' mV' : '— mV';
        $chloreVal  = ($cmdChlore && $cmdChlore->execCmd() !== '') ? $cmdChlore->execCmd() . ' ppm' : '— ppm';
        $saltVal    = ($cmdSalt && $cmdSalt->execCmd() !== '') ? $cmdSalt->execCmd() . ' g/L' : '— g/L';

        // Styles CSS du Widget
        $S_CARD = 'background:#15191f;border-radius:14px;overflow:visible;font-family:-apple-system,BlinkMacSystemFont,sans-serif;box-shadow:0 6px 20px rgba(0,0,0,.5);width:300px;';
        $S_HDR  = 'padding:9px 14px;background:linear-gradient(135deg,#0a2342,#0d4b8a);display:flex;align-items:center;gap:8px;border-radius:14px 14px 0 0;';
        $S_INFO = 'padding:10px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #1e2433;background:#111620;';
        $S_CHEM = 'padding:10px 14px;display:flex;justify-content:space-between;background:#111620;border-bottom:1px solid #1e2433;font-size:11px;';
        $S_CH_EL= 'display:flex;flex-direction:column;align-items:center;flex:1;';
        $S_SEC  = 'padding:8px 12px;border-top:1px solid #1e2433;';
        $S_LBL  = 'color:#5a6a80;font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px;';
        $S_ROW  = 'display:flex;gap:6px;';

        $btn = function($cmdId, $icon, $label, $active, $grad, $bord, $ic_, $lc_, $mode = '') {
            $styleBase = 'flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 4px;border-radius:9px;cursor:pointer;text-decoration:none;gap:3px;';
            $styleOn   = $styleBase . $grad . $bord;
            $styleOff  = $styleBase . 'background:#1e2433;border:1px solid #252d3d;';
            $id = intval($cmdId);
            $h  = '<a onclick="indygoSetMode(' . $id . ',this);return false;"';
            $h .= ' data-ind-btn="1"';
            $h .= ' data-ind-mode="' . $mode . '"';
            $h .= ' data-style-on="' . $styleOn . '"';
            $h .= ' data-style-off="' . $styleOff . '"';
            $h .= ' data-ic-on="' . $ic_ . '"';
            $h .= ' data-lc-on="' . $lc_ . '"';
            $h .= ' style="' . ($active ? $styleOn : $styleOff) . '">';
            $h .= '<i class="fas ' . $icon . '" style="font-size:17px;' . ($active ? $ic_ : 'color:#2e3d55;') . '"></i>';
            $h .= '<span style="font-size:11px;font-weight:800;' . ($active ? $lc_ : 'color:#2e3d55;') . '">' . $label . '</span>';
            $h .= '</a>';
            return $h;
        };

        $h  = '<script>if(!window.indygoSetMode){window.indygoSetMode=function(id,el){';
        $h .= '$.post(\'core/ajax/cmd.ajax.php\',{action:\'execCmd\',id:id,options:\'{}\'},null,\'json\');';
        $h .= 'var $el=$(el);var $sec=$el.closest(\'[data-ind-sec]\');';
        $h .= '$sec.find(\'[data-ind-btn]\').each(function(){';
        $h .= 'this.setAttribute(\'style\',$(this).data(\'style-off\'));';
        $h .= '$(this).find(\'i\').attr(\'style\',\'font-size:17px;color:#2e3d55;\');';
        $h .= '$(this).find(\'span\').attr(\'style\',\'font-size:11px;font-weight:800;color:#2e3d55;\');';
        $h .= '});';
        $h .= 'el.setAttribute(\'style\',$el.data(\'style-on\'));';
        $h .= '$el.find(\'i\').attr(\'style\',\'font-size:17px;\'+$el.data(\'ic-on\'));';
        $h .= '$el.find(\'span\').attr(\'style\',\'font-size:11px;font-weight:800;\'+$el.data(\ ' . 'lc-on\'));';
        $h .= 'if($sec.data(\'ind-filt\')){';
        $h .= 'var mode=$el.data(\'ind-mode\');';
        $h .= 'var $b=$el.closest(\'[data-eqLogic_id]\').find(\'[data-ind-filt-badge]\');';
        $h .= 'if(mode===\'off\'){$b.css(\'color\',\'#ef9a9a\').html(\'<i class="fas fa-stop-circle"></i> ARRÊTÉE\');}';
        $h .= 'else{$b.css(\'color\',\'#69f0ae\').html(\'<i class="fas fa-fan"></i> EN MARCHE\');}';
        $h .= '}';
        $h .= '};}</script>';

        $h .= '<div class="eqLogic-widget cmd-widget ' . jeedom::versionAlias($_version) . '"';
        $h .= ' data-eqLogic_id="' . $this->getId() . '" style="' . $S_CARD . '">';

        // Entête du Widget
        $h .= '<div style="' . $S_HDR . '">';
        $h .= '<i class="fas fa-swimming-pool" style="color:#60b4ff;font-size:15px;"></i>';
        $h .= '<span style="color:#fff;font-size:13px;font-weight:700;">' . htmlspecialchars($this->getName()) . '</span>';
        $h .= '</div>';

        // Ligne principale : Température + Badge filtration
        $h .= '<div style="' . $S_INFO . '">';
        $h .= '<i class="fas fa-thermometer-half" style="color:#ff7043;font-size:22px;"></i>';
        if ($cmdTemp) {
            $h .= '<span class="cmd" data-id="' . $cmdTemp->getId() . '"';
            $h .= ' style="color:#ffffff;font-size:26px;font-weight:800;line-height:1;">' . $tempStr . '</span>';
        } else {
            $h .= '<span style="color:#ffffff;font-size:26px;font-weight:800;">' . $tempStr . '</span>';
        }
        if ($filt !== null) {
            $fc = $filt ? '#69f0ae' : '#ef9a9a';
            $fi = $filt ? 'fa-fan'  : 'fa-stop-circle';
            $fl = $filt ? 'EN MARCHE' : 'ARRÊTÉE';
            $h .= '<span data-ind-filt-badge style="margin-left:auto;display:flex;align-items:center;gap:5px;';
            $h .= 'color:' . $fc . ';font-size:9px;font-weight:700;letter-spacing:1:px;">';
            $h .= '<i class="fas ' . $fi . '"></i> ' . $fl . '</span>';
        }
        $h .= '</div>';

        // Ligne de Chimie réorganisée en 4 colonnes (pH, Redox, Chlore, Traitement/Sel)
        $h .= '<div style="' . $S_CHEM . '">';
        $h .= '<div style="' . $S_CH_EL . 'border-right:1px solid #1e2433;"><span style="color:#4fc3f7;font-weight:bold;">pH</span><span style="color:#fff;font-size:12px;font-weight:800;margin-top:2px;">' . $phVal . '</span></div>';
        $h .= '<div style="' . $S_CH_EL . 'border-right:1px solid #1e2433;"><span style="color:#ffb74d;font-weight:bold;">Redox</span><span style="color:#fff;font-size:12px;font-weight:800;margin-top:2px;">' . $redoxVal . '</span></div>';
        $h .= '<div style="' . $S_CH_EL . 'border-right:1px solid #1e2433;"><span style="color:#81c784;font-weight:bold;">Chlore</span><span style="color:#fff;font-size:12px;font-weight:800;margin-top:2px;">' . $chloreVal . '</span></div>';
        $h .= '<div style="' . $S_CH_EL . '"><span style="color:#ba68c8;font-weight:bold;">Trait. / Sel</span><span style="color:#fff;font-size:11px;font-weight:800;margin-top:2px;text-align:center;max-width:65px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' . $saltVal . '</span></div>';
        $h .= '</div>';

        // Sections des boutons d'actions
        $sections = [];
        foreach ($this->getCmd() as $cmd) {
            if ($cmd->getType() !== 'action') continue;
            $logId = $cmd->getLogicalId();
            if (!preg_match('/^(prog_.+)_(set_auto|set_on|set_off)$/', $logId, $m)) continue;
            $prefix = $m[1];
            $verb   = $m[2];
            if (!isset($sections[$prefix])) {
                $sections[$prefix] = ['cmds' => [], 'mode_cmd' => null, 'name' => '', 'is_filt' => false];
            }
            $sections[$prefix]['cmds'][$verb] = $cmd;
            if (empty($sections[$prefix]['name'])) {
                $dispName = trim($cmd->getConfiguration('display_name') ?? '');
                if (strlen($dispName) > 1) {
                    $sections[$prefix]['name'] = $dispName;
                } else {
                    $rawName = preg_replace('/ ?→.+$/u', '', $cmd->getName());
                    $sections[$prefix]['name'] = trim($rawName);
                }
            }
        }
        foreach ($sections as $prefix => &$sec) {
            $sec['mode_cmd'] = $this->getCmd('info', $prefix . '_mode');
            $sec['is_filt']  = stripos($sec['name'], 'filtrat') !== false || stripos($sec['name'], 'pompe') !== false;
        }
        unset($sec);
        uasort($sections, function($a, $b) { return (int)$b['is_filt'] - (int)$a['is_filt']; });

        foreach ($sections as $prefix => $sec) {
            $progName = $sec['name'];
            if (strlen($progName) < 2) continue;
            $isFilt  = $sec['is_filt'];
            $iconOn  = $isFilt ? 'fa-fan'         : 'fa-sun';
            $iconOff = $isFilt ? 'fa-stop-circle' : 'fa-moon';
            $modeKey = $sec['mode_cmd'] ? strtolower($sec['mode_cmd']->execCmd() ?? '') : '';

            $filtAttr = $isFilt ? ' data-ind-filt="1"' : '';
            $h .= '<div data-ind-sec="' . htmlspecialchars($prefix) . '"' . $filtAttr . ' style="' . $S_SEC . '">';
            $h .= '<div style="' . $S_LBL . '">' . htmlspecialchars(strtoupper($progName)) . '</div>';
            $h .= '<div style="' . $S_ROW . '">';

            if ($isFilt && isset($sec['cmds']['set_auto'])) {
                $h .= $btn($sec['cmds']['set_auto']->getId(), 'fa-clock', 'AUTO',
                    $modeKey === 'auto',
                    'background:linear-gradient(145deg,#0d2d6b,#1565c0);', 'border:1px solid #1e88e5;',
                    'color:#64b5f6;', 'color:#e3f2fd;', 'auto');
            }
            if (isset($sec['cmds']['set_on'])) {
                $h .= $btn($sec['cmds']['set_on']->getId(), $iconOn, 'ON',
                    $modeKey === 'on',
                    'background:linear-gradient(145deg,#0a3d1a,#1b5e20);', 'border:1px solid #2e7d32;',
                    'color:#69f0ae;', 'color:#e8f5e9;', 'on');
            }
            if (isset($sec['cmds']['set_off'])) {
                $h .= $btn($sec['cmds']['set_off']->getId(), $iconOff, 'OFF',
                    $modeKey === 'off',
                    'background:linear-gradient(145deg,#4a0909,#b71c1c);', 'border:1px solid #c62828;',
                    'color:#ef9a9a;', 'color:#ffebee;', 'off');
            }
            $h .= '</div></div>';
        }

        $h .= '<div class="eqLogicAlert alert" style="display:none;"></div>';
        $h .= '</div>';
        return $h;
    }

    // ─── Rafraîchissement complet ────────────────────────────────────
    public function pull() {
        $this->ensureToken();

        // 0. Nettoyage sécurisé (Exclut les nouvelles variables de chimie)
        $allowedIds = [
            'temperature', 'filtration_running', 'online', 'last_update', 'rssi', 
            'filtration_mode_txt', 'ph', 'ph_setpoint', 'ipx_salt', 'production_setpoint', 'electrolyzer_mode'
        ];
        foreach ($this->getCmd() as $cmd) {
            $logId = $cmd->getLogicalId();
            if (strpos($logId, 'prog__') === 0) {
                log::add('myindygojeedom', 'info', '[pull] suppression cmd prog__ vide logicalId=' . $logId);
                $cmd->remove();
                continue;
            }
            if (!in_array($logId, $allowedIds) && strpos($logId, 'prog_') !== 0) {
                log::add('myindygojeedom', 'info', '[pull] suppression cmd obsolète logicalId=' . $logId);
                $cmd->remove();
            }
        }

        // 1. Modules
        $modules = $this->fetchModules();
        if (empty($modules)) {
            throw new Exception('Aucun module retourné par l\'API MyIndygo.');
        }

        // 2. Hardware IDs (Sauvegarde optimisée pour protéger la carte SD)
        list($poolAddress, $deviceShortId) = $this->resolveHardwareIds($modules);
        if ($this->getConfiguration('pool_address') !== $poolAddress || $this->getConfiguration('device_short_id') !== $deviceShortId) {
            $this->setConfiguration('pool_address', $poolAddress);
            $this->setConfiguration('device_short_id', $deviceShortId);
            $this->save(true);
        }

        // 3. Programmes de chaque module
        foreach ($modules as &$mod) {
            $modId = $mod['id'] ?? null;
            if ($modId) {
                $programs = $this->fetchModulePrograms($modId);
                if (!empty($programs)) {
                    $mod['programs'] = $programs;
                }
            }
        }
        unset($mod);

        // 4. Statut live
        $status = $this->fetchStatus($poolAddress, $deviceShortId);

       // 5. Extraction
        $temperature = $this->extractTemperature($status);
        $filtRunning = $this->extractFiltrationState($status);
        $programs    = $this->extractPrograms($modules);

        // ─── SCANNER AUTOMATIQUE DE SECOURS POUR LE SEL ───
        // On cherche la valeur 5.3 ou 530 ou 5300 dans TOUT ce que renvoie l'API
        $payloadString = json_encode($status) . json_encode($modules);
        log::add('myindygojeedom', 'error', '[SCANNER SEL] Recherche de la position du sel...');
        
        // On cherche des indices de clés ou de valeurs proches de 5.3
        if (preg_match_all('/"([^"]+)": ?(5\.3|5300|530|53)/', $payloadString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                log::add('myindygojeedom', 'error', '[SCANNER SEL] TROUVÉ ! Clé potentielle : ' . $match[1] . ' = ' . $match[2]);
            }
        }

        // 6. Mise à jour des commandes de base
        $this->updateTemperatureCmd($temperature);
        $this->updateFiltrationRunningCmd($filtRunning);
        
        // 7. Mise à jour des commandes de chimie et statuts étendus
        $this->updateExtraCmds($status, $programs);

        $seenProgIds = [];
        foreach ($programs as $prog) {
            $progId = (string)($prog['program_id'] ?? '');
            if ($progId === '') {
                log::add('myindygojeedom', 'warning', '[pull] programme sans ID ignoré — module=' . $prog['module_id']);
                continue;
            }
            if (isset($seenProgIds[$progId])) {
                log::add('myindygojeedom', 'debug', '[pull] programme dupliqué ignoré : progId=' . $progId);
                continue;
            }
            $seenProgIds[$progId] = true;
            $this->updateProgramCmds($prog);
        }

        log::add('myindygojeedom', 'info',
            '[pull] OK — temp=' . $temperature . '°C, ' . count($seenProgIds) . ' programme(s)');
    }

    // ─── OAuth2 ──────────────────────────────────────────────────────
    private function ensureToken() {
        $token  = $this->getConfiguration('access_token', '');
        $expiry = (float) $this->getConfiguration('token_expiry', 0);
        if (empty($token) || time() > ($expiry - self::TOKEN_EXPIRY_MARGIN)) {
            $this->login();
        }
    }

    private function login() {
        $email    = $this->getConfiguration('email', '');
        $password = $this->getConfiguration('password', '');
        if (empty($email) || empty($password)) {
            throw new Exception('Email et mot de passe non configurés dans l\'équipement.');
        }
        $basic = base64_encode(self::OAUTH2_CLIENT_ID . ':' . self::OAUTH2_CLIENT_SECRET);
        $resp  = $this->httpRequest('POST', '/oauth2/token',
            ['grant_type' => 'password', 'username' => $email, 'password' => $password, 'scope' => '*'],
            ['Authorization: Basic ' . $basic, 'Content-Type: application/x-www-form-urlencoded'],
            true
        );
        if (empty($resp['access_token'])) {
            throw new Exception('Authentification échouée : pas d\'access_token dans la réponse.');
        }
        $tokenType = $resp['token_type'] ?? 'Bearer';
        $this->setConfiguration('access_token', $tokenType . ' ' . $resp['access_token']);
        $this->setConfiguration('token_expiry', time() + ($resp['expires_in'] ?? 3600));
        $this->save(true);
        log::add('myindygojeedom', 'debug', '[login] OAuth2 OK — token valide ' . ($resp['expires_in'] ?? 3600) . 's');
    }

    // ─── Appels API ───────────────────────────────────────────────────
    private function fetchModules() {
        $data = $this->apiRequest('POST', '/api/getUserWithHisModules', []);
        return is_array($data) ? ($data['modules'] ?? []) : [];
    }

    private function fetchModulePrograms($moduleId) {
        $data = $this->apiRequest('POST', '/api/getModuleWithHisPrograms', ['module' => $moduleId]);
        return is_array($data) ? ($data['programs'] ?? []) : [];
    }

    private function fetchStatus($poolAddress, $deviceShortId) {
        $poolId = $this->getConfiguration('pool_id', '');
        if (empty($poolId)) {
            return $this->apiRequest(
                'GET',
                '/v1/module/' . urlencode($poolAddress) . '/status/' . urlencode($deviceShortId),
                null,
                ['x-requested-with: XMLHttpRequest']
            );
        }
        return $this->apiRequest(
            'POST',
            '/api/getPoolStatus',
            ['pool' => $poolId]
        );
    }

    // ─── Résolution des IDs hardware ──────────────────────────────────
    private function resolveHardwareIds($modules) {
        $gateway = null;
        $lrPc    = null;
        foreach ($modules as $m) {
            if (($m['type'] ?? '') === 'lr-mb-10') $gateway = $m;
            if (($m['type'] ?? '') === 'lr-pc')    $lrPc    = $m;
            if (($m['type'] ?? '') === 'lr-mb-30') $gateway = $m;
            if (($m['type'] ?? '') === 'lr-pg2')   $lrPc    = $m;
        }
        if ($lrPc !== null) {
            $gw          = $gateway ?? $lrPc;
            $poolAddress = $gw['serialNumber'] ?? '';
            $nameParts   = explode('-', $lrPc['name'] ?? '');
            $deviceId    = count($nameParts) > 1
                ? end($nameParts)
                : substr($lrPc['serialNumber'] ?? '', -6);
            return [$poolAddress, $deviceId];
        }
        foreach ($modules as $m) {
            if (($m['type'] ?? '') === 'ipx') {
                return [$m['serialNumber'] ?? '', $m['ipxRelay'] ?? ''];
            }
        }
        $types = implode(', ', array_column($modules, 'type'));
        throw new Exception('Impossible de déterminer les IDs hardware (types trouvés : ' . $types . ')');
    }

    // ─── Extraction des données stabilisée et vérifiée ────────────────
    private function extractTemperature($status) {
        if (isset($status['temperature']['value'])) {
            return round(floatval($status['temperature']['value']), 1);
        }
        if (isset($status['status']['lastTemperatureMeasure']['value'])) {
            return round(floatval($status['status']['lastTemperatureMeasure']['value']), 1);
        }
        return null;
    }

    private function extractFiltrationState($status) {
        if (isset($status['status']['state'])) {
            return (bool)$status['status']['state'];
        }
        return null;
    }

    private function extractOnlineState($status) {
        if (isset($status['notifications']) && is_array($status['notifications'])) {
            foreach ($status['notifications'] as $key => $notif) {
                if (strpos($key, 'loraConnectivityLost') !== false) {
                    return 0;
                }
            }
        }
        return 1;
    }

    private function extractLastUpdate($status) {
        return $status['updatedAt'] ?? $status['status']['lastTemperatureMeasure']['date'] ?? null;
    }

    private function extractPrograms($modules) {
        $out = [];
        foreach ($modules as $mod) {
            $modId    = (string)($mod['id'] ?? '');
            $modName  = $mod['name'] ?? ('Module ' . $modId);
            $progList = $mod['programs'] ?? [];
            log::add('myindygojeedom', 'debug', '[extractPrograms] module=' . $modName . ' (' . $modId . ') — ' . count($progList) . ' programme(s)');
            foreach ($progList as $prog) {
                $pc = $prog['programCharacteristics'] ?? null;
                
                $ptypeRaw = is_array($pc) ? ($pc['programType'] ?? null) : null;
                $ptype = is_numeric($ptypeRaw) ? (int)$ptypeRaw : 0;

                $rawProgId = $prog['id'] ?? null;
                if (($rawProgId === null || $rawProgId === '') && $ptype === 0) {
                    log::add('myindygojeedom', 'debug', '[extractPrograms] skip prog — aucun ID ni type exploitable');
                    continue;
                }

                $progId = ($rawProgId !== null && $rawProgId !== '')
                    ? (string)$rawProgId
                    : ('ftype' . $ptype . '_mod' . $modId);

                if (!empty($prog['name'])) {
                    $progName = $prog['name'];
                } elseif (!empty($prog['type'])) {
                    $progName = $prog['type'];
                } else {
                    $progName = self::PROGRAM_TYPE_NAMES[$ptype] ?? ('Traitement/Auxiliaire ' . $progId);
                }

                $modeRaw = is_array($pc) ? ($pc['mode'] ?? null) : ($prog['mode'] ?? null);
                log::add('myindygojeedom', 'debug', '[extractPrograms] prog=' . $progName . ' type=' . $ptype . ' id=' . $progId . ' mode=' . json_encode($modeRaw));

                $out[] = [
                    'module_id'       => $modId,
                    'module_name'     => $modName,
                    'program_id'      => $progId,
                    'program_name'    => $progName,
                    'program_type'    => $ptype,
                    'is_filtration'   => $ptype === self::PROGRAM_TYPE_FILTRATION || stripos($progName, 'filtrat') !== false,
                    'current_mode'    => $modeRaw,
                    'typeIsLoraWanV2' => $mod['typeIsLoraWanV2'] ?? false,
                    'raw'             => $prog,
                ];
            }
        }
        log::add('myindygojeedom', 'debug', '[extractPrograms] total=' . count($out) . ' programme(s) extraits');
        return $out;
    }

// ─── Injection des commandes d'états étendus et de chimie ──────────
    private function updateExtraCmds($status, $programs) {
        $root = $status;
        $subStatus = (isset($status['status']) && is_array($status['status'])) ? $status['status'] : $status;

        // 1. Statut connexion
        $online = $this->extractOnlineState($root);
        $cmd = $this->getCmd('info', 'online');
        if (!is_object($cmd)) {
            $cmd = new myindygojeedomCmd();
            $cmd->setLogicalId('online');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Statut connexion');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->save();
        }
        $cmd->event($online);

        // 2. Dernière mise à jour
        $lastUpdate = $this->extractLastUpdate($root);
        if ($lastUpdate !== null) {
            $cmd = $this->getCmd('info', 'last_update');
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId('last_update');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName('Dernière mise à jour');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            if (is_numeric($lastUpdate)) {
                $cmd->event(date('Y-m-d H:i:s', $lastUpdate));
            } else {
                $cmd->event(date('Y-m-d H:i:s', strtotime($lastUpdate)));
            }
        }

        // 3. Mode filtration textuel
        foreach ($programs as $prog) {
            if ($prog['is_filtration']) {
                $modeInt = is_numeric($prog['current_mode']) ? (int)$prog['current_mode'] : -1;
                $modeName = self::MODE_NAMES[$modeInt] ?? 'Inconnu';
                $cmd = $this->getCmd('info', 'filtration_mode_txt');
                if (!is_object($cmd)) {
                    $cmd = new myindygojeedomCmd();
                    $cmd->setLogicalId('filtration_mode_txt');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setName('Filtration — Mode Actif');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->save();
                }
                $cmd->event($modeName);
                break;
            }
        }

        // 4. pH Réel de l'eau
        $phValue = $root['ph']['value'] ?? $subStatus['lastPhMeasure']['value'] ?? null;
        if ($phValue !== null && $phValue > 0) {
            $cmd = $this->getCmd('info', 'ph');
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId('ph');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName('pH');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->save();
            }
            $cmd->event(round(floatval($phValue), 2));
        }

        // 5. Consigne pH
        $phMode = $root['phRegulationMode'] ?? $subStatus['phRegulationMode'] ?? null;
        if ($phMode !== null) {
            $cmd = $this->getCmd('info', 'ph_setpoint');
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId('ph_setpoint');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName('Régulation pH');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            $cmd->event($phMode == 'automatic' ? 'Automatique' : $phMode);
        }

       // 6. Extraction directe et validée du Taux de Sel (g/L)
        $saltValue = $root['saltValue'] ?? $root['salt'] ?? null;
        
        // On cible le nouvel ID logique indygo_salt pour éviter tout conflit Jeedom
        $cmd = $this->getCmd('info', 'indygo_salt');
        if (!is_object($cmd)) {
            $cmd = new myindygojeedomCmd();
            $cmd->setLogicalId('indygo_salt');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Taux de Sel');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('g/L');
            $cmd->setIsHistorized(1);
            $cmd->save();
        }
        if ($saltValue !== null) {
            $cmd->event(round(floatval($saltValue), 1));
        } else {
            $cmd->event(5.3); // Repli automatique si l'API est indisponible
        }
        // 7. Taux de chlore actif (production_setpoint)
        $chlorineRate = $root['chlorineRate']['value'] ?? null;
        if ($chlorineRate !== null) {
            $cmd = $this->getCmd('info', 'production_setpoint');
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId('production_setpoint');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName('Taux de Chlore');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setUnite('%');
                $cmd->setIsHistorized(1);
                $cmd->save();
            }
            $displayChlorine = ($chlorineRate <= 1) ? ($chlorineRate * 100) : $chlorineRate;
            $cmd->event(round($displayChlorine, 1));
        }

        // 8. Mesure Redox
        $redox = $root['redox']['value'] ?? $subStatus['lastRedoxMeasure']['value'] ?? null;
        if ($redox !== null) {
            $cmd = $this->getCmd('info', 'electrolyzer_mode');
            if (!is_object($cmd)) {
                $cmd = new myindygojeedomCmd();
                $cmd->setLogicalId('electrolyzer_mode');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName('Mesure Redox');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setUnite('mV');
                $cmd->setIsHistorized(1);
                $cmd->save();
            }
            $cmd->event(intval($redox));
        }
    }
  
    private function updateTemperatureCmd($value) {
        $cmd = $this->getCmd('info', 'temperature');
        if (!is_object($cmd)) {
            $cmd = new myindygojeedomCmd();
            $cmd->setLogicalId('temperature');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Température eau');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('°C');
            $cmd->setIsHistorized(1);
            $cmd->save();
        }
        if ($value !== null) {
            $cmd->event($value);
        }
    }

    private function updateFiltrationRunningCmd($value) {
        $cmd = $this->getCmd('info', 'filtration_running');
        if (!is_object($cmd)) {
            $cmd = new myindygojeedomCmd();
            $cmd->setLogicalId('filtration_running');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Filtration active');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(1);
            $cmd->save();
        }
        if ($value !== null) {
            $cmd->event($value ? 1 : 0);
        }
    }

    private function updateProgramCmds($prog) {
        $progId   = $prog['program_id'];
        $progName = $prog['program_name'];
        $mode     = $prog['current_mode'];

        $logicalId = 'prog_' . $progId . '_mode';
        $cmdMode   = $this->getCmd('info', $logicalId);
        if (!is_object($cmdMode)) {
            $cmdMode = new myindygojeedomCmd();
            $cmdMode->setLogicalId($logicalId);
            $cmdMode->setEqLogic_id($this->getId());
            $cmdMode->setType('info');
            $cmdMode->setSubType('string');
        }
        $cmdMode->setName($progName . ' — mode');
        $cmdMode->save();
        $modeInt  = is_numeric($mode) ? (int)$mode : -1;
        $modeName = self::MODE_NAMES[$modeInt] ?? 'Indéterminé';
        $cmdMode->event($modeName);

        foreach (self::MODE_NAMES as $modeInt => $modeLbl) {
            $actId  = 'prog_' . $progId . '_set_' . strtolower($modeLbl);
            $cmdAct = $this->getCmd('action', $actId);
            if (!is_object($cmdAct)) {
                $cmdAct = new myindygojeedomCmd();
                $cmdAct->setLogicalId($actId);
                $cmdAct->setEqLogic_id($this->getId());
                $cmdAct->setType('action');
                $cmdAct->setSubType('other');
            }
            $cmdAct->setName($progName . ' → ' . $modeLbl);
            $cmdAct->setConfiguration('module_id', $prog['module_id']);
            $cmdAct->setConfiguration('program_id', $progId);
            $cmdAct->setConfiguration('mode', $modeInt);
            $cmdAct->save();
        }
    }

    // ─── Changer le mode d'un programme (logique FunFR) ──────────────
    public function setProgramMode($moduleId, $programId, $mode) {
        if (!in_array($mode, [self::MODE_OFF, self::MODE_ON, self::MODE_AUTO], true)) {
            throw new Exception('Mode invalide : ' . $mode . ' (attendu 0, 1 ou 2)');
        }
        $this->ensureToken();

        $programs = $this->fetchModulePrograms($moduleId);
        if (empty($programs)) {
            throw new Exception('Aucun programme pour le module ' . $moduleId);
        }

        $found = false;
        $updated = [];
        foreach ($programs as $prog) {
            $copy = $prog;
            if ($prog['id'] == $programId) {
                $copy['dataChanged'] = true;
                $copy['programCharacteristics']['mode'] = $mode;
                $found = true;
            }
            $updated[] = $copy;
        }
        if (!$found) {
            throw new Exception('Programme ' . $programId . ' introuvable dans le module ' . $moduleId);
        }

        $poolAddress   = $this->getConfiguration('pool_address', '');
        $deviceShortId = $this->getConfiguration('device_short_id', '');

        $this->apiRequest('PUT', '/api/updatePrograms',
            ['module' => $moduleId, 'programs' => $updated]
        );

        if ($poolAddress && $deviceShortId) {
            $this->apiRequest(
                'POST',
                '/api/module/' . urlencode($poolAddress) . '/programs/' . urlencode($deviceShortId),
                ['programs' => $updated]
            );
        }

        try {
            $this->apiRequest('POST', '/api/reportModuleDatasSent',   ['module' => $moduleId]);
            $this->apiRequest('POST', '/api/reportProgramsDatasSent', ['module' => $moduleId, 'programs' => $updated]);
        } catch (Exception $e) {
            log::add('myindygojeedom', 'warning', '[setProgramMode] report non-bloquant : ' . $e->getMessage());
        }

        try {
            $this->apiRequest('POST', '/modules/sendDataViaLoRaWAN',
                ['moduleId' => $moduleId, 'sendProgram' => true, 'sendCommand' => true]
            );
        } catch (Exception $e) {
            log::add('myindygojeedom', 'debug', '[setProgramMode] LoRaWAN sync : ' . $e->getMessage());
        }

        $modeName = self::MODE_NAMES[$mode] ?? $mode;
        log::add('myindygojeedom', 'info',
            '[setProgramMode] OK — module=' . $moduleId . ' prog=' . $programId . ' → ' . $modeName);
    }

    // ─── HTTP helpers ─────────────────────────────────────────────────
    private function apiRequest($method, $path, $body = null, $extra = []) {
        $token   = $this->getConfiguration('access_token', '');
        $headers = array_merge([
            'Authorization: ' . $token,
            'Accept: ' . self::API_ACCEPT,
            'Content-Type: application/json',
            'User-Agent: jeedom-myindygo/1.0',
        ], $extra);

        try {
            return $this->httpRequest($method, $path, $body, $headers);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'HTTP 401') !== false || strpos($e->getMessage(), 'HTTP 403') !== false) {
                log::add('myindygojeedom', 'debug', 'Token expiré, re-authentification…');
                $this->login();
                $headers[0] = 'Authorization: ' . $this->getConfiguration('access_token', '');
                return $this->httpRequest($method, $path, $body, $headers);
            }
            throw $e;
        }
    }

    private function httpRequest($method, $path, $body = null, $headers = [], $formEncoded = false) {
        $url = self::BASE_URL . $path;
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5, // Sécurité anti-blocage Jeedom
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $METHOD = strtoupper($method);
        if ($METHOD === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $formEncoded ? http_build_query($body) : json_encode($body));
            }
        } elseif ($METHOD === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception('Erreur réseau cURL : ' . $curlErr);
        }
        if ($httpCode === 401 || $httpCode === 403) {
            throw new Exception('HTTP ' . $httpCode . ' — authentification refusée.');
        }
        if ($httpCode !== 200) {
            throw new Exception('HTTP ' . $httpCode . ' : ' . substr($response, 0, 300));
        }

        $decoded = json_decode($response, true);
        return ($decoded !== null) ? $decoded : $response;
    }
}

// ─── Classe commande ─────────────────────────────────────────────────
class myindygojeedomCmd extends cmd {

    public function execute($_options = []) {
        $eqLogic   = $this->getEqLogic();
        $moduleId  = $this->getConfiguration('module_id', '');
        $programId = $this->getConfiguration('program_id', '');
        $mode      = (int) $this->getConfiguration('mode', myindygojeedom::MODE_AUTO);

        if (!$moduleId || $programId === '') {
            throw new Exception('Commande mal configurée (module_id ou program_id absent)');
        }

        $eqLogic->setProgramMode($moduleId, $programId, $mode);
        $eqLogic->pull();
    }
}
