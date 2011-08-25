<?php
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove')
{
	if(DeletePrompt(strtolower(_('request'))))
	{
		DBQuery("DELETE FROM SCHEDULE_REQUESTS WHERE STUDENT_ID='$_REQUEST[student_id]' AND COURSE_ID='$_REQUEST[course_id]' AND SYEAR='".UserSyear()."'");
		unset($_REQUEST['modfunc']);
	}
}

if(!$_REQUEST['modfunc'])
{
	$sql = "SELECT
				s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,r.STUDENT_ID,c.TITLE as COURSE,r.COURSE_ID
			FROM
				SCHEDULE_REQUESTS r,COURSES c,STUDENTS s
			WHERE
				s.STUDENT_ID = r.STUDENT_ID AND r.COURSE_ID = c.COURSE_ID
				AND r.SYEAR = '".UserSyear()."' AND r.SCHOOL_ID = '".UserSchool()."'
				AND NOT EXISTS (SELECT '' FROM SCHEDULE ss WHERE ss.STUDENT_ID=r.STUDENT_ID AND ss.COURSE_ID=r.COURSE_ID)
			";
	$RET = DBGet(DBQuery($sql),array(),array('STUDENT_ID'));
	$columns = array('FULL_NAME'=>'Student','STUDENT_ID'=>'Centre ID','COURSE'=>'Course');
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('student_id'=>'STUDENT_ID','course_id'=>'COURSE_ID');
	ListOutput($RET,$columns,_('Unscheduled Request'),_('Unscheduled Requests'),$link,array(array('FULL_NAME','STUDENT_ID')));
}
?>
