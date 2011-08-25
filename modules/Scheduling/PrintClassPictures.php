<?php

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['cp_arr']))
	{
		$cp_list = '\''.implode('\',\'',$_REQUEST['cp_arr']).'\'';

		$course_periods_RET = DBGet(DBQuery("SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID,cp.MARKING_PERIOD_ID,cp.MP FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID IN ($cp_list) ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)"));
		//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';
		if($_REQUEST['include_teacher']=='Y')
			$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,ROLLOVER_ID FROM STAFF WHERE STAFF_ID IN (SELECT TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID IN ($cp_list))"),array(),array('STAFF_ID'));
		//echo '<pre>'; var_dump($teachers_RET); echo '</pre>';

		$handle = PDFStart();
		if($_REQUEST['legal_size']=='Y')
			echo '<!-- MEDIA SIZE 8.5x14in -->';
		$PCP_UserCoursePeriod = $_SESSION['UserCoursePeriod']; // save/restore for teachers

		foreach($course_periods_RET as $course_period)
		{
			$course_period_id = $course_period['COURSE_PERIOD_ID'];
			$teacher_id = $course_period['TEACHER_ID'];

			if($teacher_id)
			{
				$_CENTRE['User'] = array(1=>array('STAFF_ID'=>$teacher_id,'NAME'=>'name','PROFILE'=>'teacher','SCHOOLS'=>','.UserSchool().',','SYEAR'=>UserSyear()));
				$_SESSION['UserCoursePeriod'] = $course_period_id;

				$extra = array('SELECT_ONLY'=>'s.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME','ORDER_BY'=>'s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME','MP'=>$course_period['MARKING_PERIOD_ID'],'MPTable'=>$course_period['MP']);
				$RET = GetStuList($extra);
				//echo '<pre>'; var_dump($RET); echo '</pre>';

				if(count($RET))
				{
					echo '<TABLE border=1>';
					echo '<TR><TD colspan=5 align=center><FONT SIZE=+1><B>'.UserSyear().'-'.(UserSyear()+1).' - '.$course_period['TITLE'].'</B></FONT></TD></TR>';
					$i = 0;
					if($_REQUEST['include_teacher']=='Y')
					{
						$teacher = $teachers_RET[$teacher_id][1];

						echo '<TR><TD valign=bottom><TABLE>';
						if($UserPicturesPath && (($size=getimagesize($picture_path=$UserPicturesPath.UserSyear().'/'.$teacher_id.'.JPG')) || $_REQUEST['last_year']=='Y' && $staff['ROLLOVER_ID'] && ($size=getimagesize($picture_path=$UserPicturesPath.(UserSyear()-1).'/'.$staff['ROLLOVER_ID'].'.JPG'))))
							if($size[1]/$size[0] > 172/130)
								echo '<TR><TD width=130><IMG SRC="'.$picture_path.'" height=172></TD></TR>';
							else
								echo '<TR><TD width=130><IMG SRC="'.$picture_path.'" width=130></TD></TR>';
						else
							echo '<TR><TD width=130 height=172></TD></TR>';
						echo '<TR><TD><FONT size=-1><B>'.$teacher['LAST_NAME'].'</B><BR>'.$teacher['FIRST_NAME'].'</FONT></TD></TR>';
						echo '</TABLE></TD>';
						$i++;
					}

					foreach($RET as $student)
					{
						$student_id = $student['STUDENT_ID'];

						if($i++%5==0)
							echo '<TR>';
						echo '<TD valign=bottom><TABLE>';
						if($StudentPicturesPath && (($size=getimagesize($picture_path=$StudentPicturesPath.UserSyear().'/'.$student_id.'.JPG')) || $_REQUEST['last_year']=='Y' && ($size=getimagesize($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.$student_id.'.JPG'))))
							if($size[1]/$size[0] > 172/130)
								echo '<TR><TD width=130><IMG SRC="'.$picture_path.'" height=172></TD></TR>';
							else
								echo '<TR><TD width=130><IMG SRC="'.$picture_path.'" width=130></TD></TR>';
						else
							echo '<TR><TD width=130 height=172></TD></TR>';
						echo '<TR><TD><FONT size=-1><B>'.$student['LAST_NAME'].'</B><BR>'.$student['FIRST_NAME'].'</FONT></TD></TR>';
						echo '</TABLE></TD>';

						if($i%5==0)
							echo '</TR><!-- NEED 2in -->';
					}
					if($i%5!=0)
						echo '</TR>';
					echo '</TABLE><!-- NEW PAGE -->';
				}
			}
		}
		$_SESSION['UserCoursePeriod'] = $PCP_UserCoursePeriod;
		PDFStop($handle);
	}
	else
		BackPrompt(_('You must choose at least one course period.'));
}

