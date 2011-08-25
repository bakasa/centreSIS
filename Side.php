<?php
error_reporting(1);
include "./Warehouse.php";

$tmp_REQUEST = $_REQUEST;
$_SESSION['Side_PHP_SELF'] = "Side.php";

$old_school = UserSchool();
$old_syear = UserSyear();
$old_period = UserCoursePeriod();

if($_REQUEST['school'] && $_REQUEST['school']!=$old_school)
{
	unset($_SESSION['student_id']);
	$_SESSION['unset_student'] = true;
	unset($_SESSION['staff_id']);
	unset($_SESSION['UserMP']);
	unset($_REQUEST['mp']);
}

if($_REQUEST['modfunc']=='update' && $_POST)
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && $_REQUEST['school']!=$old_school)
	{
		$_SESSION['UserSchool'] = $_REQUEST['school'];
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
	}

	$_SESSION['UserSyear'] = $_REQUEST['syear'];
	$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];
	$_SESSION['UserMP'] = $_REQUEST['mp'];
	if(User('PROFILE')=='parent')
	{
		if($_SESSION['student_id']!=$_REQUEST['student_id'])
			unset($_SESSION['UserMP']);
		$_SESSION['student_id'] = $_REQUEST['student_id'];
	}
	echo "<script language=javascript>parent.body.location='".str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars']))."';</script>";
}

if(!$_SESSION['UserSyear'])
	$_SESSION['UserSyear'] = $DefaultSyear;

if(!$_SESSION['student_id'] && User('PROFILE')=='student')
	$_SESSION['student_id'] = $_SESSION['STUDENT_ID'];
//if(!$_SESSION['staff_id'] && User('PROFILE')!='admin' && User('PROFILE')!='teacher')
if(!$_SESSION['staff_id'] && User('PROFILE')=='parent')
	$_SESSION['staff_id'] = $_SESSION['STAFF_ID'];

if(!$_SESSION['UserSchool'])
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && (!User('SCHOOLS') || strpos(User('SCHOOLS'),','.User('CURRENT_SCHOOL_ID').',')!==false))
		$_SESSION['UserSchool'] = User('CURRENT_SCHOOL_ID');
	elseif(User('PROFILE')=='student')
		$_SESSION['UserSchool'] = trim(User('SCHOOLS'),',');
}
UpdateSchoolArray(UserSchool());

if((!$_SESSION['UserMP'] || ($_REQUEST['school'] && $_REQUEST['school']!=$old_school) || ($_REQUEST['syear'] && $_REQUEST['syear']!=$old_syear)) && User('PROFILE')!='parent')
	$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);

if(($_REQUEST['school'] && $_REQUEST['school']!=$old_school) || ($_REQUEST['syear'] && $_REQUEST['syear']!=$old_syear))
{
	unset($_SESSION['UserPeriod']);
	unset($_SESSION['UserCoursePeriod']);
}

