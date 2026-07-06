<?php
################################################################################################
# @Name : /plugins/entra_company_agency/entra_company_agency.php
# @Description : gestion de la correspondance societe Entra ID (companyName) <-> agence GestSup
# @Call : /menu.php
################################################################################################

//prevent direct access
if(!isset($_SESSION['user_id'])) {header('HTTP/1.0 403 Forbidden'); exit;}

//reuse the same sync logic the SSO login hook uses (function is guarded by function_exists)
require_once(__DIR__.'/azure_ad_auth_success.php');

//load plugin locale (fallback to French if the user's language isn't translated)
if(file_exists(__DIR__.'/locale/'.$ruser['language'].'.php')) {
	require_once(__DIR__.'/locale/'.$ruser['language'].'.php');
} else {
	require_once(__DIR__.'/locale/fr_FR.php');
}

//check right
if(!$rright['admin']) {echo DisplayMessage('error',T_("Vous n'avez pas accès à cette page, contactez votre administrateur")); exit;}

//add a mapping
if($_GET['action']=='add' && $_POST['entra_company_name'] && $_POST['agency_id'])
{
	//tcompany.name is stored HTML-entity-encoded, decode to get the raw text Entra ID actually sends
	$company_name=trim(html_entity_decode(strip_tags($_POST['entra_company_name']), ENT_QUOTES, 'UTF-8'));

	//check for an existing mapping for this company name
	$qry=$db->prepare("SELECT `id` FROM `tentra_company_agency` WHERE `entra_company_name`=:entra_company_name");
	$qry->execute(array('entra_company_name' => $company_name));
	$exist=$qry->fetch();
	$qry->closeCursor();

	if(!empty($exist['id']))
	{
		echo DisplayMessage('error',_lang_eca_msg_duplicate);
	} else {
		$qry=$db->prepare("INSERT INTO `tentra_company_agency` (`entra_company_name`,`agency_id`) VALUES (:entra_company_name,:agency_id)");
		$qry->execute(array('entra_company_name' => $company_name,'agency_id' => $_POST['agency_id']));
		logit('admin','Created Entra ID company mapping "'.$company_name.'" to agency id '.$_POST['agency_id'],$_SESSION['user_id']);
		echo DisplayMessage('success',_lang_eca_msg_added);
	}
}

//update a mapping
if($_GET['action']=='edit' && $_GET['id'] && $_POST['entra_company_name'] && $_POST['agency_id'])
{
	$company_name=trim(html_entity_decode(strip_tags($_POST['entra_company_name']), ENT_QUOTES, 'UTF-8'));

	$qry=$db->prepare("UPDATE `tentra_company_agency` SET `entra_company_name`=:entra_company_name,`agency_id`=:agency_id WHERE `id`=:id");
	$qry->execute(array('entra_company_name' => $company_name,'agency_id' => $_POST['agency_id'],'id' => $_GET['id']));
	logit('admin','Updated Entra ID company mapping id '.$_GET['id'],$_SESSION['user_id']);
	echo DisplayMessage('success',_lang_eca_msg_updated);
}

//delete a mapping
if($_GET['action']=='delete' && $_GET['id'])
{
	$qry=$db->prepare("DELETE FROM `tentra_company_agency` WHERE `id`=:id");
	$qry->execute(array('id' => $_GET['id']));
	logit('admin','Deleted Entra ID company mapping id '.$_GET['id'],$_SESSION['user_id']);
	echo DisplayMessage('success',_lang_eca_msg_deleted);
}

