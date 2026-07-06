<?php
	define("_lang_eca_menu_label", "Sociétés Entra ID");
	define("_lang_eca_page_title", "Correspondance sociétés Entra ID / agences");
	define("_lang_eca_page_description", "À chaque connexion SSO, GestSup lit l'attribut \"companyName\" du profil Entra ID de l'utilisateur et, si une correspondance existe ci-dessous, associe automatiquement l'agence GestSup correspondante (une agence assignée manuellement n'est jamais retirée ; si la société change, l'agence liée précédemment par le plugin est remplacée par la nouvelle).");
	define("_lang_eca_col_entra_company", "Société Entra ID (companyName)");
	define("_lang_eca_col_agency", "Agence GestSup");
	define("_lang_eca_col_actions", "Actions");
	define("_lang_eca_form_title_edit", "Modifier la correspondance");
	define("_lang_eca_form_title_add", "Ajouter une correspondance");
	define("_lang_eca_form_label_company", "Société Entra ID");
	define("_lang_eca_form_label_agency", "Agence GestSup");
	define("_lang_eca_form_select_placeholder", "-- Sélectionner --");
	define("_lang_eca_msg_added", "Correspondance ajoutée.");
	define("_lang_eca_msg_updated", "Correspondance modifiée.");
	define("_lang_eca_msg_deleted", "Correspondance supprimée.");
	define("_lang_eca_msg_duplicate", "Une correspondance existe déjà pour cette société Entra ID");
	define("_lang_eca_confirm_delete", "Confirmer la suppression ?");
	define("_lang_eca_btn_sync_now", "Synchroniser maintenant");
	define("_lang_eca_sync_hint", "Applique immédiatement les correspondances ci-dessus à tous les utilisateurs existants, à partir de leur société déjà connue de GestSup — sans attendre leur prochaine connexion SSO.");
	define("_lang_eca_confirm_sync", "Appliquer les correspondances à tous les utilisateurs existants ? (n'importe quelle agence assignée manuellement restera intacte)");
	define("_lang_eca_msg_sync_done", "%d utilisateur(s) examiné(s), %d correspondance(s) de société trouvée(s) et synchronisée(s).");
?>
