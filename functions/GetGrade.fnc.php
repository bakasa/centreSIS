<?php

function GetGrade($grade,$column='TITLE')
{	global $_CENTRE;

	if($column!='TITLE' && $column!='SHORT_NAME' && $column!='SORT_ORDER' && $column!='NEXT_GRADE_ID')
		$column = 'TITLE';

	if(!$_CENTRE['GetGrade'])
	{
		$QI=DBQuery("SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID FROM SCHOOL_GRADELEVELS");
		$_CENTRE['GetGrade'] = DBGet($QI,array(),array('ID'));
	}
	if($column=='TITLE')
		$extra = '<!-- '.$_CENTRE['GetGrade'][$grade][1]['SORT_ORDER'].' -->';

	return $extra.$_CENTRE['GetGrade'][$grade][1][$column];
}
?>