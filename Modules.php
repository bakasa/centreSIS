<?php
//error_reporting(1);
error_reporting(E_ERROR);
$start_time = time();
include 'Warehouse.php';
if(!get_magic_quotes_gpc())
	array_rwalk($_REQUEST,'addslashes');
array_rwalk($_REQUEST,'strip_tags');

if(!isset($_REQUEST['_CENTRE_PDF']))
{
	Warehouse('header');

	//if(strpos($_REQUEST['modname'],'misc/')===false && $_REQUEST['modname']!='Students/Student.php' && $_REQUEST['modname']!='School_Setup/Calendar.php' && $_REQUEST['modname']!='Scheduling/Schedule.php' && $_REQUEST['modname']!='Attendance/Percent.php' && $_REQUEST['modname']!='Attendance/Percent.php?list_by_day=true' && $_REQUEST['modname']!='Scheduling/MassRequests.php' && $_REQUEST['modname']!='Scheduling/MassSchedule.php' && $_REQUEST['modname']!='Student_Billing/Fees.php')
	if(strpos($_REQUEST['modname'],'misc/')===false)
		echo '<script language="JavaScript">if(window == top  && (!window.opener || window.opener.location.href.substring(0,(window.opener.location.href.indexOf("&")!=-1?window.opener.location.href.indexOf("&"):window.opener.location.href.replace("#","").length))!=window.location.href.substring(0,(window.location.href.indexOf("&")!=-1?window.location.href.indexOf("&"):window.location.href.replace("#","").length)))) window.location.href = "index.php";</script>';
	echo "<BODY marginwidth=0 leftmargin=0 border=0 onload='doOnload();' background=assets/bg.gif>";
	echo '<DIV id="Migoicons" style="visibility:hidden;position:absolute;z-index:1000;top:-100"></DIV><SCRIPT language="JavaScript1.2"  type="text/javascript">var TipId="Migoicons";var FiltersEnabled = 1;mig_clay();</SCRIPT>';
	echo "<TABLE width=100% height=100% border=0 cellpadding=0><TR><TD valign=top>";
}

if($_REQUEST['modname'])
{
	if(isset($_REQUEST['_CENTRE_PDF']) && $_REQUEST['_CENTRE_PDF']=='true')
		ob_start();
	if(strpos($_REQUEST['modname'],'?')!==false)
	{
		$modname = substr($_REQUEST['modname'],0,strpos($_REQUEST['modname'],'?'));
		$vars = substr($_REQUEST['modname'],(strpos($_REQUEST['modname'],'?')+1));

		$vars = explode('?',$vars);
		foreach($vars as $code)
		{
			$code = explode('=',$code);
			$_REQUEST[$code[0]] = $code[1];
		}
	}
	else
		$modname = $_REQUEST['modname'];

	if(isset($_REQUEST['LO_save']) && !$_REQUEST['LO_save'] && !isset($_REQUEST['_CENTRE_PDF']) && (strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
		$_SESSION['_REQUEST_vars'] = $_REQUEST;

	$allowed = false;
	include 'Menu.php';
	foreach($_CENTRE['Menu'] as $modcat=>$programs)
	{
		if($_REQUEST['modname']==$modcat.'/Search.php')
		{
			$allowed = true;
			break;
		}
		else if ($_REQUEST['modname'] == 'Students/Upload.php')
		{
			$allowed = true;
			break;
		}
		
		foreach($programs as $program=>$title)
		{
			if($_REQUEST['modname']==$program)
			{
				$allowed = true;
				break;
			}
		}
	}
	if(substr($_REQUEST['modname'],0,5)=='misc/')
		$allowed = true;

	if($allowed)
	{
		if(Preferences('SEARCH')!='Y')
			$_REQUEST['search_modfunc'] = 'list';
		include('modules/'.$modname);
	}
	else
	{
		if(User('USERNAME'))
		{
			echo _('You\'re not allowed to use this program!').' '._('This attempted violation has been logged and your IP address was captured.');
			Warehouse('footer');
			if($CentreNotifyAddress)
				mail($CentreNotifyAddress,'HACKING ATTEMPT',"INSERT INTO HACKING_LOG (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME,MODNAME,USERNAME) values('$_SERVER[SERVER_NAME]','$_SERVER[REMOTE_ADDR]','".date('Y-m-d')."','$CentreVersion','$_SERVER[PHP_SELF]','$_SERVER[DOCUMENT_ROOT]','$_SERVER[SCRIPT_NAME]','$_REQUEST[modname]','".User('USERNAME')."')");
		}
		exit;
	}

	if(isset($_SESSION['unset_student']))
	{
		unset($_SESSION['unset_student']);
		//unset($_SESSION['staff_id']); // mab 070704 why is this here
	}
}


if(!isset($_REQUEST['_CENTRE_PDF']))
{
	echo '</TD></TR></TABLE>';
	
	if (isset($_CENTRE['PrepareDate']))
	{
		for($i=1;$i<=$_CENTRE['PrepareDate'];$i++)
		{
			echo '<script type="text/javascript">
		Calendar.setup({
		    monthField     :    "monthSelect'.$i.'",
		    dayField       :    "daySelect'.$i.'",
		    yearField      :    "yearSelect'.$i.'",
		    ifFormat       :    "%d-%b-%y",
		    button         :    "trigger'.$i.'",
		    align          :    "Tl",
		    singleClick    :    true
		});
	</script>';
		}
	}
	
	echo '</BODY>';
	echo '</HTML>';
}
?>
