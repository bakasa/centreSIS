<?php

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

	if($_REQUEST['day_include_active_date'] && $_REQUEST['month_include_active_date'] && $_REQUEST['year_include_active_date'])
	{
		$date = $_REQUEST['day_include_active_date'].'-'.$_REQUEST['month_include_active_date'].'-'.$_REQUEST['year_include_active_date'];
		$date_extra = 'OR (\''.$date.'\' >= sr.START_DATE AND sr.END_DATE IS NULL)';
	}
	else
	{
		$date = DBDate();
		$date_extra = 'OR sr.END_DATE IS NULL';
	}
	$columns = array('PERIOD_TITLE'=>_('Period').' - '._('Teacher'),'MARKING_PERIOD_ID'=>_('Term'),'DAYS'=>_('Days'),'ROOM'=>_('Room'),'COURSE_TITLE'=>_('Course'));

	$extra['SELECT'] .= ',c.TITLE AS COURSE_TITLE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID,p_cp.DAYS,p_cp.ROOM';
	$extra['FROM'] .= ' LEFT OUTER JOIN SCHEDULE sr ON (sr.STUDENT_ID=ssm.STUDENT_ID),COURSES c,COURSE_PERIODS p_cp,SCHOOL_PERIODS sp ';
	$extra['WHERE'] .= " AND p_cp.PERIOD_ID=sp.PERIOD_ID AND ssm.SYEAR=sr.SYEAR AND sr.COURSE_ID=c.COURSE_ID AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID  AND ('$date' BETWEEN sr.START_DATE AND sr.END_DATE $date_extra)";
	if($_REQUEST['mp_id'])
		$extra['WHERE'] .= ' AND sr.MARKING_PERIOD_ID IN ('.GetAllMP(GetMP($_REQUEST['mp_id'],'MP'),$_REQUEST['mp_id']).')';

	$extra['functions'] = array('MARKING_PERIOD_ID'=>'GetMP','DAYS'=>'_makeDays');
	$extra['group'] = array('STUDENT_ID');
	$extra['ORDER'] = ',sp.SORT_ORDER';
	if($_REQUEST['mailing_labels']=='Y')
		$extra['group'][] = 'ADDRESS_ID';
	Widgets('mailing_labels');

	$RET = GetStuList($extra);

	if(count($RET))
	{
		$handle = PDFStart();
		foreach($RET as $student_id=>$courses)
		{
			if($_REQUEST['mailing_labels']=='Y')
			{
				foreach($courses as $address)
				{
					echo '<BR><BR><BR>';
					unset($_CENTRE['DrawHeader']);
					DrawHeader(Config('TITLE').' Student Schedule');
					DrawHeader($address[1]['FULL_NAME'],$address[1]['STUDENT_ID']);
					DrawHeader($address[1]['GRADE_ID']);
					DrawHeader(GetSchool(UserSchool()));
					DrawHeader(ProperDate($date),$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');

					echo '<BR><BR><TABLE width=100%><TR><TD width=50> &nbsp; </TD><TD>'.$address[1]['MAILING_LABEL'].'</TD></TR></TABLE><BR>';

					ListOutput($address,$columns,_('Course'),_('Courses'),array(),array(),array('center'=>false,'print'=>false));
					echo '<!-- NEW PAGE -->';
				}
			}
			else
			{
				unset($_CENTRE['DrawHeader']);
				DrawHeader(Config('TITLE').' - '._('Student Schedule'));
				DrawHeader($courses[1]['FULL_NAME'],$courses[1]['STUDENT_ID']);
				DrawHeader($courses[1]['GRADE_ID']);
				DrawHeader(GetSchool(UserSchool()));
				DrawHeader(ProperDate($date),$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');

				ListOutput($courses,$columns,_('Course'),_('Courses'),array(),array(),array('center'=>false,'print'=>false));
				echo '<!-- NEW PAGE -->';
			}
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
		$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
		$mp_select = '<SELECT name=mp_id><OPTION value="">'._('N/A');
		foreach($mp_RET as $mp)
			$mp_select .= '<OPTION value='.$mp['MARKING_PERIOD_ID'].'>'.$mp['TITLE'];
		$mp_select .= '</SELECT>';

		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = '<INPUT type=submit value="'._('Create Schedules for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR><TD align=right width=120>'._('Marking Period').'</TD><TD>'.$mp_select.'</TD></TR>';
		$extra['extra_header_left'] .= '<TR><TD align=right width=120>'._('Include only courses active as of').'</TD><TD>'.PrepareDate('','_include_active_date').'</TD></TR>';
		Widgets('mailing_labels');
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	//$extra['force_search'] = true;

	Widgets('request');
	Widgets('course');

	Search('student_id',$extra);

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER><INPUT type=submit value="'._('Create Schedules for Selected Students').'"></CENTER>';
		echo "</FORM>";
	}
}

function _makeDays($value,$column)
{
	foreach(array('U','M','T','W','H','F','S') as $day)
		if(strpos($value,$day)!==false)
			$return .= $day;
		else
			$return .= '-';
	return $return;
}

function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}
?>
