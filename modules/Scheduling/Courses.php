<?php

unset($_SESSION['_REQUEST_vars']['subject_id']);unset($_SESSION['_REQUEST_vars']['course_id']);unset($_SESSION['_REQUEST_vars']['course_period_id']);
unset($_SESSION['_REQUEST_vars']['subject_id']);unset($_SESSION['_REQUEST_vars']['course_id']);unset($_SESSION['_REQUEST_vars']['course_period_id']);

// if only one subject, select it automatically -- works for Course Setup and Choose a Course
if($_REQUEST['modfunc']!='delete' && !$_REQUEST['subject_id'])
{
	$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."'"));
	if(count($subjects_RET)==1)
		$_REQUEST['subject_id'] = $subjects_RET[1]['SUBJECT_ID'];
}

$LO_options = array('save'=>false,'search'=>false);

if($_REQUEST['course_modfunc']=='search')
{
	echo '<BR>';
	PopTable('header',_('Search'));
	echo "<FORM name=search action=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&course_modfunc=search&last_year=$_REQUEST[last_year] method=POST>";
	echo '<TABLE><TR><TD><INPUT type=text name=search_term value="'.$_REQUEST['search_term'].'"></TD><TD><INPUT type=submit value="'._('Search').'"></TD></TR></TABLE>';
	if($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php')
		echo "<INPUT type=hidden name=include_child_mps value=$_REQUEST[include_child_mps]><INPUT type=hidden name=year_date value=$_REQUEST[year_date]><INPUT type=hidden name=month_date value=$_REQUEST[month_date]><INPUT type=hidden name=day_date value=$_REQUEST[day_date]>";
	echo '</FORM>';
	echo '<script type="text/javascript"><!--
		document.search.search_term.focus();
		--></script>';
	PopTable('footer');

	if($_REQUEST['search_term'])
	{
		$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE (UPPER(TITLE) LIKE '%".strtoupper($_REQUEST['search_term'])."%' OR UPPER(SHORT_NAME)='".strtoupper($_REQUEST['search_term'])."') AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,TITLE"));
		$courses_RET = DBGet(DBQuery("SELECT SUBJECT_ID,COURSE_ID,TITLE FROM COURSES WHERE (UPPER(TITLE) LIKE '%".strtoupper($_REQUEST['search_term'])."%' OR UPPER(SHORT_NAME)='".strtoupper($_REQUEST['search_term'])."') AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND SCHOOL_ID='".UserSchool()."' ORDER BY TITLE"));
		$periods_RET = DBGet(DBQuery("SELECT c.SUBJECT_ID,cp.COURSE_ID,cp.COURSE_PERIOD_ID,cp.TITLE,cp.MP,cp.MARKING_PERIOD_ID,cp.CALENDAR_ID,cp.TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp,COURSES c WHERE cp.COURSE_ID=c.COURSE_ID AND (UPPER(cp.TITLE) LIKE '%".strtoupper($_REQUEST['search_term'])."%' OR UPPER(cp.SHORT_NAME)='".strtoupper($_REQUEST['search_term'])."') AND cp.SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND cp.SCHOOL_ID='".UserSchool()."'".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '$date'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY cp.(SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
			calcSeats1($periods_RET,$date);

		$link = array();

		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID');
		echo '<TABLE><TR><TD valign=top>';
		ListOutput($subjects_RET,array('TITLE'=>_('Subject')),_('Subject'),_('Subjects'),$link,array(),$LO_options);

		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID','course_id'=>'COURSE_ID');
		echo '</TD><TD valign=top>';
		ListOutput($courses_RET,array('TITLE'=>_('Course')),_('Course'),_('Courses'),$link,array(),$LO_options);

		$columns = array('TITLE'=>_('Course Period'));
		$link = array();
		if($_REQUEST['modname']!='Scheduling/Schedule.php' || ($_REQUEST['modname']=='Scheduling/Schedule.php' && !$_REQUEST['include_child_mps']))
		{
			$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]";
			$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID','course_id'=>'COURSE_ID','course_period_id'=>'COURSE_PERIOD_ID');
			if($_REQUEST['modfunc']=='choose_course')
				$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]";
		}
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
			$columns += array('AVAILABLE_SEATS'=>($_REQUEST['include_child_mps']?'MP<small>('._('Available Seats').')</small>':_('Available Seats')));
		echo '</TD><TD valign=top>';
		ListOutput($periods_RET,$columns,_('Course Period'),_('Course Periods'),$link,array(),$LO_options);
		echo '</TD></TR></TABLE>';
	}
}

// UPDATING
if($_REQUEST['tables'] && $_POST['tables'] && AllowEdit())
{
	$where = array('COURSE_SUBJECTS'=>'SUBJECT_ID',
				'COURSES'=>'COURSE_ID',
				'COURSE_PERIODS'=>'COURSE_PERIOD_ID');

	if($_REQUEST['tables']['parent_id'])
		$_REQUEST['tables']['COURSE_PERIODS'][$_REQUEST['course_period_id']]['PARENT_ID'] = $_REQUEST['tables']['parent_id'];

	foreach($_REQUEST['tables'] as $table_name=>$tables)
	{
		foreach($tables as $id=>$columns)
		{
			if($columns['TOTAL_SEATS'] && !is_numeric($columns['TOTAL_SEATS']))
				$columns['TOTAL_SEATS'] = ereg_replace('[^0-9]+','',$columns['TOTAL_SEATS']);
			if($columns['DAYS'])
			{
				foreach($columns['DAYS'] as $day=>$y)
				{
					if($y=='Y')
						$days .= $day;
				}
				$columns['DAYS'] = $days;
			}
			if($columns['DOES_ATTENDANCE'])
			{        
				foreach($columns['DOES_ATTENDANCE'] as $tbl=>$y)
				{
					if($y=='Y')
						$tbls .= ','.$tbl;
				}
				if($tbls)
					$columns['DOES_ATTENDANCE'] = $tbls.',';
				else
					$columns['DOES_ATTENDANCE'] = '';
			}

			if($id!='new')
			{
				if($table_name=='COURSES' && $columns['SUBJECT_ID'] && $columns['SUBJECT_ID']!=$_REQUEST['subject_id'])
					$_REQUEST['subject_id'] = $columns['SUBJECT_ID'];

				$sql = "UPDATE $table_name SET ";

				if($table_name=='COURSE_PERIODS')
				{
					$current = DBGet(DBQuery("SELECT TEACHER_ID,PERIOD_ID,MARKING_PERIOD_ID,DAYS,SHORT_NAME FROM COURSE_PERIODS WHERE ".$where[$table_name]."='$id'"));

					if($columns['TEACHER_ID'])
						$staff_id = $columns['TEACHER_ID'];
					else
						$staff_id = $current[1]['TEACHER_ID'];
					if($columns['PERIOD_ID'])
						$period_id = $columns['PERIOD_ID'];
					else
						$period_id = $current[1]['PERIOD_ID'];
					if(isset($columns['MARKING_PERIOD_ID']))
						$marking_period_id = $columns['MARKING_PERIOD_ID'];
					else
						$marking_period_id = $current[1]['MARKING_PERIOD_ID'];
					if($columns['DAYS'])
						$days = $columns['DAYS'];
					else
						$days = $current[1]['DAYS'];
					if($columns['SHORT_NAME'])
						$short_name = $columns['SHORT_NAME'];
					else
						$short_name = $current[1]['SHORT_NAME'];

					$teacher = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND STAFF_ID='$staff_id'"));
					$period = DBGet(DBQuery("SELECT TITLE FROM SCHOOL_PERIODS WHERE PERIOD_ID='$period_id' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
					if(GetMP($marking_period_id,'MP')!='FY')
						$mp_title = GetMP($marking_period_id,'SHORT_NAME').' - ';
					if(strlen($days)<5)
						$mp_title .= $days.' - ';
					if($short_name)
						$mp_title .= $short_name.' - ';

					$title = str_replace("'","''",$period[1]['TITLE'].' - '.$mp_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);
					$sql .= "TITLE='$title',";

					if(isset($columns['MARKING_PERIOD_ID']))
					{
						if(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='FY')
							$columns['MP'] = 'FY';
						elseif(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='SEM')
							$columns['MP'] = 'SEM';
						else
							$columns['MP'] = 'QTR';
					}
				}

				foreach($columns as $column=>$value)
					$sql .= $column."='".str_replace("\'","''",$value)."',";

				$sql = substr($sql,0,-1) . " WHERE ".$where[$table_name]."='$id'";
				DBQuery($sql);

			}
			else
			{
				$sql = "INSERT INTO $table_name ";

				if($table_name=='COURSE_SUBJECTS')
				{
					$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSE_SUBJECTS_SEQ').' AS ID'.FROM_DUAL));
					$fields = 'SUBJECT_ID,SCHOOL_ID,SYEAR,';
					$values = "'".$id[1]['ID']."','".UserSchool()."','".UserSyear()."',";
					$_REQUEST['subject_id'] = $id[1]['ID'];
				}
				elseif($table_name=='COURSES')
				{
					$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSES_SEQ').' AS ID'.FROM_DUAL));
					$fields = 'COURSE_ID,SUBJECT_ID,SCHOOL_ID,SYEAR,';
					$values = "'".$id[1]['ID']."','$_REQUEST[subject_id]','".UserSchool()."','".UserSyear()."',";
					$_REQUEST['course_id'] = $id[1]['ID'];
				}
				elseif($table_name=='COURSE_PERIODS')
				{
					$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSE_PERIODS_SEQ').' AS ID'.FROM_DUAL));
					$fields = 'SYEAR,SCHOOL_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FILLED_SEATS,';
					$teacher = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND STAFF_ID='$columns[TEACHER_ID]'"));
					$period = DBGet(DBQuery("SELECT TITLE FROM SCHOOL_PERIODS WHERE PERIOD_ID='$columns[PERIOD_ID]' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

					if(!isset($columns['PARENT_ID']))
						$columns['PARENT_ID'] = $id[1]['ID'];

					if(isset($columns['MARKING_PERIOD_ID']))
					{
						if(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='FY')
							$columns['MP'] = 'FY';
						elseif(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='SEM')
							$columns['MP'] = 'SEM';
						else
							$columns['MP'] = 'QTR';

						if(GetMP($columns['MARKING_PERIOD_ID'],'MP')!='FY')
							$mp_title = GetMP($columns['MARKING_PERIOD_ID'],'SHORT_NAME').' - ';
					}

					if(strlen($columns['DAYS'])<5)
						$mp_title .= $columns['DAYS'].' - ';
					if($columns['SHORT_NAME'])
						$mp_title .= $columns['SHORT_NAME'].' - ';
					$title = str_replace("'","''",$period[1]['TITLE'].' - '.$mp_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);

					$values = "'".UserSyear()."','".UserSchool()."','".$id[1]['ID']."','$_REQUEST[course_id]','$title','0',";
					$_REQUEST['course_period_id'] = $id[1]['ID'];
				}

				$go = 0;
				foreach($columns as $column=>$value)
				{
					if(isset($value))
					{
						$fields .= $column.',';
						$values .= "'".str_replace("\'","''",$value)."',";
						$go = true;
					}
				}
				$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';

				if($go)
					DBQuery($sql);
			}
		}
	}
	unset($_REQUEST['tables']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if($_REQUEST['course_period_id'])
	{
		$table = 'course period';
		$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID='$_REQUEST[course_period_id]'";
		$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
		$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
        $unset = 'course_period_id';
	}
	elseif($_REQUEST['course_id'])
	{
		$table = 'course';
		$sql[] = "DELETE FROM COURSES WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_ID='$_REQUEST[course_id]')";
		$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "DELETE FROM SCHEDULE_REQUESTS WHERE COURSE_ID='$_REQUEST[course_id]'";
        $unset = 'course_id';
	}
	elseif($_REQUEST['subject_id'])
	{
		$table = 'subject';
		$sql[] = "DELETE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
		$courses = DBGet(DBQuery("SELECT COURSE_ID FROM COURSES WHERE SUBJECT_ID='$_REQUEST[subject_id]'"));
		if(count($courses))
		{
			foreach($courses as $course)
			{
				$sql[] = "DELETE FROM COURSES WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_ID='$course[COURSE_ID]')";
				$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "DELETE FROM SCHEDULE_REQUESTS WHERE COURSE_ID='$course[COURSE_ID]'";
			}
		}
        $unset = 'subject_id';
	}

	if(DeletePrompt($table))
	{
		foreach($sql as $query)
			DBQuery($query);
        unset($_REQUEST[$unset]);
		unset($_REQUEST['modfunc']);
	}
}

if((!$_REQUEST['modfunc'] || $_REQUEST['modfunc']=='choose_course') && !$_REQUEST['course_modfunc'])
{
	if($_REQUEST['modfunc']!='choose_course')
		DrawHeader(ProgramTitle());
	$sql = "SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$subjects_RET = DBGet($QI);

	if($_REQUEST['modfunc']!='choose_course')
	{
		if(AllowEdit())
			$delete_button = "<INPUT type=button value='"._('Delete')."' onClick='javascript:window.location=\"Modules.php?modname=$_REQUEST[modname]&modfunc=delete&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]&course_period_id=$_REQUEST[course_period_id]\"'>";
		// ADDING & EDITING FORM
		if($_REQUEST['course_period_id'])
		{
			if($_REQUEST['course_period_id']!='new')
			{
				$sql = "SELECT PARENT_ID,TITLE,SHORT_NAME,PERIOD_ID,DAYS,
								MP,MARKING_PERIOD_ID,TEACHER_ID,CALENDAR_ID,
								ROOM,TOTAL_SEATS,DOES_ATTENDANCE,
								GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,
								GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,
								HALF_DAY,DOES_BREAKOFF
						FROM COURSE_PERIODS
						WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
				$new = false;
			}
			else
			{
				$sql = "SELECT TITLE
						FROM COURSES
						WHERE COURSE_ID='$_REQUEST[course_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$title = $RET[1]['TITLE'].' - '._('New Period');
				unset($delete_button);
				unset($RET);
				$checked = 'CHECKED';
				$new = true;
			}

			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]&course_period_id=$_REQUEST[course_period_id] method=POST>";
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			$header .= '<TABLE cellpadding=3 width=100%>';
			$header .= '<TR>';

			$header .= '<TD>' . TextInput($RET['SHORT_NAME'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][SHORT_NAME]',($RET['SHORT_NAME']?'':'<FONT color=red>')._('Short Name').($RET['SHORT_NAME']?'':'</FONT>')) . '</TD>';

			$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE (SCHOOLS IS NULL OR strpos(SCHOOLS,',".UserSchool().",')>0) AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME"));
			if(count($teachers_RET))
			{
				foreach($teachers_RET as $teacher)
					$teachers[$teacher['STAFF_ID']] = $teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'].' '.$teacher['MIDDLE_NAME'];
			}
			$header .= '<TD>' . SelectInput($RET['TEACHER_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][TEACHER_ID]',($RET['TEACHER_ID']?'':'<FONT color=red>')._('Teacher').($RET['TEACHER_ID']?'':'</FONT>'),$teachers) . '</TD>';

			$header .= '<TD>' . TextInput($RET['ROOM'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][ROOM]',_('Room')) . '</TD>';

			$periods_RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER,TITLE"));
			if(count($periods_RET))
			{
				foreach($periods_RET as $period)
					$periods[$period['PERIOD_ID']] = $period['TITLE'];
			}
			$header .= '<TD>' . SelectInput($RET['PERIOD_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][PERIOD_ID]',($RET['PERIOD_ID']?'':'<FONT color=red>')._('Period').($RET['PERIOD_ID']?'':'</FONT>'),$periods) . '</TD>';
			$header .= '<TD>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '<DIV id=days><div onclick=\'addHTML("';
			$header .= '<TABLE><TR>';
			$days = array(_('Su'),_('M'),_('T'),_('W'),_('Th'),_('F'),_('Sa'));
			foreach($days as $day)
			{
				if(strpos($RET['DAYS'],$day)!==false || ($new && $day!=_('Sa') && $day!=_('Su')))
					$value = 'Y';
				else
					$value = '';

				$header .= 
'<TD>'.str_replace('"','\"',CheckboxInput($value,'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DAYS]['.$day.']',$day,$checked,false,'','',false)).'</TD>';
			}
			$header .= '</TR></TABLE>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '","days",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$RET['DAYS'].'</span></div></DIV>';
			$header .= '<small><FONT color='.($RET['DAYS']?Preferences('TITLES'):'red').'>'._('Meeting Days').'</FONT></small>';
			$header .= '</TD>';
			//$header .= '<TD>' . SelectInput($RET['MP'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][MP]','Length',array('FY'=>'Full Year','SEM'=>'Semester','QTR'=>'Marking Period')) . '</TD>';
			$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,SHORT_NAME,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
			unset($options);

			if(count($mp_RET))
			{
				foreach($mp_RET as $mp)
					$options[$mp['MARKING_PERIOD_ID']] = $mp['SHORT_NAME'];
			}
			$header .= '<TD>' . SelectInput($RET['MARKING_PERIOD_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][MARKING_PERIOD_ID]',($RET['MARKING_PERIOD_ID']?'':'<FONT color=red>')._('Marking Period').($RET['MARKING_PERIOD_ID']?'':'</FONT>'),$options,false) . '</TD>';
			$header .= '<TD>' . TextInput($RET['TOTAL_SEATS'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][TOTAL_SEATS]',_('Seats'),'size=4') . '</TD>';

			$header .= '</TR>';

			$header .= '<TR>';

			//$header .= '<TD>' . CheckboxInput($RET['DOES_ATTENDANCE'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_ATTENDANCE]','Takes Attendance',$checked,$new,'<IMG SRC=assets/check.gif height=15 vspace=0 hspace=0 border=0>','<IMG SRC=assets/x.gif height=15 vspace=0 hspace=0 border=0>') . '</TD>';
			$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'"._('Attendance')."' AS TITLE UNION SELECT ID,TITLE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

			$header .= '<TD>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '<DIV id=attendance><div onclick=\'addHTML("';
			$header .= '<TABLE><TR>';
			$top = '<TABLE><TR>';
			foreach($categories_RET as $value)
			{
				if(strpos($RET['DOES_ATTENDANCE'],','.$value['ID'].',')!==false)
				{
					$val = 'Y';
					$img = 'check';
				}
				else
				{
					$val = '';
					$img = 'x';
				}

				$header .= '<TD>'.str_replace('"','\"',CheckboxInput($val,'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_ATTENDANCE]['.$value['ID'].']',$value['TITLE'],$checked,false,'','',false)).'</TD>';
				$top .= '<TD><IMG SRC=assets/'.$img.'.gif height=15 vspace=0 hspace=0 border=0><BR>'/*<small><FONT color='.Preferences('TITLES').'>'.$value['TITLE'].'</FONT></small>*/;
			}
			$header .= '</TR></TABLE>';
			$top .= '</TR></TABLE>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '","attendance",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$top.'</span></div></DIV>';
			$header .= '<small><FONT color='.Preferences('TITLES').'>'._('Takes Attendance').'</FONT></small>';
			$header .= '</TD>';

			$header .= '<TD>' . CheckboxInput($RET['DOES_HONOR_ROLL'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_HONOR_ROLL]',_('Affects Honor Roll'),$checked,$new,'<IMG SRC=assets/check.gif height=15 vspace=0 hspace=0 border=0>','<IMG SRC=assets/x.gif height=15 vspace=0 hspace=0 border=0>') . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['DOES_CLASS_RANK'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_CLASS_RANK]',_('Affects Class Rank'),$checked,$new,'<IMG SRC=assets/check.gif height=15 vspace=0 hspace=0 border=0>','<IMG SRC=assets/x.gif height=15 vspace=0 hspace=0 border=0>') . '</TD>';
			$header .= '<TD>' . SelectInput($RET['GENDER_RESTRICTION'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][GENDER_RESTRICTION]',_('Gender Restriction'),array('N'=>_('None'),'M'=>_('Male'),'F'=>_('Female')),false) . '</TD>';

			$options_RET = DBGet(DBQuery("SELECT TITLE,ID FROM REPORT_CARD_GRADE_SCALES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
			$options = array();
			foreach($options_RET as $option)
				$options[$option['ID']] = $option['TITLE'];
			$header .= '<TD>' . SelectInput($RET['GRADE_SCALE_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][GRADE_SCALE_ID]',_('Grading Scale'),$options,_('Not Graded')) . '</TD>';
            //bjj Added to handle credits
            $header .= '<TD>' . TextInput(sprintf('%0.3f',$RET['CREDITS']),'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][CREDITS]',_('Credits'),'size=4') . '</TD>'; 
			$options_RET = DBGet(DBQuery("SELECT TITLE,CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC,TITLE"));
			$options = array();
			foreach($options_RET as $option)
				$options[$option['CALENDAR_ID']] = $option['TITLE'];
			$header .= '<TD>' . SelectInput($RET['CALENDAR_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][CALENDAR_ID]',($RET['CALENDAR_ID']?'':'<FONT color=red>')._('Calendar').($RET['CALENDAR_ID']?'':'</FONT>'),$options,false) . '</TD>';

			//BJJ Parent course select was here...  moved it down

			$header .= '</TR>';

			$header .= '<TR>';

			//$header .= '<TD>' . CheckboxInput($RET['HOUSE_RESTRICTION'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][HOUSE_RESTRICTION]','Restricts House','',$new) . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['HALF_DAY'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][HALF_DAY]',_('Half Day'),$checked,$new,'<IMG SRC=assets/check.gif height=15 vspace=0 hspace=0 border=0>','<IMG SRC=assets/x.gif height=15 vspace=0 hspace=0 border=0>') . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['DOES_BREAKOFF'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_BREAKOFF]',_('Allow Teacher Gradescale'),$checked,$new,'<IMG SRC=assets/check.gif height=15 vspace=0 hspace=0 border=0>','<IMG SRC=assets/x.gif height=15 vspace=0 hspace=0 border=0>') . '</TD>';
            //BJJ added cells to place parent selection in the last column
            $header .= '<TD colspan= 4>&nbsp;</td>';
            
            if($_REQUEST['course_period_id']!='new' && $RET['PARENT_ID']!=$_REQUEST['course_period_id'])
            {
                $parent = DBGet(DBQuery("SELECT cp.TITLE as CP_TITLE,c.TITLE AS C_TITLE FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$RET['PARENT_ID']."'"));
                $parent = $parent[1]['C_TITLE'].': '.$parent[1]['CP_TITLE'];
            }
            elseif($_REQUEST['course_period_id']!='new')
            {
                $children = DBGet(DBQuery("SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE PARENT_ID='".$_REQUEST['course_period_id']."' AND COURSE_PERIOD_ID!='".$_REQUEST['course_period_id']."'"));
                if(count($children))
                    $parent = _('N/A');
                else
                    $parent = _('None');
            }

            $header .= "<TD colspan=2><DIV id=course_div>".$parent."</DIV> ".($parent!=_('N/A')?"<A HREF=# onclick='window.open(\"Modules.php?modname=".$_REQUEST['modname']."&modfunc=choose_course\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'><SMALL>"._('Choose')."</SMALL></A><BR>":'')."<small><FONT color=".Preferences('TITLES').">"._('Parent Course Period')."</FONT></small></TD>";

			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			//echo '</FORM>';
		}
		elseif($_REQUEST['course_id'])
		{
			if($_REQUEST['course_id']!='new')
			{
				$sql = "SELECT TITLE,SHORT_NAME,GRADE_LEVEL
						FROM COURSES
						WHERE COURSE_ID='$_REQUEST[course_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
			}
			else
			{
				$sql = "SELECT TITLE
						FROM COURSE_SUBJECTS
						WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$title = $RET[1]['TITLE'].' - '._('New Course');
				unset($delete_button);
				unset($RET);
			}

			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id] method=POST>";
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			$header .= '<TABLE cellpadding=3 bgcolor=#F0F0F1 width=100%>';
			$header .= '<TR>';

			$header .= '<TD>' . TextInput($RET['TITLE'],'tables[COURSES]['.$_REQUEST['course_id'].'][TITLE]',_('Title')) . '</TD>';
			$header .= '<TD>' . TextInput($RET['SHORT_NAME'],'tables[COURSES]['.$_REQUEST['course_id'].'][SHORT_NAME]',_('Short Name')) . '</TD>';
			if($_REQUEST['modfunc']!='choose_course')
			{
				foreach($subjects_RET as $type)
					$options[$type['SUBJECT_ID']] = $type['TITLE'];

				$header .= '<TD>' . SelectInput($RET['SUBJECT_ID']?$RET['SUBJECT_ID']:$_REQUEST['subject_id'],'tables[COURSES]['.$_REQUEST['course_id'].'][SUBJECT_ID]',_('Subject'),$options,false) . '</TD>';
			}
			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			echo '</FORM>';
		}
		elseif($_REQUEST['subject_id'])
		{
			if($_REQUEST['subject_id']!='new')
			{
				$sql = "SELECT TITLE,SORT_ORDER
						FROM COURSE_SUBJECTS
						WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
			}
			else
			{
				$title = _('New Subject');
				unset($delete_button);
			}

			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id] method=POST>";
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			$header .= '<TABLE cellpadding=3 bgcolor=#F0F0F1 width=100%>';
			$header .= '<TR>';

			$header .= '<TD>' . TextInput($RET['TITLE'],'tables[COURSE_SUBJECTS]['.$_REQUEST['subject_id'].'][TITLE]',_('Title')) . '</TD>';
			$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables[COURSE_SUBJECTS]['.$_REQUEST['subject_id'].'][SORT_ORDER]',_('Sort Order')) . '</TD>';

			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			echo '</FORM>';
		}
	}

	// DISPLAY THE MENU
	if($_REQUEST['modfunc']=='choose_course')
	{
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
		{
			echo "<FORM action=Modules.php?modname=$_REQUEST[modname] method=POST>";
			DrawHeader(_('Choose a ').($_REQUEST['last_year']=='true'?_('Last Year '):'').($_REQUEST['subject_id']?($_REQUEST['course_id']?_('Course Period'):_('Course')):_('Subject')),_('Enrollment Date ').PrepareDate($date,'_date',false,array('submit'=>true)),CheckBoxOnclick('include_child_mps')._('Offer Enrollment in Child Marking Periods'));
			echo '</FORM>';
		}
		else
			DrawHeader(_('Choose a ').($_REQUEST['last_year']=='true'?_('Last Year '):'').($_REQUEST['subject_id']?($_REQUEST['course_id']?_('Course Period'):_('Course')):_('Subject')));
	}
	elseif(!$_REQUEST['subject_id'])
		DrawHeader(_('Courses'));
	DrawHeader('',"<A HREF=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&course_modfunc=search&last_year=$_REQUEST[last_year]".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'').">"._('Search')."</A>");

	echo '<TABLE><TR>';

	if(count($subjects_RET))
	{
		if($_REQUEST['subject_id'])
		{
			foreach($subjects_RET as $key=>$value)
			{
				if($value['SUBJECT_ID']==$_REQUEST['subject_id'])
					$subjects_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	$columns = array('TITLE'=>_('Subject'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]";
	$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID');
	if($_REQUEST['modfunc']=='choose_course')
		$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
	else
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=new";

	echo '<TD valign=top>';
	ListOutput($subjects_RET,$columns,_('Subject'),_('Subjects'),$link,array(),$LO_options);
	echo '</TD>';

	if($_REQUEST['subject_id'] && $_REQUEST['subject_id']!='new')
	{
		$sql = "SELECT COURSE_ID,TITLE FROM COURSES WHERE SUBJECT_ID='$_REQUEST[subject_id]' ORDER BY TITLE";
		$QI = DBQuery($sql);
		$courses_RET = DBGet($QI);

		if(count($courses_RET))
		{
			if($_REQUEST['course_id'])
			{
				foreach($courses_RET as $key=>$value)
				{
					if($value['COURSE_ID']==$_REQUEST['course_id'])
						$courses_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
				}
			}
		}

		$columns = array('TITLE'=>_('Course'));
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]";
		$link['TITLE']['variables'] = array('course_id'=>'COURSE_ID');
		if($_REQUEST['modfunc']=='choose_course')
			$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		else
			$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=new";

		echo '<TD valign=top>';
		ListOutput($courses_RET,$columns,_('Course'),_('Courses'),$link,array(),$LO_options);
		echo '</TD>';

		if($_REQUEST['course_id'] && $_REQUEST['course_id']!='new')
		{
                $periods_RET = DBGet(DBQuery("SELECT '$_REQUEST[subject_id]' AS SUBJECT_ID,COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='$_REQUEST[course_id]' ".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '$date'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));

                if($_REQUEST['modname']=='Scheduling/Schedule.php')
                    calcSeats1($periods_RET,$date);

                if(count($periods_RET))
                {
                    if($_REQUEST['course_period_id'])
                    {
                        foreach($periods_RET as $key=>$value)
                        {
                            if($value['COURSE_PERIOD_ID']==$_REQUEST['course_period_id'])
                                $periods_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
                        }
                    }
                }

                $columns = array('TITLE'=>_('Course Period'));
                $link = array();
                if($_REQUEST['modname']!='Scheduling/Schedule.php' || ($_REQUEST['modname']=='Scheduling/Schedule.php' && !$_REQUEST['include_child_mps']))
                {
                    $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]";
                    $link['TITLE']['variables'] = array('course_period_id'=>'COURSE_PERIOD_ID','course_marking_period_id'=>'MARKING_PERIOD_ID');
                    if($_REQUEST['modfunc']=='choose_course')
                        $link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&student_id=$_REQUEST[student_id]&last_year=$_REQUEST[last_year]";
                    else
                        $link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]&course_period_id=new";
                }
                if($_REQUEST['modname']=='Scheduling/Schedule.php')
                    $columns += array('AVAILABLE_SEATS'=>($_REQUEST['include_child_mps']?'MP<small>(Available Seats)</small>':'Available Seats'));

                echo '<TD valign=top>';
                ListOutput($periods_RET,$columns,_('Period'),_('Periods'),$link,array(),$LO_options);
                echo '</TD>';
		}
	}

	echo '</TR></TABLE>';
}

if($_REQUEST['modname']=='Scheduling/Courses.php' && $_REQUEST['modfunc']=='choose_course' && $_REQUEST['course_period_id'])
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
	$course_title = $course_title[1]['TITLE'] . '<INPUT type=hidden name=tables[parent_id] value='.$_REQUEST['course_period_id'].'>';

	echo "<script language=javascript>opener.document.getElementById(\"".($_REQUEST['last_year']=='true'?'ly_':'')."course_div\").innerHTML = \"$course_title</small>\"; window.close();</script>";
}

function calcSeats1(&$periods,$date)
{
	$date_time = strtotime($date);
	foreach($periods as $key=>$period)
	{
		if($_REQUEST['include_child_mps'])
		{
			$mps = GetChildrenMP($period['MP'],$period['MARKING_PERIOD_ID']);
			if($period['MP']=='FY' || $period['MP']=='SEM')
				$mps = "'$period[MARKING_PERIOD_ID]'".($mps?','.$mps:'');
		}
		else
			$mps = "'".$period['MARKING_PERIOD_ID']."'";
		$periods[$key]['AVAILABLE_SEATS'] = '';
		foreach(explode(',',$mps) as $mp)
		{
			$mp = trim($mp,"'");
			if(strtotime(GetMP($mp,'END_DATE'))>=$date_time)
			{
				$link = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&subject_id=$period[SUBJECT_ID]&course_id=$period[COURSE_ID]";
				$link .= "&last_year=$_REQUEST[last_year]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]";
				$link .= "&course_period_id=$period[COURSE_PERIOD_ID]&course_marking_period_id=$mp";
				if($period['AVAILABLE_SEATS'])
				{
					$seats = DBGet(DBQuery("SELECT max((SELECT count(1) FROM SCHEDULE ss JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR) WHERE ss.COURSE_PERIOD_ID='$period[COURSE_PERIOD_ID]' AND (ss.MARKING_PERIOD_ID='$mp' OR ss.MARKING_PERIOD_ID IN (".GetAllMP(GetMP($mp,'MP'),$mp).")) AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE)) AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS FROM ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID='$period[CALENDAR_ID]' AND ac.SCHOOL_DATE BETWEEN '$date' AND '".GetMP($mp,'END_DATE')."'"));
					if($seats[1]['FILLED_SEATS']!='')
						if($_REQUEST['include_child_mps'])
							$periods[$key]['AVAILABLE_SEATS'] .= '<A href='.$link.'>'.(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'<small>('.($period['AVAILABLE_SEATS']-$seats[1]['FILLED_SEATS']).')</small></A> | ';
						else
							$periods[$key]['AVAILABLE_SEATS'] = $period['AVAILABLE_SEATS']-$seats[1]['FILLED_SEATS'];
				}
				else
					if($_REQUEST['include_child_mps'])
						$periods[$key]['AVAILABLE_SEATS'] .= '<A href='.$link.'>'.(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'</A> | ';
					else
						$periods[$key]['AVAILABLE_SEATS'] = 'n/a';
			}
		}
		if($_REQUEST['include_child_mps'])
			$periods[$key]['AVAILABLE_SEATS'] = substr($periods[$key]['AVAILABLE_SEATS'],0,-3);
	}
}
?>
