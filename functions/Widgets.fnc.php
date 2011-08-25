<?php

function Widgets($item,&$myextra=null)
{	global $extra,$_CENTRE,$CentreModules;

	if(isset($myextra))
		$extra =& $myextra;

	if(!is_array($_CENTRE['Widgets']))
		$_CENTRE['Widgets'] = array();

	if(!is_array($extra['functions']))
		$extra['functions'] = array();

	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && !$_CENTRE['Widgets'][$item])
	{
		switch($item)
		{
			case 'all':
				$extra['search'] .= '<TR><TD colspan=2>';

				if($CentreModules['Students'] && (!$_CENTRE['Widgets']['calendar'] || !$_CENTRE['Widgets']['next_year'] || !$_CENTRE['Widgets']['enrolled'] || !$_CENTRE['Widgets']['rolled']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'enrollment_table\');"><IMG SRC=assets/arrow_right.gif id=enrollment_table_arrow> <B>'._('Enrollment').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=enrollment_table style="display:none;">';
					Widgets('calendar',$extra);
					Widgets('next_year',$extra);
					Widgets('enrolled',$extra);
					Widgets('rolled',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Scheduling'] && (!$_CENTRE['Widgets']['course'] || !$_CENTRE['Widgets']['request']) && User('PROFILE')=='admin')
				{
					$extra['search'] .= '<A onclick="switchMenu(\'scheduling_table\');"><IMG SRC=assets/arrow_right.gif id=scheduling_table_arrow> <B>'._('Scheduling').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=scheduling_table style="display:none;">';
					Widgets('course',$extra);
					//Widgets('request',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Attendance'] && (!$_CENTRE['Widgets']['absences']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'absences_table\');"><IMG SRC=assets/arrow_right.gif id=absences_table_arrow> <B>'._('Attendance').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=absences_table style="display:none;">';
					Widgets('absences',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Grades'] && (!$_CENTRE['Widgets']['gpa'] || !$_CENTRE['Widgets']['class_rank'] || !$_CENTRE['Widgets']['letter_grade']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'grades_table\');"><IMG SRC=assets/arrow_right.gif id=grades_table_arrow> <B>'._('Grades').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% cellpadding=5 id=grades_table style="display:none;">';
					Widgets('gpa',$extra);
					Widgets('class_rank',$extra);
					Widgets('letter_grade',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Eligibility'] && (!$_CENTRE['Widgets']['eligibility'] || !$_CENTRE['Widgets']['activity']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'eligibility_table\');"><IMG SRC=assets/arrow_right.gif id=eligibility_table_arrow> <B>'._('Eligibility').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=eligibility_table style="display:none;">';
					Widgets('eligibility',$extra);
					Widgets('activity',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Food_Service'] && (!$_CENTRE['Widgets']['fsa_balance'] || !$_CENTRE['Widgets']['fsa_discount'] || !$_CENTRE['Widgets']['fsa_status'] || !$_CENTRE['Widgets']['fsa_barcode']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'food_service_table\');"><IMG SRC=assets/arrow_right.gif id=food_service_table_arrow> <B>'._('Food Service').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=food_service_table style="display:none;">';
					Widgets('fsa_balance',$extra);
					Widgets('fsa_discount',$extra);
					Widgets('fsa_status',$extra);
					Widgets('fsa_barcode',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Discipline'] && (!$_CENTRE['Widgets']['discipline'] || !$_CENTRE['Widgets']['discipline_categories']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'discipline_table\');"><IMG SRC=assets/arrow_right.gif id=discipline_table_arrow> <B>'._('Discipline').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=discipline_table style="display:none;">';
					Widgets('discipline',$extra);
					Widgets('discipline_categories',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Student_Billing'] && (!$_CENTRE['Widgets']['balance']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'billing_table\');"><IMG SRC=assets/arrow_right.gif id=billing_table_arrow> <B>'._('Student Billing').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=billing_table style="display:none;">';
					Widgets('balance',$extra);
					$extra['search'] .= '</TABLE>';
				}
				$extra['search'] .= '</TD></TR>';
			break;

			case 'user':
				$widgets_RET = DBGet(DBQuery("SELECT TITLE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='WidgetsSearch'".(count($_CENTRE['Widgets'])?" AND TITLE NOT IN ('".implode("','",array_keys($_CENTRE['Widgets']))."')":'')));
				foreach($widgets_RET as $widget)
					Widgets($widget['TITLE'],$extra);
			break;

			case 'course':
				if($CentreModules['Scheduling'] && User('PROFILE')=='admin')
				{
				if($_REQUEST['w_course_period_id'])
				{
					if($_REQUEST['w_course_period_id_which']=='course')
					{
						$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'"));
						$extra['FROM'] .= ",SCHEDULE w_ss";
						$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID AND w_ss.SYEAR=ssm.SYEAR AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID AND w_ss.COURSE_ID='".$course[1]['COURSE_ID']."' AND ('".DBDate()."' BETWEEN w_ss.START_DATE AND w_ss.END_DATE OR w_ss.END_DATE IS NULL)";
						if(!$extra['NoSearchTerms'])
							$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Course')).' </b></font>'.$course[1]['COURSE_TITLE'].'<BR>';
					}
					else
					{
						$extra['FROM'] .= ",SCHEDULE w_ss";
						$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID AND w_ss.SYEAR=ssm.SYEAR AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID AND w_ss.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."' AND ('".DBDate()."' BETWEEN w_ss.START_DATE AND w_ss.END_DATE OR w_ss.END_DATE IS NULL)";
						$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'"));
						if(!$extra['NoSearchTerms'])
							$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Course Period')).' </b></font>'.$course[1]['COURSE_TITLE'].': '.$course[1]['TITLE'].'<BR>';
					}
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Course')."</TD><TD><DIV id=course_div></DIV> <A HREF=# onclick='window.open(\"Modules.php?modname=misc/ChooseCourse.php\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'><SMALL>"._('Choose')."</SMALL></A></TD></TR>";
				}
			break;

			case 'request':
				if($CentreModules['Scheduling'] && User('PROFILE')=='admin')
				{
				// PART OF THIS IS DUPLICATED IN PrintRequests.php
				if($_REQUEST['request_course_id'])
				{
					$course = DBGet(DBQuery("SELECT c.TITLE FROM COURSES c WHERE c.COURSE_ID='".$_REQUEST['request_course_id']."'"));
					if(!$_REQUEST['not_request_course'])
					{
						$extra['FROM'] .= ",SCHEDULE_REQUESTS sr";
						$extra['WHERE'] .= " AND sr.STUDENT_ID=s.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.SCHOOL_ID=ssm.SCHOOL_ID AND sr.COURSE_ID='".$_REQUEST['request_course_id']."' ";
						if(!$extra['NoSearchTerms'])
							$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Request')).' </b></font>'.$course[1]['TITLE'].'<BR>';
					}
					else
					{
						$extra['WHERE'] .= " AND NOT EXISTS (SELECT '' FROM SCHEDULE_REQUESTS sr WHERE sr.STUDENT_ID=ssm.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.COURSE_ID='".$_REQUEST['request_course_id']."' ) ";
						if(!$extra['NoSearchTerms'])
							$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Missing Request')).' </b></font>'.$course[1]['TITLE'].'<BR>';
					}
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Request')."</TD><TD><DIV id=request_div></DIV> <A HREF=# onclick='window.open(\"Modules.php?modname=misc/ChooseRequest.php\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'><SMALL>"._('Choose')."</SMALL></A></TD></TR>";
				}
			break;

			case 'absences':
				if($CentreModules['Attendance'])
				{
				if(is_numeric($_REQUEST['absences_low']) && is_numeric($_REQUEST['absences_high']))
				{
					if($_REQUEST['absences_low'] > $_REQUEST['absences_high'])
					{
						$temp = $_REQUEST['absences_high'];
						$_REQUEST['absences_high'] = $_REQUEST['absences_low'];
						$_REQUEST['absences_low'] = $temp;
					}

					if($_REQUEST['absences_low']==$_REQUEST['absences_high'])
						$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE FROM ATTENDANCE_DAY ad WHERE ssm.STUDENT_ID=ad.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND ad.MARKING_PERIOD_ID IN (".GetChildrenMP($_REQUEST['absences_term'],UserMP()).")) = '$_REQUEST[absences_low]'";
					else
						$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE FROM ATTENDANCE_DAY ad WHERE ssm.STUDENT_ID=ad.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND ad.MARKING_PERIOD_ID IN (".GetChildrenMP($_REQUEST['absences_term'],UserMP()).")) BETWEEN '$_REQUEST[absences_low]' AND '$_REQUEST[absences_high]'";
					switch($_REQUEST['absences_term'])
					{
						case 'FY':
							$term = _('this school year to date');
						break;
						case 'SEM':
							$term = _('this semester to date');
						break;
						case 'QTR':
							$term = _('this marking period to date');
						break;
					}
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Days Absent').'&nbsp;'.$term.' '._('Between').' </b></font>'.$_REQUEST['absences_low'].' &amp; '.$_REQUEST['absences_high'].'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Days Absent')."<BR><INPUT type=radio name=absences_term value=FY checked><small>YTD</small><INPUT type=radio name=absences_term value=SEM><small>".GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME')."</small><INPUT type=radio name=absences_term value=QTR><small>".GetMP(UserMP(),'SHORT_NAME')."</small></TD><TD><small>"._('Between')."</small> <INPUT type=text name=absences_low size=3 maxlength=5> <small>&amp;</small> <INPUT type=text name=absences_high size=3 maxlength=5></TD></TR>";
				}
			break;

			case 'gpa':
				if($CentreModules['Grades'])
				{
				if(is_numeric($_REQUEST['gpa_low']) && is_numeric($_REQUEST['gpa_high']))
				{
					if($_REQUEST['gpa_low'] > $_REQUEST['gpa_high'])
					{
						$temp = $_REQUEST['gpa_high'];
						$_REQUEST['gpa_high'] = $_REQUEST['gpa_low'];
						$_REQUEST['gpa_low'] = $temp;
					}
					if($_REQUEST['list_gpa'])
					{
						$extra['SELECT'] .= ',sgc.WEIGHTED_GPA,sgc.UNWEIGHTED_GPA';
						$extra['columns_after']['WEIGHTED_GPA'] = _('Weighted GPA');
						$extra['columns_after']['UNWEIGHTED_GPA'] = _('Unweighted GPA');
					}
					if(strpos($extra['FROM'],'STUDENT_GPA_CALCULATED sgc')===false)
					{
						$extra['FROM'] .= ",STUDENT_GPA_CALCULATED sgc";
						$extra['WHERE'] .= " AND sgc.STUDENT_ID=s.STUDENT_ID AND sgc.MARKING_PERIOD_ID='".$_REQUEST['gpa_term']."'";
					}
					$extra['WHERE'] .= " AND sgc.".(($_REQUEST['weighted']=='Y')?'WEIGHTED_':'')."GPA BETWEEN '$_REQUEST[gpa_low]' AND '$_REQUEST[gpa_high]' AND sgc.MARKING_PERIOD_ID='".$_REQUEST['gpa_term']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.(($_REQUEST['gpa_weighted']=='Y')?'Weighted ':'').Localize('colon',_('GPA between')).' </b></font>'.$_REQUEST['gpa_low'].' &amp; '.$_REQUEST['gpa_high'].'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>GPA<BR><INPUT type=checkbox name=gpa_weighted value=Y><small>"._('Weighted')."</small><BR><INPUT type=radio name=gpa_term value=CUM checked><small>"._('Cumulative')."</small><INPUT type=radio name=gpa_term value=".GetParentMP('SEM',UserMP())."><small>".GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME')."</small><INPUT type=radio name=gpa_term value=".UserMP()."><small>".GetMP(UserMP(),'SHORT_NAME')."</small></TD><TD><small>"._('Between')."</small> <INPUT type=text name=gpa_low size=3 maxlength=5> <small>&amp;</small> <INPUT type=text name=gpa_high size=3 maxlength=5></TD></TR>";
				}
			break;

			case 'class_rank':
				if($CentreModules['Grades'])
				{
				if(is_numeric($_REQUEST['class_rank_low']) && is_numeric($_REQUEST['class_rank_high']))
				{
					if($_REQUEST['class_rank_low'] > $_REQUEST['class_rank_high'])
					{
						$temp = $_REQUEST['class_rank_high'];
						$_REQUEST['class_rank_high'] = $_REQUEST['class_rank_low'];
						$_REQUEST['class_rank_low'] = $temp;
					}
					if(strpos($extra['FROM'],'STUDENT_GPA_CALCULATED sgc')===false)
					{
						$extra['FROM'] .= ",STUDENT_GPA_CALCULATED sgc";
						$extra['WHERE'] .= " AND sgc.STUDENT_ID=s.STUDENT_ID AND sgc.MARKING_PERIOD_ID='".$_REQUEST['class_rank_term']."'";
					}
					$extra['WHERE'] .= " AND sgc.CLASS_RANK BETWEEN '$_REQUEST[class_rank_low]' AND '$_REQUEST[class_rank_high]'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Class Rank')).' '._('Between').'</b></font>'.$_REQUEST['class_rank_low'].' &amp; '.$_REQUEST['class_rank_high'].'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>Class Rank<BR><INPUT type=radio name=class_rank_term value=CUM checked><small>"._('Cumulative')."</small><INPUT type=radio name=class_rank_term value=".GetParentMP('SEM',UserMP())."><small>".GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME')."</small><INPUT type=radio name=class_rank_term value=".UserMP()."><small>".GetMP(UserMP(),'SHORT_NAME')."</small>";
				if(strlen($pros = GetChildrenMP('PRO',UserMP())))
				{
					$pros = explode(',',str_replace("'",'',$pros));
					foreach($pros as $pro)
						$extra['search'] .= "<INPUT type=radio name=class_rank_term value=".$pro."><small>".GetMP($pro,'SHORT_NAME')."</small>";
				}
				$extra['search'] .= "</TD><TD><small>"._('Between')."</small> <INPUT type=text name=class_rank_low size=3 maxlength=5> <small>&amp;</small> <INPUT type=text name=class_rank_high size=3 maxlength=5></TD></TR>";
				}
			break;

			case 'letter_grade':
				if($CentreModules['Grades'])
				{
				if(count($_REQUEST['letter_grade']))
				{
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>With'.($_REQUEST['letter_grade_exclude']=='Y'?'out':'').' Report Card Grade: </b></font>';
					$letter_grades_RET = DBGet(DBQuery("SELECT ID,TITLE FROM REPORT_CARD_GRADES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('ID'));
					foreach($_REQUEST['letter_grade'] as $grade=>$Y)
					{
						$letter_grades .= ",'$grade'";
						if(!$extra['NoSearchTerms'])
							$_CENTRE['SearchTerms'] .= $letter_grades_RET[$grade][1]['TITLE'].', ';
					}
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] = substr($_CENTRE['SearchTerms'],0,-2).'<BR>';
					$extra['WHERE'] .= " AND ".($_REQUEST['letter_grade_exclude']=='Y'?'NOT ':'')."EXISTS (SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg3 WHERE sg3.STUDENT_ID=ssm.STUDENT_ID AND sg3.SYEAR=ssm.SYEAR AND sg3.REPORT_CARD_GRADE_ID IN (".substr($letter_grades,1).") AND sg3.MARKING_PERIOD_ID='".$_REQUEST['letter_grade_term']."' )";
				}

				$extra['search'] .= "<TR><TD align=right width=120>"._('Letter Grade')."<BR><INPUT type=checkbox name=letter_grade_exclude value=Y><small>"._('Did not receive')."</small><BR><INPUT type=radio name=letter_grade_term value=".GetParentMP('SEM',UserMP())."><small>".GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME')."</small><INPUT type=radio name=letter_grade_term value=".UserMP()."><small>".GetMP(UserMP(),'SHORT_NAME')."</small>";
				if(strlen($pros = GetChildrenMP('PRO',UserMP())))
				{
					$pros = explode(',',str_replace("'",'',$pros));
					foreach($pros as $pro)
						$extra['search'] .= "<INPUT type=radio name=letter_grade_term value=".$pro."><small>".GetMP($pro,'SHORT_NAME')."</small>";
				}
				$extra['search'] .= "</TD><TD>";
				if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
					$letter_grades_RET = DBGet(DBQuery("SELECT rg.ID,rg.TITLE,rg.GRADE_SCALE_ID FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs WHERE rg.SCHOOL_ID='".UserSchool()."' AND rg.SYEAR='".UserSyear()."' AND rs.ID=rg.GRADE_SCALE_ID".(User('PROFILE')=='teacher'?' AND rg.GRADE_SCALE_ID=(SELECT GRADE_SCALE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=\''.UserCoursePeriod().'\')':'')." ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER"),array(),array('GRADE_SCALE_ID'));
				foreach($letter_grades_RET as $grades)
				{
					$i = 0;
					if(count($grades))
					{
						foreach($grades as $grade)
						{
							if($i%9==0)
								$extra['search'] .= '<BR>';

							$extra['search'] .= '<INPUT type=checkbox value=Y name=letter_grade['.$grade['ID'].']>'.$grade['TITLE'];
							$i++;
						}
					}
				}
				$extra['search'] .= '</TD></TR>';
				}
			break;

			case 'eligibility':
				if($CentreModules['Eligibility'])
				{
				if($_REQUEST['ineligible']=='Y')
				{
					$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility' AND TITLE IN ('START_DAY','END_DAY')"));
					if(count($start_end_RET))
					{
						foreach($start_end_RET as $value)
							$$value['TITLE'] = $value['VALUE'];
					}

					switch(date('D'))
					{
						case 'Mon':
						$today = 1;
						break;
						case 'Tue':
						$today = 2;
						break;
						case 'Wed':
						$today = 3;
						break;
						case 'Thu':
						$today = 4;
						break;
						case 'Fri':
						$today = 5;
						break;
						case 'Sat':
						$today = 6;
						break;
						case 'Sun':
						$today = 7;
						break;
					}

					$start_date = strtoupper(date('d-M-y',time() - ($today-$START_DAY)*60*60*24));
					$end_date = strtoupper(date('d-M-y',time()));
					$extra['WHERE'] .= " AND (SELECT count(*) FROM ELIGIBILITY e WHERE ssm.STUDENT_ID=e.STUDENT_ID AND e.SYEAR=ssm.SYEAR AND e.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date' AND e.ELIGIBILITY_CODE='FAILING') > '0'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Eligibility')).' </b></font>'._('Ineligible').'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Ineligible')."</TD><TD><INPUT type=checkbox name=ineligible value='Y'></TD></TR>";
				}
			break;

			case 'activity':
				if($CentreModules['Eligibility'])
				{
				if($_REQUEST['activity_id'])
				{
					$extra['FROM'] .= ",STUDENT_ELIGIBILITY_ACTIVITIES sea";
					$extra['WHERE'] .= " AND sea.STUDENT_ID=s.STUDENT_ID AND sea.SYEAR=ssm.SYEAR AND sea.ACTIVITY_ID='".$_REQUEST['activity_id']."'";
					$activity = DBGet(DBQuery("SELECT TITLE FROM ELIGIBILITY_ACTIVITIES WHERE ID='".$_REQUEST['activity_id']."'"));
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Activity: </b></font>'.$activity[1]['TITLE'].'<BR>';
				}
				if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
					$activities_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
				$select = "<SELECT name=activity_id><OPTION value=''>"._('Not Specified')."</OPTION>";
				if(count($activities_RET))
				{
					foreach($activities_RET as $activity)
						$select .= "<OPTION value=$activity[ID]>$activity[TITLE]</OPTION>";
				}
				$select .= '</SELECT>';
				$extra['search'] .= "<TR><TD align=right width=120>"._('Activity')."</TD><TD>".$select."</TD></TR>";
				}
			break;

			case 'mailing_labels':
				if($_REQUEST['mailing_labels']=='Y')
				{
					$extra['SELECT'] .= ',coalesce(sam.ADDRESS_ID,-ssm.STUDENT_ID) AS ADDRESS_ID,sam.ADDRESS_ID AS MAILING_LABEL';
					$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (sam.STUDENT_ID=ssm.STUDENT_ID AND sam.MAILING='Y'".($_REQUEST['residence']=='Y'?" AND sam.RESIDENCE='Y'":'').")".$extra['FROM'];
					$extra['functions'] += array('MAILING_LABEL'=>'MailingLabel');
				}

				$extra['search'] .= '<TR><TD align=right width=120>'._('Mailing Labels').'</TD><TD><INPUT type=checkbox name=mailing_labels value=Y></TD>';
			break;

			case 'balance':
				if($CentreModules['Student_Billing'])
				{
				if(is_numeric($_REQUEST['balance_low']) && is_numeric($_REQUEST['balance_high']))
				{
					if($_REQUEST['balance_low'] > $_REQUEST['balance_high'])
					{
						$temp = $_REQUEST['balance_high'];
						$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
						$_REQUEST['balance_low'] = $temp;
					}
					$extra['WHERE'] .= " AND (coalesce((SELECT sum(f.AMOUNT) FROM BILLING_FEES f,STUDENTS_JOIN_FEES sjf WHERE sjf.FEE_ID=f.ID AND sjf.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR),0)+(SELECT coalesce(sum(f.AMOUNT),0)-coalesce(sum(f.CASH),0) FROM LUNCH_TRANSACTIONS f WHERE f.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR)-coalesce((SELECT sum(p.AMOUNT) FROM BILLING_PAYMENTS p WHERE p.STUDENT_ID=ssm.STUDENT_ID AND p.SYEAR=ssm.SYEAR),0)) BETWEEN '$_REQUEST[balance_low]' AND '$_REQUEST[balance_high]' ";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Student Billing Balance')).' </b></font>'._('Between').' '.$_REQUEST['balance_low'].' &amp; '.$_REQUEST['balance_high'].'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Balance')."<BR></TD><TD><small>"._('Between')."</small> <INPUT type=text name=balance_low size=5 maxlength=10> <small>&amp;</small> <INPUT type=text name=balance_high size=5 maxlength=10></TD></TR>";
				}
			break;

			case 'discipline':
				if($CentreModules['Discipline'])
				{
				if(is_array($_REQUEST['discipline']))
				{
					foreach($_REQUEST['discipline'] as $key=>$value)
					{
						if(!$value)
							unset($_REQUEST['discipline'][$key]);
					}
				}
				if($_REQUEST['month_discipline_entry_begin'] && $_REQUEST['day_discipline_entry_begin'] && $_REQUEST['year_discipline_entry_begin'])
				{
					$_REQUEST['discipline_entry_begin'] = $_REQUEST['day_discipline_entry_begin'].'-'.$_REQUEST['month_discipline_entry_begin'].'-'.$_REQUEST['year_discipline_entry_begin'];
					if(!VerifyDate($_REQUEST['discipline_entry_begin']))
						unset($_REQUEST['discipline_entry_begin']);
					unset($_REQUEST['day_discipline_entry_begin']);unset($_REQUEST['month_discipline_entry_begin']);unset($_REQUEST['year_discipline_entry_begin']);
				}
				if($_REQUEST['month_discipline_entry_end'] && $_REQUEST['day_discipline_entry_end'] && $_REQUEST['year_discipline_entry_end'])
				{
					$_REQUEST['discipline_entry_end'] = $_REQUEST['day_discipline_entry_end'].'-'.$_REQUEST['month_discipline_entry_end'].'-'.$_REQUEST['year_discipline_entry_end'];
					if(!VerifyDate($_REQUEST['discipline_entry_end']))
						unset($_REQUEST['discipline_entry_end']);
					unset($_REQUEST['day_discipline_entry_end']);unset($_REQUEST['month_discipline_entry_end']);unset($_REQUEST['year_discipline_entry_end']);
				}
				if($_REQUEST['discipline_reporter'] || $_REQUEST['discipline_entry_begin'] || $_REQUEST['discipline_entry_end'] || count($_REQUEST['discipline']) || count($_REQUEST['discipline_begin']) || count($_REQUEST['discipline_end']))
				{
					$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';
					$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
				}
				$users_RET = DBGet(DBQuery("SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND (SCHOOLS IS NULL OR SCHOOLS LIKE '%,".UserSchool().",%') AND (PROFILE='admin' OR PROFILE='teacher') ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME"),array(),array('STAFF_ID'));
				if($_REQUEST['discipline_reporter'])
				{
					$extra['WHERE'] .= " AND dr.STAFF_ID='$_REQUEST[discipline_reporter]' ";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Reporter: </b></font>'.$users_RET[$_REQUEST['discipline_reporter']][1]['LAST_NAME'].', '.$users_RET[$_REQUEST['discipline_reporter']][1]['FIRST_NAME'].' '.$users_RET[$_REQUEST['discipline_reporter']][1]['MIDDLE_NAME'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Reporter</TD><TD>';
				$extra['search'] .= '<SELECT name=discipline_reporter><OPTION value="">'._('Not Specified').'</OPTION>';
				foreach($users_RET as $id=>$user)
					$extra['search'] .= '<OPTION value='.$id.'>'.$user[1]['LAST_NAME'].', '.$user[1]['FIRST_NAME'].' '.$user[1]['MIDDLE_NAME'].'</OPTION>';
				$extra['search'] .= '</SELECT>';
				$extra['search'] .= '</TD></TR>';

				if($_REQUEST['discipline_entry_begin'] && $_REQUEST['discipline_entry_end'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE BETWEEN '$_REQUEST[discipline_entry_begin]' AND '$_REQUEST[discipline_entry_end]' ";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Incident Date Between: </b></font>'.ProperDate($_REQUEST['discipline_entry_begin']).'<font color=gray><b> and </b></font>'.ProperDate($_REQUEST['discipline_entry_end']).'<BR>';
				}
				elseif($_REQUEST['discipline_entry_begin'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE>='$_REQUEST[discipline_entry_begin]' ";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Incident Entered On or After </b></font>'.ProperDate($_REQUEST['discipline_entry_begin']).'<BR>';
				}
				elseif($_REQUEST['discipline_entry_end'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE<='$_REQUEST[discipline_entry_end]' ";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Incident Entered On or Before </b></font>'.ProperDate($_REQUEST['discipline_entry_end']).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Incident Date</TD><TD><table cellpadding=0 cellspacing=0><tr><td>&ge;&nbsp;</td><td>'.PrepareDate('','_discipline_entry_begin',true,array('short'=>true)).'</td></tr><tr><td>&le;&nbsp;</td><td>'.PrepareDate('','_discipline_entry_end',true,array('short'=>true)).'</td></tr></table></TD></TR>';
				}
			break;

			case 'discipline_categories':
				if($CentreModules['Discipline'])
				{
				$categories_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE,OPTIONS FROM DISCIPLINE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TYPE!='textarea'"));
				foreach($categories_RET as $category)
				{
					if($category['TYPE']!='date')
					{
						$extra['search'] .= '<TR><TD align=right width=120>'.$category['TITLE'].'</TD><TD>';
						switch($category['TYPE'])
						{
							case 'text':
								$extra['search'] .= '<INPUT type=text name=discipline['.$category['ID'].']>';
								if($_REQUEST['discipline'][$cateogory['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." LIKE '".$_REQUEST['discipline'][$cateogory['ID']]."%' ";
							break;
							case 'checkbox':
								$extra['search'] .= '<INPUT type=checkbox name=discipline['.$category['ID'].'] value=Y>';
								if($_REQUEST['discipline'][$cateogory['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." = 'Y' ";
							break;
							case 'numeric':
								if($_REQUEST['discipline_begin'][$category['ID']] && $_REQUEST['discipline_end'][$category['ID']])
								{
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." BETWEEN '".$_REQUEST['discipline_begin'][$category['ID']]."' AND '".$_REQUEST['discipline_end'][$category['ID']]."' ";
									if(!$extra['NoSearchTerms'])
										$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.$category['TITLE'].' &ge; </b></font>'.$_REQUEST['discipline_begin'][$category['ID']].'<font color=gray><b> and &le; </b></font>'.$_REQUEST['discipline_end'][$category['ID']].'<BR>';
								}
								elseif($_REQUEST['discipline_begin'][$category['ID']])
								{
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID'].">='".$_REQUEST['discipline_begin'][$category['ID']]."' ";
									if(!$extra['NoSearchTerms'])
										$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.$category['TITLE'].' &ge; </b></font>'.$_REQUEST['discipline_begin'][$category['ID']].'<BR>';
								}
								elseif($_REQUEST['discipline_end'][$category['ID']])
								{
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']."<='".$_REQUEST['discipline_end'][$category['ID']]."' ";
									if(!$extra['NoSearchTerms'])
										$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.$category['TITLE'].' &le; </b></font>'.$_REQUEST['discipline_end'][$category['ID']].'<BR>';
								}
								$extra['search'] .= '&ge; <INPUT type=text name=discipline_begin['.$category['ID'].'] size=3 maxlength=11> &le; <INPUT type=text name=discipline_end['.$category['ID'].'] size=3 maxlength=11>';
							break;
							case 'multiple_checkbox':
							case 'multiple_radio':
							case 'select':
								$category['OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['OPTIONS']));
								$category['OPTIONS'] = explode("\r",$category['OPTIONS']);

								$extra['search'] .= '<SELECT name=discipline['.$category['ID'].']><OPTION value="">'._('Not Specified').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
								foreach($category['OPTIONS'] as $option)
									$extra['search'] .= '<OPTION value="'.$option.'">'.$option.'</OPTION>';
								$extra['search'] .= '</SELECT>';
								if($_REQUEST['discipline'][$category['ID']])
								{
									if($_REQUEST['discipline'][$category['ID']]=='!')
										$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." IS NULL ";
									elseif($category['TYPE']=='multiple_radio' || $category['TYPE']=='select')
										$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']."='".$_REQUEST['discipline'][$category['ID']]."' ";
									elseif($category['TYPE']=='multiple_checkbox')
										$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." LIKE '%||".$_REQUEST['discipline'][$category['ID']]."||%' ";
								}
							break;
						}
						$extra['search'] .= '</TD></TR>';
					}
				}
				}
			break;

			case 'next_year':
				if($CentreModules['Students'])
				{
				$schools_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE ID!='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('ID'));
				if($_REQUEST['next_year']=='!')
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL IS NULL";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Next Year')).' </b></font>No Value<BR>';
				}
				elseif($_REQUEST['next_year']!='')
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL='".$_REQUEST['next_year']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Next Year')).' </b></font>'.($_REQUEST['next_year']==UserSchool()?'Next grade at current school':($_REQUEST['next_year']=='0'?'Retain':($_REQUEST['next_year']=='-1'?'Do not enroll after this school year':$schools_RET[$_REQUEST['next_year']][1]['TITLE']))).'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>"._('Next Year')."</TD><TD><SELECT name=next_year><OPTION value=''>N/A</OPTION><OPTION value='!'>"._('No Value')."</OPTION><OPTION value=".UserSchool().">"._('Next grade at current school')."</OPTION><OPTION value=0>"._('Retain')."</OPTION><OPTION value=-1>"._('Do not enroll after this school year')."</OPTION>";
				foreach($schools_RET as $id=>$school)
					$extra['search'] .= '<OPTION value='.$id.'>'.$school[1]['TITLE'].'</OPTION>';
				$extra['search'] .= '</SELECT></TD></TR>';
				}
			break;

			case 'calendar':
				if($CentreModules['Students'])
				{
				$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID,TITLE FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC"),array(),array('CALENDAR_ID'));
				if($_REQUEST['calendar']=='!')
				{
					$extra['WHERE'] .= " AND ssm.CALENDAR_ID IS ".($_REQUEST['calendar_not']=='Y'?'NOT ':'')."NULL";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Calendar: </b></font>'.($_REQUEST['calendar_not']=='Y'?'Any':'No').' Value<BR>';
				}
				elseif($_REQUEST['calendar']!='')
				{
					$extra['WHERE'] .= " AND ssm.CALENDAR_ID".($_REQUEST['calendar_not']=='Y'?'!':'')."='".$_REQUEST['calendar']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Calendar: </b></font>'.($_REQUEST['calendar_not']=='Y'?'Not ':'').$calendars_RET[$_REQUEST['calendar']][1]['TITLE'].'<BR>';
				}
				$extra['search'] .= "<TR><TD align=right width=120>Calendar</TD><TD><INPUT type=checkbox name=calendar_not value=Y>Not <SELECT name=calendar><OPTION value=''>N/A</OPTION><OPTION value='!'>No Value</OPTION>";
				foreach($calendars_RET as $id=>$calendar)
					$extra['search'] .= '<OPTION value='.$id.'>'.$calendar[1]['TITLE'].'</OPTION>';
				$extra['search'] .= '</SELECT></TD></TR>';
				}
			break;

			case 'enrolled':
				if($CentreModules['Students'])
				{
				if($_REQUEST['month_enrolled_begin'] && $_REQUEST['day_enrolled_begin'] && $_REQUEST['year_enrolled_begin'])
				{
					$_REQUEST['enrolled_begin'] = $_REQUEST['day_enrolled_begin'].'-'.$_REQUEST['month_enrolled_begin'].'-'.$_REQUEST['year_enrolled_begin'];
					if(!VerifyDate($_REQUEST['enrolled_begin']))
						unset($_REQUEST['enrolled_begin']);
				}
				if($_REQUEST['month_enrolled_end'] && $_REQUEST['day_enrolled_end'] && $_REQUEST['year_enrolled_end'])
				{
					$_REQUEST['enrolled_end'] = $_REQUEST['day_enrolled_end'].'-'.$_REQUEST['month_enrolled_end'].'-'.$_REQUEST['year_enrolled_end'];
					if(!VerifyDate($_REQUEST['enrolled_end']))
						unset($_REQUEST['enrolled_end']);
				}
				if($_REQUEST['enrolled_begin'] && $_REQUEST['enrolled_end'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE BETWEEN '".$_REQUEST['enrolled_begin']."' AND '".$_REQUEST['enrolled_end']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Enrolled Between: </b></font>'.ProperDate($_REQUEST['enrolled_begin']).' and '.ProperDate($_REQUEST['enrolled_end']).'<BR>';
				}
				elseif($_REQUEST['enrolled_begin'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE>='".$_REQUEST['enrolled_begin']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Enrolled On or After: </b></font>'.ProperDate($_REQUEST['enrolled_begin']).'<BR>';
				}
				if($_REQUEST['enrolled_end'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE<='".$_REQUEST['enrolled_end']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Enrolled On or Before: </b></font>'.ProperDate($_REQUEST['enrolled_end']).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Attendance Start</TD><TD><table cellpadding=0 cellspacing=0><tr><td>&ge;&nbsp;</td><td>'.PrepareDate('','_enrolled_begin',true,array('short'=>true)).'</td></tr><tr><td>&le;&nbsp;</td><td>'.PrepareDate('','_enrolled_end',true,array('short'=>true)).'</td></tr></table></TD></TR>';
				}
			break;

			case 'rolled':
				if($CentreModules['Students'])
				{
				if($_REQUEST['rolled'])
				{
					$extra['WHERE'] .= " AND ".($_REQUEST['rolled']=='Y'?'':'NOT ')."exists (SELECT '' FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR<ssm.SYEAR)";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Previously Enrolled: </b></font>'.($_REQUEST['rolled']=='Y'?'Yes':'No').'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Previously Enrolled</TD><TD><INPUT type=radio value="" name=rolled checked>N/A <INPUT type=radio value=Y name=rolled>Yes <INPUT type=radio value=N name=rolled>No</TD></TR>';
				}
			break;

			case 'fsa_balance_warning':
				$value = $GLOBALS['warning'];
				$item = 'fsa_balance';
			case 'fsa_balance':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_balance']!='')
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID';
					}
					$extra['FROM'] .= ",FOOD_SERVICE_ACCOUNTS fsa";
					$extra['WHERE'] .= " AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fsa.BALANCE".($_REQUEST['fsa_bal_ge']=='Y'?'>=':'<')."'".round($_REQUEST['fsa_balance'],2)."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Food Service Balance: </b></font>'.($_REQUEST['fsa_bal_ge']=='Y'?'&ge;':'&lt;').number_format($_REQUEST['fsa_balance'],2).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Balance</TD><TD><table cellpadding=0 cellspacing=0><tr><td>&lt;<INPUT type=radio name=fsa_bal_ge value="" CHECKED></td><td rowspan=2><INPUT type=text name=fsa_balance size=10'.($value?' value="'.$value.'"':'').'></td></tr><tr><td>&ge;<INPUT type=radio name=fsa_bal_ge value=Y></td></tr></table></TD></TR>';
				}
			break;

			case 'fsa_discount':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_discount'])
				{
					if(!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					if($_REQUEST['fsa_discount']=='Full')
						$extra['WHERE'] .= " AND fssa.DISCOUNT IS NULL";
					else
						$extra['WHERE'] .= " AND fssa.DISCOUNT='".$_REQUEST['fsa_discount']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Food Service Dscount: </b></font>'.$_REQUEST['fsa_discount'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Discount</TD><TD><SELECT name=fsa_discount><OPTION value="">Not Specified</OPTION><OPTION value="Full">Full</OPTION><OPTION value="Reduced">Reduced</OPTION><OPTION value="Free">Free</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_status_active':
				$value = 'active';
				$item = 'fsa_status';
			case 'fsa_status':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_status']) {
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					if($_REQUEST['fsa_status']=='Active')
						$extra['WHERE'] .= " AND fssa.STATUS IS NULL";
					else
						$extra['WHERE'] .= " AND fssa.STATUS='".$_REQUEST['fsa_status']."'";
				}
				$extra['search'] .= '<TR><TD align=right width=120>Account Status</TD><TD><SELECT name=fsa_status><OPTION value="">Not Specified</OPTION><OPTION value="Active"'.($value=='active'?' SELECTED':'').'>Active</OPTION><OPTION value="Inactive">Inactive</OPTION><OPTION value="Disabled">Disabled</OPTION><OPTION value="Closed">Closed</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_barcode':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_barcode'])
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					$extra['WHERE'] .= " AND fssa.BARCODE='".$_REQUEST['fsa_barcode']."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Food Service Barcode: </b></font>'.$_REQUEST['fsa_barcode'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Barcode</TD><TD><INPUT type=text name=fsa_barcode size="15"></TD></TR>';
				}
			break;

			case 'fsa_account_id':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_account_id'])
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					$extra['WHERE'] .= " AND fssa.ACCOUNT_ID='".($_REQUEST['fsa_account_id']+0)."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>Food Service Account ID: </b></font>'.($_REQUEST['fsa_account_id']+0).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>Account ID</TD><TD><INPUT type=text name=fsa_account_id size="15"></TD></TR>';
				}
			break;
		}
		$_CENTRE['Widgets'][$item] = true;
	}
}
?>
