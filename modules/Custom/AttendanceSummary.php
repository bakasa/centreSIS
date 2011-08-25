<?php
include 'modules/Grades/config.inc.php';

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

	Preferences(); // cache the user's preferences then force the following
	$_CENTRE['Preferences']['Preferences']['NAME'][1]['VALUE'] = '';

	$months = array(1=>_('Jan'),_('Feb'),_('Mar'),_('Apr'),_('May'),_('Jun'),_('Jul'),_('Aug'),_('Sep'),_('Oct'),_('Nov'),_('Dec'));
	$custom_RET = DBGet(DBQuery("SELECT TITLE,ID FROM CUSTOM_FIELDS WHERE ID IN('200000000','200000003')"),array(),array('ID'));

	$extra['SELECT'] = ",ssm.CALENDAR_ID,ssm.START_DATE,ssm.END_DATE";
	foreach($custom_RET as $id=>$custom)
		$extra['SELECT'] .= ",CUSTOM_".$id;
	// ACTIVE logic taken from GetStuList()
	$extra['SELECT'] .= ','.db_case(array("(ssm.SYEAR='".UserSyear()."' AND ('".DBDate()."'>=ssm.START_DATE AND ('".DBDate()."'<=ssm.END_DATE OR ssm.END_DATE IS NULL)))",'TRUE',"'Active'","'Inactive'")).' AS STATUS';
	$RET = GetStuList($extra);

	if(count($RET))
	{
		$school_RET = DBGet(DBQuery("SELECT SCHOOL_NUMBER FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		// override default options
		$options="--webpage --quiet -t pdf12 --jpeg --no-links --portrait --footer t --header . --left 0.25in --right 0.25in --top 0.5in --size 8.5x11in --bodyfont Courier --fontsize 8";
		$handle = PDFStart($options);
		foreach($RET as $student)
		{
			$calendar_RET = DBGet(DBquery("SELECT ".db_case(array("MINUTES>=300",'true',"'1.0'","'0.5'"))."AS POS,trim(leading '0' from to_char(SCHOOL_DATE,'MM')) AS MON,trim(leading '0' from to_char(SCHOOL_DATE,'DD')) AS DAY FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='".$student['CALENDAR_ID']."' AND SCHOOL_DATE>='".$student['START_DATE']."'".($student['END_DATE']?" AND SCHOOL_DATE<='".$student['END_DATE']."'":'')),array(),array('MON','DAY'));
			$attendance_RET = DBGet(DBQuery("SELECT trim(leading '0' from to_char(ap.SCHOOL_DATE,'MM')) AS MON,trim(leading '0' from to_char(ap.SCHOOL_DATE,'DD')) AS DAY,ac.STATE_CODE,ac.SHORT_NAME FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac,SCHOOL_PERIODS sp WHERE ap.STUDENT_ID='".$student['STUDENT_ID']."' AND ap.PERIOD_ID=sp.PERIOD_ID AND sp.SCHOOL_ID='".UserSchool()."' AND sp.SYEAR='".UserSyear()."' AND ac.ID=ap.ATTENDANCE_CODE AND sp.ATTENDANCE='Y'"),array(),array('MON','DAY'));
			//echo '<pre>'; var_dump($calendar_RET); echo '</pre>';

			echo '<TABLE width=100% border=1>';
			echo '<TR><TD width=100% align=center>';

			echo '<TABLE width=96% border=0>';
			echo '<TR><TD width=100% align=center>';
			echo '<FONT size=+2><B>'.$student['FULL_NAME'].'</B></FONT>';
			echo '</TD><TR>';
			echo '</TABLE>';

			echo '<TABLE width=96% border=1>';

			echo '<TR align=center><TD><B>'._('Student Name').'</B></TD><TD><B>ID#</B></TD><TD><B>'._('School / Year').'</B></TD></TR>';
			echo '<TR><TD>'.strtoupper($student['FULL_NAME']).'</TD><TD align=center>'.$student['STUDENT_ID'].'</TD><TD align=center>'.$school_RET[1]['SCHOOL_NUMBER'].' / '.UserSyear().'-'.(UserSyear()+1).'</TD></TR>';


			echo '<TR><TD colspan=3><FONT size=+1><B>'._('Demographics').'</B></FONT><CENTER><TABLE width=98% cellpadding=0 cellspacing=0 border=0>';
			echo '<TR><TD align=right>'.$custom_RET[200000000][1]['TITLE'].':&nbsp;</TD><TD>'.$student['CUSTOM_200000000'].'</TD><TD align=right>Status:&nbsp;</TD><TD>'.$student['STATUS'].'</TD></TR>';
			echo '<TR><TD align=right>'.$custom_RET[200000003][1]['TITLE'].':&nbsp;</TD><TD>'.$student['CUSTOM_200000003'].'</TD><TD align=right>Grade:&nbsp;</TD><TD>'.$student['GRADE_ID'].'</TD></TR>';
			echo '</TABLE></CENTER></TD></TR>';


			echo '<TR><TD colspan=3><FONT size=+1><B>'._('Attendance').'</B></FONT><CENTER><TABLE width=98% cellpadding=0 cellspacing=0 border=1>';

			echo '<TR align=center><TD colspan=32></TD><TD colspan=3><B>MTD</B></TD></TR>';
            /* TRANSLATORS: Abreviation for month */
			echo '<TR align=center><TD><B>'._('Mon').'</B></TD>';
			for($day=1; $day<=31; $day++)
				echo '<TD><B>'.($day<10?'&nbsp;':'').$day.'</B></TD>';
            /* TRANSLATORS: Abreviations for Absences, Tardy and Position */
			echo '<TD><B>'._('Abs').'</B><TD><B>'._('Tdy').'</B><TD><B>'._('Pos')'</B></TD></TR>';
			$abs_tot = $tdy_tot = $pos_tot = 0;
			foreach(array(7,8,9,10,11,12,1,2,3,4,5,6) as $month)
			if($month!=7 && $month!=6 || $calendar_RET[$month] || $attendance_RET[$month])
			{
				echo '<TR><TD>'.$months[$month].'</TD>';
				$abs = $tdy = $pos = 0;
				for($day=1; $day<=31; $day++)
				{
					if($calendar_RET[$month][$day])
					{
						$calendar = $calendar_RET[$month][$day][1];
						if($attendance_RET[$month][$day])
						{
							$attendance = $attendance_RET[$month][$day][1];
							echo '<TD align=center>'.$attendance['STATE_CODE'].'</TD>';
							$abs += ($attendance['STATE_CODE']=='A'?$calendar['POS']:($attendance['STATE_CODE']=='H'?$calendar['POS']/2:0));
							$tdy += ($attendance['STATE_CODE']=='T'||$attendance['SHORT_NAME']=='TD'?1:0);
						}
						else
							echo '<TD align=center bgcolor=#e8e800>&nbsp;</TD>';
						$pos += $calendar['POS'];
					}
					else
					{
						if($attendance_RET[$month][$day])
						{
							$attendance = $attendance_RET[$month][$day][1];
							echo '<TD align=center bgcolor=#e80000>'.$attendance['STATE_CODE'].'</TD>';
						}
						else
							echo '<TD align=center bgcolor=#e8e8e9>&nbsp;</TD>';
					}
				}
				echo '<TD align=right>'.number_format($abs,1).'<TD align=right>'.number_format($tdy,0).'<TD align=right>'.number_format($pos,1).'</TD></TR>';
				$abs_tot += $abs;
				$tdy_tot += $tdy;
				$pos_tot += $pos;
			}
			echo '<TR><TD colspan=28></TD><TD colspan=4 align=right><B>'.Localize('colon',_('YTD Totals')).'</B></TD>';
			echo '<TD align=right>'.number_format($abs_tot,1).'<TD align=right>'.number_format($tdy_tot,0).'<TD align=right>'.number_format($pos_tot,1).'</TD></TR>';

			echo '</TABLE></CENTER></TD></TR>';


			echo '</TABLE>';

			echo '</TD><TR>';
			echo '</TABLE>';
			echo '<!-- NEW PAGE -->';
		}
		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if(!$_REQUEST['modfunc'])
{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Attendance Report for Selected Students').'">';

	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	//$extra['force_search'] = true;

	Widgets('course');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Attendance Report for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}

function _makeTeacher($teacher,$column)
{
	return substr($teacher,strrpos(str_replace(' - ',' ^ ',$teacher),'^')+2);
}
?>
