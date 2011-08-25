<?php

function DeCodeds($value,$column)
{	global $_CENTRE;

	$field = explode('_',$column);

	if(!$_CENTRE['DeCodeds'][$column])
	{
		$RET = DBGet(DBQuery("SELECT TYPE,SELECT_OPTIONS FROM $field[0]_FIELDS WHERE ID='$field[1]'"));
		if($RET[1]['TYPE']=='codeds' || $RET[1]['TYPE']=='exports')
		{
			$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$RET[1]['SELECT_OPTIONS']));
			$select_options = explode("\r",$select_options);
			foreach($select_options as $option)
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			$RET[1]['SELECT_OPTIONS'] = $options;
			$_CENTRE['DeCodeds'][$column] = $RET[1];
		}
		else
			$_CENTRE['DeCodeds'][$column] = true;
	}

	if($_CENTRE['DeCodeds'][$column]['TYPE']=='codeds')
	{
	if($value!='')
		if($_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_CENTRE_PDF'] && $_REQUEST['LO_save'] && Preferences('E_CODEDS')=='Y')
				return $value;
			else
				return $_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
		else
			return '<FONT color=red>'.$value.'</FONT>';
	else
		return '';
	}
	elseif($_CENTRE['DeCodeds'][$column]['TYPE']=='exports')
	{
	if($value!='')
	{
		if($_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_CENTRE_PDF'] && $_REQUEST['LO_save'] && Preferences('E_EXPORTS')!='Y')
				return $_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
			else
				return $value;
		else
			return '<FONT color=red>'.$value.'</FONT>';
	}
	else
		return '';
	}
}

function StaffDeCodeds($value,$column)
{	global $_CENTRE;

	$field = explode('_',$column);

	if(!$_CENTRE['DeCodeds'][$column])
	{
		$RET = DBGet(DBQuery("SELECT TYPE,SELECT_OPTIONS FROM STAFF_FIELDS WHERE ID='$field[1]'"));
		if($RET[1]['TYPE']=='codeds' || $RET[1]['TYPE']=='exports')
		{
			$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$RET[1]['SELECT_OPTIONS']));
			$select_options = explode("\r",$select_options);
			foreach($select_options as $option)
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			$RET[1]['SELECT_OPTIONS'] = $options;
			$_CENTRE['DeCodeds'][$column] = $RET[1];
		}
		else
			$_CENTRE['DeCodeds'][$column] = true;
	}

	if($_CENTRE['DeCodeds'][$column]['TYPE']=='codeds')
	{
	if($value!='')
		if($_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			return $_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
		else
			return '<FONT color=red>'.$value.'</FONT>';
	else
		return '';
	}
	elseif($_CENTRE['DeCodeds'][$column]['TYPE']=='exports')
	{
	if($value!='')
	{
		if($_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_CENTRE_PDF'] && $_REQUEST['LO_save'] && Preferences('E_EXPORTS')!='Y')
				return $_CENTRE['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
			else
				return $value;
		else
			return '<FONT color=red>'.$value.'</FONT>';
	}
	else
		return '';
	}
}
?>
