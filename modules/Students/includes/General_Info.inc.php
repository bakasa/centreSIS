<?php
echo '<TABLE width=100% border=0 cellpadding=6>';
echo '<TR>';
echo '<TD colspan=2>';

echo '<TABLE width=100% cellpadding=5><TR>';

echo '<TD>';
if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	if($_REQUEST['student_id']=='new' || Preferences('HIDDEN')!='Y')
		echo '<TABLE><TR><TD>'.TextInput($student['FIRST_NAME'],'students[FIRST_NAME]',($student['FIRST_NAME']==''?'<FONT color=red>':'')._('First').($student['FIRST_NAME']==''?'</FONT>':''),'size=12 maxlength=50').'</TD><TD>'.TextInput($student['MIDDLE_NAME'],'students[MIDDLE_NAME]',_('Middle'),'size=3 maxlength=50').'</TD><TD>'.TextInput($student['LAST_NAME'],'students[LAST_NAME]',($student['LAST_NAME']==''?'<FONT color=red>':'')._('Last').($student['LAST_NAME']==''?'</FONT>':''),'size=12 maxlength=50').'</TD><TD>'.SelectInput($student['NAME_SUFFIX'],'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>'Jr','Sr'=>'Sr','II'=>'II','III'=>'III','IV'=>'IV','V'=>'V'),'').'</TD></TR></TABLE>';
	else
		echo '<DIV id=student_name><div onclick=\'addHTML("<TABLE><TR><TD>'.str_replace('"','\"',TextInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$student['FIRST_NAME']),'students[FIRST_NAME]',_('First'),'maxlength=50',false)).'</TD><TD>'.str_replace('"','\"',TextInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$student['MIDDLE_NAME']),'students[MIDDLE_NAME]',_('Middle'),'size=3 maxlength=50',false)).'</TD><TD>'.str_replace('"','\"',TextInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$student['LAST_NAME']),'students[LAST_NAME]',_('Last'),'maxlength=50',false)).'</TD><TD>'.str_replace('"','\"',SelectInput($student['NAME_SUFFIX'],'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>'Jr','Sr'=>'Sr','II'=>'II','III'=>'III','IV'=>'IV','V'=>'V'),'','',false)).'</TD></TR></TABLE>","student_name",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX'].'</span></div></DIV><small><FONT color='.Preferences('TITLES').'>'._('Given Name').'</FONT></small>';
else
	echo ($student['FIRST_NAME']!=''||$student['MIDDLE_NAME']!=''||$student['LAST_NAME']!=''||$student['NAME_SUFFIX']!=''?$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX']:'-').'<BR><small><FONT color='.Preferences('TITLES').'>'._('Given Name').'</FONT></small>';
echo '</TD>';

echo '<TD>';
if($_REQUEST['student_id']=='new')
	echo TextInput('','assign_student_id',_('Centre ID'),'maxlength=10 size=10');
else
	echo NoInput(UserStudentID(),_('Centre ID'));
echo '</TD>';

echo '<TD>';
if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID'])
	$school_id = $student['SCHOOL_ID'];
else
	$school_id = UserSchool();
$sql = "SELECT ID,TITLE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".$school_id."' ORDER BY SORT_ORDER";
$QI = DBQuery($sql);
$grades_RET = DBGet($QI);
unset($options);
if(count($grades_RET))
{
	foreach($grades_RET as $value)
		$options[$value['ID']] = $value['TITLE'];
}
if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool())
{
	$allow_edit = $_CENTRE['allow_edit'];
	$AllowEdit = $_CENTRE['AllowEdit'][$_REQUEST['modname']];
	$_CENTRE['AllowEdit'][$_REQUEST['modname']] = $_CENTRE['allow_edit'] = false;
}

if($_REQUEST['student_id']=='new')
	$student_id = 'new';
else
	$student_id = UserStudentID();

if($student_id=='new' && !VerifyDate($_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']['START_DATE']))
	unset($student['GRADE_ID']);

echo SelectInput($student['GRADE_ID'],'values[STUDENT_ENROLLMENT]['.$student_id.'][GRADE_ID]',(!$student['GRADE_ID']?'<FONT color=red>':'')._('Grade').(!$student['GRADE_ID']?'</FONT>':''),$options);
echo '</TD>';

if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool())
{
	$_CENTRE['allow_edit'] = $allow_edit;
	$_CENTRE['AllowEdit'][$_REQUEST['modname']] = $AllowEdit;
}

echo '</TR><TR>';

echo '<TD>';
echo TextInput($student['USERNAME'],'students[USERNAME]',_('Username'));
echo '</TD>';

echo '<TD>';
//echo TextInput($student['PASSWORD'],'students[PASSWORD]','Password');
echo TextInput(array($student['PASSWORD'],str_repeat('*',strlen($student['PASSWORD']))),'students[PASSWORD]',($student['USERNAME']&&!$student['PASSWORD']?'<FONT color=red>':'')._('Password').($student['USERNAME']&&!$student['PASSWORD']?'</FONT>':''));
echo '</TD>';

echo '<TD>';
echo NoInput(makeLogin($student['LAST_LOGIN']),_('Last Login'));
echo '</TD>';

echo '</TR></TABLE>';
// IMAGE
if($_REQUEST['student_id']!='new' && $StudentPicturesPath && (($file = @fopen($picture_path=$StudentPicturesPath.UserSyear().'/'.UserStudentID().'.JPG','r')) || ($file = @fopen($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.UserStudentID().'.JPG','r'))))
{
	fclose($file);
	echo '<TD><IMG SRC="'.$picture_path.'" width=150>';
	
	if(User('PROFILE')=='admin' && User('PROFILE')!='student' && User('PROFILE')!='parent')
		echo '<br><a href=Modules.php?modname=Students/Upload.php style="text-decoration:none"><b>Upload Student\'s Photo</b></a></div>';
			
	echo '</TD><TD valign=top></TD>';
}
else
{
	if($_REQUEST['student_id']!='new')
	{
		echo '<TD><div align="center"><IMG SRC="assets/noimage.jpg?id='.rand(6,100000).'" width=144 class=pic>';
		
		if(User('PROFILE')=='admin' && User('PROFILE')!='student' && User('PROFILE')!='parent')
			echo '<br><a href=Modules.php?modname=Students/Upload.php style="text-decoration:none"><b>Upload Student\'s Photo</b></a></div>';
			
		echo '</TD>';
	}
	else
		echo '<TD colspan=2></TD>';
}

echo '</TD></TR></TABLE>';

echo '<HR>';

$_REQUEST['category_id'] = '1';
include 'modules/Students/includes/Other_Info.inc.php';

if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool() && $student['SCHOOL_ID'])
	$_CENTRE['AllowEdit'][$_REQUEST['modname']] = $_CENTRE['allow_edit'] = false;
include 'modules/Students/includes/Enrollment.inc.php';
?>
