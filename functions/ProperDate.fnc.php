<?php

/*
Outputs a pretty date when sent an oracle or postgres date.
*/

function ProperDate($date='',$length='long')
{
	if($date)
	{
	if(strlen($date)==9) // ORACLE
	{
		$months_number = array('JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12');
		$year = substr($date,7,2);
		$year = ($year<50?'20':'19').$year;
		$month = $months_number[strtoupper(substr($date,3,3))];
		$day = substr($date,0,2);
	}
	elseif(strlen($date)==10) // POSTGRES
	{
		$year = substr($date,0,4);
		$month = substr($date,5,2);
		$day = substr($date,8,2);
	}
	else //strlen($date)==11 ORACLE with 4-digit year
	{
		$months_number = array('JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12');
		$year = substr($date,7,4);
		$day = substr($date,0,2);
		$month = $months_number[strtoupper(substr($date,3,3))];
	}
	$comment = '<!-- '.$year.$month.$day.' -->';

	if($_REQUEST['_CENTRE_PDF'] && $_REQUEST['LO_save'] && Preferences('E_DATE')=='MM/DD/YYYY')
		return $comment.$month.'/'.$day.'/'.$year;

	if((Preferences('MONTH')=='m' || Preferences('MONTH')=='M') && (Preferences('DAY')=='j' || Preferences('DAY')=='d') && Preferences('YEAR'))
		$sep = '/';
	else
		$sep = ' ';

	return $comment.date((($length=='long' || Preferences('MONTH')!='F')?Preferences('MONTH'):'M').$sep.Preferences('DAY').$sep.Preferences('YEAR'),mktime(0,0,0,$month+0,$day+0,$year+0));
	}
}

function ShortDate($date='',$column)
{
	return ProperDate($date,'short');
}
?>