if($_REQUEST['student_id']=='new')
{
	unset($_SESSION['student_id']);
	unset($_SESSION['_REQUEST_vars']['student_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	echo "<script language=javascript>parent.body.location='".str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced')))."';</script>";
}
if($_REQUEST['staff_id']=='new')
{
	unset($_SESSION['staff_id']);
	unset($_SESSION['_REQUEST_vars']['staff_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	echo "<script language=javascript>parent.body.location='".str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced')))."';</script>";
}
unset($_REQUEST['modfunc']);

echo "
<HTML>
	<head>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
		<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" />
		<link rel=stylesheet type=\"text/css\" href=\"assets/stylesheet.css\">
		<script language=\"JavaScript\" type=\"text/javascript\">
		<!--
			var old_modcat = false;
			function openMenu(modcat)
			{
				document.getElementById(\"menu_visible\"+modcat).innerHTML = document.getElementById(\"menu_hidden\"+modcat).innerHTML;
				if(old_modcat!=false)
					document.getElementById(\"menu_visible\"+old_modcat).innerHTML = \"\";
				document.getElementById(\"modcat_input\").value=modcat;
				if(old_modcat==modcat)
					old_modcat = false;
				else
					old_modcat = modcat;
			}

		-->
		</script>
		<title>".Config('TITLE')."</title>
	</head>
	<BODY background=assets/themes/".Preferences('THEME')."/bg.jpg leftmargin=6 marginwidth=4 topmargin=0 ".($_REQUEST['modcat']?"onload=openMenu('".$_REQUEST['modcat']."');":'').">";

// User Information
echo "<TABLE border=0 cellpadding=0 cellspacing=0 width=100% height=100%><TR><TD height=30>";
echo '<A HREF=index.php target=_top>'.DrawPNG('themes/'.Preferences('THEME').'/logo.png','border=0 width=160').'</A>';
echo "</TD></TR><TR>";
echo "<TD class=BoxContents style='border: inset #C9C9C9 2px; background-image:url(\"assets/bg.gif\")' width=100% valign=top>
	<FORM action=Side.php?modfunc=update method=POST>
	<INPUT type=hidden name=modcat value='' id=modcat_input>
	<b>".User('NAME')."</b><BR>
	".date('l F j, Y')."<BR>
	";
if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
{
	$schools = substr(str_replace(",","','",User('SCHOOLS')),2,-2);
	$QI = DBQuery("SELECT ID,TITLE,SHORT_NAME FROM SCHOOLS WHERE SYEAR='".UserSyear()."'".($schools?" AND ID IN ($schools)":''));
	$RET = DBGet($QI);

	if(!UserSchool())
	{
		$_SESSION['UserSchool'] = $RET[1]['ID'];
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
	}

	echo "<SELECT name=school onChange='document.forms[0].submit();' style='width:150;'>";
	foreach($RET as $school)
		echo "<OPTION value=$school[ID]".((UserSchool()==$school['ID'])?' SELECTED':'').">".($school['SHORT_NAME']?$school['SHORT_NAME']:$school['TITLE'])."</OPTION>";

	echo "</SELECT><BR>";
}

if(User('PROFILE')=='parent')
{
	$RET = DBGet(DBQuery("SELECT sju.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,se.SCHOOL_ID FROM STUDENTS s,STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE s.STUDENT_ID=sju.STUDENT_ID AND sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=".UserSyear()." AND se.STUDENT_ID=sju.STUDENT_ID AND ('".DBDate()."'>=se.START_DATE AND ('".DBDate()."'<=se.END_DATE OR se.END_DATE IS NULL))"));

	if(!UserStudentID())
		$_SESSION['student_id'] = $RET[1]['STUDENT_ID'];

	echo "<SELECT name=student_id onChange='document.forms[0].submit();'>";
	if(count($RET))
	{
		foreach($RET as $student)
		{
			echo "<OPTION value=$student[STUDENT_ID]".((UserStudentID()==$student['STUDENT_ID'])?' SELECTED':'').">".$student['FULL_NAME']."</OPTION>";
			if(UserStudentID()==$student['STUDENT_ID'])
				$_SESSION['UserSchool'] = $student['SCHOOL_ID'];
		}
	}
	echo "</SELECT><BR>";

	if(!UserMP() || UserSchool()!=$old_school || UserSyear()!=$old_syear)
		$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);
}

if(1)
{
if(User('PROFILE')!='student')
	$sql = "SELECT sy.SYEAR FROM SCHOOLS sy,STAFF s WHERE sy.ID='$_SESSION[UserSchool]' AND s.SYEAR=sy.SYEAR AND (s.SCHOOLS IS NULL OR position(','||sy.ID||',' IN s.SCHOOLS)>0) AND s.USERNAME=(SELECT USERNAME FROM STAFF WHERE STAFF_ID='$_SESSION[STAFF_ID]')";
else
	$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR";
$sql .= " ORDER BY sy.SYEAR DESC";
$years_RET = DBGet(DBQuery($sql));
}
else
$years_RET = array(1=>array('SYEAR'=>$DefaultSyear));

echo "<SELECT name=syear onChange='document.forms[0].submit();'>";
foreach($years_RET as $year)
	echo "<OPTION value=$year[SYEAR]".((UserSyear()==$year['SYEAR'])?' SELECTED':'').">$year[SYEAR]-".($year['SYEAR']+1)."</OPTION>";
echo '</SELECT><BR>';

$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"));
echo "<SELECT name=mp onChange='document.forms[0].submit();'>";
if(count($RET))
{
	if(!UserMP())
		$_SESSION['UserMP'] = $RET[1]['MARKING_PERIOD_ID'];

	foreach($RET as $quarter)
		echo "<OPTION value=$quarter[MARKING_PERIOD_ID]".(UserMP()==$quarter['MARKING_PERIOD_ID']?' SELECTED':'').">".$quarter['TITLE']."</OPTION>";
}
echo "</SELECT>";

if(User('PROFILE')=='teacher')
{
	$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp, SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
	$RET = DBGet($QI);
	// get the fy marking period id, there should be exactly one fy marking period per school
	$fy_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

	if(!UserCoursePeriod())
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		unset($_SESSION['student_id']);
		$_SESSION['unset_student'] = true;
	}

	echo "<BR><SELECT name=period onChange='document.forms[0].submit();' style='width:150;'>";
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

		echo "<OPTION value=$period[COURSE_PERIOD_ID]$selected>".$period['SHORT_NAME'].($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?' '.GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME'):'').(strlen($period['DAYS'])<5?' '.$period['DAYS']:'').' - '.$period['COURSE_TITLE']."</OPTION>";
	}
	if(!$found)
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
		unset($_SESSION['student_id']);
		$_SESSION['unset_student'] = true;
	}
	echo "</SELECT>";
}
echo '</FORM>';

if(UserStudentID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
{
	if(Preferences('NAME')=='Common')
		$sql = "SELECT coalesce(CUSTOM_200000002,FIRST_NAME)||' '||LAST_NAME AS FULL_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'";
	else
		$sql = "SELECT FIRST_NAME||' '||coalesce(MIDDLE_NAME,' ')||' '||LAST_NAME||' '||coalesce(NAME_SUFFIX,' ') AS FULL_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'";
	$RET = DBGet(DBQuery($sql));
	echo '<TABLE border=0 cellpadding=0 cellspacing=0 width=100%><TR><TD bgcolor=#333366 width=19 valign=middle><A HREF=Side.php?student_id=new&modcat='.$_REQUEST['modcat'].'><IMG SRC=assets/x.gif height=17 border=0></A></TD><TD bgcolor=#333366><B>'.(AllowUse('Students/Student.php')?'<A HREF=Modules.php?modname=Students/Student.php&student_id='.UserStudentID().' target=body>':'').'<font color=#FFFFFF size=-2>'.$RET[1]['FULL_NAME'].'</font>'.(AllowUse('Students/Student.php')?'</A>':'').'</B></TD></TR></TABLE>';
}
if(UserStaffID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
{
	if(UserStudentID())
		echo '<IMG SRC=assets/pixel_trans.gif height=2>';
	$sql = "SELECT FIRST_NAME||' '||LAST_NAME AS FULL_NAME FROM STAFF WHERE STAFF_ID='".UserStaffID()."'";
	$RET = DBGet(DBQuery($sql));
	echo '<TABLE border=0 cellpadding=0 cellspacing=0 width=100%><TR><TD bgcolor='.(UserStaffID()==User('STAFF_ID')?'#663333':'#336633').' width=19 valign=middle><A HREF=Side.php?staff_id=new&modcat='.$_REQUEST['modcat'].'><IMG SRC=assets/x.gif height=17 border=0></A></TD><TD bgcolor='.(UserStaffID()==User('STAFF_ID')?'#663333':'#336633').'><B>'.(AllowUse('Users/User.php')?'<A HREF=Modules.php?modname=Users/User.php&staff_id='.UserStaffID().' target=body>':'').'<font color=#FFFFFF size=-2>'.$RET[1]['FULL_NAME'].'</font>'.(AllowUse('Users/User.php')?'</A>':'').'</B></TD></TR></TABLE>';
}
echo '<BR>';

// echo 'Locale: '.$_SESSION['locale'].'<BR>';

// Program Information
require('Menu.php');
foreach($_CENTRE['Menu'] as $modcat=>$programs)
{
	if(count($_CENTRE['Menu'][$modcat]))
	{
		$keys = array_keys($_CENTRE['Menu'][$modcat]);
		$menu = false;
		foreach($keys as $key_index=>$file)
		{
			if(!is_numeric($file))
			{
				$menu = true;
				break;
			}
		}
		if(!$menu)
			continue;

		echo "<A style='cursor: pointer; cursor:hand;' onclick='openMenu(\"".$modcat."\");parent.body.location=\"Modules.php?modname=$modcat/Search.php\";'><IMG SRC=assets/icons/$modcat.gif height=20 border=0 align=absmiddle><font color=blue face='Verdana, Arial, sans-serif'><B>"._(str_replace('_',' ',$modcat))."</B></font></A><BR><DIV id=menu_visible".$modcat."></DIV>";
		echo "<DIV id=menu_hidden".$modcat." style=\"display:none;\"><TABLE width=100%>";
		//foreach($_CENTRE['Menu'][$modcat] as $file=>$title)
		foreach($keys as $key_index=>$file)
		{
			$title = $_CENTRE['Menu'][$modcat][$file];
			if(substr($file,0,7)=='http://')
				echo "<TR><TD width=20></TD><TD class=BoxContents><b>&rsaquo;</b> <A HREF=$file target=body onclick='javascript:parent.help.location=\"Bottom.php?modname=$file\"'><font color=blue>$title</font></A></TD></TR>";
			elseif(substr($file,0,7)=='HTTP://')
				echo "<TR><TD width=20></TD><TD class=BoxContents><b>&rsaquo;</b> <A HREF=$file target=_blank><font color=blue>$title</font></A></TD></TR>";
			elseif(!is_numeric($file))
				echo "<TR><TD width=20></TD><TD class=BoxContents><b>&rsaquo;</b> <A HREF=Modules.php?modname=$file target=body onclick='javascript:parent.help.location=\"Bottom.php?modname=$file\"'><font color=blue>$title</font></A></TD></TR>";
			elseif($keys[$key_index+1] && !is_numeric($keys[$key_index+1]))
				echo '<TR><TD colspan=2 height=3></TD></TR><TR><TD colspan=2 class=BoxContents> &nbsp; <b>'.$title.'</b></TD></TR>';
		}
		echo "</TABLE></DIV>";
	}
}

echo '</TD></TR></TABLE>';
echo '</BODY>';
echo '</HTML>';
?>
