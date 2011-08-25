<?php

DrawHeader(ProgramTitle());

Widgets('activity');
Widgets('course');
Widgets('eligibility');

Search('student_id',$extra);

if($_REQUEST['modfunc']=='add' && AllowEdit())
{
	DBQuery("INSERT INTO STUDENT_ELIGIBILITY_ACTIVITIES (STUDENT_ID,ACTIVITY_ID,SYEAR) values('".UserStudentID()."','".$_REQUEST['new_activity']."','".UserSyear()."')");
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(strtolower(_('activity'))))
	{
		DBQuery("DELETE FROM STUDENT_ELIGIBILITY_ACTIVITIES WHERE STUDENT_ID='".UserStudentID()."' AND ACTIVITY_ID='".$_REQUEST['activity_id']."' AND SYEAR='".UserSyear()."'");
		unset($_REQUEST['modfunc']);
	}
}

if(UserStudentID() && !$_REQUEST['modfunc'])
{
	$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility' AND TITLE IN ('START_DAY','END_DAY')"));
	if(count($start_end_RET))
	{
		foreach($start_end_RET as $value)
			$$value['TITLE'] = $value['VALUE'];
	}
	
	switch(date('D'))
	{
		case 'Mon':
		$today = 1;
		break;
		case 'Tue':
		$today = 2;
		break;
		case 'Wed':
		$today = 3;
		break;
		case 'Thu':
		$today = 4;
		break;
		case 'Fri':
		$today = 5;
		break;
		case 'Sat':
		$today = 6;
		break;
		case 'Sun':
		$today = 7;
		break;
	}
	
	$start = time() - ($today-$START_DAY)*60*60*24;
	$end = time();
	
	if(!$_REQUEST['start_date'])
	{
		$start_time = $start;
		$start_date = strtoupper(date('d-M-y',$start_time));
		$end_date = strtoupper(date('d-M-y',$end));
	}
	else
	{
		$start_time = $_REQUEST['start_date'];
		$start_date = strtoupper(date('d-M-y',$start_time));
		$end_date = strtoupper(date('d-M-y',$start_time+60*60*24*6));
	}

	$begin_year = DBGet(DBQuery("SELECT min(date_part('epoch',SCHOOL_DATE)) as SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
	$begin_year = $begin_year[1]['SCHOOL_DATE'];
	
	$date_select = "<OPTION value=$start>".date('M d, Y',$start).' - '.date('M d, Y',$end).'</OPTION>';
	for($i=$start-(60*60*24*7);$i>=$begin_year;$i-=(60*60*24*7))
		$date_select .= "<OPTION value=$i".(($i+86400>=$start_time && $i-86400<=$start_time)?' SELECTED':'').">".date('M d, Y',$i).' - '.date('M d, Y',($i+1+(($END_DAY-$START_DAY))*60*60*24)).'</OPTION>';
	
	echo "<FORM action=Modules.php?modname=$_REQUEST[modname] method=POST>";
	DrawHeader('<SELECT name=start_date>'.$date_select.'</SELECT>','<INPUT type=submit value='._('Go').'>');
	echo '</FORM>';

	echo '<TABLE border=0 width=100%><TR><TD width=50% valign=top>';
	
	$RET = DBGet(DBQuery("SELECT em.STUDENT_ID,em.ACTIVITY_ID,ea.TITLE,ea.START_DATE,ea.END_DATE FROM ELIGIBILITY_ACTIVITIES ea,STUDENT_ELIGIBILITY_ACTIVITIES em WHERE em.SYEAR='".UserSyear()."' AND em.STUDENT_ID='".UserStudentID()."' AND em.SYEAR=ea.SYEAR AND em.ACTIVITY_ID=ea.ID ORDER BY ea.START_DATE"),array('START_DATE'=>'ProperDate','END_DATE'=>'ProperDate'));

	$activities_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	if(count($activities_RET))
	{
		foreach($activities_RET as $value)
			$activities[$value['ID']] = $value['TITLE'];
	}

	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&start_date=$_REQUEST[start_date]";
	$link['remove']['variables'] = array('activity_id'=>'ACTIVITY_ID');
	$link['add']['html']['TITLE'] = '<TABLE border=0 cellpadding=0 cellspacing=0><TR><TD>'.SelectInput('','new_activity','',$activities).'</TD><TD><INPUT type=submit value="'._('Add').'"></TD></TR></TABLE>';
	$link['add']['html']['remove'] = button('add');

	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=add&start_date=$_REQUEST[start_date] method=POST>";
	$columns = array('TITLE'=>_('Activity'),'START_DATE'=>_('Starts'),'END_DATE'=>_('Ends'));
	ListOutput($RET,$columns,_('Activity'),_('Activities'),$link);
	echo '</FORM>';

	echo '</TD><TD width=50% valign=top>';
	
	$RET = DBGet(DBQuery("SELECT e.ELIGIBILITY_CODE,c.TITLE as COURSE_TITLE FROM ELIGIBILITY e,COURSES c,COURSE_PERIODS cp WHERE e.STUDENT_ID='".UserStudentID()."' AND e.SYEAR='".UserSyear()."' AND e.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_ID=c.COURSE_ID AND e.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date'"),array('ELIGIBILITY_CODE'=>'_makeLower'));
	$columns = array('COURSE_TITLE'=>_('Course'),'ELIGIBILITY_CODE'=>_('Grade'));
	ListOutput($RET,$columns,_('Course'),_('Courses'));
	
	echo '</TD></TR></TABLE>';
}

function _makeLower($word)
{
	return ucwords(strtolower($word));
}

?>
