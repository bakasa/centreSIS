<?php
include 'modules/Grades/config.inc.php';

if($_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$mp_RET = DBGet(DBQuery("SELECT TITLE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND MARKING_PERIOD_ID='".UserMP()."'"));
		$school_info_RET = DBGet(DBQuery("SELECT TITLE,PRINCIPAL FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

		$extra['SELECT'] = ",coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS NICK_NAME";
		$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";
		$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'Y'"))." AS HIGH_HONOR";
		//$extra['SELECT'] .= ",(SELECT TITLE FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS SCHOOL";
		//$extra['SELECT'] .= ",(SELECT PRINCIPAL FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS PRINCIPAL";
		$extra['SELECT'] .= ",(SELECT coalesce(st.TITLE||' ',' ')||st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
		$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN(".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
		$extra['ORDER_BY'] = 'HIGH_HONOR,SORT_ORDER DESC,ROOM,FULL_NAME';
		if($_REQUEST['list'])
			$extra['group'] = array('HIGH_HONOR');
		$RET = GetStuList($extra);

		if($_REQUEST['list'])
		{
			$handle = PDFStart();
			echo '<CENTER>';
			echo '<TABLE width=80%>';
			echo '<TR align=center><TD colspan=6><B>'.sprintf(_('%s Honor Roll'),$school_info_RET[1]['TITLE']).' </B> - '.$mp_RET[1]['TITLE'].' - '.date('F j, Y',strtotime($mp_RET[1]['END_DATE'])).'</TD></TR>';
			echo '<TR align=center><TD colspan=6>&nbsp;</TD></TR>';

			foreach(array('Y','') AS $high)
			{
				if($n = count($RET[$high]))
				{
					$n = (int) (($n+1)/2);
					echo '<TR align=center><TD colspan=6 bgcolor=#C0C0C0><B>'.($high=='Y'?$high_honor:$honor).'</B></TD></TR>';
					for($i=1; $i<=$n; $i++)
					{
						echo '<TR><TD>&nbsp;</TD>';
						$student = $RET[$high][$i];
						echo '<TD>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</TD><TD>'.$student['ROOM'].'</TD>';
						echo '<TD></TD>';
						$student = $RET[$high][$i+$n];
						echo '<TD>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</TD><TD>'.$student['ROOM'].'</TD></TR>';
					}
					echo '<TR align=center><TD colspan=6>&nbsp;</TD></TR>';
				}
			}
			echo '</TABLE>';
			echo '</CENTER>';
			PDFStop($handle);
		}
		else
		{
			$options = '--webpage --quiet -t pdf12 --jpeg --no-links --portrait --footer t --header . --left 0.5in --top 0.5in --bodyimage '.($htmldocAssetsPath?$htmldocAssetsPath:'assets/').'hr_bg.jpg --fontsize 24 --textfont times';
			$handle = PDFStart($options);
			echo '<!-- MEDIA SIZE 8.5x11in -->';
			echo '<!-- MEDIA LANDSCAPE YES -->';
			foreach($RET as $student)
			{
				echo '<CENTER>';
				echo '<TABLE>';
				echo '<TR align=center><TD><FONT size=1><BR><BR><BR><BR><BR><BR><BR><BR></FONT></TD></TR>';
				echo '<TR align=center><TD><FONT size=3>'._('We hereby recognize').'</FONT></TD><TR>';
				echo '<TR align=center><TD><FONT size=12>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</FONT></TD><TR>';
				//echo '<TR align=center><TD><FONT size=3>Who has completed all the academic<BR>requirements for<BR>'.$student['SCHOOL'].' '.($student['HIGH_HONOR']=='Y'?$high_honor:$honor).' Honor Roll</FONT></TD><TR>';
				echo '<TR align=center><TD><FONT size=3>'._('Who has completed all the academic<BR>requirements for<BR>').$school_info_RET[1]['TITLE'].' '.($student['HIGH_HONOR']=='Y'?$high_honor:$honor).' '._('Honor Roll').'</FONT></TD><TR>';
				echo '</TABLE>';

				echo '<TABLE width=80%>';
				echo '<TR><TD width=65%><FONT size=1><BR></TD></TR>';
				echo '<TR><TD><FONT size=4>'.$student['TEACHER'].'<BR></FONT><FONT size=0>'._('Teacher').'</FONT></TD>';
				echo '<TD><FONT size=3>'.$mp_RET[1]['TITLE'].'<BR></FONT><FONT size=0>'._('Marking Period').'</FONT></TD></TR>';
				//echo '<TR><TD><FONT size=4>'.$student['PRINCIPAL'].'<BR></FONT><FONT size=0>Principal</FONT></TD>';
				echo '<TR><TD><FONT size=4>'.$school_info_RET[1]['PRINCIPAL'].'<BR></FONT><FONT size=0>'._('Principal').'</FONT></TD>';
				echo '<TD><FONT size=3>'.date('F j, Y',strtotime($mp_RET[1]['END_DATE'])).'<BR></FONT><FONT size=0>'._('Date').'</FONT></TD></TR>';
				echo '</TABLE>';
				echo '</CENTER>';
				echo '<!-- NEW PAGE -->';
			}
			PDFStop($handle);
		}
	}
	else
		BackPrompt(_('You must choose at least one student'));
}

if(!$_REQUEST['modfunc'])
{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_CENTRE_PDF=true method=POST>";
		$extra['header_right'] = SubmitButton(_('Create Honor Roll for Selected Students'));

		$extra['extra_header_left'] = '<TABLE>';

		$extra['extra_header_left'] .= '<TR><TD><INPUT type=radio name=list value="" checked>'._('Certificates').'</TD></TR>';
		$extra['extra_header_left'] .= '<TR><TD><INPUT type=radio name=list value=list>'._('List').'</TD></TR>';

		$extra['extra_header_left'] .= '</TABLE>';
	}

	if(!isset($_REQUEST['_CENTRE_PDF']))
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	}
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['new'] = true;
	$extra['options']['search'] = false;
	$extra['force_search'] = true;

	Widgets('course');
	MyWidgets('honor_roll');
	if($for_news_web)
		$extra['student_fields'] = array('search'=>"'$for_news_web'",'view'=>"'$for_news_web'");
	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR><CENTER>'.SubmitButton(_('Create Honor Roll for Selected Students')).'</CENTER>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	if($_REQUEST['honor_roll']=='Y' || $_REQUEST['high_honor_roll']=='Y')
		return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
	else
		return '';
}

