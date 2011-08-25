<?php
unset($_SESSION['_REQUEST_vars']['values']);unset($_SESSION['_REQUEST_vars']['modfunc']);
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update' && ($_REQUEST['button']==_('Save') || $_REQUEST['button']==''))
{
	if($_REQUEST['values'] && $_POST['values'] && User('PROFILE')=='admin')
	{
		if($_REQUEST['new_school']!='true')
		{
			$sql = "UPDATE SCHOOLS SET ";

			foreach($_REQUEST['values'] as $column=>$value)
			{
				$sql .= $column."='".str_replace("\'","''",$value)."',";
			}
			$sql = substr($sql,0,-1) . " WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'";
			DBQuery($sql);
			echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
			$note[] = _('This school has been modified.');
		}
		else
		{
			$fields = $values = '';

			foreach($_REQUEST['values'] as $column=>$value)
				if($column!='ID' && $value)
				{
					$fields .= ','.$column;
					$values .= ",'".str_replace("\'","''",$value)."'";
				}

			if($fields && $values)
			{
				$id = DBGet(DBQuery("SELECT ".db_seq_nextval('SCHOOLS_SEQ')." AS ID".FROM_DUAL));
				$id = $id[1]['ID'];
				$sql = "INSERT INTO SCHOOLS (ID,SYEAR$fields) values('$id','".UserSyear()."'$values)";
				DBQuery($sql);
				DBQuery("UPDATE STAFF SET SCHOOLS=rtrim(SCHOOLS,',')||',$id,' WHERE STAFF_ID='".User('STAFF_ID')."' AND SCHOOLS IS NOT NULL");
				$_SESSION['UserSchool'] = $id;
				echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
				unset($_REQUEST['new_school']);
			}
		}
        UpdateSchoolArray(UserSchool());
	}

	$_REQUEST['modfunc'] = '';
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['modfunc']=='update' && $_REQUEST['button']==_('Delete') && User('PROFILE')=='admin')
{
	if(DeletePrompt('school'))
	{
		DBQuery("DELETE FROM SCHOOLS WHERE ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_MARKING_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='".UserSchool()."'");
		DBQuery("UPDATE STAFF SET SCHOOLS=replace(SCHOOLS,',".UserSchool().",',',')");

		unset($_SESSION['UserSchool']);
		echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
		unset($_REQUEST);
		$_REQUEST['modname'] = "School_Setup/Schools.php?new_school=true";
		$_REQUEST['new_school'] = true;
		unset($_REQUEST['modfunc']);
        UpdateSchoolArray(UserSchool());
	}
}

if(!$_REQUEST['modfunc'])
{
	if(!$_REQUEST['new_school'])
	{
		$schooldata = DBGet(DBQuery("SELECT ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,REPORTING_GP_SCALE,SHORT_NAME FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		$schooldata = $schooldata[1];
		$school_name = GetSchool(UserSchool());
	}
	else
		$school_name = _('Add a School');

	echo "<FORM METHOD='POST' ACTION='Modules.php?modname=".$_REQUEST['modname']."&modfunc=update&new_school=$_REQUEST[new_school]'>";
	DrawHeader('',"<INPUT TYPE=SUBMIT name=button VALUE=\""._('Save')."\">".(($_REQUEST['new_school']!='true')?"<INPUT type=submit name=button value=\""._('Delete')."\">":''));
	echo '<BR>';
	PopTable('header',$school_name);
	echo "<FIELDSET><TABLE>";

	echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['TITLE'],'values[TITLE]',(!$schooldata['TITLE']?'<FONT color=red>':'')._('School Name').(!$schooldata['TITLE']?'</FONT>':''),'maxlength=100')."</TD></TR>";
	echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['ADDRESS'],'values[ADDRESS]',_('Address'),'maxlength=100')."</TD></TR>";
	echo "<TR ALIGN=LEFT><TD>".TextInput($schooldata['CITY'],'values[CITY]',_('City'),'maxlength=100').'</TD><TD>'.TextInput($schooldata['STATE'],'values[STATE]',_('State'),'maxlength=10').'</TD>';
	echo "<TD>".TextInput($schooldata['ZIPCODE'],'values[ZIPCODE]',_('Zip'),'maxlength=10')."</TD></TR>";

	echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['PHONE'],'values[PHONE]',_('Phone'),'maxlength=30')."</TD></TR>";
	echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['PRINCIPAL'],'values[PRINCIPAL]',_('Principal'),'maxlength=100')."</TD></TR>";
    echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['REPORTING_GP_SCALE'],'values[REPORTING_GP_SCALE]',_('Base Grading Scale'),'maxlength=10')."</TD></TR>";
	if(AllowEdit() || !$schooldata['WWW_ADDRESS'])
		echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['WWW_ADDRESS'],'values[WWW_ADDRESS]',_('Website'),'maxlength=100')."</TD></TR>";
	else
		echo "<TR ALIGN=LEFT><TD colspan=3><A HREF=http://$schooldata[WWW_ADDRESS] target=_blank>$schooldata[WWW_ADDRESS]</A><BR><small><FONT color=".Preferences('TITLES').">"._('Website')."</FONT></small></TD></TR>";
    echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['SHORT_NAME'],'values[SHORT_NAME]',_('Short Name'),'maxlength=25')."</TD></TR>";
	echo "<TR ALIGN=LEFT><TD colspan=3>".TextInput($schooldata['SCHOOL_NUMBER'],'values[SCHOOL_NUMBER]',_('School Number'),'maxlength=100')."</TD></TR>";

	echo "</TABLE></FIELDSET>";
	PopTable('footer');
	if(User('PROFILE')=='admin' && AllowEdit())
		echo "<CENTER><INPUT TYPE=SUBMIT name=button VALUE=\""._('Save')."\"></CENTER>";
	echo "</FORM>";
}
?>