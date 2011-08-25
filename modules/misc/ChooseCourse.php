<?php

$_REQUEST['modfunc'] = 'choose_course';

if(!$_REQUEST['course_period_id'])
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
	$course_title = $course_title[1]['TITLE'] . '<INPUT type=hidden name=w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id value='.$_REQUEST['course_period_id'].'>';

	echo "<script language=javascript>opener.document.getElementById(\"".($_REQUEST['last_year']=='true'?'ly_':'')."course_div\").innerHTML = \"$course_title<BR><small><INPUT type=radio name=w_".($_REQUEST['last_year']=='true'?'ly_':'')."course_period_id_which value=course_period CHECKED>"._('Course Period')."<INPUT type=radio name=w_".($_REQUEST['last_year']=='true'?'ly_':'')."course_period_id_which value=course>"._('Course')." </small>\"; window.close();</script>";
}

?>