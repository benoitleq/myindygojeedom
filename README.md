# MyIndygo — Plugin Jeedom

![version](https://img.shields.io/badge/version-1.0.0-blue)
![licence](https://img.shields.io/badge/licence-MIT-green)
![jeedom](https://img.shields.io/badge/Jeedom-4.2+-orange)

Plugin Jeedom pour piloter votre piscine connectée **Indygo Pool Command** via l'API officielle MyIndygo (OAuth2).

---

## Fonctionnalités

- 🌡️ **Température de l'eau** — commande info numérique, historisée
- 💧 **Filtration active** — commande info binaire, historisée
- 🎛️ **Mode courant** de chaque programme (Filtration, Éclairage, PAC…)
- ▶️ **Commandes action** Off / On / Auto par programme détecté
- 🔍 **Auto-détection** des équipements au premier lancement
- 🔄 **Rafraîchissement automatique** toutes les 5 min (cron Jeedom natif)
- 📡 **API officielle OAuth2** — pas de scraping, robuste aux mises à jour
- 🔒 Identifiants stockés **localement** dans Jeedom

## Installation

**Plugins > Gestion des plugins > +** (icône en haut à droite) **> GitHub**

| Champ | Valeur |
|---|---|
| Utilisateur | `benoitleq` |
| Dépôt | `myindygojeedom` |
| Branche | `main` |

1. Installer et activer le plugin
2. **Plugins > Confort > MyIndygo** → **Ajouter une piscine**
3. Renseigner l'**email**, le **mot de passe** myindygo.com et le **Pool ID**
4. Sauvegarder → les commandes sont créées automatiquement

> 💡 **Trouver le Pool ID** : connectez-vous sur [myindygo.com](https://myindygo.com), l'URL contient `pools/<POOL_ID>#dashboard`.

## Commandes créées automatiquement

| Commande | Type | Détail |
|---|---|---|
| Température eau | info numérique | °C, historisée |
| Filtration active | info binaire | 1 = en marche, 0 = arrêtée, historisée |
| `<Programme>` — mode | info texte | Off / On / Auto |
| `<Programme>` → Off | action | Forcer l'arrêt |
| `<Programme>` → On | action | Forcer la marche |
| `<Programme>` → Auto | action | Repasser en programmation automatique |

## Compatibilité

Testé avec **Pool Command** (module LoRaWAN V2, type `lr-pc`).  
Compatible aussi avec les modules **IPX**.

> ⚠️ La latence de **10 à 30 secondes** entre la commande et le retour visuel est normale — la commande transite par le cloud → passerelle LRMB → antenne LoRa → Pool Command.

## Dépannage

| Problème | Solution |
|---|---|
| Identifiants refusés | Vérifiez email/mot de passe, cliquez « Tester la connexion » |
| Aucune commande créée | Cliquez « Synchroniser les équipements » après sauvegarde |
| Mode changé dans Jeedom mais pas sur le device | Attendez 30 s, vérifiez la LED verte de la passerelle LRMB |

**Logs** : Analyse > Logs > `myindygojeedom`

## Structure du plugin

```
myindygojeedom/
├── plugin_info/info.json              ← métadonnées Jeedom
├── core/
│   ├── class/myindygojeedom.class.php ← client OAuth2 + eqLogic + cmd
│   ├── php/jeeIndygo.ajax.php         ← handler AJAX
│   └── i18n/fr_FR.json               ← traductions
├── desktop/
│   ├── php/myindygojeedom.php         ← page de configuration
│   ├── js/myindygojeedom.js           ← boutons Tester / Synchroniser
│   └── img/myindygojeedom_icon.png    ← icône du plugin
├── install/install.php
└── docs/fr_FR/                        ← documentation + changelog
```

## Crédits

- API reverse-engineerée par [FunFR](https://github.com/FunFR) via [ha-indygo-pool](https://github.com/FunFR/ha-indygo-pool) (Apache 2.0)
- Inspiré du travail de B_Leq sur le forum HACF

## Disclaimer

Ce plugin n'est pas affilié à Indygo / Solem. Utilisez-le à vos propres risques.  
Ne pas utiliser sur des installations professionnelles ou critiques.
