<?php

function ProgramTitle($modname='')
{	global $_CENTRE;

	if(!$modname)
		$modname = $_REQUEST['modname'];
	if(!$_CENTRE['Menu'])
	{
		global $CentreModules;
		include 'Menu.php';
	}
	foreach($_CENTRE['Menu'] as $modcat=>$programs)
	{
		if(count($programs))
		{
			foreach($programs as $program=>$title)
			{
				if($modname==$program)
				{
					if($_CENTRE['HeaderIcon']!==false)
						if(substr($modname,0,25)=='Users/TeacherPrograms.php')
							$_CENTRE['HeaderIcon'] = substr($modname,34,strpos($modname,'/',34)-34).'.gif';
						else
							$_CENTRE['HeaderIcon'] = $modcat.'.gif';
					return $title;
				}
			}
		}
	}
	if($_CENTRE['HeaderIcon']!==false)
		unset($_CENTRE['HeaderIcon']);
	return 'Centre';
}
?>
