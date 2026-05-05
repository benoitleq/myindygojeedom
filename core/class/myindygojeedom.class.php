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
    const TOKEN_EXPIRY_MARGIN   = 300;   // secondes avant expiration pour renouveler
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

    // ─── Appelé après chaque sauvegarde dans l'UI ─────────────────────
    public function postSave() {
        // Ne pas appeler pull() au postSave : les identifiants viennent d'être sauvegardés
        // mais l'utilisateur doit valider avec "Tester la connexion" ou "Synchroniser".
    }

    // ─── Widget dashboard ────────────────────────────────────────────
    public function toHtml($_version = 'dashboard') {
        // Pas de cache : on relit toujours les valeurs courantes des commandes
        // pour que l'état actif (AUTO/ON/OFF) soit toujours à jour.
        if (!$this->getIsEnable()) {
            return '';
        }

        $cmdTemp = $this->getCmd('info', 'temperature');
        $cmdFilt = $this->getCmd('info', 'filtration_running');

        $temp = ($cmdTemp) ? $cmdTemp->execCmd() : null;
        $filt = ($cmdFilt && $cmdFilt->execCmd() !== '') ? (bool)$cmdFilt->execCmd() : null;
        $tempDisplay = ($temp !== null && $temp !== '')
            ? number_format(floatval($temp), 1) . ' °C' : '— °C';

        // ── Styles helpers ──────────────────────────────────────────
        // overflow:visible sur le card pour que les sections ne soient pas coupées
        $S_CARD   = 'background:#15191f;border-radius:14px;overflow:visible;font-family:-apple-system,BlinkMacSystemFont,sans-serif;min-width:280px;box-shadow:0 8px 24px rgba(0,0,0,.5);';
        $S_HDR    = 'padding:10px 16px;background:linear-gradient(135deg,#0a2342,#0d4b8a);display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #1e3a5f;border-radius:14px 14px 0 0;';
        $S_TITLE  = 'color:#fff;font-size:13px;font-weight:700;letter-spacing:.3px;';
        $S_TEMP   = 'color:#60b4ff;font-size:18px;font-weight:800;';
        $S_SEC    = 'padding:10px 14px;border-top:1px solid #1e2433;';
        $S_STTL   = 'color:#5a6a80;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;';
        $S_ROW    = 'display:flex;gap:8px;';

        // ── Bouton helper ────────────────────────────────────────────
        // Retourne le HTML d'un bouton de mode (Auto/On/Off)
        $btn = function($cmdId, $icon, $label, $sublabel, $active, $activeGrad, $activeBorder, $activeIconColor, $activeLabelColor) {
            $bg     = $active ? $activeGrad  : 'background:#1e2433;';
            $border = $active ? $activeBorder : 'border:1px solid #252d3d;';
            $ic     = $active ? $activeIconColor  : 'color:#2e3d55;';
            $lc     = $active ? $activeLabelColor : 'color:#2e3d55;';
            $sc     = $active ? 'color:#a0c0e0;'  : 'color:#1e2d40;';
            $h  = '<a class="cmd action" data-id="' . $cmdId . '" data-action="other"';
            $h .= ' style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;';
            $h .= 'padding:9px 4px;border-radius:10px;cursor:pointer;text-decoration:none;gap:3px;' . $bg . $border . '">';
            $h .= '<i class="fas ' . $icon . '" style="font-size:20px;' . $ic . '"></i>';
            $h .= '<span style="font-size:12px;font-weight:800;letter-spacing:.5px;' . $lc . '">' . $label . '</span>';
            if ($sublabel !== '') {
                $h .= '<span style="font-size:9px;letter-spacing:.5px;' . $sc . '">' . $sublabel . '</span>';
            }
            $h .= '</a>';
            return $h;
        };

        // ── Conteneur ────────────────────────────────────────────────
        $h  = '<div class="eqLogic-widget cmd-widget ' . jeedom::versionAlias($_version) . '"';
        $h .= ' data-eqLogic_id="' . $this->getId() . '" style="' . $S_CARD . '">';

        // ── En-tête ──────────────────────────────────────────────────
        $h .= '<div style="' . $S_HDR . '">';
        $h .= '<div style="' . $S_TITLE . '"><i class="fas fa-swimming-pool" style="margin-right:7px;opacity:.8;"></i>' . htmlspecialchars($this->getName()) . '</div>';
        $h .= '<div style="' . $S_TEMP . 'display:flex;align-items:center;gap:5px;">';
        $h .= '<i class="fas fa-thermometer-half" style="color:#ff7043;font-size:14px;"></i>';
        if ($cmdTemp) {
            $h .= '<span class="cmd" data-id="' . $cmdTemp->getId() . '">' . $tempDisplay . '</span>';
        } else {
            $h .= $tempDisplay;
        }
        $h .= '</div></div>';

        // ── Sections programmes (construites depuis les cmds action) ────
        // Regrouper les commandes action par préfixe prog_X
        $sections = [];
        foreach ($this->getCmd() as $cmd) {
            if ($cmd->getType() !== 'action') continue;
            $logId = $cmd->getLogicalId();
            if (!preg_match('/^(prog_.+)_(set_auto|set_on|set_off)$/', $logId, $m)) continue;
            $prefix = $m[1];
            $verb   = $m[2];
            if (!isset($sections[$prefix])) {
                $sections[$prefix] = ['cmds' => [], 'mode_cmd' => null, 'name' => ''];
            }
            $sections[$prefix]['cmds'][$verb] = $cmd;
            if (empty($sections[$prefix]['name'])) {
                // Extraire le nom du programme depuis le nom de la commande (ex: "Filtration → Auto" → "Filtration")
                $rawName = preg_replace('/ ?→.+$/u', '', $cmd->getName());
                $sections[$prefix]['name'] = trim($rawName);
            }
        }
        // Attacher la commande info mode (si elle existe)
        foreach ($sections as $prefix => &$sec) {
            $sec['mode_cmd'] = $this->getCmd('info', $prefix . '_mode');
            $sec['is_filt']  = stripos($sec['name'], 'filtrat') !== false || stripos($sec['name'], 'pompe') !== false;
        }
        unset($sec);

        // Filtration en premier
        uasort($sections, function($a, $b) { return (int)$b['is_filt'] - (int)$a['is_filt']; });

        foreach ($sections as $prefix => $sec) {
            $progName = $sec['name'];
            if (strlen($progName) < 2) continue;

            $isFilt  = $sec['is_filt'];
            $iconOn  = $isFilt ? 'fa-fan'         : 'fa-sun';
            $iconOff = $isFilt ? 'fa-stop-circle' : 'fa-moon';

            $modeKey = '';
            if ($sec['mode_cmd']) {
                $modeKey = strtolower($sec['mode_cmd']->execCmd() ?? '');
            }

            $onSublabel = ($isFilt && $filt !== null) ? ($filt ? 'ACTIF' : 'ARRÊTÉ') : '';

            $h .= '<div style="' . $S_SEC . '">';
            $h .= '<div style="' . $S_STTL . '">' . htmlspecialchars(strtoupper($progName)) . '</div>';
            $h .= '<div style="' . $S_ROW . '">';

            // AUTO uniquement pour la filtration
            if ($isFilt && isset($sec['cmds']['set_auto'])) {
                $h .= $btn($sec['cmds']['set_auto']->getId(), 'fa-clock', 'AUTO', '',
                    $modeKey === 'auto',
                    'background:linear-gradient(145deg,#0d2d6b,#1565c0);',
                    'border:1px solid #1e88e5;',
                    'color:#64b5f6;', 'color:#e3f2fd;'
                );
            }

            if (isset($sec['cmds']['set_on'])) {
                $h .= $btn($sec['cmds']['set_on']->getId(), $iconOn, 'ON', $onSublabel,
                    $modeKey === 'on',
                    'background:linear-gradient(145deg,#0a3d1a,#1b5e20);',
                    'border:1px solid #2e7d32;',
                    'color:#69f0ae;', 'color:#e8f5e9;'
                );
            }

            if (isset($sec['cmds']['set_off'])) {
                $h .= $btn($sec['cmds']['set_off']->getId(), $iconOff, 'OFF', '',
                    $modeKey === 'off',
                    'background:linear-gradient(145deg,#4a0909,#b71c1c);',
                    'border:1px solid #c62828;',
                    'color:#ef9a9a;', 'color:#ffebee;'
                );
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

        // 0. Nettoyage des commandes avec des logicalId obsolètes (anciennes versions du plugin)
        foreach ($this->getCmd() as $cmd) {
            $logId = $cmd->getLogicalId();
            // prog__* = program_id vide (ancienne version avec null program_id)
            if (strpos($logId, 'prog__') === 0) {
                log::add('myindygojeedom', 'info', '[pull] suppression cmd prog__ vide logicalId=' . $logId);
                $cmd->remove();
                continue;
            }
            if ($logId !== 'temperature' && $logId !== 'filtration_running'
                && strpos($logId, 'prog_') !== 0) {
                log::add('myindygojeedom', 'info', '[pull] suppression cmd obsolète logicalId=' . $logId);
                $cmd->remove();
            }
        }

        // 1. Modules
        $modules = $this->fetchModules();
        if (empty($modules)) {
            throw new Exception('Aucun module retourné par l\'API MyIndygo.');
        }

        // 2. Hardware IDs
        list($poolAddress, $deviceShortId) = $this->resolveHardwareIds($modules);
        $this->setConfiguration('pool_address', $poolAddress);
        $this->setConfiguration('device_short_id', $deviceShortId);
        $this->save(true); // true = pas de postSave récursif

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
        $temperature      = $this->extractTemperature($status);
        $filtRunning      = $this->extractFiltrationState($status);
        $programs         = $this->extractPrograms($modules);

        // 6. Mise à jour des commandes info
        $this->updateTemperatureCmd($temperature);
        $this->updateFiltrationRunningCmd($filtRunning);

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

        log::add(
            'myindygojeedom', 'info',
            '[pull] OK — temp=' . $temperature . '°C, ' . count($seenProgIds) . ' programme(s)'
        );
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

        $resp = $this->httpRequest('POST', '/oauth2/token',
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
        return $this->apiRequest(
            'GET',
            '/v1/module/' . urlencode($poolAddress) . '/status/' . urlencode($deviceShortId),
            null,
            ['x-requested-with: XMLHttpRequest']
        );
    }

    // ─── Résolution des IDs hardware (logique FunFR) ──────────────────
    private function resolveHardwareIds($modules) {
        $gateway = null;
        $lrPc    = null;

        foreach ($modules as $m) {
            if (($m['type'] ?? '') === 'lr-mb-10') $gateway = $m;
            if (($m['type'] ?? '') === 'lr-pc')    $lrPc    = $m;
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

    // ─── Extraction des données ───────────────────────────────────────
    private function extractTemperature($status) {
        foreach ($status['sensorState'] ?? [] as $s) {
            if (($s['index'] ?? -1) === 0 && isset($s['value'])) {
                return round(floatval($s['value']) / 100.0, 1);
            }
        }
        $temp = $status['temperature'] ?? null;
        return $temp !== null ? round(floatval($temp), 1) : null;
    }

    private function extractFiltrationState($status) {
        foreach ($status['pool'] ?? [] as $item) {
            if (($item['index'] ?? -1) === 0 && isset($item['value'])) {
                return floatval($item['value']) === 1.0;
            }
        }
        return null;
    }

    private function extractPrograms($modules) {
        $out = [];
        foreach ($modules as $mod) {
            $modId   = (string)($mod['id'] ?? '');
            $modName = $mod['name'] ?? ('Module ' . $modId);
            $progList = $mod['programs'] ?? [];
            log::add('myindygojeedom', 'debug', '[extractPrograms] module=' . $modName . ' (' . $modId . ') — ' . count($progList) . ' programme(s)');
            foreach ($progList as $prog) {
                $pc = $prog['programCharacteristics'] ?? null;
                if (!is_array($pc)) {
                    log::add('myindygojeedom', 'debug', '[extractPrograms] skip prog id=' . json_encode($prog['id'] ?? null) . ' — pas de programCharacteristics');
                    continue;
                }
                $ptypeRaw = $pc['programType'] ?? null;
                if (!is_numeric($ptypeRaw) || (int)$ptypeRaw <= 0) {
                    log::add('myindygojeedom', 'debug', '[extractPrograms] skip prog — programType invalide : ' . json_encode($ptypeRaw));
                    continue;
                }
                $ptype = (int)$ptypeRaw;

                $rawProgId = $prog['id'] ?? null;
                $progId    = ($rawProgId !== null && $rawProgId !== '')
                    ? (string)$rawProgId
                    : ('ftype' . $ptype . '_mod' . $modId);

                $progName  = (!empty($prog['name'])) ? $prog['name'] : (self::PROGRAM_TYPE_NAMES[$ptype] ?? 'Programme ' . $ptype);
                $modeRaw   = $pc['mode'] ?? null;
                log::add('myindygojeedom', 'debug', '[extractPrograms] prog=' . $progName . ' type=' . $ptype . ' id=' . $progId . ' mode=' . json_encode($modeRaw));

                $out[] = [
                    'module_id'       => $modId,
                    'module_name'     => $modName,
                    'program_id'      => $progId,
                    'program_name'    => $progName,
                    'program_type'    => $ptype,
                    'is_filtration'   => $ptype === self::PROGRAM_TYPE_FILTRATION,
                    'current_mode'    => $modeRaw,
                    'typeIsLoraWanV2' => $mod['typeIsLoraWanV2'] ?? false,
                    'raw'             => $prog,
                ];
            }
        }
        log::add('myindygojeedom', 'debug', '[extractPrograms] total=' . count($out) . ' programme(s) extraits');
        return $out;
    }

    // ─── Création / mise à jour des commandes ─────────────────────────
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

        // Commande info : mode courant — toujours mettre à jour le nom
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
        $modeName = self::MODE_NAMES[$mode] ?? 'Indéterminé';
        $cmdMode->event($modeName);

        // Commandes action — toujours mettre à jour le nom et la configuration
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

        $target = null;
        foreach ($programs as $p) {
            if ($p['id'] == $programId) { $target = $p; break; }
        }
        if ($target === null) {
            throw new Exception('Programme ' . $programId . ' introuvable dans le module ' . $moduleId);
        }

        $targetType = $target['programCharacteristics']['programType'] ?? null;

        // Construire la liste complète avec le nouveau mode pour la cible,
        // mode=null pour les autres types (protocole FunFR — ne pas corrompre la config)
        $updated = [];
        foreach ($programs as $prog) {
            $copy              = $prog;
            $copy['dataChanged'] = true;
            $progType          = $copy['programCharacteristics']['programType'] ?? null;

            if ($prog['id'] == $programId) {
                $copy['programCharacteristics']['mode'] = $mode;
            } elseif ($progType !== $targetType) {
                if (array_key_exists('mode', $copy['programCharacteristics'] ?? [])) {
                    $copy['programCharacteristics']['mode'] = null;
                }
            }
            $updated[] = $copy;
        }

        $poolAddress   = $this->getConfiguration('pool_address', '');
        $deviceShortId = $this->getConfiguration('device_short_id', '');

        // 1. Mise à jour base cloud
        $this->apiRequest('PUT', '/api/updatePrograms',
            ['module' => $moduleId, 'programs' => $updated]
        );

        // 2. Push vers le device (cloud → gateway → LoRa)
        if ($poolAddress && $deviceShortId) {
            $this->apiRequest(
                'POST',
                '/api/module/' . urlencode($poolAddress) . '/programs/' . urlencode($deviceShortId),
                ['programs' => $updated]
            );
        }

        // 3. Rapports (non bloquants)
        try {
            $this->apiRequest('POST', '/api/reportModuleDatasSent',    ['module' => $moduleId]);
            $this->apiRequest('POST', '/api/reportProgramsDatasSent',  ['module' => $moduleId, 'programs' => $updated]);
        } catch (Exception $e) {
            log::add('myindygojeedom', 'warning', '[setProgramMode] report non-bloquant : ' . $e->getMessage());
        }

        // 4. Sync LoRaWAN (tentative — non bloquant si non applicable)
        try {
            $this->apiRequest('POST', '/modules/sendDataViaLoRaWAN',
                ['moduleId' => $moduleId, 'sendProgram' => true, 'sendCommand' => true]
            );
        } catch (Exception $e) {
            log::add('myindygojeedom', 'debug', '[setProgramMode] LoRaWAN sync : ' . $e->getMessage());
        }

        $modeName = self::MODE_NAMES[$mode] ?? $mode;
        log::add('myindygojeedom', 'info',
            '[setProgramMode] OK — module=' . $moduleId . ' prog=' . $programId . ' → ' . $modeName
        );
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

        // Re-auth automatique sur 401
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
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
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

        // Rafraîchissement rapide pour mettre à jour l'état dans Jeedom
        // (le device LoRa peut prendre 10-30s, mais le cloud est mis à jour immédiatement)
        $eqLogic->pull();
    }
}
