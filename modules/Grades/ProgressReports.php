<?php
include 'ProgramFunctions/_makeLetterGrade.fnc.php';

$course_period_id = UserCoursePeriod();
$course_id = DBGet(DBQuery("SELECT cp.COURSE_ID,c.TITLE FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='$course_period_id'"));
$course_title = $course_id[1]['TITLE'];
$course_id = $course_id[1]['COURSE_ID'];

if($_REQUEST['modfunc']=='save')
{
	$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
	if(count($config_RET))
		foreach($config_RET as $title=>$value)
			$programconfig[User('STAFF_ID')][$title] = $value[1]['VALUE'];
	else
		$programconfig[User('STAFF_ID')] = true;

	if(count($_REQUEST['st_arr']))
	{
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";
	Widgets('mailing_labels');

	$RET = GetStuList($extra);

	if(count($RET))
	{
		$LO_columns = array('TITLE'=>_('Assignment'));
		if($_REQUEST['assigned_date']=='Y')
			$LO_columns += array('ASSIGNED_DATE'=>_('Assigned Date'));
		if($_REQUEST['due_date']=='Y')
			$LO_columns += array('DUE_DATE'=>_('Due Date'));
		$LO_columns += array('POINTS'=>_('Points'),'PERCENT_GRADE'=>_('Percent'),'LETTER_GRADE'=>_('Letter'),'COMMENT'=>_('Comment'));

		$extra2['SELECT_ONLY'] = "ga.TITLE,ga.ASSIGNED_DATE,ga.DUE_DATE,gt.ASSIGNMENT_TYPE_ID,gg.POINTS,ga.POINTS AS TOTAL_POINTS,gt.FINAL_GRADE_PERCENT,gg.COMMENT,gg.POINTS AS PERCENT_GRADE,gg.POINTS AS LETTER_GRADE,CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) THEN 'Y' ELSE NULL END AS DUE,gt.TITLE AS CATEGORY_TITLE";
		$extra2['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='".UserMP()."') LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";
		$extra2['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
		$extra2['WHERE'] .=" AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";
		if($_REQUEST['exclude_notdue']=='Y')
			$extra2['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
		if($_REQUEST['exclude_ec']=='Y')
			$extra2['WHERE'] .= " AND (ga.POINTS!='0' OR gg.POINTS IS NOT NULL AND gg.POINTS!='-1')";
		$extra2['ORDER_BY'] = "ga.ASSIGNMENT_ID";
		if($_REQUEST['by_category']=='Y')
		{
			$extra2['group'] = $LO_group = array('ASSIGNMENT_TYPE_ID');
			$singular = _('Assignment Type');
			$plural = _('Assignment Types');
		}
		else
		{
			$LO_group = array();
			$singular = _('Assignment');
			$plural = _('Assignments');
		}
		$extra2['functions'] = array('ASSIGNED_DATE'=>'_removeSpaces','DUE_DATE'=>'_removeSpaces','TITLE'=>'_removeSpaces','POINTS'=>'_makeExtra','PERCENT_GRADE'=>'_makeExtra','LETTER_GRADE'=>'_makeExtra');

		$handle = PDFStart();
		foreach($RET as $student)
		{
			unset($_CENTRE['DrawHeader']);

			if($_REQUEST['mailing_labels']=='Y')
				echo '<BR><BR><BR>';
			DrawHeader(Config('TITLE').' '._('Progress Report'));
			DrawHeader('</font>'.$student['FULL_NAME'].'<font>',$student['STUDENT_ID']);
			DrawHeader($student['GRADE_ID'],GetSchool(UserSchool()));
			DrawHeader($course_title,GetMP(GetCurrentMP('QTR',DBDate())));
			DrawHeader(ProperDate(DBDate()));

			if($_REQUEST['mailing_labels']=='Y')
				echo '<BR><BR><TABLE width=100%><TR><TD width=50> &nbsp; </TD><TD>'.$student['MAILING_LABEL'].'</TD></TR></TABLE><BR>';

			$extra = $extra2;
			$extra['WHERE'] .= " AND s.STUDENT_ID='$student[STUDENT_ID]'";
			$student_points = $total_points = $percent_weights = array();
			$grades_RET = GetStuList($extra);

			$sum_student_points = $sum_total_points = 0;
			$sum_points = $sum_percent = 0;
			foreach($percent_weights as $assignment_type_id=>$percent)
			{
				$sum_student_points += $student_points[$assignment_type_id];
				$sum_total_points += $total_points[$assignment_type_id];
				$sum_points += $student_points[$assignment_type_id]*($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$percent/$total_points[$assignment_type_id]:1);
				$sum_percent += ($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$percent:$total_points[$assignment_type_id]);
			}
			if($sum_percent>0)
				$sum_points /= $sum_percent;
			else
				$sum_points = 0;
			if($_REQUEST['by_category']=='Y')
			{
				foreach($grades_RET as $assignment_type_id=>$grades)
				{
					$grades_RET[$assignment_type_id][] = array('TITLE'=>_removeSpaces('<B>'.$grades[1]['CATEGORY_TITLE'].' '._('Total').'</B>'.($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'&&$sum_percent>0?' <SMALL>('.sprintf(_('%s of grade'),Percent($percent_weights[$assignment_type_id]/$sum_percent)).')</SMALL>':'')),
						'ASSIGNED_DATE'=>'&nbsp;','DUE_DATE'=>'&nbsp;',
						'POINTS'=>'<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1><b>'.$student_points[$assignment_type_id].'</b></font></TD><TD><font size=-1>&nbsp;<b>/</b>&nbsp;</font></TD><TD><font size=-1><b>'.$total_points[$assignment_type_id].'</b></font></TD></TR></TABLE>',
						'PERCENT_GRADE'=>$total_points[$assignment_type_id]?'<B>'.Percent($student_points[$assignment_type_id]/$total_points[$assignment_type_id]).'</B>':'&nbsp;');
				}
			}
			$link['add']['html'] = array('TITLE'=>'<B>Total</B>',
						'POINTS'=>'<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1><b>'.$sum_student_points.'</b></font></TD><TD><font size=-1>&nbsp;<b>/</b>&nbsp;</font></TD><TD><font size=-1><b>'.$sum_total_points.'</b></font></TD></TR></TABLE>',
						'PERCENT_GRADE'=>'<B>'.Percent($sum_points).'</B>','LETTER_GRADE'=>'<B>'._makeLetterGrade($sum_points).'</B>');
			$link['add']['html']['ASSIGNED_DATE'] = $link['add']['html']['DUE_DATE'] = $link['add']['html']['COMMENT'] = ' &nbsp; ';

			ListOutput($grades_RET,$LO_columns,$singular,$plural,$link,$LO_group,array('center'=>false,'add'=>true));

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
	DrawHeader('Gradebook - '.ProgramTitle());

	if($_REQUEST['search_modfunc']=='list') // || UserStudentID())
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Progress Reports for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR><TD align=right width=120> '._('Assigned Date').'</TD><TD><INPUT type=checkbox value=Y name=assigned_date></TD>';
		$extra['extra_header_left'] .= '<TD align=right> '._('Exclude Ungraded E/C Assignments').'</TD><TD><INPUT type=checkbox value=Y name=exclude_ec checked></TD><TR>';

		$extra['extra_header_left'] .= '<TR><TD align=right width=120> Due Date</TD><TD><INPUT type=checkbox value=Y name=due_date checked></TD>';
		$extra['extra_header_left'] .= '<TD align=right> '._('Exclude Ungraded Assignments Not Due').'</TD><TD><INPUT type=checkbox value=Y name=exclude_notdue></TD></TR>';
		Widgets('mailing_labels');
		$extra['extra_header_left'] .= substr($extra['search'],0,-5);
		$extra['search'] = '';
		$extra['extra_header_left'] .= '<TD align=right> '._('Group by Assignment Category').'</TD><TD><INPUT type=checkbox value=Y name=by_category></TD></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
		//$extra['old'] = true; // proceed to 'list' if UserStudentID()
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	//$extra['force_search'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Progress Reports for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeExtra($value,$column)
{	global $THIS_RET,$student_points,$total_points,$percent_weights;

	if($column=='POINTS')
	{
		if($THIS_RET['TOTAL_POINTS']!='0')
			if($value!='-1')
			{
				if($THIS_RET['DUE'] || $value!='')
				{
					$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
					$total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['TOTAL_POINTS'];
					$percent_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = $THIS_RET['FINAL_GRADE_PERCENT'];
				}
				return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.(rtrim(rtrim($value,'0'),'.')+0).'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
			}
			else
				return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'._('Excluded').'</font></TD><TD></TD><TD></TD></TR></TABLE>';
		else
		{
			$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
			return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.(rtrim(rtrim($value,'0'),'.')+0).'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
		}
	}
	elseif($column=='PERCENT_GRADE')
	{
		if($THIS_RET['TOTAL_POINTS']!='0')
			if($value!='-1')
				if($THIS_RET['DUE'] || $value!='')
					return Percent($value/$THIS_RET['TOTAL_POINTS'],1);
				else
					return 'not due';
			else
				return 'n/a';
		else
			return 'e/c';
	}
	elseif($column=='LETTER_GRADE')
	{
		if($THIS_RET['TOTAL_POINTS']!='0')
			if($value!='-1')
				if($THIS_RET['DUE'] || $value!='')
					return _makeLetterGrade($value/$THIS_RET['TOTAL_POINTS']);
				else
					return 'not due';
			else
				return 'n/a';
		else
			return 'e/c';
	}
}

function _removeSpaces($value,$column)
{
	if($column=='ASSIGNED_DATE' || $column=='DUE_DATE')
		$value = '<small>'.ProperDate($value).'</small>';

	return str_replace(' ','&nbsp;',str_replace('&','&amp;',$value));
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}
?>
