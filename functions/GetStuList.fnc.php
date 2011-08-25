<?php

function GetStuList(& $extra)
{	global $contacts_RET,$view_other_RET,$_CENTRE;

	if((!$extra['SELECT_ONLY'] || strpos($extra['SELECT_ONLY'],'GRADE_ID')!==false) && !$extra['functions']['GRADE_ID'])
		$functions = array('GRADE_ID'=>'GetGrade');
	else
		$functions = array();

	if($extra['functions'])
		$functions += $extra['functions'];

	if(!$extra['MP'] && !$extra['DATE'])
	{
		$extra['MP'] = UserMP();
		$extra['DATE'] = DBDate();
	}
	elseif(!$extra['MP'])
		$extra['MP'] = GetCurrentMP('QTR',$extra['DATE'],false);
	elseif(!$extra['DATE'])
		$extra['DATE'] = DBDate();

	if($_REQUEST['expanded_view']=='true')
	{
		if(!$extra['columns_after'])
			$extra['columns_after'] = array();

		$view_fields_RET = DBGet(DBQuery("SELECT cf.ID,cf.TYPE,cf.TITLE FROM CUSTOM_FIELDS cf WHERE ((SELECT VALUE FROM PROGRAM_USER_CONFIG WHERE TITLE=cast(cf.ID AS TEXT) AND PROGRAM='StudentFieldsView' AND USER_ID='".User('STAFF_ID')."')='Y'".($extra['student_fields']['view']?" OR cf.ID IN (".$extra['student_fields']['view'].")":'').") ORDER BY cf.SORT_ORDER,cf.TITLE"));
		$view_address_RET = DBGet(DBQuery("SELECT VALUE FROM PROGRAM_USER_CONFIG WHERE PROGRAM='StudentFieldsView' AND TITLE='ADDRESS' AND USER_ID='".User('STAFF_ID')."'"));
		$view_address_RET = $view_address_RET[1]['VALUE'];
		$view_other_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE PROGRAM='StudentFieldsView' AND TITLE IN ('CONTACT_INFO','HOME_PHONE','GUARDIANS','ALL_CONTACTS') AND USER_ID='".User('STAFF_ID')."'"),array(),array('TITLE'));

		if(!count($view_fields_RET) && !isset($view_address_RET) && !isset($view_other_RET['CONTACT_INFO']))
		{
			$extra['columns_after'] = array('CONTACT_INFO'=>'<IMG SRC=assets/down_phone_button.gif border=0>','CUSTOM_200000000'=>_('Gender'),'CUSTOM_200000001'=>_('Ethnicity'),'ADDRESS'=>_('Mailing Address'),'CITY'=>_('City'),'STATE'=>_('State'),'ZIPCODE'=>_('Zipcode')) + $extra['columns_after'];
			$select = ',ssm.STUDENT_ID AS CONTACT_INFO,s.CUSTOM_200000000,s.CUSTOM_200000001,coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,coalesce(a.MAIL_CITY,a.CITY) AS CITY,coalesce(a.MAIL_STATE,a.STATE) AS STATE,coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE ';
			$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID AND sam.RESIDENCE='Y') LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) ".$extra['FROM'];
			$functions['CONTACT_INFO'] = 'makeContactInfo';
			$RET = DBGet(DBQuery("SELECT ID,TYPE FROM CUSTOM_FIELDS WHERE ID IN ('200000000','200000001')"),array(),array('ID'));
			// if gender and ethnicity are converted to codeds or exports type
			if($RET['200000000'][1]['TYPE']=='codeds' || $RET['200000000'][1]['TYPE']=='exports')
				$functions['CUSTOM_200000000'] = 'DeCodeds';
			if($RET['200000001'][1]['TYPE']=='codeds' || $RET['200000001'][1]['TYPE']=='exports')
				$functions['CUSTOM_200000001'] = 'DeCodeds';
			$extra['singular'] = _('Student Address');
			$extra['plural'] = _('Student Addresses');

			$extra2['NoSearchTerms'] = true;
			$extra2['SELECT_ONLY'] = 'ssm.STUDENT_ID,p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';
			$extra2['FROM'] .= ',ADDRESS a,STUDENTS_JOIN_ADDRESS sja LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\')) LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID) LEFT OUTER JOIN PEOPLE_JOIN_CONTACTS pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';
			$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID=ssm.STUDENT_ID ';
			$extra2['ORDER_BY'] .= 'sjp.CUSTODY';
			$extra2['group'] = array('STUDENT_ID','PERSON_ID');

			// EXPANDED VIEW AND ADDR BREAKS THIS QUERY ... SO, TURN 'EM OFF
			if(!$_REQUEST['_CENTRE_PDF'])
			{
				$expanded_view = $_REQUEST['expanded_view'];
				$_REQUEST['expanded_view'] = false;
				$addr = $_REQUEST['addr'];
				unset($_REQUEST['addr']);
				$contacts_RET = GetStuList($extra2);
				$_REQUEST['expanded_view'] = $expanded_view;
				$_REQUEST['addr'] = $addr;
			}
			else
				unset($extra2['columns_after']['CONTACT_INFO']);
		}
		else
		{
			if($view_other_RET['CONTACT_INFO'][1]['VALUE']=='Y' && !$_REQUEST['_CENTRE_PDF'])
			{
				$select .= ',ssm.STUDENT_ID AS CONTACT_INFO ';
				$extra['columns_after']['CONTACT_INFO'] = '<IMG SRC=assets/down_phone_button.gif border=0>';
				$functions['CONTACT_INFO'] = 'makeContactInfo';

				$extra2 = $extra;
				$extra2['NoSearchTerms'] = true;
				$extra2['SELECT'] = '';
				$extra2['SELECT_ONLY'] = 'ssm.STUDENT_ID,p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';
				$extra2['FROM'] .= ',ADDRESS a,STUDENTS_JOIN_ADDRESS sja LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\')) LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID) LEFT OUTER JOIN PEOPLE_JOIN_CONTACTS pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';
				$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID=ssm.STUDENT_ID ';
				$extra2['ORDER_BY'] .= 'sjp.CUSTODY';
				$extra2['group'] = array('STUDENT_ID','PERSON_ID');
				$extra2['functions'] = array();
				$extra2['link'] = array();

				// EXPANDED VIEW AND ADDR BREAKS THIS QUERY ... SO, TURN 'EM OFF
				$expanded_view = $_REQUEST['expanded_view'];
				$_REQUEST['expanded_view'] = false;
				$addr = $_REQUEST['addr'];
				unset($_REQUEST['addr']);
				$contacts_RET = GetStuList($extra2);
				$_REQUEST['expanded_view'] = $expanded_view;
				$_REQUEST['addr'] = $addr;
			}
			foreach($view_fields_RET as $field)
			{
				$extra['columns_after']['CUSTOM_'.$field['ID']] = $field['TITLE'];
				if($field['TYPE']=='date')
					$functions['CUSTOM_'.$field['ID']] = 'ProperDate';
				elseif($field['TYPE']=='numeric')
					$functions['CUSTOM_'.$field['ID']] = 'removeDot00';
				elseif($field['TYPE']=='codeds')
					$functions['CUSTOM_'.$field['ID']] = 'DeCodeds';
				elseif($field['TYPE']=='exports')
					$functions['CUSTOM_'.$field['ID']] = 'DeCodeds';
				$select .= ',s.CUSTOM_'.$field['ID'];
			}
			if($view_address_RET)
			{
				$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID AND sam.".$view_address_RET."='Y') LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) ".$extra['FROM'];
				$extra['columns_after'] += array('ADDRESS'=>ucwords(strtolower(str_replace('_',' ',$view_address_RET))).' Address','CITY'=>'City','STATE'=>'State','ZIPCODE'=>'Zipcode');
				if($view_address_RET!='MAILING')
					$select .= ",a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,ssm.STUDENT_ID AS PARENTS";
				else
					$select .= ",a.ADDRESS_ID,coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,coalesce(a.MAIL_CITY,a.CITY) AS CITY,coalesce(a.MAIL_STATE,a.STATE) AS STATE,coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE,a.PHONE,ssm.STUDENT_ID AS PARENTS ";
				$extra['singular'] = 'Student Address';
				$extra['plural'] = 'Student Addresses';

				if($view_other_RET['HOME_PHONE'][1]['VALUE']=='Y')
				{
					$functions['PHONE'] = 'makePhone';
					$extra['columns_after']['PHONE'] = _('Home Phone');
				}
				if($view_other_RET['GUARDIANS'][1]['VALUE']=='Y' || $view_other_RET['ALL_CONTACTS'][1]['VALUE']=='Y')
				{
					$functions['PARENTS'] = 'makeParents';
					if($view_other_RET['ALL_CONTACTS'][1]['VALUE']=='Y')
						$extra['columns_after']['PARENTS'] = _('Contacts');
					else
						$extra['columns_after']['PARENTS'] = _('Guardians');
				}
			}
			elseif($_REQUEST['addr'] || $extra['addr'])
			{
				$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID ".$extra['STUDENTS_JOIN_ADDRESS'].") LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) ".$extra['FROM'];
				$distinct = 'DISTINCT ';
			}
		}
		$extra['SELECT'] .= $select;
	}
	else
	{
		if($extra['student_fields']['view'])
		{
			if(!$extra['columns_after'])
				$extra['columns_after'] = array();

			$view_fields_RET = DBGet(DBQuery("SELECT cf.ID,cf.TYPE,cf.TITLE FROM CUSTOM_FIELDS cf WHERE cf.ID IN (".$extra['student_fields']['view'].") ORDER BY cf.SORT_ORDER,cf.TITLE"));
			foreach($view_fields_RET as $field)
			{
				$extra['columns_after']['CUSTOM_'.$field['ID']] = $field['TITLE'];
				if($field['TYPE']=='date')
					$functions['CUSTOM_'.$field['ID']] = 'ProperDate';
				elseif($field['TYPE']=='numeric')
					$functions['CUSTOM_'.$field['ID']] = 'removeDot00';
				elseif($field['TYPE']=='codeds')
					$functions['CUSTOM_'.$field['ID']] = 'DeCodeds';
				elseif($field['TYPE']=='exports')
					$functions['CUSTOM_'.$field['ID']] = 'DeCodeds';
				$select .= ',s.CUSTOM_'.$field['ID'];
			}
			$extra['SELECT'] .= $select;
		}
		if($_REQUEST['addr'] || $extra['addr'])
		{
			$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID ".$extra['STUDENTS_JOIN_ADDRESS'].") LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) ".$extra['FROM'];
			$distinct = 'DISTINCT ';
		}
	}

	switch(User('PROFILE'))
	{
		case 'admin':
			$sql = 'SELECT ';
			//$sql = 'SELECT '.$distinct;
			if($extra['SELECT_ONLY'])
				$sql .= $extra['SELECT_ONLY'];
			else
			{
				//$sql .= "s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS FULL_NAME,s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.STUDENT_ID,ssm.SCHOOL_ID,ssm.SCHOOL_ID AS LIST_SCHOOL_ID,ssm.GRADE_ID ".$extra['SELECT'];
				if(Preferences('NAME')=='Common')
					$sql .= "s.LAST_NAME||', '||coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS FULL_NAME,";
				else
					$sql .= "s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS FULL_NAME,";
				$sql .='s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.STUDENT_ID,ssm.SCHOOL_ID,ssm.SCHOOL_ID AS LIST_SCHOOL_ID,ssm.GRADE_ID '.$extra['SELECT'];
				if($_REQUEST['include_inactive']=='Y')
					$sql .= ','.db_case(array("(ssm.SYEAR='".UserSyear()."' AND ('".$extra['DATE']."'>=ssm.START_DATE AND ('".$extra['DATE']."'<=ssm.END_DATE OR ssm.END_DATE IS NULL)))",'TRUE',"'<FONT color=green>Active</FONT>'","'<FONT color=red>Inactive</FONT>'")).' AS ACTIVE';
			}

			$sql .= " FROM STUDENTS s JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID";
			if($_REQUEST['include_inactive']=='Y')
				//$sql .= " AND ssm.ID=(SELECT max(ID) FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR<='".UserSyear()."')";
				$sql .= " AND ssm.ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR<='".UserSyear()."' ORDER BY SYEAR DESC,START_DATE DESC LIMIT 1)";
			else
				$sql .= " AND ssm.SYEAR='".UserSyear()."' AND ('".$extra['DATE']."'>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR '".$extra['DATE']."'<=ssm.END_DATE))";

			if(UserSchool() && $_REQUEST['_search_all_schools']!='Y')
				$sql .= " AND ssm.SCHOOL_ID='".UserSchool()."'";
			else
			{
				if(User('SCHOOLS'))
					$sql .= " AND ssm.SCHOOL_ID IN (".substr(str_replace(',',"','",User('SCHOOLS')),2,-2).") ";
				$extra['columns_after']['LIST_SCHOOL_ID'] = 'School';
				$functions['LIST_SCHOOL_ID'] = 'GetSchool';
			}
			$sql .= ")".$extra['FROM']." WHERE TRUE";

			if(!$extra['SELECT_ONLY'] && $_REQUEST['include_inactive']=='Y')
				$extra['columns_after']['ACTIVE'] = _('Status');
		break;

		case 'teacher':
			$sql = 'SELECT ';
			//$sql = 'SELECT '.$distinct;
			if($extra['SELECT_ONLY'])
				$sql .= $extra['SELECT_ONLY'];
			else
			{
				if(Preferences('NAME')=='Common')
					$sql .= "s.LAST_NAME||', '||coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS FULL_NAME,";
				else
					$sql .= "s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS FULL_NAME,";
				$sql .='s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.STUDENT_ID,ssm.SCHOOL_ID,ssm.GRADE_ID '.$extra['SELECT'];
				if($_REQUEST['include_inactive']=='Y')
				{
					$sql .= ','.db_case(array("('".$extra['DATE']."'>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR '".$extra['DATE']."'<=ssm.END_DATE))",'TRUE',"'<FONT color=green>Active</FONT>'","'<FONT color=red>Inactive</FONT>'")).' AS ACTIVE';
					$sql .= ','.db_case(array("('".$extra['DATE']."'>=ss.START_DATE AND (ss.END_DATE IS NULL OR '".$extra['DATE']."'<=ss.END_DATE)) AND ss.MARKING_PERIOD_ID IN (".GetAllMP($extra['MPTable'],$extra['MP']).")",'TRUE',"'<FONT color=green>Active</FONT>'","'<FONT color=red>Inactive</FONT>'")).' AS ACTIVE_SCHEDULE';
				}
			}

			$sql .= " FROM STUDENTS s JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."'";
			if($_REQUEST['include_inactive']=='Y')
				$sql .= " AND ss.START_DATE=(SELECT START_DATE FROM SCHEDULE WHERE STUDENT_ID=s.STUDENT_ID AND SYEAR=ss.SYEAR AND COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID ORDER BY START_DATE DESC LIMIT 1)";
			else
				$sql .= " AND ss.MARKING_PERIOD_ID IN (".GetAllMP($extra['MPTable'],$extra['MP']).") AND ('".$extra['DATE']."'>=ss.START_DATE AND ('".$extra['DATE']."'<=ss.END_DATE OR ss.END_DATE IS NULL))";

			$sql .= ") JOIN COURSE_PERIODS cp ON (cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ".($extra['all_courses']=='Y'?"cp.TEACHER_ID='".User('STAFF_ID')."'":"cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'").")
				JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR=ss.SYEAR AND ssm.SCHOOL_ID='".UserSchool()."'";

			if($_REQUEST['include_inactive']=='Y')
				$sql .= " AND ssm.ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR=ssm.SYEAR ORDER BY START_DATE DESC LIMIT 1)";
			else
				$sql .= " AND ('".$extra['DATE']."'>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR '".$extra['DATE']."'<=ssm.END_DATE))";
			$sql .= ")".$extra['FROM']." WHERE TRUE";

			if(!$extra['SELECT_ONLY'] && $_REQUEST['include_inactive']=='Y')
			{
				$extra['columns_after']['ACTIVE'] = _('School Status');
				$extra['columns_after']['ACTIVE_SCHEDULE'] = _('Course Status');
			}
		break;

		case 'parent':
		case 'student':
			$sql = 'SELECT ';
			if($extra['SELECT_ONLY'])
				$sql .= $extra['SELECT_ONLY'];
			else
			{
				if(Preferences('NAME')=='Common')
					$sql .= "s.LAST_NAME||', '||coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS FULL_NAME,";
				else
					$sql .= "s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS FULL_NAME,";
				$sql .='s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.STUDENT_ID,ssm.SCHOOL_ID,ssm.GRADE_ID '.$extra['SELECT'];
			}
			$sql .= " FROM STUDENTS s JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' AND ssm.SCHOOL_ID='".UserSchool()."'
					AND ('".$extra['DATE']."'>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR '".$extra['DATE']."'<=ssm.END_DATE)) AND s.STUDENT_ID".($extra['ASSOCIATED']?" IN (SELECT STUDENT_ID FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='".$extra['ASSOCIATED']."')":"='".UserStudentID()."'");
			$sql .= ")".$extra['FROM']." WHERE TRUE";
		break;
		default:
			exit(_('Error'));
	}

	//$sql = appendSQL($sql,array('NoSearchTerms'=>$extra['NoSearchTerms']));

	$sql .= ' '.$extra['WHERE'].' ';

	if($extra['GROUP'])
		$sql .= ' GROUP BY '.$extra['GROUP'];

	if(!$extra['ORDER_BY'] && !$extra['SELECT_ONLY'])
	{
		$sql .= ' ORDER BY ';
		if(Preferences('SORT')=='Grade')
			$sql .= '(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID),';
		// it would be easier to sort on full_name but postgres sometimes yields strange results
		if(Preferences('NAME')=='Common')
			$sql .= 's.LAST_NAME,coalesce(s.CUSTOM_200000002,s.FIRST_NAME)';
		else
			$sql .= 's.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME';
		$sql .= $extra['ORDER'];
	}
	elseif($extra['ORDER_BY'])
		$sql .= ' ORDER BY '.$extra['ORDER_BY'];

	if($extra['DEBUG']===true)
		echo '<!--'.$sql.'-->';

	return DBGet(DBQuery($sql),$functions,$extra['group']);
}

