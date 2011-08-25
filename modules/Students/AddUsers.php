<?php
if($_REQUEST['modfunc']=='save' && AllowEdit())
{
	$current_RET = DBGet(DBQuery("SELECT STAFF_ID FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID='".UserStudentID()."'"),array(),array('STAFF_ID'));
	foreach($_REQUEST['staff'] as $staff_id=>$yes)
	{
		if(!$current_RET[$staff_id])
		{
			$sql = "INSERT INTO STUDENTS_JOIN_USERS (STAFF_ID,STUDENT_ID) values('".$staff_id."','".UserStudentID()."')";
			DBQuery($sql);
		}
	}
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	$note = _('The selected user\'s profile now includes access to the selected students.');
}
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(DeletePrompt('student from that user','remove access to'))
	{
		DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='$_REQUEST[staff_id]' AND STUDENT_ID='".UserStudentID()."'");
		unset($_REQUEST['modfunc']);
	}
}

if($note)
	DrawHeader('<IMG SRC=assets/check.gif>'.$note);

if($_REQUEST['modfunc']!='delete')
{
	$extra['SELECT'] = ",(SELECT count(u.STAFF_ID) FROM STUDENTS_JOIN_USERS u,STAFF st WHERE u.STUDENT_ID=s.STUDENT_ID AND st.STAFF_ID=u.STAFF_ID AND st.SYEAR=ssm.SYEAR) AS ASSOCIATED";
	$extra['columns_after'] = array('ASSOCIATED'=>'# '._('Associated'));
	Search('student_id',$extra);

	if(UserStudentID())
	{
		if($_REQUEST['search_modfunc']=='list')
		{
			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save method=POST>";
			DrawHeader('',SubmitButton(_('Add Selected Parents')));
		}

		echo '<CENTER><TABLE><TR><TD valign=top>';
		$current_RET = DBGet(DBQuery("SELECT u.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,s.LAST_LOGIN FROM STUDENTS_JOIN_USERS u,STAFF s WHERE s.STAFF_ID=u.STAFF_ID AND u.STUDENT_ID='".UserStudentID()."' AND s.SYEAR='".UserSyear()."'"),array('LAST_LOGIN'=>'makeLogin'));
		$link['remove'] = array('link'=>"Modules.php?modname=$_REQUEST[modname]&modfunc=delete",'variables'=>array('staff_id'=>'STAFF_ID'));
		ListOutput($current_RET,array('FULL_NAME'=>_('Parents'),'LAST_LOGIN'=>_('Last Login')),'.','.',$link,array(),array('search'=>false));
		echo '</TD><TD valign=top>';

		if(AllowEdit())
		{
			unset($extra);
			$extra['link'] = array('FULL_NAME'=>false);
			$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
			$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
			$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
			$extra['new'] = true;
			$extra['options']['search'] = false;
			$extra['profile'] = 'parent';

		Search('staff_id',$extra);
		}

		echo '</TD></TR></TABLE></CENTER>';

		if($_REQUEST['search_modfunc']=='list')
			echo "<BR><CENTER>".SubmitButton(_('Add Selected Parents'))."</CENTER></FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return "<INPUT type=checkbox name=staff[".$THIS_RET['STAFF_ID']."] value=Y>";
}
?>
