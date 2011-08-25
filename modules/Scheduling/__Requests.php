<?php
DrawHeader(ProgramTitle());

Widgets('request');
Search('student_id',$extra);

if(!$_REQUEST['modfunc'] && UserStudentID())
	$_REQUEST['modfunc'] = 'choose';

if(UserStudentID())
{
	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=verify method=POST>";
	DrawHeader('','<INPUT type=submit value="'._('Save').'">');
}

if($_REQUEST['modfunc']=='verify')
{
	unset($courses);
	$QI = DBQuery("SELECT TITLE,COURSE_ID,SUBJECT_ID FROM COURSES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
	$courses_RET = DBGet($QI,array(),array('COURSE_ID'));

	DBQuery("DELETE FROM SCHEDULE_REQUESTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."'");
	
	foreach($_REQUEST['courses'] as $subject=>$r_courses)
	{
		$courses_count = count($r_courses);
		for($i=0;$i<$courses_count;$i++)
		{
			$course = $r_courses[$i];

			if(!$course)
				continue;
			$sql = "INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID)
						values(".db_seq_nextval('SCHEDULE_REQUESTS_SEQ').",'".UserSyear()."','".UserSchool()."','".UserStudentID()."','".$courses_RET[$course][1]['SUBJECT_ID']."','".$course."',NULL,'".$_REQUEST['with_teacher'][$subject][$i]."','".$_REQUEST['without_teacher'][$subject][$i]."','".$_REQUEST['with_period'][$subject][$i]."','".$_REQUEST['without_period'][$subject][$i]."')";
			DBQuery($sql);
		}
	}
	echo ErrorMessage($error,_('Error'));
	
	$_SCHEDULER['student_id'] = UserStudentID();
	$_SCHEDULER['dont_run'] = true;
	include('modules/Scheduling/Scheduler.php');
	$_REQUEST['modfunc'] = 'choose';
}

if($_REQUEST['modfunc']=='choose')
{
	$QI = DBQuery("SELECT SUBJECT_ID,COURSE_ID,WITH_PERIOD_ID,NOT_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID FROM SCHEDULE_REQUESTS WHERE SYEAR='".UserSyear()."' AND STUDENT_ID='".UserStudentID()."'");
	$requests_RET = DBGet($QI,array(),array('SUBJECT_ID'));
	
	$QI = DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TITLE");
	$subjects_RET = DBGet($QI,array(),array('SUBJECT_ID'));
	
	$QI = DBQuery("SELECT DISTINCT COURSE_ID,TITLE,SUBJECT_ID FROM COURSES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
	$courses_RET = DBGet($QI,array(),array('SUBJECT_ID','COURSE_ID'));

	$QI = DBQuery("SELECT COURSE_ID,TEACHER_ID,PERIOD_ID FROM COURSE_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
	$periods_RET = DBGet($QI,array(),array('COURSE_ID'));

	$__DBINC_NO_SQLSHOW = true;

	echo "<script language=javascript>\n";

	foreach($subjects_RET as $key=>$value)
	{
		$html[$key] = "<TABLE><TR><TD width=10></TD><TD><SELECT name=courses[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>";
		
		if(count($courses_RET[$key]))
		{
			foreach($courses_RET[$key] as $crs_num=>$course)
				$html[$key] .= "<OPTION value='$crs_num'>".$course[1][TITLE]."</OPTION>";
		}
		$html[$key] .= "</SELECT></TD>";
		$html[$key] .= "</TR></TABLE>";

		echo "var html_$key=\"$html[$key]\";\n";
	}
	echo "</script>";
	
	if(count($requests_RET))
	{
		foreach($requests_RET as $key=>$requests)
		{
			foreach($requests as $value)
			{
				$select_html[$key] .= "<TABLE><TR><TD width=10></TD><TD><SELECT name=courses[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>";
				
				if(count($courses_RET[$key]))
				{
					foreach($courses_RET[$key] as $crs_num=>$course)
						$select_html[$key] .= "<OPTION value='$crs_num'".(($value['COURSE_ID']==$crs_num)?' SELECTED':'').">".$course[1][TITLE]."</OPTION>";
				}
				$select_html[$key] .= "</SELECT></TD>";
				$with_teachers = $with_periods = $without_teachers = $without_periods = '';
				$teachers_done = $periods_done = array();
				foreach($periods_RET[$value['COURSE_ID']] as $period)
				{
					if(!$teachers_done[$period['TEACHER_ID']])
					{
						$with_teachers .= "<OPTION value=".$period['TEACHER_ID']." ".(($value['WITH_TEACHER_ID']==$period['TEACHER_ID'])?' SELECTED':'').">".GetTeacher($period['TEACHER_ID'])."</OPTION>";
						$without_teachers .= "<OPTION value=".$period['TEACHER_ID']." ".(($value['NOT_TEACHER_ID']==$period['TEACHER_ID'])?' SELECTED':'').">".GetTeacher($period['TEACHER_ID'])."</OPTION>";
					}
					if(!$periods_done[$period['PERIOD_ID']])
					{
						$with_periods .= "<OPTION value=".$period['PERIOD_ID']." ".(($value['WITH_PERIOD_ID']==$period['PERIOD_ID'])?' SELECTED':'').">".GetPeriod($period['PERIOD_ID']).'</OPTION>';
						$without_periods .= "<OPTION value=".$period['PERIOD_ID']." ".(($value['NOT_PERIOD_ID']==$period['PERIOD_ID'])?' SELECTED':'').">".GetPeriod($period['PERIOD_ID']).'</OPTION>';
					}
					
					$periods_done[$period['PERIOD_ID']] = true;
					$teachers_done[$period['TEACHER_ID']] = true;
				}
				
				$select_html[$key] .= "<TD><TABLE><TR><TD>"._('With')."</TD><TD><SELECT name=with_teacher[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>".$with_teachers."</SELECT></TD><TD><SELECT name=with_period[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>".$with_periods."</TD></TR><TR><TR><TD>"._('Without')."</TD><TD><SELECT name=without_teacher[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>".$without_teachers."</SELECT></TD><TD><SELECT name=without_period[$key][]><OPTION value=''>"._('Not Specified')."</OPTION>".$without_periods."</TD></TR></TABLE></TD>";
				$select_html[$key] .= "</TR></TABLE>";
			}
		}
	}	

	echo "<BR><TABLE>";
	if(count($subjects_RET))
	{
		foreach($subjects_RET as $key=>$value)
		{
			echo "<TR><TD>".button('add','',"# onclick='javascript:addHTML(html_$key,$key); return false;'")."<TD><b>".$value[1][TITLE]."</b></TD></TR>";
			echo "<TR><TD></TD><TD>";
			if($select_html[$key])
				echo $select_html[$key];
			echo "<div id=$key>$html[$key]</div></TD></TR>";
		}
	}
	echo "</TABLE>";
	echo '</FORM>';
}
?>
