<?php
if(!$_REQUEST['month'])
	$_REQUEST['month'] = date("m");
else
	$_REQUEST['month'] = MonthNWSwitch($_REQUEST['month'],'tonum');
if(!$_REQUEST['year'])
	$_REQUEST['year'] = date("Y");
else
	$_REQUEST['year'] = ($_REQUEST['year']<1900?'20'.$_REQUEST['year']:$_REQUEST['year']);

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['period']) && count($_REQUEST['student']) && count($_REQUEST['dates']))
	{
		foreach($_REQUEST['period'] as $period_id=>$yes)
			$periods_list .= ",'".$period_id."'";
		$periods_list = '('.substr($periods_list,1).')';

		foreach($_REQUEST['student'] as $student_id=>$yes)
			$students_list .= ",'".$student_id."'";
		$students_list = '('.substr($students_list,1).')';

		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID,PERIOD_ID,SCHOOL_DATE FROM ATTENDANCE_PERIOD WHERE EXTRACT(MONTH FROM SCHOOL_DATE)='".($_REQUEST['month']*1)."' AND EXTRACT(YEAR FROM SCHOOL_DATE)='$_REQUEST[year]' AND PERIOD_ID IN $periods_list AND STUDENT_ID IN $students_list"),array(),array('STUDENT_ID','SCHOOL_DATE','PERIOD_ID'));
		$state_code = DBGet(DBQuery("SELECT STATE_CODE FROM ATTENDANCE_CODES WHERE ID='$_REQUEST[absence_code]'"));
		$state_code = $state_code[1]['STATE_CODE'];
		foreach($_REQUEST['student'] as $student_id=>$yes)
		{
			foreach($_REQUEST['dates'] as $date=>$yes)
			{
				$current_mp = GetCurrentMP('QTR',$date);
				$all_mp = GetAllMP('QTR',$current_mp);
				$course_periods_RET = DBGet(DBQuery("SELECT s.COURSE_PERIOD_ID,cp.PERIOD_ID,cp.HALF_DAY FROM SCHEDULE s,COURSE_PERIODS cp,ATTENDANCE_CALENDAR ac,SCHOOL_PERIODS sp WHERE sp.PERIOD_ID=cp.PERIOD_ID AND ac.SCHOOL_DATE='$date' AND ac.CALENDAR_ID=cp.CALENDAR_ID AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL) AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID='$student_id' AND cp.PERIOD_ID IN $periods_list AND position(',0,' IN cp.DOES_ATTENDANCE)>0 AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE)) AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM ac.SCHOOL_DATE) AS INT)+1 FOR 1) IN cp.DAYS)>0 AND s.MARKING_PERIOD_ID IN ($all_mp)"),array(),array('PERIOD_ID'));
				//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';
				foreach($_REQUEST['period'] as $period_id=>$yes)
				{
					$course_period_id = $course_periods_RET[$period_id][1]['COURSE_PERIOD_ID'];
					if($course_period_id && !($course_periods_RET[$period_id][1]['COURSE_PERIOD_ID']=='Y' && $state_code=='H'))
					{
						if(!$current_RET[$student_id][$date][$period_id])
						{
							$sql = "INSERT INTO ATTENDANCE_PERIOD (STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_REASON,ADMIN)
										values('$student_id','$date','$period_id','$current_mp','$course_period_id','$_REQUEST[absence_code]','$_REQUEST[absence_reason]','Y')";
							DBQuery($sql);
						}
						else
						{
							$sql = "UPDATE ATTENDANCE_PERIOD SET ATTENDANCE_CODE='$_REQUEST[absence_code]',ATTENDANCE_REASON='$_REQUEST[absence_reason]',ADMIN='Y',COURSE_PERIOD_ID='$course_period_id'
								WHERE STUDENT_ID='$student_id' AND SCHOOL_DATE='$date' AND PERIOD_ID='$period_id'";
							DBQuery($sql);
						}
					}
				}
				UpdateAttendanceDaily($student_id,$date,($_REQUEST['absence_reason']?$_REQUEST['absence_reason']:false));
			}
		}
		unset($_REQUEST['modfunc']);
		$note = _('Absence records were added for the selected students.');
	}
	else
		BackPrompt(_('You must choose at least one period and one student.'));
}