function makeContactInfo($student_id,$column)
{	global $contacts_RET;

	if(count($contacts_RET[$student_id]))
	{
		foreach($contacts_RET[$student_id] as $person)
		{
			if($person[1]['FIRST_NAME'] || $person[1]['LAST_NAME'])
				$tipmessage .= '<B>'.$person[1]['STUDENT_RELATION'].': '.$person[1]['FIRST_NAME'].' '.$person[1]['LAST_NAME'].'</B><BR>';
			$tipmessage .= '<TABLE>';
			if($person[1]['PHONE'])
				$tipmessage .= '<TR><TD align=right><font color=gray size=1 face=Verdana,Arial,Helvetica>'._('Home Phone').'</font> </TD><TD><font size=1 face=Verdana,Arial,Helvetica>'.$person[1]['PHONE'].'</font></TD></TR>';
			foreach($person as $info)
			{
				if($info['TITLE'] || $info['VALUE'])
					$tipmessage .= '<TR><TD align=right><font color=gray size=1 face=Verdana,Arial,Helvetica>'.$info['TITLE'].'</font></TD><TD><font size=1 face=Verdana,Arial,Helvetica>'.$info['VALUE'].'</font></TD></TR>';
			}
			$tipmessage .= '</TABLE>';
		}
	}
	else
		$tipmessage = _('This student has no contact information.');
	return button('phone','','# onMouseOver=\'stm(["'._('Contact Information').'","'.str_replace("'",'&#39;',$tipmessage).'"],["white","#333366","","","",,"black","#e8e8ff","","","",,,,2,"#333366",2,,,,,"",,,,]);\' onMouseOut=\'htm()\'');
}

