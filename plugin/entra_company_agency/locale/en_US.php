<?php
	define("_lang_eca_menu_label", "Entra ID Companies");
	define("_lang_eca_page_title", "Entra ID company / agency mapping");
	define("_lang_eca_page_description", "On every SSO login, GestSup reads the \"companyName\" attribute from the user's Entra ID profile and, if a mapping exists below, automatically links the matching GestSup agency (a manually-assigned agency is never removed; if the company changes, the agency the plugin previously linked is swapped for the new one).");
	define("_lang_eca_col_entra_company", "Entra ID company (companyName)");
	define("_lang_eca_col_agency", "GestSup agency");
	define("_lang_eca_col_actions", "Actions");
	define("_lang_eca_form_title_edit", "Edit mapping");
	define("_lang_eca_form_title_add", "Add a mapping");
	define("_lang_eca_form_label_company", "Entra ID company");
	define("_lang_eca_form_label_agency", "GestSup agency");
	define("_lang_eca_form_select_placeholder", "-- Select --");
	define("_lang_eca_msg_added", "Mapping added.");
	define("_lang_eca_msg_updated", "Mapping updated.");
	define("_lang_eca_msg_deleted", "Mapping deleted.");
	define("_lang_eca_msg_duplicate", "A mapping already exists for this Entra ID company");
	define("_lang_eca_confirm_delete", "Confirm deletion?");
	define("_lang_eca_btn_sync_now", "Sync now");
	define("_lang_eca_sync_hint", "Immediately applies the mappings above to all existing users, based on the company GestSup already knows for them — no need to wait for their next SSO login.");
	define("_lang_eca_confirm_sync", "Apply the mappings to all existing users now? (any manually-assigned agency is left untouched)");
	define("_lang_eca_msg_sync_done", "%d user(s) checked, %d had a matching company and were synced.");
?>
