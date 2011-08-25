<?php

$max_cols = 3;
$max_rows = 10;

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$extra['SELECT'] .= ",coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS NICK_NAME";
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				if($_REQUEST['teacher'])
					$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS TEACHER";
				if($_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS ROOM";
			}
			else
			{
				if($_REQUEST['teacher'])
					$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				if($_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
			}
		}
		else
		{
			if($_REQUEST['teacher'])
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS TEACHER";
			if($_REQUEST['room'])
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS ROOM";
		}
		$RET = GetStuList($extra);

		if(count($RET))
		{
			$skipRET = array();
			for($i=($_REQUEST['start_row']-1)*$max_cols+$_REQUEST['start_col']; $i>1; $i--)
				$skipRET[-$i] = array('LAST_NAME'=>' ');

			$handle = PDFstart();
			echo '<!-- MEDIA SIZE 8.5x11in -->';
			echo '<!-- MEDIA TOP 0.5in -->';
			echo '<!-- MEDIA BOTTOM 0.25in -->';
			echo '<!-- MEDIA LEFT 0.25in -->';
			echo '<!-- MEDIA RIGHT 0.25in -->';
			echo '<!-- FOOTER RIGHT "" -->';
			echo '<!-- FOOTER LEFT "" -->';
			echo '<!-- FOOTER CENTER "" -->';
			echo '<!-- HEADER RIGHT "" -->';
			echo '<!-- HEADER LEFT "" -->';
			echo '<!-- HEADER CENTER "" -->';
			echo '<table width="100%" height="860" border="0" cellspacing="0" cellpadding="0">';

			$cols = 0;
			$rows = 0;
			foreach($skipRET+$RET as $i=>$student)
			{
				if($cols<1)
					echo '<tr>';
				echo '<td width="33.3%" height="86" align="center" valign="middle">';
				if($_REQUEST['full_name']=='common')
					$name = $student['LAST_NAME'].', '.$student['NICK_NAME'];
				elseif($_REQUEST['full_name']=='given')
					$name = $student['LAST_NAME'].', '.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'];
				elseif($_REQUEST['full_name']=='common_natural')
					$name = $student['NICK_NAME'].' '.$student['LAST_NAME'];
				elseif($_REQUEST['full_name']=='given_natural')
					$name = $student['FIRST_NAME'].' '.$student['LAST_NAME'];
				else
					$name = $student['FULL_NAME'];
				echo '<B>'.$name.'</B>';
				if($_REQUEST['teacher'])
					echo '<BR><Small>'.Localize('colon',_('Teacher')).'&nbsp;</SMALL>'.$student['TEACHER'];
				if($_REQUEST['room'])
					echo '<BR><Small>'.Localize('colon',_('Room')).'&nbsp;</SMALL>'.$student['ROOM'];
				echo '</td>';

				$cols++;

				if($cols==$max_cols)
				{
					echo '</tr>';
					$rows++;
					$cols = 0;
				}

				if($rows==$max_rows)
				{
					echo '</table><!--NEW PAGE -->';
					echo '<table width="100%" height="860" border="0" cellspacing="0" cellpadding="0">';
					$rows = 0;
				}
			}

			if ($cols==0 && $rows==0)
			{}
			else
			{
				while ($cols!=0 && $cols<$max_cols)
				{
					echo '<td width="33.3%" height="86" align="center" valign="middle">&nbsp;</td>';
					$cols++;
				}
				if ($cols==$max_cols)
					echo '</tr>';
				echo '</table>';
			}
			//echo '</body></html>';
			echo '</body>';
			PDFstop($handle);
		}
		else
			BackPrompt(_('No Students were found.'));
	}
}

if(!$_REQUEST['modfunc'])
{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_search_all_schools=$_REQUEST[_search_all_schools]".(User('PROFILE')=='admin'?"&w_course_period_id_which=$_REQUEST[w_course_period_id_which]&w_course_period_id=$_REQUEST[w_course_period_id]":'')."&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Labels for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';

		$extra['extra_header_left'] .= '<TR><TD colspan=4><b>'.Localize('colon',_('Include On Labels')).'</b></TD></TR>';
		$extra['extra_header_left'] .= '<TR>';
		if(Preferences('NAME')=='Common')
		{
			$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value="" checked><small>'._('Last, Common').'</small></TD>';
			$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value=given><small>'._('Last, Given Middle').'</small></TD>';
		}
		else
		{
			$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value="" checked><small>'._('Last, Given Middle').'</small></TD>';
			$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value=common><small>'._('Last, Common').'</small></TD>';
		}
		$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value=given_natural><small>'._('Given Last').'</small></TD>';
		$extra['extra_header_left'] .= '<TD><INPUT type=radio name=full_name value=common_natural><small>'._('Common Last').'</small></TD></TR>';
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				$course_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS TEACHER,cp.ROOM FROM STAFF s,COURSE_PERIODS cp WHERE s.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]'"));
				$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=teacher value=Y>'._('Teacher').' ('.$course_RET[1]['TEACHER'].')</TD></TR>';
				$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=room value=Y>'._('Room').' ('.$course_RET[1]['ROOM'].')</TD></TR>';
			}
			else
			{
				$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=teacher value=Y>'._('Attendance Teacher').'</TD></TR>';
				$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=room value=Y>'._('Attendance Room').'</TD></TR>';
			}
		}
		else
		{
			$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=teacher value=Y>'._('Teacher').'</TD></TR>';
			$extra['extra_header_left'] .= '<TR><TD colspan=4><INPUT type=checkbox name=room value=Y>'._('Room').'</TD></TR>';
		}

		$extra['extra_header_left'] .= '</TABLE>';
		$extra['extra_header_right'] = '<TABLE>';

		$extra['extra_header_right'] .= '<TR><TD align=right>'._('Starting row').'</TD><TD><SELECT name=start_row>';
		for($row=1; $row<=$max_rows; $row++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$row.'">'.$row;
		$extra['extra_header_right'] .=  '</SELECT></TD></TR>';
		$extra['extra_header_right'] .= '<TR><TD align=right>'._('Starting column').'</TD><TD><SELECT name=start_col>';
		for($col=1; $col<=$max_cols; $col++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$col.'">'.$col;
		$extra['extra_header_right'] .= '</SELECT></TD></TR>';

		$extra['extra_header_right'] .= '</TABLE>';
	}

	Widgets('course');
	//Widgets('request');
	//Widgets('activity');
	//Widgets('absences');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');
	//Widgets('eligibility');
	//$extra['force_search'] = true;

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Labels for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}
?>