//apply the mappings to all existing users right now, instead of waiting for their next SSO
//login — GestSup's own Entra ID directory sync already keeps tusers.company up to date
if($_GET['action']=='sync_all')
{
	$qry=$db->prepare("
		SELECT `u`.`id` AS `user_id`, `c`.`name` AS `company_name`
		FROM `tusers` `u`
		INNER JOIN `tcompany` `c` ON `c`.`id`=`u`.`company`
		WHERE `u`.`company`!=0 AND `u`.`disable`='0'
	");
	$qry->execute();
	$users=$qry->fetchAll();
	$qry->closeCursor();

	$matched=0;
	foreach($users as $u)
	{
		$company_name=trim(html_entity_decode($u['company_name'], ENT_QUOTES, 'UTF-8'));
		if(gs_sync_user_agency_from_entra_company($db, $u['user_id'], $company_name)) {$matched++;}
	}

	logit('admin','Synchronisation manuelle des agences Entra ID : '.count($users).' utilisateur(s) examiné(s), '.$matched.' correspondance(s) trouvée(s)',$_SESSION['user_id']);
	echo DisplayMessage('success',sprintf(_lang_eca_msg_sync_done,count($users),$matched));
}

echo '
<div class="page-header position-relative">
	<h1 class="page-title text-primary-m2">
		<i class="fa fa-building text-primary-m2"></i>
		'._lang_eca_page_title.'
	</h1>
	<a href="./index.php?page=plugins/entra_company_agency/entra_company_agency&amp;action=sync_all" class="btn btn-secondary position-absolute" style="top:0;right:0;" onclick="return confirm(\''._lang_eca_confirm_sync.'\');" title="'._lang_eca_sync_hint.'"><i class="fa fa-sync"></i> '._lang_eca_btn_sync_now.'</a>
</div>
<div class="card bcard bgc-transparent shadow">
	<div class="card-body p-3">
		<p class="text-grey-m2">'._lang_eca_page_description.'</p>

		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th>'._lang_eca_col_entra_company.'</th>
					<th>'._lang_eca_col_agency.'</th>
					<th class="text-center" style="width:120px;">'._lang_eca_col_actions.'</th>
				</tr>
			</thead>
			<tbody>
';

$qry=$db->prepare("
	SELECT tentra_company_agency.id, tentra_company_agency.entra_company_name, tagencies.name AS agency_name
	FROM `tentra_company_agency`
	LEFT JOIN `tagencies` ON tagencies.id=tentra_company_agency.agency_id
	ORDER BY tentra_company_agency.entra_company_name
");
$qry->execute();
while($row=$qry->fetch())
{
	echo '
				<tr>
					<td>'.htmlspecialchars($row['entra_company_name']).'</td>
					<td>'.htmlspecialchars(html_entity_decode($row['agency_name'], ENT_QUOTES, 'UTF-8')).'</td>
					<td class="text-center">
						<a href="./index.php?page=plugins/entra_company_agency/entra_company_agency&amp;action=edit_form&amp;id='.$row['id'].'" title="'.T_('Modifier').'"><i class="fa fa-edit text-primary-m2"></i></a>&nbsp;
						<a href="./index.php?page=plugins/entra_company_agency/entra_company_agency&amp;action=delete&amp;id='.$row['id'].'" onclick="return confirm(\''._lang_eca_confirm_delete.'\');" title="'.T_('Supprimer').'"><i class="fa fa-trash text-danger"></i></a>
					</td>
				</tr>
	';
}
$qry->closeCursor();

echo '
			</tbody>
		</table>
';

//preload mapping for edit form
$edit_row=array('id' => '','entra_company_name' => '','agency_id' => '');
if($_GET['action']=='edit_form' && $_GET['id'])
{
	$qry=$db->prepare("SELECT `id`,`entra_company_name`,`agency_id` FROM `tentra_company_agency` WHERE `id`=:id");
	$qry->execute(array('id' => $_GET['id']));
	$found=$qry->fetch();
	$qry->closeCursor();
	if(!empty($found)) {$edit_row=$found;}
}

echo '
		<hr>
		<h3 class="text-primary-m2">'; if($edit_row['id']) {echo _lang_eca_form_title_edit;} else {echo _lang_eca_form_title_add;} echo '</h3>
		<form method="post" action="./index.php?page=plugins/entra_company_agency/entra_company_agency&amp;action='; if($edit_row['id']) {echo 'edit&amp;id='.$edit_row['id'];} else {echo 'add';} echo '">
			<div class="form-group row">
				<label class="col-md-3 col-form-label" for="entra_company_name">'._lang_eca_form_label_company.'</label>
				<div class="col-md-5">
					<select class="form-control" name="entra_company_name" id="entra_company_name" required>
						<option value="">'._lang_eca_form_select_placeholder.'</option>
';

//dropdown fed from the existing GestSup company list (tcompany), decoded so the stored value
//matches the raw plain-text companyName Entra ID actually sends
$qry=$db->prepare("SELECT `id`,`name` FROM `tcompany` WHERE `id`!=0 AND `disable`='0' ORDER BY `name`");
$qry->execute();
while($company=$qry->fetch())
{
	$company_name_decoded=html_entity_decode($company['name'], ENT_QUOTES, 'UTF-8');
	echo '<option value="'.htmlspecialchars($company_name_decoded).'"'; if($company_name_decoded==$edit_row['entra_company_name']) {echo ' selected';} echo '>'.htmlspecialchars($company_name_decoded).'</option>';
}
$qry->closeCursor();

echo '
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-md-3 col-form-label" for="agency_id">'._lang_eca_form_label_agency.'</label>
				<div class="col-md-5">
					<select class="form-control" name="agency_id" id="agency_id" required>
						<option value="">'._lang_eca_form_select_placeholder.'</option>
';

$qry=$db->prepare("SELECT `id`,`name` FROM `tagencies` WHERE `disable`='0' ORDER BY `name`");
$qry->execute();
while($agency=$qry->fetch())
{
	echo '<option value="'.$agency['id'].'"'; if($agency['id']==$edit_row['agency_id']) {echo ' selected';} echo '>'.htmlspecialchars(html_entity_decode($agency['name'], ENT_QUOTES, 'UTF-8')).'</option>';
}
$qry->closeCursor();

echo '
					</select>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-md-5 offset-md-3">
					<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> '.T_('Enregistrer').'</button>
'; if($edit_row['id']) {echo '<a href="./index.php?page=plugins/entra_company_agency/entra_company_agency" class="btn btn-secondary"><i class="fa fa-times"></i> '.T_('Annuler').'</a>';} echo '
				</div>
			</div>
		</form>
	</div>
</div>
';
?>
