<?php

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['mp_arr']) && count($_REQUEST['st_arr']))
	{
	$mp_list = '\''.implode('\',\'',$_REQUEST['mp_type_arr']).'\'';

	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

	$extra['SELECT'] = ",sg1.SYEAR,rpg.TITLE as GRADE_TITLE,rpg.GPA_VALUE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,cp.CREDITS,cp.MP,sgc.WEIGHTED_GPA,sgc.CLASS_RANK,ssm.GRADE_ID as STU_GRADE,
				cp.DOES_ATTENDANCE,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES";

	$extra['FROM'] = ",STUDENT_REPORT_CARD_GRADES sg1 LEFT OUTER JOIN REPORT_CARD_GRADES rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID),
					COURSE_PERIODS cp,COURSES c,STUDENT_GPA_CALCULATED sgc,SCHOOL_PERIODS sp";
	$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
                    AND ssm.STUDENT_ID=sgc.STUDENT_ID AND sg1.MARKING_PERIOD_ID=cast(sgc.MARKING_PERIOD_ID AS TEXT) AND cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND c.COURSE_ID = cp.COURSE_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=cp.PERIOD_ID";
	$extra['ORDER'] = ",sp.SORT_ORDER,c.TITLE";

	$extra['group']	= array('STUDENT_ID','SYEAR','COURSE_PERIOD_ID','MARKING_PERIOD_ID');

	$stu_count_RET = DBGet(DBQuery("SELECT COUNT(*) AS COUNT,GRADE_ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND ('".DBDate()."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL) GROUP BY GRADE_ID"),array(),array('GRADE_ID'));

	$RET = GetStuList($extra);

	if(count($RET))
	{
		$handle = PDFStart();

		$columns = array('COURSE_TITLE'=>_('Course'));
		if($extra['columns_after'])
			$columns += $extra['columns_after'];
		foreach($_REQUEST['mp_arr'] as $mp)
			$columns[$mp] = GetMP($mp);
		$columns['ABSENCES'] = _('Abs');
		$columns['CREDITS'] = _('Credit');

		foreach($RET as $student_id=>$syears)
		{
			DrawHeader(Config('TITLE').' - '._('Transcript'));
			foreach($syears as $syear=>$course_periods)
			{
				foreach($course_periods as $course_period_id=>$mps)
				{
					foreach($mps as $mp=>$values)
					{
						$student = $values[1];
						break 3;
					}
				}
			}

			DrawHeader($student['FULL_NAME'],$student['STUDENT_ID']);
			DrawHeader(GetSchool(UserSchool()));

			unset($_CENTRE['DrawHeader']);
			unset($grades_RET);
			unset($total_credits);
			foreach($syears as $syear=>$course_periods)
			{
				$i = 0;
				foreach($course_periods as $course_period_id=>$mps)
				{
					$i++;
					$grades_RET[$i]['COURSE_TITLE'] = $mps[key($mps)][1]['COURSE_TITLE'];
					foreach($_REQUEST['mp_arr'] as $mp)
					{
						if($mps[$mp])
						{
							if(GetMP($mps[$mp][1]['MARKING_PERIOD_ID'],'TABLE')=='SCHOOL_SEMESTERS' && $mps[$mp][1]['GPA_VALUE'] && $mps[$mp][1]['GPA_VALUE']!='0' && $mps[$mp][1]['MP']=='FY')
								$grades_RET[$i]['CREDITS'] += ($mps[$mp][1]['CREDITS'] / 2);
							elseif(GetMP($mps[$mp][1]['MARKING_PERIOD_ID'],'TABLE')=='SCHOOL_SEMESTERS' && $mps[$mp][1]['GPA_VALUE'] && $mps[$mp][1]['GPA_VALUE']!='0')
								$grades_RET[$i]['CREDITS'] += ($mps[$mp][1]['CREDITS']);

							$total_credits[$mp]+=$grades_RET[$i]['CREDITS'];
							$stu_credits[$student_id]+=$grades_RET[$i]['CREDITS'];

							$grades_RET[$i][$mp] = $mps[$mp][1]['GRADE_TITLE'];
							$last_mp = $mp;
						}
					}
					if(!$grades_RET[$i]['CREDITS'])
						$grades_RET[$i]['CREDITS'] = 0;
					if($mps[$last_mp][1]['DOES_ATTENDANCE'])
						$grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'];
					else
						$grades_RET[$i]['ABSENCES'] = 'n/a';
				}
				$columns['COURSE_TITLE'] = $syear.' - '.($syear+1);
				ListOutput($grades_RET,$columns,'.','.',array(),array(),array('print'=>false,'center'=>false));
				echo '<TABLE>';
				foreach($_REQUEST['mp_arr'] as $mp)
				{
					if(GetMP($mp,'TABLE')=='SCHOOL_SEMESTERS')
						echo '<TR><TD><B>'.GetMP($mp).'</b></TD><TD>'.Localize('colon',_('Credits MP/Total')).' <B>'.($total_credits[$mp]*1).'</B> / <B>'.($stu_credits[$student_id]*1).'</B></TD><TD>'.Localize('colon',_('GPA Wtd')).' <B>'.$student['WEIGHTED_GPA'].'</B></TD><TD>'.Localize('colon',_('Rank')).' <B>'.$student['CLASS_RANK'].'</B> / <B>'.$stu_count_RET[$student['STU_GRADE']][1]['COUNT'].'</B></TD></TR>';
				}
				echo '</TABLE>';
			}
			echo '<!-- NEW PAGE -->';
		}
		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student and marking period'));
}

if(!$_REQUEST['modfunc'])
{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=modules/Grades/gentranscript.php method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Transcripts for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';

		$extra['extra_header_left'] .= '<TR><TD colspan=2><b>'.Localize('colon',_('Include on Transcript')).'</b><INPUT type=hidden name=SCHOOL_ID value='.UserSchool().'><BR></TD></TR>';
        $mp_types = DBGet(DBQuery("SELECT DISTINCT MP_TYPE FROM MARKING_PERIODS WHERE NOT MP_TYPE IS NULL AND SCHOOL_ID = ".UserSchool()),array(),array());
        $extra['extra_header_left'] .= '<TR><TD align=right>'._('Marking Periods').'</TD><TD><TABLE><TR><TD><TABLE>';
		foreach($mp_types as $mp_type)
		{
			$extra['extra_header_left'] .= '<TR>';
			$extra['extra_header_left'] .= '<TD><INPUT type=checkbox name=mp_type_arr[] value='.$mp_type['MP_TYPE'].'>'.ucwords($mp_type['MP_TYPE']).'</TD>';              
            $extra['extra_header_left'] .= '</TR>';
		}
		$extra['extra_header_left'] .= '</TABLE></TD>';
        $extra['extra_header_left'] .= '<INPUT type=hidden name=syear value='.UserSyear().'>';
        $extra['extra_header_left'] .= '<TD align=right width=20>&nbsp;</TD>';
        $extra['extra_header_left'] .= '<TD align=right>&nbsp;&nbsp;&nbsp;&nbsp;'._('Other Options').'</TD>';
        $extra['extra_header_left'] .= '<TD><TABLE>';
        $extra['extra_header_left'] .= '<TR><TD><INPUT type=checkbox name=showstudentpic value=1>'._('Student Photo').'</TD></TR>';
        //$extra['extra_header_left'] .= '<TR><TD><INPUT type=checkbox name=showsat value=1>SAT Scores</TD></TR>';
        $extra['extra_header_left'] .= '</TABLE></TD>';

		$extra['extra_header_left'] .= '</TD></TR></TABLE></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['new'] = true;
	$extra['options']['search'] = false;
	$extra['force_search'] = true;

	Widgets('course');
	Widgets('gpa');
	Widgets('class_rank');
	Widgets('letter_grade');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Transcripts for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}
?>
