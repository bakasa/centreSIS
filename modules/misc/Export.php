<?php
include_once('ProgramFunctions/miscExport.fnc.php');
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';
$extra['extra_search'] .= '<TR><TD></TD><TD><DIV id=fields_div></DIV></TD></TR>';
$extra['extra_search'] .= '<TR><TD></TD><TD><INPUT type=hidden name=relation><INPUT type=hidden name=residence><INPUT type=hidden name=mailing><INPUT type=hidden name=bus_pickup><INPUT type=hidden name=bus_dropoff></TD></TR>';
$extra['action'] .= " onsubmit='document.forms[0].relation.value=document.getElementById(\"relation\").value; document.forms[0].residence.value=document.getElementById(\"residence\").checked; document.forms[0].mailing.value=document.getElementById(\"Mailing\").checked; document.forms[0].bus_pickup.value=document.getElementById(\"bus_pickup\").checked; document.forms[0].bus_dropoff.value=document.getElementById(\"bus_dropoff\").checked;'";
$extra['new'] = true;

$_CENTRE['CustomFields'] = true;
if($_REQUEST['fields']['ADDRESS'] || $_REQUEST['fields']['CITY'] || $_REQUEST['fields']['STATE'] || $_REQUEST['fields']['ZIPCODE'] || $_REQUEST['fields']['PHONE'] || $_REQUEST['fields']['MAIL_ADDRESS'] || $_REQUEST['fields']['MAIL_CITY'] || $_REQUEST['fields']['MAIL_STATE'] || $_REQUEST['fields']['MAIL_ZIPCODE'] || $_REQUEST['fields']['PARENTS'])
{
	$extra['SELECT'] .= ',a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_ADDRESS,a.ADDRESS)','NULL')).' AS MAIL_ADDRESS,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_CITY,a.CITY)','NULL')).' AS MAIL_CITY,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_STATE,a.STATE)','NULL')).' AS MAIL_STATE,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_ZIPCODE,a.ZIPCODE)','NULL')).' AS MAIL_ZIPCODE';
	$extra['addr'] = true;
	if($_REQUEST['residence']!='false' || $_REQUEST['mailing']!='false' || $_REQUEST['bus_pickup']!='false' || $_REQUEST['bus_dropoff']!='false')
	{
		$extra['STUDENTS_JOIN_ADDRESS'] .= ' AND (';
		if($_REQUEST['residence']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.RESIDENCE='Y' OR ";
		if($_REQUEST['mailing']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.MAILING='Y' OR ";
		if($_REQUEST['bus_pickup']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_PICKUP='Y' OR ";
		if($_REQUEST['bus_dropoff']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_DROPOFF='Y' OR ";
		$extra['STUDENTS_JOIN_ADDRESS'] .= 'FALSE)';
	}

	if($_REQUEST['fields']['PARENTS'])
	{
		$extra['SELECT'] .= ',ssm.STUDENT_ID AS PARENTS';
		$view_other_RET['ALL_CONTACTS'][1]['VALUE']='Y';
		if($_REQUEST['relation']!='')
		{
			$_CENTRE['makeParents'] = $_REQUEST['relation'];
			//$extra['STUDENTS_JOIN_ADDRESS'] .= " AND EXISTS (SELECT '' FROM STUDENTS_JOIN_PEOPLE sjp WHERE sjp.ADDRESS_ID=sam.ADDRESS_ID AND ".($_REQUEST['relation']!='!'?"lower(sjp.STUDENT_RELATION) LIKE '".strtolower($_REQUEST['relation'])."%'":"sjp.STUDENT_RELATION IS NULL").") ";
		}
	}
}
$extra['SELECT'] .= ',ssm.NEXT_SCHOOL,ssm.CALENDAR_ID,ssm.SYEAR,ssm.SCHOOL_ID AS SCHOOL_NUMBER,s.*';
if($_REQUEST['fields']['FIRST_INIT'])
	$extra['SELECT'] .= ',substr(s.FIRST_NAME,1,1) AS FIRST_INIT';
if($_REQUEST['fields']['GIVEN_NAME'])
	$extra['SELECT'] .= ",s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS GIVEN_NAME";
if($_REQUEST['fields']['COMMON_NAME'])
	$extra['SELECT'] .= ",s.LAST_NAME||', '||coalesce(s.CUSTOM_200000002,s.FIRST_NAME) AS COMMON_NAME";

if(!$extra['functions'])
	$extra['functions'] = array('NEXT_SCHOOL'=>'_makeNextSchool','CALENDAR_ID'=>'_makeCalendar','SCHOOL_ID'=>'GetSchool','SCHOOL_NUMBER'=>'GetSchool','PARENTS'=>'makeParents','LAST_LOGIN'=>'makeLogin');

if($_REQUEST['search_modfunc']=='list')
{
	if(!$fields_list)
	{
		$fields_list = array('FULL_NAME'=>(Preferences('NAME')=='Common'?_('Last, Common'):_('Last, First M')),'GIVEN_NAME'=>_('Last, First M'),'COMMON_NAME'=>_('Last, Common'),'FIRST_NAME'=>_('First'),'FIRST_INIT'=>_('First Initial'),'LAST_NAME'=>_('Last'),'MIDDLE_NAME'=>_('Middle'),'NAME_SUFFIX'=>_('Suffix'),'CUSTOM_200000002'=>_('Common'),'STUDENT_ID'=>_('Centre ID'),'GRADE_ID'=>_('Grade'),'SCHOOL_ID'=>_('School'),'SCHOOL_NUMBER'=>_('School Number'),'NEXT_SCHOOL'=>_('Rolling / Retention Options'),'CALENDAR_ID'=>_('Calendar'),'USERNAME'=>_('Username'),'PASSWORD'=>_('Password'),'START_DATE'=>_('Enrollment Start Date'),'END_DATE'=>_('Enrollment End Date'),'ENROLLMENT_SHORT'=>_('Enrollment Code'),'DROP_SHORT'=>_('Drop Code'),'ADDRESS'=>_('Address'),'CITY'=>_('City'),'STATE'=>_('State'),'ZIPCODE'=>_('Zip Code'),'PHONE'=>_('Home Phone'),'MAIL_ADDRESS'=>_('Mailing Address'),'MAIL_CITY'=>_('Mailing City'),'MAIL_STATE'=>_('Mailing State'),'MAIL_ZIPCODE'=>_('Mailing Zipcode'),'PARENTS'=>_('Contacts'));
		if($extra['field_names'])
			$fields_list += $extra['field_names'];

		$fields_list['PERIOD_ATTENDANCE'] = _('Teacher');
		$periods_RET = DBGet(DBQuery("SELECT TITLE,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		foreach($periods_RET as $period)
			$fields_list['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'].' '._('Teacher').' - '._('Room');
	}

	$custom_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE FROM CUSTOM_FIELDS WHERE ID!='200000002' ORDER BY SORT_ORDER,TITLE"),array(),array('ID'));

	foreach($custom_RET as $id=>$field)
	{
		if(!$fields_list['CUSTOM_'.$id])
			$fields_list['CUSTOM_'.$id] = $field[1]['TITLE'];
	}

	$address_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE FROM ADDRESS_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('ID'));

	foreach($address_RET as $id=>$field)
	{
		if(!$fields_list['ADDRESS_'.$id])
		{
			$fields_list['ADDRESS_'.$id] = $field[1]['TITLE'];
			if($_REQUEST['fields']['ADDRESS_'.$id])
			{
				$extra['SELECT'] .= ',a.CUSTOM_'.$id.' AS ADDRESS_'.$id;
				$extra['addr'] = true;
			}
		}
	}
if($_REQUEST['fields']['START_DATE'] || $_REQUEST['fields']['END_DATE'] || $_REQUEST['fields']['ENROLLMENT_SHORT'] || $_REQUEST['fields']['DROP_SHORT'])
	{
        	$extra['SELECT'] .= ',xse.START_DATE, xse.END_DATE, (select short_name from student_enrollment_codes where id = xse.enrollment_code and syear = xse.syear) as enrollment_short, (select short_name from student_enrollment_codes where id = xse.drop_code and syear = xse.syear) as drop_short' ;
        	$extra['FROM'] .= ',STUDENT_ENROLLMENT xse';
        	$extra['WHERE'] .= ' AND xse.STUDENT_ID=s.STUDENT_ID AND xse.SYEAR=\''.UserSyear().'\'';
	}

	if($_REQUEST['month_include_active_date'])
		$date = $_REQUEST['day_include_active_date'].'-'.$_REQUEST['month_include_active_date'].'-'.$_REQUEST['year_include_active_date'];
	else
		$date = DBDate();

	if($_REQUEST['fields']['PERIOD_ATTENDANCE'])
		$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,SCHOOL_PERIODS p WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=p.PERIOD_ID AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).') AND p.ATTENDANCE=\'Y\') AS PERIOD_ATTENDANCE';
	foreach($periods_RET as $period)
	{
		if($_REQUEST['fields']['PERIOD_'.$period['PERIOD_ID']]=='Y')
		{
			$extra['SELECT'] .= ',array(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=\''.$period['PERIOD_ID'].'\' AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')) AS PERIOD_'.$period['PERIOD_ID'];
			$extra['functions']['PERIOD_'.$period['PERIOD_ID']] = '_makeTeachers';
		}
	}

	if($CentreModules['Food_Service'] && ($_REQUEST['fields']['FS_ACCOUNT_ID']=='Y' || $_REQUEST['fields']['FS_DISCOUNT']=='Y' || $_REQUEST['fields']['FS_STATUS']=='Y' || $_REQUEST['fields']['FS_BARCODE']=='Y' || $_REQUEST['fields']['FS_BALANCE']=='Y'))
	{
		$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		$extra['WHERE'] .= ' AND fssa.STUDENT_ID=ssm.STUDENT_ID';
		if($_REQUEST['fields']['FS_ACCOUNT_ID']=='Y')
			$extra['SELECT'] .= ',fssa.ACCOUNT_ID AS FS_ACCOUNT_ID';
		if($_REQUEST['fields']['FS_DISCOUNT']=='Y')
			$extra['SELECT'] .= ',coalesce(fssa.DISCOUNT,\'Full\') AS FS_DISCOUNT';
		if($_REQUEST['fields']['FS_STATUS']=='Y')
			$extra['SELECT'] .= ',coalesce(fssa.STATUS,\'Active\') AS FS_STATUS';
		if($_REQUEST['fields']['FS_BARCODE']=='Y')
			$extra['SELECT'] .= ',fssa.BARCODE AS FS_BARCODE';
		if($_REQUEST['fields']['FS_BALANCE']=='Y')
			$extra['SELECT'] .= ',(SELECT fsa.BALANCE FROM FOOD_SERVICE_ACCOUNTS fsa WHERE fsa.ACCOUNT_ID=fssa.ACCOUNT_ID) AS FS_BALANCE';
		$fields_list += array('FS_ACCOUNT_ID'=>'F/S '._('Account ID'),'FS_DISCOUNT'=>'F/S '._('Discount'),'FS_STATUS'=>'F/S '._('Status'),'FS_BARCODE'=>'F/S '._('Barcode'),'FS_BALANCE'=>'F/S '._('Balance'));
	}

	if($_REQUEST['fields'])
	{
		foreach($_REQUEST['fields'] as $field=>$on)
		{
			$columns[$field] = ParseMLField($fields_list[$field]);
			if(substr($field,0,7)=='CUSTOM_')
			{
				if($custom_RET[substr($field,7)][1]['TYPE']=='date' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'ProperDate';
				elseif($custom_RET[substr($field,7)][1]['TYPE']=='codeds' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
				elseif($custom_RET[substr($field,7)][1]['TYPE']=='exports' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
			}
			elseif(substr($field,0,8)=='ADDRESS_')
			{
				if($address_RET[substr($field,8)][1]['TYPE']=='date' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'ProperDate';
				elseif($address_RET[substr($field,8)][1]['TYPE']=='codeds' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
				elseif($address_RET[substr($field,8)][1]['TYPE']=='exports' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
			}
		}
		if($_REQUEST['address_group'])
		{
			$extra['singular'] = _('Family');
			$extra['plural'] = _('Families');
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y'),-ssm.STUDENT_ID) AS FAMILY_ID";
			$extra['group'] = $extra['LO_group'] = array('FAMILY_ID');
		}

        Widgets('all',$extra);
		$extra['WHERE'] .= appendSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
		$extra['WHERE'] .= CustomFields('where','student',array('NoSearchTerms'=>$extra['NoSearchTerms']));
		$RET = GetStuList($extra);
		if($extra['array_function'] && function_exists($extra['array_function']))
			$extra['array_function']($RET);

		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
		}
		if(!$_REQUEST['address_group'])
			$header_left = '<A HREF='.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'Y')).'>'._('Group by Family').'</A>';
		else
			$header_left = '<A HREF='.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'')).'>'._('Ungroup by Family').'</A>';
                DrawHeader($header_left);
		DrawHeader(str_replace('<BR>','<BR> &nbsp;',substr($_CENTRE['SearchTerms'],0,-4)));
		ListOutput($RET,$columns,$extra['singular']?$extra['singular']:_('Student'),$extra['plural']?$extra['plural']:_('Students'),array(),$extra['LO_group'],$extra['LO_options']);
	}
}
else
{
	if(!$fields_list)
	{
		if(AllowUse('Students/Student.php&category_id=1'))
			$fields_list['General'] = array('FULL_NAME'=>(Preferences('NAME')=='Common'?_('Last, Common'):_('Last, First M')),(Preferences('NAME')=='Common'?'GIVEN_NAME':'COMMON_NAME')=>(Preferences('NAME')=='Common'?_('Last, First M'):_('Last, Common')),'FIRST_NAME'=>_('First'),'FIRST_INIT'=>_('First Initial'),'LAST_NAME'=>_('Last'),'MIDDLE_NAME'=>_('Middle'),'NAME_SUFFIX'=>_('Suffix'),'CUSTOM_200000002'=>_('Common'),'STUDENT_ID'=>_('Centre ID'),'GRADE_ID'=>_('Grade'),'SCHOOL_ID'=>_('School'),'SCHOOL_NUMBER'=>_('School Number'),'NEXT_SCHOOL'=>_('Rolling / Retention Options'),'CALENDAR_ID'=>_('Calendar'),'USERNAME'=>_('Username'),'PASSWORD'=>_('Password'),'START_DATE'=>_('Enrollment Start Date'),'END_DATE'=>_('Enrollment End Date'),'ENROLLMENT_SHORT'=>_('Enrollment Code'),'DROP_SHORT'=>_('Drop Code'),'LAST_LOGIN'=>_('Last Login'));
		if(AllowUse('Students/Student.php&category_id=3'))
		{
			$fields_list['Address'] = array('ADDRESS'=>_('Address'),'MAIL_ADDRESS'=>_('Mailing Address'),'CITY'=>_('City'),'MAIL_CITY'=>_('Mailing City'),'STATE'=>_('State'),'MAIL_STATE'=>_('Mailing State'),'ZIPCODE'=>_('Zip Code'),'MAIL_ZIPCODE'=>_('Mailing Zipcode'),'PHONE'=>_('Home Phone'),'PARENTS'=>_('Contacts'));
			$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ADDRESS_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
			$address_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE,CATEGORY_ID FROM ADDRESS_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));

			foreach($categories_RET as $category)
			{
				foreach($address_RET[$category['ID']] as $field)
				{
					$fields_list['Address']['ADDRESS_'.$field['ID']] = str_replace("'",'&#39;',$field['TITLE']);
				}
			}
		}
		if($extra['field_names'])
			$fields_list['General'] += $extra['field_names'];
	}

	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
	$custom_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE,CATEGORY_ID FROM CUSTOM_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));

	foreach($categories_RET as $category)
	{
		if(AllowUse('Students/Student.php&category_id='.$category['ID']))
		{
			foreach($custom_RET[$category['ID']] as $field)
				$fields_list[$category['TITLE']]['CUSTOM_'.$field['ID']] = str_replace("'",'&#39;',$field['TITLE']);
		}
	}

	if($CentreModules['Food_Service'])
		$fields_list['Food_Service'] = array('FS_ACCOUNT_ID'=>_('Account ID'),'FS_DISCOUNT'=>_('Discount'),'FS_STATUS'=>_('Status'),'FS_BARCODE'=>_('Barcode'),'FS_BALANCE'=>_('Balance'));

	$fields_list['Schedule']['PERIOD_ATTENDANCE'] = _('Attendance Period Teacher').' - '._('Room');
	$periods_RET = DBGet(DBQuery("SELECT TITLE,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
	foreach($periods_RET as $period)
		$fields_list['Schedule']['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'].' '._('Teacher').' - '._('Room');

	DrawHeader('<OL><SPAN id=names_div></SPAN></OL>');
	echo '<TABLE><TR><TD valign=top>';
	echo '<BR>';
	PopTable('header',_('Fields'));
	echo '<TABLE><TR>';
	foreach($fields_list as $category=>$fields)
	{
		echo '<TD colspan=2><b>'.ParseMLField($category).'<BR><HR></b></TD></TR><TR>';
		if(ParseMLField($category,'default')=='Address')
		{
			echo '<TD colspan=2><TABLE width=100% bgcolor=#f8f8f9><TR>';
			echo '<TD><INPUT type=checkbox name=residence value=Y>'._('Residence').'</TD>';
			echo '<TD><INPUT type=checkbox name=mailing value=Y>'._('Mailing').'</TD>';
			echo '</TR><TR>';
			echo '<TD><INPUT type=checkbox name=bus_pickup value=Y>'._('Bus Pickup').'</TD>';
			echo '<TD><INPUT type=checkbox name=bus_dropoff value=Y>'._('Bus Dropoff').'</TD>';
			echo '</TR></TABLE></TD></TR><TR>';
		}
		foreach($fields as $field=>$title)
		{
			$i++;
            echo '<TD><INPUT type=checkbox onclick="addHTML(\'<LI>'.str_replace("'","\'",ParseMLField($title)).'</LI>\',\'names_div\',false);addHTML(\'<INPUT type=hidden name=fields['.$field.'] value=Y>\',\'fields_div\',false);this.disabled=true">'.ParseMLField($title);
			if(ParseMLField($category,'default')=='Address' && $field=='PARENTS')
			{
				$relations_RET = DBGet(DBQuery("SELECT DISTINCT STUDENT_RELATION FROM STUDENTS_JOIN_PEOPLE ORDER BY STUDENT_RELATION"));
				$select = '<SELECT name=relation><OPTION value="">'._('N/A');
				foreach($relations_RET as $relation)
					if($relation['STUDENT_RELATION']!='')
						$select .= '<OPTION value='.$relation['STUDENT_RELATION'].'>'.$relation['STUDENT_RELATION'];
					else
						$select .= '<OPTION value="!">'._('No Value');
				$select .= '</SELECT>';
				echo '<BR><TABLE width=100% bgcolor=#f8f8f9><TR><TD>'.$select.'<BR><small>'._('Relation').'</small></TD></TR></TABLE>';
			}
			echo '</TD>';
			if($i%2==0)
				echo '</TR><TR>';
		}
		if($i%2!=0)
		{
			echo '<TD></TD></TR><TR>';
			$i++;
		}
	}
	echo '</TR></TABLE>';
	PopTable('footer');
	echo '</TD><TD valign=top>';
	if($Search && function_exists($Search))
	 	$Search($extra);
	else
		Search('student_id',$extra);
	echo '</TD></TR></TABLE>';
}
?>
