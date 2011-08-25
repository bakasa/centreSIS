<?php
// DebugBreak();

// Include vendor libraries
global $CentrePath;
require_once($CentrePath.'vendors/IXR_Library.inc.php');
require_once($CentrePath.'vendors/simplepie/simplepie.inc');
 
if(!UserSyear())
{
	$_SESSION['UserSyear'] = $DefaultSyear;
}

//while(!UserSyear())
//{
//	session_write_close();
//	session_start();
//}

$_CENTRE['HeaderIcon'] = 'centre.gif';
DrawHeader(Config('TITLE'),'Centre SIS v'.$CentreVersion);

DrawHeader('<FONT SIZE=+1><script language="javascript">
var currentTime = new Date();
var hours = currentTime.getHours();
if (hours < 12) document.write("'.sprintf(_('Good Morning, %s.'), User('NAME')).'");
else if (hours < 18) document.write("'.sprintf(_('Good Afternoon, %s.'), User('NAME')).'");
else document.write("'.sprintf(_('Good Evening, %s.'), User('NAME')).'");</script></FONT>');

$welcome = _('Welcome to the Centre School Information System!');
if($_SESSION['LAST_LOGIN'])
	$welcome .= '<BR>&nbsp;'.sprintf(_('Your last login was <b>%s</b>.'), ProperDate(substr($_SESSION['LAST_LOGIN'],0,10)).substr($_SESSION['LAST_LOGIN'],10));
if($_REQUEST['failed_login'])
	$welcome .= '<BR>&nbsp;<FONT color=red><b>'._('Warning!').'</b></FONT>&nbsp;'.sprintf(_('There have been <b>%d</b> failed login attempts since your last successful login.'),$_REQUEST['failed_login']);
switch (User('PROFILE'))
{
	case 'admin':
		DrawHeader($welcome.'<BR>&nbsp;'._('You are an <b>Administrator</b> on the system.<BR>').PHPCheck().versionCheck());

        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_nl2br'));

		if(count($notes_RET))
		{
			echo '<p>';
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SCHOOL'=>_('School')),_('Note'),_('Notes'),array(),array(),array('save'=>false,'search'=>false));
			echo '</p>';
		}

		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Dy') AS DAY,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s,STAFF st WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+6 AND ce.SYEAR='".UserSyear()."' AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||ce.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			echo '<p>';
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),_('Day With Upcoming Events'),_('Days With Upcoming Events'),array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
			echo '</p>';
		}

        RSSOutput(USER('PROFILE'));
        
		if(Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if missing attendances
		$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
		foreach($categories_RET as $category)
		{
			$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st WHERE acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID
            AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
		    AND sp.PERIOD_ID=cp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cp.DAYS)>0
				OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
			AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 AND s.ID=acc.SCHOOL_ID AND s.SYEAR=acc.SYEAR ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			if (count($RET))
			{
				echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.sprintf(Localize('colon',_('Teachers have missing <b>%s</b> attendance data')),$category['TITLE']);
				ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period - Teacher'),'SCHOOL'=>_('School')),sprintf(_('Teacher with missing %s data'),$category['TITLE']),sprintf(_('Teachers with missing %s data'),$category['TITLE']),array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
				echo '</p>';
			}
		}
		}

		if($CentreModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		    // warn if negative food service balance
		    $staff = DBGet(DBQuery('SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='.User('STAFF_ID')));
		    $staff = $staff[1];
		    if($staff['BALANCE'] && $staff['BALANCE']<0)
			    echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.sprintf(_('You have a <b>negative</b> food service balance of <font color=red>%d</font>'),$staff['BALANCE']).'</p>';

		    // warn if students with way low food service balances
		    $extra['SELECT'] = ',fssa.STATUS,fsa.BALANCE';
		    $extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		    $extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\'-10\'';
		    $_REQUEST['_search_all_schools'] = 'Y';
		    $RET = GetStuList($extra);
		    if (count($RET))
            {
			    echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.Localize('colon',_('Some students have food service balances below -$10.00'));
			    ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade'),'BALANCE'=>_('Balance')),_('Student'),_('Students'),array(),array(),array('save'=>false,'search'=>false));
			    echo '</p>';
  		    }
		}

		echo '<p>&nbsp;'._('Happy administrating...').'</p>';
	break;

	case 'teacher':
		DrawHeader($welcome.'<BR>&nbsp;'._('You are a <b>Teacher</b> on the system.'));

        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_nl2br'));

		if(count($notes_RET))
		{
			echo '<p>';
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SCHOOL'=>_('School')),_('Note'),_('Notes'),array(),array(),array('save'=>false,'search'=>false));
			echo '</p>';
		}

		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Dy') AS DAY,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+6 AND ce.SYEAR='".UserSyear()."' AND position(','||ce.SCHOOL_ID||',' IN (SELECT SCHOOLS FROM STAFF WHERE STAFF_ID='".User('STAFF_ID')."'))>0 AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			echo '<p>';
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),_('Day With Upcoming Events'),_('Days With Upcoming Events'),array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
			echo '</p>';
		}

        RSSOutput(USER('PROFILE'));

		if(Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if missing attendances
		$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
		foreach($categories_RET as $category)
		{
			$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp WHERE acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID AND cp.TEACHER_ID='".User('STAFF_ID')."'
            AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
			AND sp.PERIOD_ID=cp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cp.DAYS)>0
				OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
			AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			if (count($RET))
			{
				echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.sprintf(Localize('colon',_('You have missing <b>%s</b> attendance data')),$category['TITLE']);
				ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period - Teacher')),sprintf(_('Period with missing %s attendance data'),$category['TITLE']),sprintf(_('Periods with missing %s attendance data'),$category['TITLE']),array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
				echo '</p>';
			}
		}
		}

		if($CentreModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if negative food service balance
		$staff = DBGet(DBQuery('SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='.User('STAFF_ID')));
		$staff = $staff[1];
		if($staff['BALANCE'] && $staff['BALANCE']<0)
			echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.sprintf(_('You have a <b>negative</b> food service balance of <font color=red>%s</font>'),$staff['BALANCE']).'</p>';
		}

		echo '<p>&nbsp;'._('Happy teaching...').'</p>';
	break;

	case 'parent':
		DrawHeader($welcome.'<BR>&nbsp;'._('You are a <b>Parent</b> on the system.'));

        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_nl2br'));

		if(count($notes_RET))
		{
			echo '<p>';
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SCHOOL'=>_('School')),_('Note'),_('Notes'),array(),array(),array('save'=>false,'search'=>false));
			echo '</p>';
		}

		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Dy') AS DAY,ce.DESCRIPTION,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+6 AND ce.SYEAR='".UserSyear()."' AND ce.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=ce.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			echo '<p>';
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),_('Day With Upcoming Events'),_('Days With Upcoming Events'),array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
			echo '</p>';
		}

        RSSOutput(USER('PROFILE'));

		if($CentreModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if students with low food service balances
		$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,\'$\'||fsa.BALANCE AS BALANCE,\'$\'||16.5-fsa.BALANCE AS DEPOSIT';
		$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		$extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\'5\'';
		$extra['ASSOCIATED'] = User('STAFF_ID');
		$RET = GetStuList($extra);
		if (count($RET))
		{
			echo '<p><font color=red><b>'._('Warning!').'</b></font> - '._('You have students with food service balance below $5.00 - please deposit at least the Minimum Deposit into you children\'s accounts.');
			ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade'),'ACCOUNT_ID'=>_('Account ID'),'BALANCE'=>_('Balance'),'DEPOSIT'=>_('Minimum Deposit')),_('Student'),_('Students'),array(),array(),array('save'=>false,'search'=>false));
			echo '</p>';
		}

		// warn if negative food service balance
		$staff = DBGet(DBQuery('SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='.User('STAFF_ID')));
		$staff = $staff[1];
		if($staff['BALANCE'] && $staff['BALANCE']<0)
			echo '<p><font color=red><b>'._('Warning!').'</b></font> - '.sprintf(_('You have a <b>negative</b> food service balance of <font color=red>%s</font>'),$staff['BALANCE']).'</p>';
		}

		echo '<p>&nbsp;'._('Happy parenting...').'</p>';
	break;

	case 'student':
		DrawHeader($welcome.'<BR>&nbsp;'._('You are a <b>Student</b> on the system.'));

        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND  position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_nl2br'));

		if(count($notes_RET))
		{
			echo '<p>';
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note')),_('Note'),_('Notes'),array(),array(),array('save'=>false,'search'=>false));
			echo '</p>';
		}

		$events_RET = DBGet(DBQuery("SELECT TITLE,SCHOOL_DATE,to_char(SCHOOL_DATE,'Dy') AS DAY,DESCRIPTION FROM CALENDAR_EVENTS WHERE SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+6 AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array('SCHOOL_DATE'=>'ProperDate'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			echo '<p>';
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description')),_('Day With Upcoming Events'),_('Days With Upcoming Events'),array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
			echo '</p>';
		}

        RSSOutput(USER('PROFILE'));

		echo '<p>&nbsp;'._('Happy learning...').'</p>';
	break;
}

