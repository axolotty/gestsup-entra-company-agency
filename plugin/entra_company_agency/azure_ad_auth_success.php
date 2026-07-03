<?php
################################################################################################
# @Name : /plugins/entra_company_agency/azure_ad_auth_success.php
# @Description : links the user to the GestSup agency mapped to their Entra ID company (companyName)
# @Call : /azure_ad_auth.php, /azure_ad_auth2.php (hook 'azure_ad_auth_success')
################################################################################################

if(!function_exists("gs_sync_user_agency_from_entra_company")){
	//never removes an existing agency link, only adds the association if it's missing
	function gs_sync_user_agency_from_entra_company($db, $user_id, $entra_company_name)
	{
		if(!$entra_company_name) {return false;}

		//look up the agency mapped to this Entra ID company
		$qry=$db->prepare("SELECT `agency_id` FROM `tentra_company_agency` WHERE `entra_company_name`=:entra_company_name");
		$qry->execute(array('entra_company_name' => $entra_company_name));
		$mapping=$qry->fetch();
		$qry->closeCursor();

		if(empty($mapping['agency_id'])) {return false;}

		//check whether the user is already linked to this agency
		$qry=$db->prepare("SELECT `id` FROM `tusers_agencies` WHERE `user_id`=:user_id AND `agency_id`=:agency_id");
		$qry->execute(array('user_id' => $user_id,'agency_id' => $mapping['agency_id']));
		$assoc=$qry->fetch();
		$qry->closeCursor();

		if(empty($assoc['id']))
		{
			$qry=$db->prepare("INSERT INTO `tusers_agencies` (`user_id`,`agency_id`) VALUES (:user_id,:agency_id)");
			$qry->execute(array('user_id' => $user_id,'agency_id' => $mapping['agency_id']));
			logit('azure_ad_auth','Linked agency id '.$mapping['agency_id'].' to user id '.$user_id.' from Entra ID company "'.$entra_company_name.'"','0');
		}

		return $mapping['agency_id'];
	}
}

//$db, $me and $gs_user are provided by azure_ad_auth.php / azure_ad_auth2.php at hook time
if(!empty($me['companyName']) && !empty($gs_user['id']))
{
	gs_sync_user_agency_from_entra_company($db, $gs_user['id'], $me['companyName']);
}
?>