DrawHeader(ProgramTitle());

if(!$_REQUEST['modfunc'])
{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save METHOD=POST>";
		DrawHeader('',SubmitButton(_('Add Absences to Selected Students')));

		echo '<BR>';

		echo '<CENTER><TABLE bgcolor='.Preferences('COLOR').'><TR><TD align=right>'._('Add Absence to Periods').'</TD>';
		echo '<TD><TABLE><TR>';
		//$periods_RET = DBGet(DBQuery("SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		$periods_RET = DBGet(DBQuery("SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position(',0,' IN DOES_ATTENDANCE)>0) ORDER BY SORT_ORDER"));
		foreach($periods_RET as $period)
			echo '<TD><INPUT type=CHECKBOX value=Y name=period['.$period['PERIOD_ID'].']>'.$period['SHORT_NAME'].'</TD>';
		echo '</TR></TABLE></TD>';
		echo '<TR><TD align=right>'._('Absence Code').'</TD><TD><SELECT name=absence_code>';
		$codes_RET = DBGet(DBQuery("SELECT TITLE,ID FROM ATTENDANCE_CODES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TABLE_NAME='0'"));
		foreach($codes_RET as $code)
			echo '<OPTION value='.$code['ID'].'>'.$code['TITLE'].'</OPTION>';
		echo '</SELECT></TD></TR>';
		echo '<TR><TD align=right>'._('Absence Reason').'</TD><TD><INPUT type=text name=absence_reason></TD></TR>';
		//echo '<TR><TD colspan=2>Date<BR> &nbsp; &nbsp; <small>Between</small> '.PrepareDate(DBDate(),'_begin',true,array('hidden'=>true)).' <small>&</small> '.PrepareDate(DBDate(),'_end',true,array('hidden'=>true)).'</TD></TR>';
		echo '<TR><TD colspan=2 align=center>';
		$time = mktime(0,0,0,$_REQUEST['month']*1,1,substr($_REQUEST['year'],2));
		echo PrepareDate(strtoupper(date("d-M-y",$time)),'',false,array('M'=>1,'Y'=>1,'submit'=>true));

		$skip = date("w",$time);
		$last = 31;
		while(!checkdate($_REQUEST['month']*1, $last, substr($_REQUEST['year'],2)))
			$last--;

		echo '<TABLE><TR>';
		echo '<TH>'._('Su').'</TH><TH>'._('M').'</TH><TH>'._('T').'</TH><TH>'._('W').'</TH><TH>'._('Th').'</TH><TH>'._('F').'</TH><TH>'._('Sa').'</TH></TR><TR>';
		$calendar_RET = DBGet(DBQuery("SELECT SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND MINUTES!='0' AND EXTRACT(MONTH FROM SCHOOL_DATE)='".($_REQUEST['month']*1)."'"),array(),array('SCHOOL_DATE'));
		for($i=1;$i<=$skip;$i++)
			echo '<TD></TD>';

		for($i=1;$i<=$last;$i++)
		{
			$this_date = $_REQUEST['year'].'-'.$_REQUEST['month'].'-'.($i<10?'0'.$i:$i);
			if(!$calendar_RET[$this_date])
				$disabled = ' DISABLED';
			elseif(date('Y-m-d')==$this_date)
				$disabled = ' CHECKED';
			else
				$disabled = '';

			echo '<TD align=right>'.$i.'<INPUT type=checkbox name=dates['.$this_date.'] value=Y'.$disabled.'></TD>';
			$skip++;
			if($skip%7==0 && $i!=$last)
				echo '</TR><TR>';
		}
		echo '</TR></TABLE>';
		echo '</TD></TR></TABLE>';
	}
	elseif($note)
		DrawHeader('<IMG SRC=assets/check.gif>'.$note);

	Widgets('course');
	Widgets('absences');

	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
		echo '<BR><CENTER>'.SubmitButton(_('Add Absences to Selected Students'))."</CENTER></FORM>";
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return "<INPUT type=checkbox name=student[".$THIS_RET['STUDENT_ID']."] value=Y>";
}
?>
