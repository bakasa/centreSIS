<?php
if(GetTeacher(UserStaffID(),'','PROFILE',false)=='teacher')
{
	DrawHeader('','','<INPUT type=checkbox name=all_schools value=Y'.($_REQUEST['all_schools']=='Y'?" CHECKED onclick='document.location.href=\"".PreparePHP_SELF($_REQUEST,array(),array('all_schools'=>''))."\";'":" onclick='document.location.href=\"".PreparePHP_SELF($_REQUEST,array(),array('all_schools'=>'Y'))."\";'").'>'._('List Courses For All Schools'));

	// preload GetMP cache with all schools
	if($_REQUEST['all_schools']=='Y')
		$_CENTRE['GetMP'] = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,POST_START_DATE,POST_END_DATE,MP,SORT_ORDER,SHORT_NAME,START_DATE,END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS FROM SCHOOL_MARKING_PERIODS WHERE SYEAR='".UserSyear()."'"),array(),array('MARKING_PERIOD_ID'));

	$columns = array('TITLE'=>_('Course'),'PERIOD_ID'=>_('Period'),'ROOM'=>_('Room'),'MARKING_PERIOD_ID'=>_('Marking Period'));
	if($_REQUEST['all_schools']=='Y')
	{
		$columns += array('SCHOOL'=>_('School'));
		$group = array('SCHOOL_ID');
	}
	else
		$group = array();

	$schedule_RET = DBGet(DBQuery("SELECT cp.PERIOD_ID,cp.ROOM,c.TITLE,cp.MARKING_PERIOD_ID,cp.SCHOOL_ID,s.TITLE AS SCHOOL FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s WHERE cp.COURSE_ID=c.COURSE_ID AND cp.TEACHER_ID='".UserStaffID()."' AND cp.SYEAR='".UserSyear()."'".($_REQUEST['all_schools']=='Y'?'':" AND cp.SCHOOL_ID='".UserSchool()."'")." AND s.ID=cp.SCHOOL_ID AND s.SYEAR=cp.SYEAR ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)"),array('PERIOD_ID'=>'GetPeriod','MARKING_PERIOD_ID'=>'GetMP'),$group);

	ListOutput($schedule_RET,$columns,_('Course'),_('Courses'),false,$group);
	echo '<HR>';
}

$_REQUEST['category_id'] = '2';
include('modules/Users/includes/Other_Info.inc.php');
?>
