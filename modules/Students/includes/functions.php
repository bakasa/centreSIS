<?php
// OTHER INFO
function _makeTextInput($column,$name,$size,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<FONT color=red>','</FONT>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<FONT color=red>','</FONT>') : array('','');
	}

	if($field['TYPE']=='numeric')
		$value[$column] = str_replace('.00','',$value[$column]);

	return TextInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size,$div);
}

function _makeDateInput($column,$name,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<FONT color=red>','</FONT>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<FONT color=red>','</FONT>') : array('','');
	}

	return DateInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$div);
}

function _makeSelectInput($column,$name,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<FONT color=red>','</FONT>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<FONT color=red>','</FONT>') : array('','');
	}

	$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
	$select_options = explode("\r",$field['SELECT_OPTIONS']);
	if(count($select_options))
	{
		foreach($select_options as $option)
			if($field['TYPE']=='codeds')
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			elseif($field['TYPE']=='exports')
			{
				$option = explode('|',$option);
				if($option[0]!='')
					$options[$option[0]] = $option[0];
			}
			else
				$options[$option] = $option;
	}

	$extra = 'style="max-width:250;"';
	return SelectInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$options,'N/A',$extra,$div);
}

function _makeAutoSelectInput($column,$name,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<FONT color=red>','</FONT>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && ($value[$column]=='' || $value[$column]=='---') ? array('<FONT color=red>','</FONT>') : array('','');
	}

	// build the select list...
	// get the standard selects
	if($field['SELECT_OPTIONS'])
	{
		$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
		$select_options = explode("\r",$field['SELECT_OPTIONS']);
	}
	else
		$select_options = array();
	if(count($select_options))
	{
		foreach($select_options as $option)
			if($option!='')
				$options[$option] = $option;
	}
	// add the 'new' option, is also the separator
	$options['---'] = '---';

	if($field['TYPE']=='autos' && AllowEdit()) // we don't really need the select list if we can't edit anyway
	{
		// add values found in current and previous year
		if($request=='values[ADDRESS]')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT a.CUSTOM_$field[ID],upper(a.CUSTOM_$field[ID]) AS SORT_KEY FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS s,STUDENT_ENROLLMENT sse WHERE a.ADDRESS_ID=sja.ADDRESS_ID AND s.STUDENT_ID=sja.STUDENT_ID AND sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND a.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		elseif($request=='values[PEOPLE]')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT p.CUSTOM_$field[ID],upper(p.CUSTOM_$field[ID]) AS SORT_KEY FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp,STUDENTS s,STUDENT_ENROLLMENT sse WHERE p.PERSON_ID=sjp.PERSON_ID AND s.STUDENT_ID=sjp.STUDENT_ID AND sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND p.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		else // students
			$options_RET = DBGet(DBQuery("SELECT DISTINCT s.CUSTOM_$field[ID],upper(s.CUSTOM_$field[ID]) AS SORT_KEY FROM STUDENTS s,STUDENT_ENROLLMENT sse WHERE sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND s.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		if(count($options_RET))
		{
			foreach($options_RET as $option)
				if($option['CUSTOM_'.$field['ID']]!='' && !$options[$option['CUSTOM_'.$field['ID']]])
					$options[$option['CUSTOM_'.$field['ID']]] = array($option['CUSTOM_'.$field['ID']],'<FONT color=blue>'.$option['CUSTOM_'.$field['ID']].'</FONT>');
		}
	}
	// make sure the current value is in the list
	if($value[$column]!='' && !$options[$value[$column]])
		$options[$value[$column]] = array($value[$column],'<FONT color='.($field['TYPE']=='autos'?'blue':'green').'>'.$value[$column].'</FONT>');

	if($value[$column]!='---' && count($options)>1)
	{
		$extra = 'style="max-width:250;"';
		return SelectInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$options,'N/A',$extra,$div);
	}
	else
		return TextInput($value[$column]=='---'?array('---','<FONT color=red>---</FONT>'):''.$value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size,$div);
}

function _makeCheckboxInput($column,$name,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
	}
	else
		$div = true;

	return CheckboxInput($value[$column],$request.'['.$column.']',$name,'',($_REQUEST['student_id']=='new'));
}

function _makeTextareaInput($column,$name,$request='students')
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
	}
	else
		$div = true;

	return TextAreaInput($value[$column],$request.'['.$column.']',$name,'',$div);
}

