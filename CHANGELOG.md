# Changelog

## [1.1] — 2026-07-06

### FR
- Suivi des changements de société : si le `companyName` Entra ID d'un utilisateur change (changement de maison), l'agence précédemment liée par le plugin est retirée et remplacée par la nouvelle, au lieu de s'accumuler.
- Une agence assignée manuellement par un administrateur n'est jamais retirée par le plugin — seules les agences que le plugin a lui-même liées sont concernées.
- Nouvelle table interne `tentra_company_agency_sync` pour suivre quelle agence a été liée par le plugin pour chaque utilisateur.
- `core-patch/install.sh` peut désormais aussi exécuter le SQL d'installation du plugin (`--db-name`/`--db-user`/...), en plus du patch core — GestSup ne propose aucune page qui le fait automatiquement.

### EN
- Tracks company changes: if a user's Entra ID `companyName` changes (moving to a different "maison"), the agency the plugin previously linked is removed and replaced by the new one, instead of piling up.
- An agency assigned manually by an administrator is never removed by the plugin — only agencies the plugin linked itself are affected.
- New internal table `tentra_company_agency_sync` to track which agency the plugin linked for each user.
- `core-patch/install.sh` can now also run the plugin's SQL install (`--db-name`/`--db-user`/...) alongside the core patch — GestSup has no page that does this automatically.

## [1.0] — 2026-07-03

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