function _nl2br($value,$column)
{
 	return nl2br($value);
}

function PHPCheck() {
    $ret = '';
    if ((bool)ini_get('safe_mode'))
       $ret .= '&nbsp;WARNING: safe_mode is set to On in your PHP configuration.<br />';
    if (strpos('passthru',ini_get('disable_functions'))!==false)
       $ret .= '&nbsp;WARNING: passthru is disabled in your PHP configuration.<br />';
    return $ret;
}

function versionCheck() {
    global $CentreVersion, $CentreInstallKey;

    $versionString = 'core:'.$CentreVersion;
    $client = new IXR_Client('http://go.centresis.org/xmlrpc.php');
    if (!$client->query('version.check', array($CentreInstallKey, $versionString))) {
        return('&nbsp;<strong>An error occurred - '.$client->getErrorCode().":".$client->getErrorMessage().'</strong>');
    }
    return '&nbsp;'.$client->getResponse();
}

function RSSOutput($profile) {
    if (!in_array($profile,array('admin', 'teacher', 'parent', 'student'))) return;
    $feed = new SimplePie('http://www.centresis.org/index.php/portal-'.$profile.'?format=feed&type=rss');
    $feed->handle_content_type();

    $i=1;   
    $items_RET = array();
    foreach ($feed->get_items() as $item) {
        $items_RET[$i++] = array('DATE'=>$item->get_date('n/j/y'),'TITLE'=>$item->get_title(),'DESCRIPTION'=>$item->get_description());
        if ($i==5) break;
    }
    if (!empty($items_RET))
        ListOutput($items_RET,array('DATE'=>_('Date'),'TITLE'=>_('Title'),'DESCRIPTION'=>_('Description')),$feed->get_title(),$feed->get_title(),array(),array(),array('centre'=>true,'save'=>false,'search'=>false));
}
?>