if(!$_REQUEST['modfunc'])
{
	DrawHeader(ProgramTitle());

	if(User('PROFILE')!='admin')
		$_REQUEST['search_modfunc'] = 'list';

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Class Pictures for Selected Course Periods').'">';

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR><TD><INPUT type=checkbox name=include_teacher value=Y checked>'._('Include Teacher').'</TD>';
		$extra['extra_header_left'] .= '<TD><INPUT type=checkbox name=legal_size value=Y>'._('Legal Size Paper').'</TD>';
		$extra['extra_header_left'] .= '<TD><INPUT type=checkbox name=last_year value=Y>'._('Use Last Year\'s if Missing').'</TD></TR>';
		if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
			$extra['extra_header_left'] .= '<TR><TD><INPUT type=checkbox name=include_inactive value=Y>'._('Include Inactive Students').'</TD></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	mySearch('course_period',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Class Pictures for Selected Course Periods').'"></CENTER>';
		echo "</FORM>";
	}
}

function mySearch($type,$extra='')
{	global $extra;

	if(($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc']))
	{
		$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
		if($_SESSION['Back_PHP_SELF']!='course')
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset($_SESSION['List_PHP_SELF']);
		}
		echo '<script language=JavaScript>parent.help.location.reload();</script>';
		echo '<BR>';
		PopTable('header',_('Find a Course'));
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&search_modfunc=list&next_modname=$_REQUEST[next_modname] method=POST>";
		echo '<TABLE border=0>';

		$RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME||', '||FIRST_NAME AS FULL_NAME FROM STAFF WHERE PROFILE='teacher' AND (SCHOOLS IS NULL OR position(',".UserSchool().",' IN SCHOOLS)>0) AND SYEAR='".UserSyear()."' ORDER BY FULL_NAME"));
		echo '<TR><TD align=right width=120>'._('Teacher').'</TD><TD>';
		echo "<SELECT name=teacher_id style='max-width:250;'><OPTION value=''>"._('N/A')."</OPTION>";
		foreach($RET as $teacher)
			echo "<OPTION value=$teacher[STAFF_ID]>$teacher[FULL_NAME]</OPTION>";
		echo '</SELECT>';
		echo '</TD></TR>';

		$RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TITLE"));
		echo '<TR><TD align=right width=120>'._('Subject').'</TD><TD>';
		echo "<SELECT name=subject_id style='max-width:250;'><OPTION value=''>"._('N/A')."</OPTION>";
		foreach($RET as $subject)
			echo "<OPTION value=$subject[SUBJECT_ID]>$subject[TITLE]</OPTION>";
		echo '</SELECT>';

		$RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		echo '<TR><TD align=right width=120>Period</TD><TD>';
		echo "<SELECT name=period_id style='max-width:250;'><OPTION value=''>"._('N/A')."</OPTION>";
		foreach($RET as $period)
			echo "<OPTION value=$period[PERIOD_ID]>$period[TITLE]</OPTION>";
		echo '</SELECT>';
		echo '</TD></TR>';

		Widgets('course');
		echo $extra['search'];

		echo '<TR><TD colspan=2 align=center>';
		echo '<BR>';
		echo Buttons(_('Submit'),_('Reset'));
		echo '</TD></TR>';
		echo '</TABLE>';
		echo '</FORM>';
		PopTable('footer');
	}
	else
	{
		DrawHeader('',$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);

		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['teacher_id'])
				$where .= " AND cp.TEACHER_ID='$_REQUEST[teacher_id]'";
			if($_REQUEST['first'])
				$where .= " AND UPPER(s.FIRST_NAME) LIKE '".strtoupper($_REQUEST['first'])."%'";
			if($_REQUEST['w_course_period_id'])
				if($_REQUEST['w_course_period_id_which']=='course')
					$where .= " AND cp.COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."')";
				else
					$where .= " AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'";
			if($_REQUEST['subject_id'])
			{
				$from .= ",COURSES c";
				$where .= " AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID='".$_REQUEST['subject_id']."'";
			}
			if($_REQUEST['period_id'])
				$where .= " AND cp.PERIOD_ID='".$_REQUEST['period_id']."'";

			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp$from WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND sp.PERIOD_ID=cp.PERIOD_ID$where";
		}
		elseif(User('PROFILE')=='teacher')
		{
			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND sp.PERIOD_ID=cp.PERIOD_ID";
		}
		else                       		{
			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHEDULE ss WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.SYEAR='".UserSyear()."' AND ss.STUDENT_ID='".UserStudentID()."' AND (CURRENT_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR CURRENT_DATE<=ss.END_DATE)) AND sp.PERIOD_ID=cp.PERIOD_ID";
		}
		$sql .= ' ORDER BY sp.PERIOD_ID';

		$course_periods_RET = DBGet(DBQuery($sql),array('COURSE_PERIOD_ID'=>'_makeChooseCheckbox'));
		$LO_columns = array('COURSE_PERIOD_ID'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'cp_arr\');"><A>','TITLE'=>_('Course Period'));
		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='course')
			{
				$_SESSION['Back_PHP_SELF'] = 'course';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
		}
		echo '<INPUT type=hidden name=relation>';
		ListOutput($course_periods_RET,$LO_columns,'Course Period','Course Periods');
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return "<INPUT type=checkbox name=cp_arr[] value=$value".($THIS_RET['ATTENDANCE']=='Y'?' checked':'').">";
}
?>
