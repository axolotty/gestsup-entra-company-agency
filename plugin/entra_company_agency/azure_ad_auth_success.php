<?php
################################################################################################
# @Name : /plugins/entra_company_agency/azure_ad_auth_success.php
# @Description : links the user to the GestSup agency mapped to their Entra ID company (companyName)
# @Call : /azure_ad_auth.php, /azure_ad_auth2.php (hook 'azure_ad_auth_success')
################################################################################################

if(!function_exists("gs_sync_user_agency_from_entra_company")){
	//keeps the user's "current company" agency in sync with Entra ID: adds the mapped
	//agency, and if a previous login had linked a different one, removes that old link.
	//tentra_company_agency_sync only ever records a link this function created itself
	//(the INSERT actually happened) — a pre-existing association, whether assigned
	//manually or coinciding by chance with the mapped agency, is never claimed or removed.
	function gs_sync_user_agency_from_entra_company($db, $user_id, $entra_company_name)
	{
		if(!$entra_company_name) {return false;}

		//look up the agency mapped to this Entra ID company
		$qry=$db->prepare("SELECT `agency_id` FROM `tentra_company_agency` WHERE `entra_company_name`=:entra_company_name");
		$qry->execute(array('entra_company_name' => $entra_company_name));
		$mapping=$qry->fetch();
		$qry->closeCursor();

		if(empty($mapping['agency_id'])) {return false;}
		$new_agency_id=$mapping['agency_id'];

		//what agency (if any) did we ourselves link for this user last time?
		$qry=$db->prepare("SELECT `agency_id` FROM `tentra_company_agency_sync` WHERE `user_id`=:user_id");
		$qry->execute(array('user_id' => $user_id));
		$sync=$qry->fetch();
		$qry->closeCursor();

		if(!empty($sync['agency_id']))
		{
			if($sync['agency_id']==$new_agency_id) {return $new_agency_id;} //already in sync

			//company changed since last login: drop the agency we ourselves linked last time
			$qry=$db->prepare("DELETE FROM `tusers_agencies` WHERE `user_id`=:user_id AND `agency_id`=:agency_id");
			$qry->execute(array('user_id' => $user_id,'agency_id' => $sync['agency_id']));
			$qry=$db->prepare("DELETE FROM `tentra_company_agency_sync` WHERE `user_id`=:user_id");
			$qry->execute(array('user_id' => $user_id));
			logit('azure_ad_auth','Unlinked agency id '.$sync['agency_id'].' from user id '.$user_id.' (Entra ID company changed to "'.$entra_company_name.'")','0');
		}

		//add the new agency, unless the user already happens to have it (manually assigned,
		//or from some other source) — in that case leave it untouched and untracked, so a
		//future company change can never remove a link this plugin didn't create itself
		$qry=$db->prepare("SELECT `id` FROM `tusers_agencies` WHERE `user_id`=:user_id AND `agency_id`=:agency_id");
		$qry->execute(array('user_id' => $user_id,'agency_id' => $new_agency_id));
		$assoc=$qry->fetch();
		$qry->closeCursor();

		if(empty($assoc['id']))
		{
			$qry=$db->prepare("INSERT INTO `tusers_agencies` (`user_id`,`agency_id`) VALUES (:user_id,:agency_id)");
			$qry->execute(array('user_id' => $user_id,'agency_id' => $new_agency_id));
			logit('azure_ad_auth','Linked agency id '.$new_agency_id.' to user id '.$user_id.' from Entra ID company "'.$entra_company_name.'"','0');

			$qry=$db->prepare("REPLACE INTO `tentra_company_agency_sync` (`user_id`,`agency_id`) VALUES (:user_id,:agency_id)");
			$qry->execute(array('user_id' => $user_id,'agency_id' => $new_agency_id));
		}

		return $new_agency_id;
	}
}

//$db, $me and $gs_user are provided by azure_ad_auth.php / azure_ad_auth2.php at hook time
if(!empty($me['companyName']) && !empty($gs_user['id']))
{
	gs_sync_user_agency_from_entra_company($db, $gs_user['id'], $me['companyName']);
}
?>
