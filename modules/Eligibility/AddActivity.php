<?php
if($_REQUEST['modfunc']=='save')
{
	if($_REQUEST['activity_id'])
	{
		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENT_ELIGIBILITY_ACTIVITIES WHERE ACTIVITY_ID='".$_SESSION['activity_id']."' AND SYEAR='".UserSyear()."'"),array(),array('STUDENT_ID'));
		foreach($_REQUEST['student'] as $student_id=>$yes)
		{
			if(!$current_RET[$student_id])
			{
				$sql = "INSERT INTO STUDENT_ELIGIBILITY_ACTIVITIES (SYEAR,STUDENT_ID,ACTIVITY_ID)
							values('".UserSyear()."','".$student_id."','".$_REQUEST['activity_id']."')";
				DBQuery($sql);
			}
		}
		unset($_REQUEST['modfunc']);
		$note = _('This activity has been added to the selected students.');
	}
	else
		BackPrompt(_('You must choose an activity.'));
}

DrawHeader(ProgramTitle());

if($note)
	DrawHeader('<IMG SRC=assets/check.gif>'.$note);

if($_REQUEST['search_modfunc']=='list')
{
	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save METHOD=POST>";
	DrawHeader('',SubmitButton(_('Add Activity to Selected Students')));
	echo '<BR>';

	echo '<CENTER><TABLE bgcolor=#'.Preferences('COLOR').' cellpadding=6><TR><TD align=right>'._('Activity').'</TD>';
	echo '<TD>';
	$activities_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	echo '<SELECT name=activity_id><OPTION value="">'._('N/A').'</OPTION>';
	if(count($activities_RET))
	{
		foreach($activities_RET as $activity)
			echo "<OPTION value=$activity[ID]>$activity[TITLE]</OPTION>";
	}
	echo '</SELECT>';
	echo '</TD>';
	echo '</TR></TABLE><BR>';

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;
}
	Widgets('activity');
	Widgets('course');

Search('student_id',$extra);
if($_REQUEST['search_modfunc']=='list')
	echo '<BR><CENTER>'.SubmitButton(_('Add Activity to Selected Students'))."</CENTER></FORM>";

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return "<INPUT type=checkbox name=student[".$THIS_RET['STUDENT_ID']."] value=Y>";
}

?>