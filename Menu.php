<?php
if(!isset($_CENTRE['Menu']))
{
	foreach($CentreModules as $module=>$include)
		if($include)
			include "modules/$module/Menu.php";

	$profile = User('PROFILE');

	if($profile!='student')
		if(User('PROFILE_ID'))
			$_CENTRE['AllowUse'] = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
		else
			$_CENTRE['AllowUse'] = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
	else
	{
		$_CENTRE['AllowUse'] = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='0' AND CAN_USE='Y'"),array(),array('MODNAME'));
		$profile = 'parent';
	}

	foreach($menu as $modcat=>$profiles)
	{
		$programs = $profiles[$profile];
		foreach($programs as $program=>$title)
		{
			if(!is_numeric($program))
			{
				if($_CENTRE['AllowUse'][$program] && ($profile!='admin' || !isset($exceptions[$modcat][$program]) || AllowEdit($program)))
					$_CENTRE['Menu'][$modcat][$program] = $title;
			}
			else
				$_CENTRE['Menu'][$modcat][$program] = $title;
		}
	}

	if(User('PROFILE')=='student')
		unset($_CENTRE['Menu']['Users']);
}
?>
