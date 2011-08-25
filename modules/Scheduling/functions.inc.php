<?php
function MyWidgets($item)
{	global $extra,$_CENTRE;

	switch($item)
	{
		case 'ly_course':
			if($_REQUEST['w_ly_course_period_id'])
			{
				if($_REQUEST['w_ly_course_period_id_which']=='course')
				{
					$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_ly_course_period_id']."'"));
					$extra['WHERE'] .= " AND exists(SELECT '' FROM SCHEDULE WHERE STUDENT_ID=ssm.STUDENT_ID AND COURSE_ID='".$course[1]['COURSE_ID']."')";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Last Year Course: </b></font>'.$course[1]['COURSE_TITLE'].'<BR>';
				}
				else
				{
					$extra['WHERE'] .= " AND exists(SELECT '' FROM SCHEDULE WHERE STUDENT_ID=ssm.STUDENT_ID AND COURSE_PERIOD_ID='".$_REQUEST['w_ly_course_period_id']."')";
					$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_ly_course_period_id']."'"));
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Last Year Course Period')).' </b></font>'.$course[1]['COURSE_TITLE'].' - '.$course[1]['TITLE'].'<BR>';
				}
			}
			$extra['search'] .= "<TR><TD align=right width=120>"._('Last Year Course')."</TD><TD><DIV id=ly_course_div></DIV> <A HREF=# onclick='window.open(\"Modules.php?modname=misc/ChooseCourse.php&last_year=true\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'><SMALL>"._('Choose')."</SMALL></A></TD></TR>";
		break;
	}
}
?>