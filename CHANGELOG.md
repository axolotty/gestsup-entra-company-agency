# Changelog

## [1.3] - 2026-07-06

### FR
- Nouveau patch core optionnel (`core-patch/core/azure_ad.php.diff`) : désactive la réactivation automatique des comptes par la synchronisation d'annuaire Entra ID native de GestSup. Les désactivations automatiques (compte désactivé ou absent d'Entra ID) continuent de fonctionner ; seule la réactivation automatique est neutralisée, pour éviter que des boîtes partagées/génériques désactivées à dessein ne redeviennent des comptes actifs.
- `install.sh` applique désormais 4 patchs (au lieu de 3) et gère les fichiers cibles dans des sous-dossiers (`core/...`).
- Correction d'un bug dans `install.sh` : la sauvegarde d'un fichier cible situé dans un sous-dossier échouait faute de création du sous-dossier correspondant dans le répertoire de backup.

### EN
- New optional core patch (`core-patch/core/azure_ad.php.diff`): disables automatic account re-enabling in GestSup's native Entra ID directory sync. Automatic disabling (account disabled or removed from Entra ID) keeps working; only auto re-enabling is neutralized, so shared/generic mailboxes disabled on purpose don't silently become active accounts again.
- `install.sh` now applies 4 patches (up from 3) and supports target files nested in subdirectories (`core/...`).
- Fixed a bug in `install.sh`: backing up a target file located in a subdirectory failed because the matching subdirectory wasn't created under the backup directory.

## [1.2] - 2026-07-06

### FR
- Nouveau bouton **Synchroniser maintenant** sur la page du plugin : applique toutes les correspondances société ↔ agence aux utilisateurs existants immédiatement, sans attendre leur prochaine connexion SSO. S'appuie sur `tusers.company`, déjà tenu à jour par la synchronisation d'annuaire Entra ID native de GestSup.
- Réutilise exactement la même logique de synchronisation que le hook de connexion SSO : une agence assignée manuellement n'est jamais retirée.

### EN
- New **Sync now** button on the plugin page: applies every company ↔ agency mapping to existing users immediately, without waiting for their next SSO login. Relies on `tusers.company`, already kept up to date by GestSup's own Entra ID directory sync.
- Reuses the exact same sync logic as the SSO login hook: a manually-assigned agency is never removed.

## [1.1] - 2026-07-06

### FR
- Suivi des changements de société : si le `companyName` Entra ID d'un utilisateur change (changement de maison), l'agence précédemment liée par le plugin est retirée et remplacée par la nouvelle, au lieu de s'accumuler.
- Une agence assignée manuellement par un administrateur n'est jamais retirée par le plugin - seules les agences que le plugin a lui-même liées sont concernées.
- Nouvelle table interne `tentra_company_agency_sync` pour suivre quelle agence a été liée par le plugin pour chaque utilisateur.
- `core-patch/install.sh` peut désormais aussi exécuter le SQL d'installation du plugin (`--db-name`/`--db-user`/...), en plus du patch core - GestSup ne propose aucune page qui le fait automatiquement.

### EN
- Tracks company changes: if a user's Entra ID `companyName` changes (moving to a different "maison"), the agency the plugin previously linked is removed and replaced by the new one, instead of piling up.
- An agency assigned manually by an administrator is never removed by the plugin - only agencies the plugin linked itself are affected.
- New internal table `tentra_company_agency_sync` to track which agency the plugin linked for each user.
- `core-patch/install.sh` can now also run the plugin's SQL install (`--db-name`/`--db-user`/...) alongside the core patch - GestSup has no page that does this automatically.

## [1.0] - 2026-07-03

### FR
- Version initiale du plugin `entra_company_agency`.
- Association automatique société Entra ID (`companyName`) → agence GestSup lors de la connexion SSO Office 365.
- Page d'administration : liste, ajout, modification, suppression des correspondances, avec liste déroulante des sociétés GestSup existantes.
- Interface bilingue français / anglais.
- Testé de bout en bout sur environnement de préproduction (cycle install/activation/utilisation/désinstallation).

### EN
- Initial release of the `entra_company_agency` plugin.
- Automatic Entra ID company (`companyName`) → GestSup agency link on Office 365 SSO login.
- Admin page: list, add, edit, delete mappings, with a dropdown of existing GestSup companies.
- Bilingual French / English UI.
- Tested end-to-end on a preproduction environment (install/enable/use/uninstall lifecycle).
