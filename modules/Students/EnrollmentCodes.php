<?php
if($_REQUEST['values'] && $_POST['values'])
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
		if($id!='new')
		{
			$sql = "UPDATE STUDENT_ENROLLMENT_CODES SET ";

			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".str_replace("\'","''",$value)."',";
			}
			$sql = substr($sql,0,-1) . " WHERE ID='$id'";
			DBQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO STUDENT_ENROLLMENT_CODES ";

			$fields = 'ID,SYEAR,';
			$values = db_seq_nextval('STUDENT_ENROLLMENT_CODES_SEQ').",'".UserSyear()."',";

			$go = 0;
			foreach($columns as $column=>$value)
			{
				if($value)
				{
					$fields .= $column.',';
					$values .= "'".str_replace("\'","''",$value)."',";
					$go = true;
				}
			}
			$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';

			if($go)
				DBQuery($sql);
		}
	}
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove')
{
	if(DeletePrompt('enrollment code'))
	{
		DBQuery("DELETE FROM STUDENT_ENROLLMENT_CODES WHERE ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,TITLE,SHORT_NAME,TYPE,DEFAULT_CODE,SORT_ORDER FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$codes_RET = DBGet($QI,array('TITLE'=>'makeTextInput','SHORT_NAME'=>'makeTextInput','TYPE'=>'makeSelectInput','DEFAULT_CODE'=>'makeCheckBoxInput','SORT_ORDER'=>'makeTextInput'));

	$columns = array('TITLE'=>_('Title'),'SHORT_NAME'=>_('Short Name'),'TYPE'=>_('Type'),'DEFAULT_CODE'=>_('Rollover Default'),'SORT_ORDER'=>_('Sort Order'));
	$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'SHORT_NAME'=>makeTextInput('','SHORT_NAME'),'TYPE'=>makeSelectInput('','TYPE'),'DEFAULT_CODE'=>makeCheckBoxInput('','DEFAULT_CODE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>_('ID'));

	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=update method=POST>";
	DrawHeader('',SubmitButton(_('Save')));
	ListOutput($codes_RET,$columns,_('Enrollment Code'),_('Enrollment Codes'),$link);
	echo '<CENTER>'.SubmitButton(_('Save')).'</CENTER>';
	echo '</FORM>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='SHORT_NAME')
		$extra = 'size=5 maxlength=10';
	elseif($name=='SORT_ORDER')
		$extra = 'size=5 maxlength=10';

	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function makeSelectInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='TYPE')
		$options = array('Add'=>_('Add'),'Drop'=>_('Drop'));

	return SelectInput($value,'values['.$id.']['.$name.']','',$options);
}

function makeCheckBoxInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	return CheckboxInput($value,'values['.$id.']['.$name.']','','',($id=='new'));
}
?>