function removeDot00($value,$column)
{
	return str_replace('.00','',$value);
}

function makePhone($phone,$column='')
{
	if(strlen($phone)==10)
		$return .= '('.substr($phone,0,3).')'.substr($phone,3,7).'-'.substr($phone,7);
	if(strlen($phone)=='7')
		$return .= substr($phone,0,3).'-'.substr($phone,3);
	else
		$return .= $phone;

	return $return;
}

function makeParents($student_id,$column)
{	global $THIS_RET,$view_other_RET,$_CENTRE;

	if($THIS_RET['PARENTS']==$student_id)
	{
		//if($THIS_RET['ADDRESS_ID']=='')
		//	$THIS_RET['ADDRESS_ID'] = '0';

		$THIS_RET['PARENTS'] = '';

		if($THIS_RET['ADDRESS_ID']!='')
		{
		if($_CENTRE['makeParents'])
			if($_CENTRE['makeParents']!='!')
				$constraint = " AND (lower(sjp.STUDENT_RELATION) LIKE '".strtolower($_CENTRE['makeParents'])."%')";
			else
				$constraint = " AND sjp.STUDENT_RELATION IS NULL";
		if($view_other_RET['ALL_CONTACTS'][1]['VALUE']=='Y')
			$constraint .= '';
		else
			$constraint .= " AND sjp.CUSTODY='Y'";

		$people_RET = DBGet(DBQuery("SELECT p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,sjp.CUSTODY,sjp.EMERGENCY FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p WHERE sjp.PERSON_ID=p.PERSON_ID AND sjp.STUDENT_ID='$student_id' AND sjp.ADDRESS_ID='$THIS_RET[ADDRESS_ID]'$constraint ORDER BY sjp.CUSTODY,sjp.STUDENT_RELATION,p.LAST_NAME,p.FIRST_NAME"));
		if(count($people_RET))
		{
			$THIS_RET['PARENTS'] .= '<TABLE border=0 cellpadding=0 cellspacing=0 class=LO_field>';
			foreach($people_RET as $person)
			{
				if($person['CUSTODY']=='Y')
					$color = '#00FF00';
				elseif($person['EMERGENCY']=='Y')
					$color = '#FFFF00';
				else
					$color = '#FF0000';

				if($_REQUEST['_CENTRE_PDF'])
					$THIS_RET['PARENTS'] .= '<TR><TD>'.button('dot',$color,'',6).'</TD><TD>'.$person['FIRST_NAME'].' '.$person['LAST_NAME'].'</TD></TR>, ';
				else
					$THIS_RET['PARENTS'] .= '<TR><TD>'.button('dot',$color,'',6).'</TD><TD><A HREF=# onclick=\'window.open("Modules.php?modname=misc/ViewContact.php?person_id='.$person['PERSON_ID'].'&student_id='.$student_id.'","","scrollbars=yes,resizable=yes,width=400,height=200");\'>'.$person['FIRST_NAME'].' '.$person['LAST_NAME'].'</A></TD></TR>';
			}
			if($_REQUEST['_CENTRE_PDF'])
				$THIS_RET['PARENTS'] = substr($THIS_RET['PARENTS'],0,-2);
			$THIS_RET['PARENTS'] .= '</TABLE>';
		}
		}
	}
	return $THIS_RET['PARENTS'];
}