function _makeMultipleInput($column,$name,$request='students')
{	global $value,$field,$_CENTRE;

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
		$select_options = explode("\r",$field['SELECT_OPTIONS']);
		if(count($select_options))
		{
			foreach($select_options as $option)
				$options[$option] = $option;
		}

		if($value[$column]!='')
			echo '<DIV id=\'div'.$request.'['.$column.']\'><div onclick=\'javascript:addHTML("';
		echo '<TABLE border=0 cellpadding=3>';
		if(count($options)>12)
		{
			echo '<TR><TD colspan=2>';
			echo '<small><FONT color='.Preferences('TITLES').'>'.($value[$column]!=''?str_replace(array("'",'"'),array('&#39;','\"'),$name):$name).'</FONT></small>';
			if($value[$column]!='')
				echo '<TABLE width=100% height=7 style=\"border:1;border-style: solid solid none solid;\"><TR><TD></TD></TR></TABLE>';
			else
				echo '<TABLE width=100% height=7 style="border:1;border-style: solid solid none solid;"><TR><TD></TD></TR></TABLE>';

			echo '</TD></TR>';
		}
		echo '<TR>';
		$i = 0;
		foreach($options as $option)
		{
			if($i%2==0)
				echo '</TR><TR>';
			if($value[$column]!='')
				echo '<TD><INPUT type=checkbox name='.$request.'['.$column.'][] value=\"'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$option).'\"'.(strpos($value[$column],'||'.$option.'||')!==false?' CHECKED':'').'><small>'.str_replace(array("'",'"'),array('&#39;','\"'),$option).'</small></TD>';
			else
				echo '<TD><INPUT type=checkbox name='.$request.'['.$column.'][] value="'.str_replace('"','&quot;',$option).'"><small>'.$option.'</small></TD>';
			$i++;
		}
		echo '</TR><TR><TD colspan=2>';
		if($value[$column]!='')
			echo '<TABLE width=100% height=7 style=\"border:1;border-style: none solid solid solid;\"><TR><TD></TD></TR></TABLE>';
		else
			echo '<TABLE width=100% height=7 style="border:1;border-style: none solid solid solid;"><TR><TD></TD></TR></TABLE>';

		echo '</TD></TR></TABLE>';
		if($value[$column]!='')
		{
			echo '","div'.$request.'['.$column.']",true);\' >';
			echo '<span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.($value[$column]!=''?str_replace('||',', ',substr($value[$column],2,-2)):'-').'</span>';
			echo '</div></DIV>';
		}
	}
	else
		echo ($value[$column]!=''?str_replace('||',', ',substr($value[$column],2,-2)):'-').'<BR>';

	echo '<small><FONT color='.Preferences('TITLES').'>'.$name.'</FONT></small>';
}

// MEDICAL ----
function _makeType($value,$column)
{	global $THIS_RET;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return SelectInput($value,'values[STUDENT_MEDICAL]['.$THIS_RET['ID'].'][TYPE]','',array('Immunization'=>_('Immunization'),'Physical'=>_('Physical')));
}

function _makeDate($value,$column='MEDICAL_DATE')
{	global $THIS_RET,$table;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return DateInput($value,'values['.$table.']['.$THIS_RET['ID'].']['.$column.']');
}

function _makeComments($value,$column)
{	global $THIS_RET,$table;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return TextInput($value,'values['.$table.']['.$THIS_RET['ID'].']['.$column.']');
}

// ENROLLMENT
function _makeStartInput($value,$column)
{	global $THIS_RET,$add_codes;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	elseif($_REQUEST['student_id']=='new')
	{
		$id = 'new';
		$default = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS START_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$default = $default[1]['START_DATE'];
		if(!$default || DBDate('postgres')>$default)
			$default = DBDate();
		$value = $default;
	}
	else
	{
		$add = '<TD>'.button('add').'</TD>';
		$id = 'new';
	}

	if(!$add_codes)
	{
		$options_RET = DBGet(DBQuery("SELECT ID,TITLE AS TITLE FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' AND TYPE='Add' ORDER BY SORT_ORDER"));

		if($options_RET)
		{
			foreach($options_RET as $option)
				$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	if($_REQUEST['student_id']=='new')
		$div = false;
	else
		$div = true;

	return '<TABLE class=LO_field><TR>'.$add.'<TD>'.DateInput($value,'values[STUDENT_ENROLLMENT]['.$id.']['.$column.']','',$div,true).'</TD><TD> - </TD><TD>'.SelectInput($THIS_RET['ENROLLMENT_CODE'],'values[STUDENT_ENROLLMENT]['.$id.'][ENROLLMENT_CODE]','',$add_codes,'N/A','style="max-width:150;"').'</TD></TR></TABLE>';
}

function _makeEndInput($value,$column)
{	global $THIS_RET,$drop_codes;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if(!$drop_codes)
	{
		$options_RET = DBGet(DBQuery("SELECT ID,TITLE AS TITLE FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' AND TYPE='Drop' ORDER BY SORT_ORDER"));

		if($options_RET)
		{
			foreach($options_RET as $option)
				$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	return '<TABLE class=LO_field><TR><TD>'.DateInput($value,'values[STUDENT_ENROLLMENT]['.$id.']['.$column.']').'</TD><TD> - </TD><TD>'.SelectInput($THIS_RET['DROP_CODE'],'values[STUDENT_ENROLLMENT]['.$id.'][DROP_CODE]','',$drop_codes,_('N/A'),'style="max-width:150;"').'</TD></TR></TABLE>';
}

function _makeSchoolInput($value,$column)
{	global $THIS_RET,$schools;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if(!$schools)
		$schools = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."'"),array(),array('ID'));

	foreach($schools as $sid=>$school)
		$options[$sid] = $school[1]['TITLE'];

	// mab - allow school to be editted if illegal value
	if($_REQUEST['student_id']!='new')
		if($id!='new')
			if($schools[$value])
				return $schools[$value][1]['TITLE'];
			else
				return SelectInput($value,'values[STUDENT_ENROLLMENT]['.$id.'][SCHOOL_ID]','',$options);
		else
			return SelectInput(UserSchool(),'values[STUDENT_ENROLLMENT]['.$id.'][SCHOOL_ID]','',$options,false,'',false);
	else
		return $schools[UserSchool()][1]['TITLE'];
}
?>
