<?php
################################################################################
# @Name : /plugins/entra_company_agency/menu.php
# @Description : display menu of current plugin
# @Call : /plugin.php
################################################################################

if($rright['admin'])
{
	//load plugin locale (fallback to French if the user's language isn't translated)
	if(!defined('_lang_eca_menu_label'))
	{
		if(file_exists(__DIR__.'/locale/'.$ruser['language'].'.php')) {
			require_once(__DIR__.'/locale/'.$ruser['language'].'.php');
		} else {
			require_once(__DIR__.'/locale/fr_FR.php');
		}
	}

	if($_GET['page']=='plugins/entra_company_agency/entra_company_agency') {echo '<li class="nav-item active" >';} else {echo '<li class="nav-item" >';}
		echo '
		<a class="nav-link" href="index.php?page=plugins/entra_company_agency/entra_company_agency" >
			<i class="nav-icon fa fa-building"></i>
			<span class="nav-text fadeable">'._lang_eca_menu_label.'</span>
		</a>
	</li>';
}
?>