function appendSQL($sql,$extra)
{	global $_CENTRE;

	if($_REQUEST['stuid'])
	{
		$sql .= " AND ssm.STUDENT_ID = '".$_REQUEST['stuid']."'";
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Centre ID')).' </b></font>'.$_REQUEST['stuid'].'<BR>';
	}
	if($_REQUEST['last'])
	{
		$sql .= " AND LOWER(s.LAST_NAME) LIKE '".strtolower($_REQUEST['last'])."%'";
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Last Name starts with')).' </b></font>'.$_REQUEST['last'].'<BR>';
	}
	if($_REQUEST['first'])
	{
		$sql .= " AND LOWER(s.FIRST_NAME) LIKE '".strtolower($_REQUEST['first'])."%'";
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('First Name starts with')).' </b></font>'.$_REQUEST['first'].'<BR>';
	}
	if($_REQUEST['grade'])
	{
		$sql .= " AND ssm.GRADE_ID = '$_REQUEST[grade]'";
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Grade')).' </b></font>'.GetGrade($_REQUEST['grade']).'<BR>';
	}
	if(count($_REQUEST['grades']))
	{
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',ngettext('Grade','Grades',sizeof($_REQUEST['grades']))).' </b></font>'.($_REQUEST['grades_not']=='Y'?_('Excluded').' ':'');
		$list = $sep = '';
		foreach($_REQUEST['grades'] as $id=>$y)
		{
			$list .= "$sep'$id'";
			if(!$extra['NoSearchTerms'])
				$_CENTRE['SearchTerms'] .= $sep.GetGrade($id);
			$sep = ',';
		}
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<BR>';
		$sql .= " AND ssm.GRADE_ID ".($_REQUEST['grades_not']=='Y'?'NOT ':'')." IN ($list)";
	}
	if($_REQUEST['addr'])
	{
		$sql .= " AND (LOWER(a.ADDRESS) LIKE '%".strtolower($_REQUEST['addr'])."%' OR LOWER(a.CITY) LIKE '".strtolower($_REQUEST['addr'])."%' OR LOWER(a.STATE)='".strtolower($_REQUEST['addr'])."' OR ZIPCODE LIKE '".$_REQUEST['addr']."%')";
		if(!$extra['NoSearchTerms'])
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Address contains')).' </b></font>'.$_REQUEST['addr'].'<BR>';
	}

	return $sql;
}
?>
