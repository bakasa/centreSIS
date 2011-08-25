<?php

if(!$_REQUEST['month'])
	$_REQUEST['month'] = date('n');
else
	$_REQUEST['month'] = MonthNWSwitch($_REQUEST['month'],'tonum')*1;
if(!$_REQUEST['year'])
	$_REQUEST['year'] = date('Y');

$time = mktime(0,0,0,$_REQUEST['month'],1,$_REQUEST['year']);

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='create')
{
	$fy_RET = DBGet(DBQuery("SELECT START_DATE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
	$fy_RET = $fy_RET[1];
    $title_RET = DBGet(DBQuery("SELECT ac.CALENDAR_ID,ac.TITLE,ac.DEFAULT_CALENDAR,ac.SCHOOL_ID,(SELECT coalesce(SHORT_NAME,TITLE) FROM SCHOOLS WHERE SYEAR=ac.SYEAR AND ID=ac.SCHOOL_ID) AS SCHOOL_TITLE,(SELECT min(SCHOOL_DATE) FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID=ac.CALENDAR_ID) AS START_DATE,(SELECT max(SCHOOL_DATE) FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID=ac.CALENDAR_ID) AS END_DATE FROM ATTENDANCE_CALENDARS ac,STAFF s WHERE ac.SYEAR='".UserSyear()."' AND s.STAFF_ID='".User('STAFF_ID')."' AND (s.SCHOOLS IS NULL OR position(','||ac.SCHOOL_ID||',' IN s.SCHOOLS)>0) ORDER BY ".db_case(array('ac.SCHOOL_ID',"'".UserSchool()."'",0,'ac.SCHOOL_ID')).",ac.DEFAULT_CALENDAR ASC,ac.TITLE"));

	$message = '<SELECT name=copy_id><OPTION value="">N/A';
	foreach($title_RET as $id=>$title)
	{
		if($_REQUEST['calendar_id'] && $title['CALENDAR_ID']==$_REQUEST['calendar_id'])
		{
			$message .=  '<OPTION value="'.$title['CALENDAR_ID'].'" selected>'.$title['TITLE'].(AllowEdit()&&$title['DEFAULT_CALENDAR']=='Y'?' (default)':'');
			$default_id = $id;
			$prompt = $title['TITLE'];
		}
		else
            $message .= '<OPTION value="'.$title['CALENDAR_ID'].'">'.($title['SCHOOL_ID']!=UserSchool()?$title['SCHOOL_TITLE'].':':'').$title['TITLE'].(AllowEdit()&&$title['DEFAULT_CALENDAR']=='Y'?' (default)':'');

	}
	$message .= '</SELECT>';
	$message = '<TABLE><TR><TD colspan=7 align=center><table><tr><td>'.NoInput('<INPUT type=text name=title'.($_REQUEST['calendar_id']?' value='.$title_RET[$default_id]['TITLE']:'').'>',_('Title')).'</td><td>'.NoInput('<INPUT type=checkbox name=default value=Y'.($_REQUEST['calendar_id']&&$title_RET[$default_id]['DEFAULT_CALENDAR']=='Y'?' checked':'').'>',_('Default Calendar for this School')).'</td><td>'.NoInput($message,_('Copy Calendar')).'</td></tr></table></TD></TR>';
	$message .= '<TR><TD colspan=7 align=center><table><tr><td>'.NoInput(PrepareDate($_REQUEST['calendar_id']&&$title_RET[$default_id]['START_DATE']?$title_RET[$default_id]['START_DATE']:$fy_RET['START_DATE'],'_min'),_('From')).'</td><td>'.NoInput(PrepareDate($_REQUEST['calendar_id']&&$title_RET[$default_id]['END_DATE']?$title_RET[$default_id]['END_DATE']:$fy_RET['END_DATE'],'_max'),_('To')).'</td></tr></table></TD></TR>';
	$message .= '<TR><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[0]'.($_REQUEST['calendar_id']?' CHECKED':'').'>',_('Sunday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[1] CHECKED>',_('Monday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[2] CHECKED>',_('Tuesday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[3] CHECKED>',_('Wednesday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[4] CHECKED>',_('Thursday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[5] CHECKED>',_('Friday')).'</TD><TD>'.NoInput('<INPUT type=checkbox value=Y name=weekdays[6]'.($_REQUEST['calendar_id']?' CHECKED':'').'>',_('Saturday')).'</TD></TR>';
	$message .= '<TR><TD colspan=7 align=center><table><tr><td>'.NoInput('<INPUT type=text name=minutes size=3 maxlength=3>',_('Minutes')).'</td><td><FONT color='.Preferences('TITLES').'><SMALL>('.($_REQUEST['calendar_id']?_('Default is Full Day if Copy Calendar is N/A.').'<BR>'._('Otherwise Default is minutes from the Copy Calendar'):_('Default is Full Day')).')</SMALL></FONT></td></tr></table></TD></TR>';
	$message .= '</TABLE>';
	if(Prompt($_REQUEST['calendar_id']?sprintf(_('Recreate %s calendar'),$prompt):_('Create new calendar'),'',$message))
	{
		if($_REQUEST['calendar_id'])
			$calendar_id = $_REQUEST['calendar_id'];
		else
		{
			$calendar_id = DBGet(DBQuery("SELECT ".db_seq_nextval('CALENDARS_SEQ')." AS CALENDAR_ID ".FROM_DUAL));
			$calendar_id = $calendar_id[1]['CALENDAR_ID'];
		}
		if($_REQUEST['default'])
			DBQuery("UPDATE ATTENDANCE_CALENDARS SET DEFAULT_CALENDAR=NULL WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		if($_REQUEST['calendar_id'])
			DBQuery("UPDATE ATTENDANCE_CALENDARS SET TITLE='".$_REQUEST['title']."',DEFAULT_CALENDAR='".$_REQUEST['default']."' WHERE CALENDAR_ID='".$calendar_id."'");
		else
			DBQuery("INSERT INTO ATTENDANCE_CALENDARS (CALENDAR_ID,SYEAR,SCHOOL_ID,TITLE,DEFAULT_CALENDAR) values('".$calendar_id."','".UserSyear()."','".UserSchool()."','".$_REQUEST['title']."','".$_REQUEST['default']."')");

		if($_REQUEST['copy_id'])
		{
			$weekdays_list = '\''.implode('\',\'',array_keys($_REQUEST['weekdays'])).'\'';
			if($_REQUEST['calendar_id'] && $_REQUEST['calendar_id']==$_REQUEST['copy_id'])
			{
				DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$calendar_id."' AND (SCHOOL_DATE NOT BETWEEN '".$_REQUEST['day_min'].'-'.$_REQUEST['month_min'].'-'.$_REQUEST['year_min']."' AND '".$_REQUEST['day_max'].'-'.$_REQUEST['month_max'].'-'.$_REQUEST['year_max']."' OR extract(DOW FROM SCHOOL_DATE) NOT IN (".$weekdays_list."))");
				if($_REQUEST['minutes'])
					DBQuery("UPDATE ATTENDANCE_CALENDAR SET MINUTES='".$_REQUEST['minutes']."' WHERE CALENDAR_ID='".$calendar_id."'");
			}
			else
			{
				if($_REQUEST['calendar_id'])
					DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$calendar_id."'");
				DBQuery("INSERT INTO ATTENDANCE_CALENDAR (SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID) (SELECT '".UserSyear()."','".UserSchool()."',SCHOOL_DATE,".($_REQUEST['minutes']?"'".$_REQUEST['minutes']."'":'MINUTES').",'".$calendar_id."' FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$_REQUEST['copy_id']."' AND SCHOOL_DATE BETWEEN '".$_REQUEST['day_min'].'-'.$_REQUEST['month_min'].'-'.$_REQUEST['year_min']."' AND '".$_REQUEST['day_max'].'-'.$_REQUEST['month_max'].'-'.$_REQUEST['year_max']."' AND extract(DOW FROM SCHOOL_DATE) IN (".$weekdays_list."))");
			}
		}
		else
		{
			$begin = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_min'],'to_num'),$_REQUEST['day_min']*1,$_REQUEST['year_min']) + 43200;
			$end = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_max'],'to_num'),$_REQUEST['day_max']*1,$_REQUEST['year_max']) + 43200;

			$weekday = date('w',$begin);

			if($_REQUEST['calendar_id'])
				DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$calendar_id."'");
			for($i=$begin;$i<=$end;$i+=86400)
			{
				if($_REQUEST['weekdays'][$weekday]=='Y')
					DBQuery("INSERT INTO ATTENDANCE_CALENDAR (SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID) values('".UserSyear()."','".UserSchool()."','".date('d-M-y',$i)."',".($_REQUEST['minutes']?"'".$_REQUEST['minutes']."'":"'999'").",'".$calendar_id."')");
				$weekday++;
				if($weekday==7)
					$weekday = 0;
			}
		}

		$_REQUEST['calendar_id'] = $calendar_id;
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['weekdays']);
		unset($_SESSION['_REQUEST_vars']['weekdays']);
		unset($_REQUEST['title']);
		unset($_SESSION['_REQUEST_vars']['title']);
		unset($_REQUEST['minutes']);
		unset($_SESSION['_REQUEST_vars']['minutes']);
		unset($_REQUEST['copy_id']);
		unset($_SESSION['_REQUEST_vars']['copy_id']);
	}
}

if($_REQUEST['modfunc']=='delete_calendar')
{
	if(DeletePrompt('calendar'))
	{
		DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$_REQUEST['calendar_id']."'");
		DBQuery("DELETE FROM ATTENDANCE_CALENDARS WHERE CALENDAR_ID='".$_REQUEST['calendar_id']."'");
		$default_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND DEFAULT_CALENDAR='Y'"));
		if(count($default_RET))
			$_REQUEST['calendar_id'] = $default_RET[1]['CALENDAR_ID'];
		else
		{
			$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
			if(count($calendars_RET))
				$_REQUEST['calendar_id'] = $calendars_RET[1]['CALENDAR_ID'];
			else
				$error = array(_('There are no calendars setup yet.'));
		}
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
	}
}

if(User('PROFILE')!='admin')
{
	$course_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
	if($course_RET[1]['CALENDAR_ID'])
		$_REQUEST['calendar_id'] = $course_RET[1]['CALENDAR_ID'];
	else
	{
		$default_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND DEFAULT_CALENDAR='Y'"));
		$_REQUEST['calendar_id'] = $default_RET[1]['CALENDAR_ID'];
	}
}
elseif(!$_REQUEST['calendar_id'])
{
	$default_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND DEFAULT_CALENDAR='Y'"));
	if(count($default_RET))
		$_REQUEST['calendar_id'] = $default_RET[1]['CALENDAR_ID'];
	else
	{
		$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		if(count($calendars_RET))
			$_REQUEST['calendar_id'] = $calendars_RET[1]['CALENDAR_ID'];
		else
			$error = array(_('There are no calendars setup yet.'));
	}
}
unset($_SESSION['_REQUEST_vars']['calendar_id']);

if($_REQUEST['modfunc']=='detail')
{
	if($_REQUEST['month_values'] && $_REQUEST['day_values'] && $_REQUEST['year_values'])
	{
		$_REQUEST['values']['SCHOOL_DATE'] = $_REQUEST['day_values']['SCHOOL_DATE'].'-'.$_REQUEST['month_values']['SCHOOL_DATE'].'-'.$_REQUEST['year_values']['SCHOOL_DATE'];
		if(!VerifyDate($_REQUEST['values']['SCHOOL_DATE']))
			unset($_REQUEST['values']['SCHOOL_DATE']);
	}

	if($_POST['button']==_('Save') && AllowEdit())
	{
		if($_REQUEST['values'])
		{
			if($_REQUEST['event_id']!='new')
			{
				$sql = "UPDATE CALENDAR_EVENTS SET ";

				foreach($_REQUEST['values'] as $column=>$value)
					$sql .= $column."='".str_replace("\'","''",$value)."',";

				$sql = substr($sql,0,-1) . " WHERE ID='$_REQUEST[event_id]'";
				DBQuery($sql);
			}
			else
			{
				$sql = "INSERT INTO CALENDAR_EVENTS ";

				$fields = 'ID,SYEAR,SCHOOL_ID,';
				$values = db_seq_nextval('CALENDAR_EVENTS_SEQ').",'".UserSyear()."','".UserSchool()."',";

				$go = 0;
				foreach($_REQUEST['values'] as $column=>$value)
				{
					if($value)
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
			echo '<SCRIPT language=javascript>opener.document.location = "Modules.php?modname='.$_REQUEST['modname'].'&year='.$_REQUEST['year'].'&month='.MonthNWSwitch($_REQUEST['month'],'tochar').'"; window.close();</script>';
			unset($_REQUEST['values']);
			unset($_SESSION['_REQUEST_vars']['values']);
		}
	}
	elseif($_REQUEST['button']==_('Delete'))
	{
		if(DeletePrompt('event'))
		{
			DBQuery("DELETE FROM CALENDAR_EVENTS WHERE ID='".$_REQUEST[event_id]."'");
			echo '<SCRIPT language=javascript>opener.document.location = "Modules.php?modname='.$_REQUEST['modname'].'&year='.$_REQUEST['year'].'&month='.MonthNWSwitch($_REQUEST['month'],'tochar').'"; window.close();</script>';
			unset($_REQUEST['values']);
			unset($_SESSION['_REQUEST_vars']['values']);
			unset($_REQUEST['button']);
			unset($_SESSION['_REQUEST_vars']['button']);
		}
	}
	else
	{
		if($_REQUEST['event_id'])
		{
			if($_REQUEST['event_id']!='new')
			{
				$RET = DBGet(DBQuery("SELECT TITLE,DESCRIPTION,to_char(SCHOOL_DATE,'dd-MON-yy') AS SCHOOL_DATE FROM CALENDAR_EVENTS WHERE ID='$_REQUEST[event_id]'"));
				$title = $RET[1]['TITLE'];
			}
			else
			{
				$title = _('New Event');
				$RET[1]['SCHOOL_DATE'] = $_REQUEST['school_date'];
			}
			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=detail&event_id=$_REQUEST[event_id]&month=$_REQUEST[month]&year=$_REQUEST[year] METHOD=POST>";
		}
		else
		{
			$RET = DBGet(DBQuery("SELECT TITLE,STAFF_ID,to_char(DUE_DATE,'dd-MON-yy') AS SCHOOL_DATE,DESCRIPTION FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'"));
			$title = $RET[1]['TITLE'];
			$RET[1]['STAFF_ID'] = GetTeacher($RET[1]['STAFF_ID']);
		}

		echo '<BR>';
		PopTable('header',$title);
		echo '<TABLE>';
		echo '<TR><TD>'._('Date').'</TD><TD>'.DateInput($RET[1]['SCHOOL_DATE'],'values[SCHOOL_DATE]','',false).'</TD></TR>';
		echo '<TR><TD>'._('Title').'</TD><TD>'.TextInput($RET[1]['TITLE'],'values[TITLE]').'</TD></TR>';
		if($RET[1]['STAFF_ID'])
			echo '<TR><TD>'._('Teacher').'</TD><TD>'.TextAreaInput($RET[1]['STAFF_ID'],'values[STAFF_ID]').'</TD></TR>';
		echo '<TR><TD>'._('Notes').'</TD><TD>'.TextAreaInput($RET[1]['DESCRIPTION'],'values[DESCRIPTION]').'</TD></TR>';
		if(AllowEdit())
		{
			echo '<TR><TD colspan=2 align=center><INPUT type=submit name=button value="'._('Save').'">';
			if($_REQUEST['event_id']!='new')
				echo '<INPUT type=submit name=button value="'._('Delete').'">';
			echo '</TD></TR>';
		}
		echo '</TABLE>';
		PopTable('footer');
		if($_REQUEST['event_id'])
			echo '</FORM>';

		unset($_REQUEST['values']);
		unset($_SESSION['_REQUEST_vars']['values']);
		unset($_REQUEST['button']);
		unset($_SESSION['_REQUEST_vars']['button']);
	}
}

if($_REQUEST['modfunc']=='list_events')
{
	if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
	{
		while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
			$_REQUEST['day_start']--;
	}
	else
	{
		$min_date = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS MIN_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		if($min_date[1]['MIN_DATE'])
			$start_date = $min_date[1]['MIN_DATE'];
		else
			$start_date = '01-'.strtoupper(date('M-y'));
	}

	if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
	{
		while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
			$_REQUEST['day_end']--;
	}
	else
	{
		$max_date = DBGet(DBQuery("SELECT max(SCHOOL_DATE) AS MAX_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		if($max_date[1]['MAX_DATE'])
			$end_date = $max_date[1]['MAX_DATE'];
		else
			$end_date = strtoupper(date('d-M-y'));
	}

	echo '<FORM action=Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&month='.$_REQUEST['month'].'&year='.$_REQUEST['year'].' METHOD=POST>';
	DrawHeader(PrepareDate($start_date,'_start').' - '.PrepareDate($end_date,'_end').' <A HREF=Modules.php?modname='.$_REQUEST['modname'].'&month='.$_REQUEST['month'].'&year='.$_REQUEST['year'].'>'._('Back to Calendar').'</A>','<INPUT type=submit value='._('Go').'>');
	$functions = array('SCHOOL_DATE'=>'ProperDate');
	$events_RET = DBGet(DBQuery("SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION FROM CALENDAR_EVENTS WHERE SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),$functions);
	ListOutput($events_RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description')),_('Event'),_('Events'));
	echo '</FORM>';
}

if(!$_REQUEST['modfunc'])
{
	$last = 31;
	while(!checkdate($_REQUEST['month'], $last, $_REQUEST['year']))
		$last--;

	$calendar_RET = DBGet(DBQuery("SELECT to_char(SCHOOL_DATE,'dd-MON-YY') AS SCHOOL_DATE,MINUTES,BLOCK FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'"),array(),array('SCHOOL_DATE'));
	if($_REQUEST['minutes'])
	{
		foreach($_REQUEST['minutes'] as $date=>$minutes)
		{
			if($calendar_RET[$date])
			{
				if($minutes!='0' && $minutes!='')
					DBQuery("UPDATE ATTENDANCE_CALENDAR SET MINUTES='".$minutes."' WHERE SCHOOL_DATE='".$date."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'");
				else
					DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE='".$date."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'");
			}
			elseif($minutes!='0' && $minutes!='')
				DBQuery("INSERT INTO ATTENDANCE_CALENDAR (SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES) values('".UserSyear()."','".UserSchool()."','".$date."','".$_REQUEST['calendar_id']."','".$minutes."')");
		}
		$calendar_RET = DBGet(DBQuery("SELECT to_char(SCHOOL_DATE,'dd-MON-YY') AS SCHOOL_DATE,MINUTES,BLOCK FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'"),array(),array('SCHOOL_DATE'));
		unset($_REQUEST['minutes']);
		unset($_SESSION['_REQUEST_vars']['minutes']);
	}
	if($_REQUEST['all_day'])
	{
		foreach($_REQUEST['all_day'] as $date=>$yes)
		{
			if($yes=='Y')
			{
				if($calendar_RET[$date])
					DBQuery("UPDATE ATTENDANCE_CALENDAR SET MINUTES='999' WHERE SCHOOL_DATE='$date' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'");
				else
					DBQuery("INSERT INTO ATTENDANCE_CALENDAR (SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES) values('".UserSyear()."','".UserSchool()."','".$date."','".$_REQUEST['calendar_id']."','999')");
			}
			else
				DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE='$date' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'");
		}
		$calendar_RET = DBGet(DBQuery("SELECT to_char(SCHOOL_DATE,'dd-MON-YY') AS SCHOOL_DATE,MINUTES,BLOCK FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'"),array(),array('SCHOOL_DATE'));
		unset($_REQUEST['all_day']);
		unset($_SESSION['_REQUEST_vars']['all_day']);
	}
	if($_REQUEST['blocks'])
	{
		foreach($_REQUEST['blocks'] as $date=>$block)
		{
			if($calendar_RET[$date])
			{
				DBQuery("UPDATE ATTENDANCE_CALENDAR SET BLOCK='".$block."' WHERE SCHOOL_DATE='$date' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'");
			}
		}
		$calendar_RET = DBGet(DBQuery("SELECT to_char(SCHOOL_DATE,'dd-MON-YY') AS SCHOOL_DATE,MINUTES,BLOCK FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND CALENDAR_ID='".$_REQUEST['calendar_id']."'"),array(),array('SCHOOL_DATE'));
		unset($_REQUEST['blocks']);
		unset($_SESSION['_REQUEST_vars']['blocks']);
	}

	echo "<FORM action=Modules.php?modname=$_REQUEST[modname] METHOD=POST>";
	if(AllowEdit())
	{
		$title_RET = DBGet(DBQuery("SELECT CALENDAR_ID,TITLE,DEFAULT_CALENDAR FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY DEFAULT_CALENDAR ASC,TITLE"));
		foreach($title_RET as $title)
		{
			$options[$title['CALENDAR_ID']] = $title['TITLE'].($title['DEFAULT_CALENDAR']=='Y'?' (default)':'');
			if($title['DEFAULT_CALENDAR']=='Y')
				$defaults++;
		}
		$link = SelectInput($_REQUEST['calendar_id'],'calendar_id','',$options,false," onchange='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('calendar_id')).'&amp;calendar_id="+this.form.calendar_id.value;\' ',false)."<A HREF=Modules.php?modname=$_REQUEST[modname]&modfunc=create>".button('add')._('Create new calendar')."</A> <A HREF=Modules.php?modname=$_REQUEST[modname]&modfunc=create&calendar_id=$_REQUEST[calendar_id]>"._('Recreate this calendar')."</A> <A HREF=Modules.php?modname=$_REQUEST[modname]&modfunc=delete_calendar&calendar_id=$_REQUEST[calendar_id]>".button('remove')." "._('Delete this calendar')."</A>";
	}
	DrawHeader(PrepareDate(strtoupper(date("d-M-y",$time)),'',false,array('M'=>1,'Y'=>1,'submit'=>true)).' <A HREF=Modules.php?modname='.$_REQUEST['modname'].'&modfunc=list_events&month='.$_REQUEST['month'].'&year='.$_REQUEST['year'].'>'._('List Events').'</A>',SubmitButton(_('Save')));
	DrawHeader($link);
	if(count($error))
		echo ErrorMessage($error,'fatal');
	if(AllowEdit() && $defaults!=1)
		DrawHeader('<IMG src=assets/warning_button.gif><FONT color=red> '.($defaults?_('This school has more than one default calendar!'):_('This school does not have a default calendar!')).'</FONT>');
	echo '<BR>';

	$events_RET = DBGet(DBQuery("SELECT ID,to_char(SCHOOL_DATE,'dd-MON-yy') AS SCHOOL_DATE,TITLE FROM CALENDAR_EVENTS WHERE SCHOOL_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('SCHOOL_DATE'));
	if(User('PROFILE')=='parent' || User('PROFILE')=='student')
		$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID AS ID,to_char(a.DUE_DATE,'dd-MON-yy') AS SCHOOL_DATE,a.TITLE,'Y' AS ASSIGNED FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s WHERE (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID) AND s.STUDENT_ID='".UserStudentID()."' AND (a.DUE_DATE BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL) AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL) AND a.DUE_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."'"),array(),array('SCHOOL_DATE'));
	elseif(User('PROFILE')=='teacher')
		$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID AS ID,to_char(a.DUE_DATE,'dd-MON-yy') AS SCHOOL_DATE,a.TITLE,CASE WHEN a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL THEN 'Y' ELSE NULL END AS ASSIGNED FROM GRADEBOOK_ASSIGNMENTS a WHERE a.STAFF_ID='".User('STAFF_ID')."' AND a.DUE_DATE BETWEEN '".date('d-M-y',$time)."' AND '".date('d-M-y',mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']))."'"),array(),array('SCHOOL_DATE'));

	$skip = date("w",$time);

	echo "<CENTER><TABLE bgcolor=#333366 border=1 bordercolor=black cellpadding=3><TR><TD>";
	echo "<TABLE border=0 bgcolor=#EEEEEE><TR bgcolor=black align=center>";
	echo "<TD><font color=white>"._('Sunday')."</font></TD><TD><font color=white>"._('Monday')."</font></TD><TD><font color=white>"._('Tuesday')."</font></TD><TD><font color=white>"._('Wednesday')."</font></TD><TD><font color=white>"._('Thursday')."</font></TD><TD><font color=white>"._('Friday')."</font></TD><TD width=99><font color=white>"._('Saturday')."</font></TD>";
	echo "</TR><TR>";

	if($skip)
	{
		echo "<td colspan=" . $skip . "></td>";
		$return_counter = $skip;
	}
	for($i=1;$i<=$last;$i++)
	{
		$day_time = mktime(0,0,0,$_REQUEST['month'],$i,$_REQUEST['year']);
		$date = strtoupper(date('d-M-y',$day_time));

		echo "<TD width=100 bgcolor=".($calendar_RET[$date][1]['MINUTES']?$calendar_RET[$date][1]['MINUTES']=='999'?'#EEFFEE':'#EEEEFF':'#FFEEEE')." valign=top><table width=100><tr><td width=5 valign=top>$i</td><td width=95 align=right>";
		if(AllowEdit())
		{
			echo '<TABLE><TR><TD>';
			if($calendar_RET[$date][1]['MINUTES']=='999')
				echo CheckboxInput($calendar_RET[$date],"all_day[$date]",'','',false,'<IMG SRC=assets/check.gif height=10 vspace=0 hspace=0 border=0>');
			elseif($calendar_RET[$date][1]['MINUTES'])
				echo TextInput($calendar_RET[$date][1]['MINUTES'],"minutes[$date]",'','size=3');
			else
			{
				echo "<INPUT type=checkbox name=all_day[$date] value=Y></TD>";
				echo "<TD><INPUT type=text name=minutes[$date] size=3>";
			}
			echo '</TD></TR></TABLE>';
		}
		$blocks_RET = DBGet(DBQuery("SELECT DISTINCT BLOCK FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND BLOCK IS NOT NULL ORDER BY BLOCK"));
		if(count($blocks_RET)>0)
		{
			unset($options);
			foreach($blocks_RET as $block)
				$options[$block['BLOCK']] = $block['BLOCK'];

			echo SelectInput($calendar_RET[$date][1]['BLOCK'],"blocks[$date]",'',$options);
		}
		echo "</td></tr><tr><TD colspan=2 height=50 valign=top>";

		if(count($events_RET[$date]))
		{
			echo '<TABLE cellpadding=0 cellspacing=0 border=0>';
			foreach($events_RET[$date] as $event)
				echo "<TR><TD>".button('dot','#0000FF','',6)."</TD><TD><font size=-3><A HREF=# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&event_id=$event[ID]&year=$_REQUEST[year]&month=".MonthNWSwitch($_REQUEST['month'],'tochar')."\",\"blank\",\"width=500,height=300\"); return false;'>".($event['TITLE']?$event['TITLE']:'***')."</A></font></TD></TR>";
			if(count($assignments_RET[$date]))
			{
				foreach($assignments_RET[$date] as $event)
					echo "<TR><TD>".button('dot',$event['ASSIGNED']=='Y'?'#00FF00':'#FF0000','',6)."</TD><TD><font size=-3><A HREF=# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&assignment_id=$event[ID]&year=$_REQUEST[year]&month=".MonthNWSwitch($_REQUEST['month'],'tochar')."\",\"blank\",\"width=500,height=300\"); return false;'>".$event['TITLE']."</A></font></TD></TR>";
			}
			echo '</TABLE>';
		}
		elseif(count($assignments_RET[$date]))
		{
			echo '<TABLE cellpadding=0 cellspacing=0 border=0>';
			foreach($assignments_RET[$date] as $event)
				echo "<TR><TD>".button('dot',$event['ASSIGNED']=='Y'?'#00FF00':'#FF0000','',6)."</TD><TD><font size=-3><A HREF=# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&assignment_id=$event[ID]&year=$_REQUEST[year]&month=".MonthNWSwitch($_REQUEST['month'],'tochar')."\",\"blank\",\"width=500,height=300\"); return false;'>".$event['TITLE']."</A></font></TD></TR>";
			echo '</TABLE>';
		}

		echo "</td></tr>";
		if(AllowEdit())
			echo "<tr><td valign=bottom align=left>".button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&event_id=new&school_date=$date&year=$_REQUEST[year]&month=".MonthNWSwitch($_REQUEST['month'],'tochar')."\",\"blank\",\"width=500,height=300\"); return false;'")."</td></tr>";
		echo "</table></TD>";
		$return_counter++;

		if($return_counter%7==0)
			echo "</TR><TR>";
	}
	echo "</TR></TABLE>";

	echo "</TD></TR></TABLE>";
	echo '<BR>'.SubmitButton(_('Save'));
	echo "</CENTER>";
	echo '</FORM>';
}
?>
