# MyIndygo — Plugin Jeedom

Pilotez votre piscine connectée **Indygo Pool Command** depuis Jeedom.

## Fonctionnalités

- Température de l'eau (commande info numérique, historisée)
- Filtration active (commande info binaire, historisée)
- Mode courant de chaque programme — Filtration, Auxiliaires (commande info texte)
- Commandes action Off / On / Auto pour chaque programme
- Détection automatique des équipements au premier lancement
- Rafraîchissement automatique toutes les 5 minutes (cron Jeedom)

## Installation

1. Dans Jeedom : **Plugins > Gestion des plugins > +**
2. Choisir le dépôt **GitHub**, entrer : `benoitleq/myindygojeedom`, branche `main`
3. Installer puis activer le plugin
4. Aller dans **Plugins > Confort > MyIndygo**
5. Cliquer **Ajouter une piscine**, renseigner nom + objet
6. Onglet Équipement : saisir l'email, le mot de passe et le Pool ID
7. Sauvegarder → les commandes sont créées automatiquement

## Trouver le Pool ID

Connectez-vous sur [myindygo.com](https://myindygo.com), allez sur votre piscine.  
L'URL contient : `https://myindygo.com/pools/<POOL_ID>#dashboard`

## Commandes créées automatiquement

| Nom | Type | Description |
|---|---|---|
| `Température eau` | info numérique | Température en °C (historisée) |
| `Filtration active` | info binaire | 1 = en marche, 0 = arrêtée (historisée) |
| `<Programme> — mode` | info texte | Off / On / Auto |
| `<Programme> → Off` | action | Force l'arrêt |
| `<Programme> → On` | action | Force la marche |
| `<Programme> → Auto` | action | Repasse en programmation automatique |

## Latence LoRa

Les commandes passent par le cloud → gateway LRMB → antenne LoRa → Pool Command.  
Le retour visuel peut prendre **10 à 30 secondes**. C'est normal.

## Dépannage

**Identifiants refusés** : vérifiez email/mot de passe, utilisez « Tester la connexion ».

**Aucune commande créée** : cliquez « Synchroniser les équipements » après sauvegarde.

**Le mode change dans Jeedom mais pas sur le device** : attendez 30 s, vérifiez la LED verte de la passerelle LRMB.

## Crédits

- API reverse-engineerée par [FunFR](https://github.com/FunFR/ha-indygo-pool) (Apache 2.0)
- Plugin Jeedom par [benoitleq](https://github.com/benoitleq/myindygojeedom)

## Disclaimer

Ce plugin n'est pas affilié à Indygo / Solem. Utilisez-le à vos propres risques.
