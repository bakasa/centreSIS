<?php

function GetSchool($sch,$name='TITLE')
{	global $_CENTRE;

	if(!$_CENTRE['GetSchool'])
	{
		$QI=DBQuery("SELECT ID,TITLE,SCHOOL_NUMBER FROM SCHOOLS WHERE SYEAR='".UserSyear()."'");
		$_CENTRE['GetSchool'] = DBGet($QI,array(),array('ID'));
	}

	if($name=='TITLE' || $name=='SCHOOL_ID')
		if($_CENTRE['GetSchool'][$sch])
			return $_CENTRE['GetSchool'][$sch][1]['TITLE'];
		else
			return $sch;
	else
		return $_CENTRE['GetSchool'][$sch][1][$name];
}
?>