function MyWidgets($item)
{	global $extra,$_CENTRE;

	switch($item)
	{
		case 'honor_roll':
			if($_REQUEST['honor_roll']=='Y' && $_REQUEST['high_honor_roll']=='Y')
			{
				$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'Y'"))." AS HIGH_HONOR";
				$extra['WHERE'] =  " AND     exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp                                           WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg                     WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				$extra['columns_after']['HIGH_HONOR'] = 'High Honor';
				if(!$extra['NoSearchTerms'])
					$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Honor Roll').' &amp; '._('High Honor Roll').'</b></font><BR>';
			}
			elseif($_REQUEST['honor_roll']=='Y')
			{
				$extra['WHERE'] =  " AND     exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp                                           WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg                     WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				$extra['WHERE'] .= " AND     exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg                     WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				if(!$extra['NoSearchTerms'])
					$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Honor Roll').'</b></font><BR>';
			}
			elseif($_REQUEST['high_honor_roll']=='Y')
			{
				$extra['WHERE'] =  " AND     exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp                                           WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg                     WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				if(!$extra['NoSearchTerms'])
					$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('High Honor Roll').'</b></font><BR>';
			}
			$extra['search'] .= '<TR><TD align=right width=120>'._('Honor Roll').'</TD><TD><INPUT type=checkbox name=honor_roll value=Y checked>'._('Honor').' <INPUT type=checkbox name=high_honor_roll value=Y checked>'._('High Honor').'</TD></TR>';
		break;
	}
}
?>
