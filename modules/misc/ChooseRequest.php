<?php

$_REQUEST['modfunc'] = 'choose_course';

if(!$_REQUEST['course_id'])
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_REQUEST['course_id']."'"));
	$course_title = $course_title[1]['TITLE'].'<INPUT type=hidden name=request_course_id value='.$_REQUEST['course_id'].'>'; 

	echo "<script language=javascript>opener.document.getElementById(\"request_div\").innerHTML = \"$course_title<BR><small><INPUT type=checkbox name=not_request_course value=Y>"._('Not Requested')."</small>\"; window.close();</script>";
}

?>