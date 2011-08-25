<?php
DrawHeader(_('Teacher Programs').' - '._(ProgramTitle()));

if(UserStaffID())
{
	$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	if($profile[1]['PROFILE']!='teacher')
	{
		unset($_SESSION['staff_id']);
		echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
	}
}

$extra['profile'] = 'teacher';
Search('staff_id',$extra);

if(UserStaffID())
{
	echo "<FORM action=Modules.php?modname=$_REQUEST[modname] method=POST>";
	$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".UserStaffID()."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
	$RET = DBGet($QI);
	// get the fy marking period id, there should be exactly one fy marking period
	$fy_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

	if($_REQUEST['period'])
		$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];

	if(!UserCoursePeriod())
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
	//if(!UserPeriod()) // mab - this is kinda silly
	//	$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];

	$period_select = "<SELECT name=period onChange='document.forms[0].submit();'>";
	foreach($RET as $period)
	{
		if(UserCoursePeriod()==$period['COURSE_PERIOD_ID'])
		{
			$selected = ' SELECTED';
			$_SESSION['UserPeriod'] = $period['PERIOD_ID'];
			$found = true;
		}
		else
			$selected = '';

		$period_select .= "<OPTION value=$period[COURSE_PERIOD_ID]$selected>".$period['SHORT_NAME'].($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?' '.GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME'):'').(strlen($period['DAYS'])<5?' '.$period['DAYS']:'').' - '.$period['COURSE_TITLE']."</OPTION>";
	}
	$period_select .= "</SELECT>";
	if(!$found)
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
	}

	DrawHeader($period_select);
	echo '</FORM><BR>';
	unset($_CENTRE['DrawHeader']);
	$_CENTRE['HeaderIcon'] = false;

	$_CENTRE['allow_edit'] = AllowEdit($_REQUEST['modname']);
	$_CENTRE['User'] = array(0=>$_CENTRE['User'][1],1=>array('STAFF_ID'=>UserStaffID(),'NAME'=>GetTeacher(UserStaffID()),'USERNAME'=>GetTeacher(UserStaffID(),'','USERNAME'),'PROFILE'=>'teacher','SCHOOLS'=>','.UserSchool().',','SYEAR'=>UserSyear()));

	echo '<CENTER><TABLE width=95% style="border:1px solid #000000"><TR><TD>';

	include('modules/'.$_REQUEST['include']);

	echo '</TD></TR></TABLE></CENTER>';
}
?>
