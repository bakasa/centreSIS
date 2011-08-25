<?php

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		if($_REQUEST['mailing_labels']=='Y')
			Widgets('mailing_labels');
		$extra['SELECT'] .= ",coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS NICK_NAME";
		$extra['functions']['SCHOOL_ID'] = 'GetSchool';
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS TEACHER";
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS ROOM";
			}
			else
			{
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
			}
		}
		else
		{
			$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS TEACHER";
			$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS ROOM";
		}

		$RET = GetStuList($extra);

		if(count($RET))
		{
			$_REQUEST['letter_text'] = nl2br(str_replace("\'","'",str_replace('  ',' &nbsp;',$_REQUEST['letter_text'])));

			$handle = PDFStart();
			foreach($RET as $student)
			{
				$student_points = $total_points = 0;
				unset($_CENTRE['DrawHeader']);

				if($_REQUEST['mailing_labels']=='Y')
					echo '<BR><BR><BR>';
				DrawHeader(Config('TITLE').' Letter');
				DrawHeader($student['FULL_NAME'],$student['STUDENT_ID']);
				DrawHeader($student['GRADE_ID'],GetSchool(UserSchool()));
				DrawHeader($course_title,GetMP(GetCurrentMP('QTR',DBDate(),false)));
				DrawHeader(ProperDate(DBDate()));

				if($_REQUEST['mailing_labels']=='Y')
					echo '<BR><BR><TABLE width=100%><TR><TD width=50> &nbsp; </TD><TD>'.$student['MAILING_LABEL'].'</TD></TR></TABLE><BR>';

				$letter_text = $_REQUEST['letter_text'];
				foreach($student as $column=>$value)
					$letter_text = str_replace('__'.$column.'__',$value,$letter_text);

				echo '<TABLE width=100%><TR><TD>'.$letter_text.'</TD></TR></TABLE>';
				echo '<!-- NEW PAGE -->';
			}
			PDFStop($handle);
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
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_search_all_schools=$_REQUEST[_search_all_schools]&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Print Letters for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';

		Widgets('mailing_labels');
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '<TR><TD valign=top align=right>'._('Letter Text').'</TD><TD><TEXTAREA name=letter_text rows=10 cols=70></TEXTAREA></TD></TR>';

		$extra['extra_header_left'] .= '<TR><TD valign=top align=right>'.Localize('colon',_('Substitutions related to students')).'</TD><TD><TABLE><TR>';
		$extra['extra_header_left'] .= '<TD><small>__FULL_NAME__</small></TD><TD><small>= '.(Preferences('NAME')=='Common'?_('Last, Common'):_('Last, First M')).'</small></TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD><small>__NICK_NAME__</small></TD><TD><small>= '._('Common Name').'</small></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><small>__FIRST_NAME__</small></TD><TD><small>= '._('First Name').'</small></TD><TD></TD>';
		$extra['extra_header_left'] .= '<TD><small>__LAST_NAME__</small></TD><TD><small>= '._('Last Name').'</small></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><small>__MIDDLE_NAME__</small></TD><TD><small>= '._('Middle Name').'</small></TD><TD></TD>';
		$extra['extra_header_left'] .= '<TD><small>__STUDENT_ID__</small></TD><TD><small>= '._('Centre ID').'</small></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><small>__SCHOOL_ID__</small></TD><TD><small>= '._('School').'</small></TD><TD></TD>';
		$extra['extra_header_left'] .= '<TD><small>__GRADE_ID__</small></TD><TD><small>= '._('Grade').'</small></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		if(User('PROFILE')=='admin')
		{
			$extra['extra_header_left'] .= '<TD><small>__TEACHER__</small></TD><TD><small>= '._('Attendance Teacher').'</small></TD><TD></TD>';
			$extra['extra_header_left'] .= '<TD><small>__ROOM__</small></TD><TD><small>= '._('Attendance Room').'</small></TD>';
		}
		else
		{
			$extra['extra_header_left'] .= '<TD><small>__TEACHER__</small></TD><TD><small>= '._('Your Name').'</small></TD><TD></TD>';
			$extra['extra_header_left'] .= '<TD><small>__ROOM__</small></TD><TD><small>= '._('Your Room').'</small></TD>';
		}
		$extra['extra_header_left'] .= '</TR></TABLE></TD></TR>';

		$extra['extra_header_left'] .= '</TABLE>';
	}

	//$extra['force_search'] = true;

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Print Letters for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}
?>