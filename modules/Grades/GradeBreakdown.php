<?php
DrawHeader(ProgramTitle());

if(!$_REQUEST['mp'])
	$_REQUEST['mp'] = UserMP();

// Get all the mp's associated with the current mp
$mps_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,DOES_EXAM,0,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."')) AND MP='FY' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,DOES_EXAM,1,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') AND MP='SEM' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,DOES_EXAM,2,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,DOES_EXAM,3,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='".UserMP()."' AND MP='PRO' ORDER BY 5,SORT_ORDER"));
echo "<FORM action=Modules.php?modname=$_REQUEST[modname] method=POST>";
$mp_select = "<SELECT name=mp onchange='document.forms[0].submit();'>";
foreach($mps_RET as $mp)
{
    if($mp['DOES_GRADES']=='Y' || $mp['MARKING_PERIOD_ID']==UserMP())
        $mp_select .= '<OPTION value='.$mp['MARKING_PERIOD_ID'].($mp['MARKING_PERIOD_ID']==$_REQUEST['mp']?' SELECTED':'').'>'.$mp['TITLE'].'</OPTION>';
    if($mp['DOES_EXAM']=='Y')
        $mp_select .= '<OPTION value=E'.$mp['MARKING_PERIOD_ID'].('E'.$mp['MARKING_PERIOD_ID']==$_REQUEST['mp']?' SELECTED':'').'>'.sprintf(_('%s Exam'),$mp['TITLE']).'</OPTION>';
}
$mp_select .= "</SELECT>";

DrawHeader($mp_select);
echo '</FORM>';

$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME as FULL_NAME,s.STAFF_ID,g.REPORT_CARD_GRADE_ID FROM STUDENT_REPORT_CARD_GRADES g,STAFF s,COURSE_PERIODS cp WHERE g.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.TEACHER_ID=s.STAFF_ID AND cp.SYEAR=s.SYEAR AND cp.SYEAR=g.SYEAR AND cp.SYEAR='".UserSyear()."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'";
$grouped_RET = DBGet(DBQuery($sql),array(),array('STAFF_ID','REPORT_CARD_GRADE_ID'));

$grades_RET = DBGet(DBQuery("SELECT rg.ID,rg.TITLE FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs WHERE rg.SCHOOL_ID='".UserSchool()."' AND rg.SYEAR='".UserSyear()."' AND rs.ID=rg.GRADE_SCALE_ID ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER"));

if(count($grouped_RET))
{
	foreach($grouped_RET as $staff_id=>$grades)
	{
		$i++;
		$teachers_RET[$i]['FULL_NAME'] = $grades[key($grades)][1]['FULL_NAME']; 
		foreach($grades_RET as $grade)
			$teachers_RET[$i][$grade['ID']] = count($grades[$grade['ID']]);
	}
}

$columns = array('FULL_NAME'=>'Teacher');
foreach($grades_RET as $grade)
	$columns[$grade['ID']] = $grade['TITLE'];

ListOutput($teachers_RET,$columns,_('Teacher'),_('Teachers'));	
?>
