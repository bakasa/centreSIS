<?php
/**
* @file $Id: PortalNotes.php 43 2006-07-07 11:27:12Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

if($_REQUEST['day_values'] && $_POST['day_values'])
{
	foreach($_REQUEST['day_values'] as $id=>$values)
	{
		if($_REQUEST['day_values'][$id]['START_DATE'] && $_REQUEST['month_values'][$id]['START_DATE'] && $_REQUEST['year_values'][$id]['START_DATE'])
			$_REQUEST['values'][$id]['START_DATE'] = $_REQUEST['day_values'][$id]['START_DATE'].'-'.$_REQUEST['month_values'][$id]['START_DATE'].'-'.$_REQUEST['year_values'][$id]['START_DATE'];
		elseif(isset($_REQUEST['day_values'][$id]['START_DATE']) && isset($_REQUEST['month_values'][$id]['START_DATE']) && isset($_REQUEST['year_values'][$id]['START_DATE']))
			$_REQUEST['values'][$id]['START_DATE'] = '';

		if($_REQUEST['day_values'][$id]['END_DATE'] && $_REQUEST['month_values'][$id]['END_DATE'] && $_REQUEST['year_values'][$id]['END_DATE'])
			$_REQUEST['values'][$id]['END_DATE'] = $_REQUEST['day_values'][$id]['END_DATE'].'-'.$_REQUEST['month_values'][$id]['END_DATE'].'-'.$_REQUEST['year_values'][$id]['END_DATE'];
		elseif(isset($_REQUEST['day_values'][$id]['END_DATE']) && isset($_REQUEST['month_values'][$id]['END_DATE']) && isset($_REQUEST['year_values'][$id]['END_DATE']))
			$_REQUEST['values'][$id]['END_DATE'] = '';
	}
	if(!$_POST['values'])
		$_POST['values'] = $_REQUEST['values'];
}

$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID"));
if((($_REQUEST['profiles'] && $_POST['profiles']) || ($_REQUEST['values'] && $_POST['values'])) && AllowEdit())
{
	$notes_RET = DBGet(DBQuery("SELECT ID FROM PORTAL_NOTES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

	foreach($notes_RET as $note_id)
	{
		$note_id = $note_id['ID'];
		$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] = '';
		foreach(array('admin','teacher','parent') as $profile_id)
			if($_REQUEST['profiles'][$note_id][$profile_id])
				$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
		if(count($_REQUEST['profiles'][$note_id]))
		{
			foreach($profiles_RET as $profile)
			{
				$profile_id = $profile['ID'];

				if($_REQUEST['profiles'][$note_id][$profile_id])
					$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
			}
		}
		if($_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'])
			$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ',';
	}
}

if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
		if($id!='new')
		{
			$sql = "UPDATE PORTAL_NOTES SET ";

			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".str_replace("\'","''",$value)."',";
			}
			$sql = substr($sql,0,-1) . " WHERE ID='$id'";
			DBQuery($sql);
		}
		else
		{
			if(count($_REQUEST['profiles']['new']))
			{
				foreach(array('admin','teacher','parent') as $profile_id)
				{
					if($_REQUEST['profiles']['new'][$profile_id])
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id.',';
					$columns['PUBLISHED_PROFILES'] = ','.$_REQUEST['values']['new']['PUBLISHED_PROFILES'];
				}
				foreach($profiles_RET as $profile)
				{
					$profile_id = $profile['ID'];

					if($_REQUEST['profiles']['new'][$profile_id])
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id.',';
					$columns['PUBLISHED_PROFILES'] = ','.$_REQUEST['values']['new']['PUBLISHED_PROFILES'];
				}
			}
			else
				$_REQUEST['values']['new']['PUBLISHED_PROFILES'] = '';

			$sql = "INSERT INTO PORTAL_NOTES ";

			$fields = 'ID,SCHOOL_ID,SYEAR,PUBLISHED_DATE,PUBLISHED_USER,';
			$values = db_seq_nextval('PORTAL_NOTES_SEQ').",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";

			$go = 0;
			foreach($columns as $column=>$value)
			{
				if($value)
				{
					$fields .= $column.',';
					$values .= "'".str_replace("\'","''",$value)."',";
					$go = true;
				}
			}
			$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';

			if($go)
				DBQuery($sql);
		}
	}
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_REQUEST['profiles']);
	unset($_SESSION['_REQUEST_vars']['profiles']);
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('message')))
	{
		DBQuery("DELETE FROM PORTAL_NOTES WHERE ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,SORT_ORDER,TITLE,CONTENT,START_DATE,END_DATE,PUBLISHED_PROFILES,CASE WHEN END_DATE IS NOT NULL AND END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED FROM PORTAL_NOTES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY EXPIRED DESC,SORT_ORDER,PUBLISHED_DATE DESC";
	$QI = DBQuery($sql);
	$notes_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','CONTENT'=>'_makeContentInput','SORT_ORDER'=>'_makeTextInput','START_DATE'=>'_makePublishing'));

	$columns = array('TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SORT_ORDER'=>_('Sort Order'),'START_DATE'=>_('Publishing Options'));
	//,'START_TIME'=>'Start Time','END_TIME'=>'End Time'
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'CONTENT'=>_makeContentInput('','CONTENT'),'SHORT_NAME'=>_makeTextInput('','SHORT_NAME'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'START_DATE'=>_makePublishing('','START_DATE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'ID');

	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=update method=POST>";
	DrawHeader('',SubmitButton(_('Save')));
	ListOutput($notes_RET,$columns,_('Note'),_('Notes'),$link);
	echo '<CENTER>'.SubmitButton(_('Save')).'</CENTER>';
	echo '</FORM>';
}

function _makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name!='TITLE')
		$extra = 'size=5 maxlength=10';

	return TextInput($name=='TITLE' && $THIS_RET['EXPIRED']?array($value,'<FONT color=red>'.$value.'</FONT>'):$value,"values[$id][$name]",'',$extra);
}

function _makeContentInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	return TextareaInput($value,"values[$id][$name]",'','rows=8');
}

function _makePublishing($value,$name)
{	global $THIS_RET,$profiles_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$return = '<TABLE><TR><TD class=LO_field><b>'.Localize('colon',_('Visible Between')).'</b></TD><TD align=right>';
	$return .= DateInput($value,"values[$id][$name]").'</TD><TD width=1> '._('to').' </TD><TD>';
	$return .= DateInput($THIS_RET['END_DATE'],"values[$id][END_DATE]").'</TD></TR>';
	$return .= '<TR><TD width=100% colspan=4 bgcolor=black height=1></TD></TR><TR><TD colspan=4>';

	if(!$profiles_RET)
		$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID"));

	$return .= '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD colspan=4><b>'.Localize('colon',_('Visible To')).'</b></TD></TR>';
	foreach(array('admin'=>_('Administrator w/Custom'),'teacher'=>_('Teacher w/Custom'),'parent'=>_('Parent w/Custom')) as $profile_id=>$profile)
		$return .= "<TD><INPUT type=checkbox name=profiles[$id][$profile_id] value=Y".(strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile_id,")!==false?' CHECKED':'')."> $profile</TD>";
	$i = 3;
	foreach($profiles_RET as $profile)
	{
		$i++;
		$return .= '<TD><INPUT type=checkbox name=profiles['.$id.']['.$profile['ID'].'] value=Y'.(strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile[ID],")!==false?' CHECKED':'').">"._($profile[TITLE])."</TD>";
		if($i%4==0 && $i!=count($profile))
			$return .= '</TR><TR>';
	}
	for(;$i%4!=0;$i++)
		$return .= '<TD></TD>';
	$return .= '</TR>';
	//<TR><TD colspan=4><B><A HREF=#>Schools: ...</A></B></TD></TR></TABLE>';
	$return .= '</TABLE>';
	$return .= '</TD></TR></TABLE>';
	return $return;
}
?>
