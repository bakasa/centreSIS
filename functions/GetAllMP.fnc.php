<?php

function GetAllMP($mp,$marking_period_id='0')
{	global $_CENTRE;

	if($marking_period_id==0)
	{
		// there should be exactly one fy marking period
		$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$marking_period_id = $RET[1]['MARKING_PERIOD_ID'];
		$mp = 'FY';
	}
	elseif(!$mp)
		$mp = GetMP($marking_period_id,'MP');

	if(!$_CENTRE['GetAllMP'][$mp])
	{
		switch($mp)
		{
			case 'PRO':
				// there should be exactly one fy marking period
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				$fy = $RET[1]['MARKING_PERIOD_ID'];

				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				foreach($RET as $value)
				{
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] = "'$fy','$value[PARENT_ID]','$value[MARKING_PERIOD_ID]'";
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] .= ','.GetChildrenMP($mp,$value['MARKING_PERIOD_ID']);
					if(substr($_CENTRE['GetAllMP'][$mp][$marking_period_id],-1)==',')
						$_CENTRE['GetAllMP'][$mp][$marking_period_id] = substr($_CENTRE['GetAllMP'][$mp][$value['MARKING_PERIOD_ID']],0,-1);
				}
			break;

			case 'QTR':
				// there should be exactly one fy marking period
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				$fy = $RET[1]['MARKING_PERIOD_ID'];

				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				foreach($RET as $value)
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] = "'$fy','$value[PARENT_ID]','$value[MARKING_PERIOD_ID]'";
			break;

			case 'SEM':
				// there should be exactly one fy marking period
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				$fy = $RET[1]['MARKING_PERIOD_ID'];

				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('PARENT_ID'));
				foreach($RET as $sem=>$value)
				{
					$_CENTRE['GetAllMP'][$mp][$sem] = "'$fy','$sem'";
					foreach($value as $qtr)
						$_CENTRE['GetAllMP'][$mp][$sem] .= ",'$qtr[MARKING_PERIOD_ID]'";
				}
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS s WHERE MP='SEM' AND NOT EXISTS (SELECT '' FROM SCHOOL_MARKING_PERIODS q WHERE q.MP='QTR' AND q.PARENT_ID=s.MARKING_PERIOD_ID) AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				foreach($RET as $value)
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] = "'$fy','$value[MARKING_PERIOD_ID]'";
			break;

			case 'FY':
				// there should be exactly one fy marking period which better be $marking_period_id
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('PARENT_ID'));
				$_CENTRE['GetAllMP'][$mp][$marking_period_id] = "'$marking_period_id'";
				foreach($RET as $sem=>$value)
				{
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] .= ",'$sem'";
					foreach($value as $qtr)
						$_CENTRE['GetAllMP'][$mp][$marking_period_id] .= ",'$qtr[MARKING_PERIOD_ID]'";
				}
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS s WHERE MP='SEM' AND NOT EXISTS (SELECT '' FROM SCHOOL_MARKING_PERIODS q WHERE q.MP='QTR' AND q.PARENT_ID=s.MARKING_PERIOD_ID) AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
				foreach($RET as $value)
					$_CENTRE['GetAllMP'][$mp][$marking_period_id] .= ",'$value[MARKING_PERIOD_ID]'";
			break;
		}
	}

	return $_CENTRE['GetAllMP'][$mp][$marking_period_id];
}

function GetParentMP($mp,$marking_period_id='0')
{	global $_CENTRE;

	if(!$_CENTRE['GetParentMP'][$mp])
	{
		switch($mp)
		{
			case 'QTR':

			break;

			case 'SEM':
				$_CENTRE['GetParentMP'][$mp] = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('MARKING_PERIOD_ID'));
			break;

			case 'FY':
				$_CENTRE['GetParentMP'][$mp] = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('MARKING_PERIOD_ID'));
			break;
		}
	}

	return $_CENTRE['GetParentMP'][$mp][$marking_period_id][1]['PARENT_ID'];
}

function GetChildrenMP($mp,$marking_period_id='0')
{	global $_CENTRE;

	switch($mp)
	{
		case 'FY':
			if(!$_CENTRE['GetChildrenMP']['FY'])
			{
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('PARENT_ID'));
				foreach($RET as $sem=>$value)
				{
					$_CENTRE['GetChildrenMP'][$mp]['0'] .= ",'$sem'";
					foreach($value as $qtr)
						$_CENTRE['GetChildrenMP'][$mp]['0'] .= ",'$qtr[MARKING_PERIOD_ID]'";
				}
				$_CENTRE['GetChildrenMP'][$mp]['0'] = substr($_CENTRE['GetChildrenMP'][$mp]['0'],1);
			}
			return $_CENTRE['GetChildrenMP'][$mp]['0'];
		break;

		case 'SEM':
			if(GetMP($marking_period_id,'MP')=='QTR')
				$marking_period_id = GetParentMP('SEM',$marking_period_id);
			if(!$_CENTRE['GetChildrenMP']['SEM'])
			{
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('PARENT_ID'));
				foreach($RET as $sem=>$value)
				{
					foreach($value as $qtr)
						$_CENTRE['GetChildrenMP'][$mp][$sem] .= ",'$qtr[MARKING_PERIOD_ID]'";
					$_CENTRE['GetChildrenMP'][$mp][$sem] = substr($_CENTRE['GetChildrenMP'][$mp][$sem],1);
				}
			}
			return $_CENTRE['GetChildrenMP'][$mp][$marking_period_id];
		break;

		case 'QTR':
			return "'".$marking_period_id."'";
		break;

		case 'PRO':
			if(!$_CENTRE['GetChildrenMP']['PRO'])
			{
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='PRO' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('PARENT_ID'));
				foreach($RET as $qtr=>$value)
				{
					foreach($value as $pro)
						$_CENTRE['GetChildrenMP'][$mp][$qtr] .= ",'$pro[MARKING_PERIOD_ID]'";
					$_CENTRE['GetChildrenMP'][$mp][$qtr] = substr($_CENTRE['GetChildrenMP'][$mp][$qtr],1);
				}
			}
			return $_CENTRE['GetChildrenMP'][$mp][$marking_period_id];
		break;
	}
}
?>
