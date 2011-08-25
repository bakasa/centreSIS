<?php
DrawHeader(_('Gradebook').' - '.ProgramTitle());
/*
$course_id = DBGet(DBQuery("SELECT COURSE_ID,COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE TEACHER_ID='".User('STAFF_ID')."' AND PERIOD_ID='".UserPeriod()."' AND MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).')'));
$course_period_id = $course_id[1]['COURSE_PERIOD_ID'];
$course_id = $course_id[1]['COURSE_ID'];
*/
$course_id = DBGet(DBQuery("SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
$course_id = $course_id[1]['COURSE_ID'];

$_CENTRE['allow_edit'] = true;
unset($_SESSION['_REQUEST_vars']['assignment_type_id']);
unset($_SESSION['_REQUEST_vars']['assignment_id']);

if($_REQUEST['day_tables'] && $_POST['day_tables'])
{
	foreach($_REQUEST['day_tables'] as $id=>$values)
	{
		if($_REQUEST['day_tables'][$id]['DUE_DATE'] && $_REQUEST['month_tables'][$id]['DUE_DATE'] && $_REQUEST['year_tables'][$id]['DUE_DATE'])
			$_REQUEST['tables'][$id]['DUE_DATE'] = $_REQUEST['day_tables'][$id]['DUE_DATE'].'-'.$_REQUEST['month_tables'][$id]['DUE_DATE'].'-'.$_REQUEST['year_tables'][$id]['DUE_DATE'];
		if($_REQUEST['day_tables'][$id]['ASSIGNED_DATE'] && $_REQUEST['month_tables'][$id]['ASSIGNED_DATE'] && $_REQUEST['year_tables'][$id]['ASSIGNED_DATE'])
			$_REQUEST['tables'][$id]['ASSIGNED_DATE'] = $_REQUEST['day_tables'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['month_tables'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['year_tables'][$id]['ASSIGNED_DATE'];
	}
	$_POST['tables'] = $_REQUEST['tables'];
}

if($_REQUEST['tables'] && $_POST['tables'])
{
	$table = $_REQUEST['table'];
	foreach($_REQUEST['tables'] as $id=>$columns)
	{
		if($id!='new')
		{
			if($columns['ASSIGNMENT_TYPE_ID'] && $columns['ASSIGNMENT_TYPE_ID']!=$_REQUEST['assignment_type_id'])
				$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];

			$sql = "UPDATE $table SET ";

			//if(!$columns['COURSE_ID'] && $table=='GRADEBOOK_ASSIGNMENTS')
			//	$columns['COURSE_ID'] = 'N';

			foreach($columns as $column=>$value)
			{
				if($column=='DUE_DATE' || $column=='ASSIGNED_DATE')
		 		{
					if(!VerifyDate($value))
			 			BackPrompt(_('Some dates were not entered correctly.'));
				}
				elseif($column=='COURSE_ID' && $value=='Y' && $table=='GRADEBOOK_ASSIGNMENTS')
				{
					$value = $course_id;
					$sql .= 'COURSE_PERIOD_ID=NULL,';
				}
				elseif($column=='COURSE_ID' && $table=='GRADEBOOK_ASSIGNMENTS')
				{
					$column = 'COURSE_PERIOD_ID';
					$value = UserCoursePeriod();
					$sql .= 'COURSE_ID=NULL,';
				}
				elseif($column=='FINAL_GRADE_PERCENT' && $table=='GRADEBOOK_ASSIGNMENT_TYPES')
					$value = ereg_replace('[^0-9.]','',$value) / 100;


				$sql .= $column."='".str_replace("\'","''",$value)."',";
			}
			$sql = substr($sql,0,-1) . " WHERE ".substr($table,10,-1)."_ID='$id'";
			$go = true;
		}
		else
		{
			$sql = "INSERT INTO $table ";

			if($table=='GRADEBOOK_ASSIGNMENTS')
			{
				if($columns['ASSIGNMENT_TYPE_ID'])
				{
					$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];
					unset($columns['ASSIGNMENT_TYPE_ID']);
				}
				$id = DBGet(DBQuery("SELECT ".db_seq_nextval('GRADEBOOK_ASSIGNMENTS_SEQ').' AS ID '.FROM_DUAL));
				$id = $id[1]['ID'];
				$fields = "ASSIGNMENT_ID,ASSIGNMENT_TYPE_ID,STAFF_ID,MARKING_PERIOD_ID,";
				$values = $id.",'".$_REQUEST['assignment_type_id']."','".User('STAFF_ID')."','".UserMP()."',";
				$_REQUEST['assignment_id'] = $id;
			}
			elseif($table=='GRADEBOOK_ASSIGNMENT_TYPES')
			{
				$id = DBGet(DBQuery("SELECT ".db_seq_nextval('GRADEBOOK_ASSIGNMENT_TYPES_SEQ').' AS ID '.FROM_DUAL));
				$id = $id[1]['ID'];
				$fields = "ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_ID,";
				$values = $id.",'".User('STAFF_ID')."','$course_id',";
				$_REQUEST['assignment_type_id'] = $id;
			}

			$go = false;

			if(!$columns['COURSE_ID'] && $_REQUEST['table']=='GRADEBOOK_ASSIGNMENTS')
				$columns['COURSE_ID'] = 'N';

			foreach($columns as $column=>$value)
			{
				if($column=='DUE_DATE' || $column=='ASSIGNED_DATE')
		 		{
					if(!VerifyDate($value))
			 			BackPrompt(_('Some dates were not entered correctly.'));
				}
				elseif($column=='COURSE_ID' && $value=='Y')
					$value = $course_id;
				elseif($column=='COURSE_ID')
				{
					$column = 'COURSE_PERIOD_ID';
					$value = UserCoursePeriod();
				}
				elseif($column=='FINAL_GRADE_PERCENT' && $table=='GRADEBOOK_ASSIGNMENT_TYPES')
					$value = ereg_replace('[^0-9.]','',$value) / 100;

				if($value!='')
				{
					$fields .= $column.',';
					$values .= "'".str_replace("\'","''",$value)."',";
					$go = true;
				}
			}
			$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';
		}

		if($go)
			DBQuery($sql);
	}
	unset($_REQUEST['tables']);
	unset($_SESSION['_REQUEST_vars']['tables']);
}

if($_REQUEST['modfunc']=='delete')
{
	if($_REQUEST['assignment_id'])
	{
		$table = 'assignment';
		$sql = "DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'";
	}
	else
	{
		$table = 'assignment type';
		$sql = "DELETE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'";
	}

	if(DeletePrompt($table))
	{
		DBQuery($sql);
		if(!$_REQUEST['assignment_id'])
		{
			$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'"));
			if(count($assignments_RET))
			{
				foreach($assignments_RET as $assignment_id)
					DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='".$assignment_id['ASSIGNMENT_ID']."'");
			}
			DBQuery("DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'");
			unset($_REQUEST['assignment_type_id']);
		}
		else
		{
			DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'");
			unset($_REQUEST['assignment_id']);
		}
		unset($_REQUEST['modfunc']);
	}
}

if(!$_REQUEST['modfunc'])
{
	// ASSIGNMENT TYPES
	$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,SORT_ORDER FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$types_RET = DBGet($QI);

	if($_REQUEST['assignment_id']!='new' && $_REQUEST['assignment_type_id']!='new')
		$delete_button = "<INPUT type=button value=Delete onClick='javascript:window.location=\"Modules.php?modname=$_REQUEST[modname]&modfunc=delete&assignment_type_id=$_REQUEST[assignment_type_id]&assignment_id=$_REQUEST[assignment_id]\"'>";

	// ADDING & EDITING FORM
	if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='new')
	{
		$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,ASSIGNED_DATE,DUE_DATE,POINTS,COURSE_ID,DESCRIPTION,
				CASE WHEN DUE_DATE<ASSIGNED_DATE THEN 'Y' ELSE NULL END AS DATE_ERROR,
				CASE WHEN ASSIGNED_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') THEN 'Y' ELSE NULL END AS ASSIGNED_ERROR,
				CASE WHEN DUE_DATE>(SELECT END_DATE+1 FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') THEN 'Y' ELSE NULL END AS DUE_ERROR
				FROM GRADEBOOK_ASSIGNMENTS
				WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI);
		$RET = $RET[1];
		$title = $RET['TITLE'];
	}
	elseif($_REQUEST['assignment_type_id'] && $_REQUEST['assignment_type_id']!='new' && $_REQUEST['assignment_id']!='new')
	{
		$sql = "SELECT at.TITLE,at.FINAL_GRADE_PERCENT,SORT_ORDER,COLOR,
				(SELECT sum(FINAL_GRADE_PERCENT) FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND STAFF_ID='".User('STAFF_ID')."') AS TOTAL_PERCENT
				FROM GRADEBOOK_ASSIGNMENT_TYPES at
				WHERE at.ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI,array('FINAL_GRADE_PERCENT'=>'_makePercent'));
		$RET = $RET[1];
		$title = $RET['TITLE'];
	}
	elseif($_REQUEST['assignment_id']=='new')
	{
		$title = _('New Assignment');
		$new = true;
	}
	elseif($_REQUEST['assignment_type_id']=='new')
	{
		$sql = "SELECT sum(FINAL_GRADE_PERCENT) AS TOTAL_PERCENT FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND STAFF_ID='".User('STAFF_ID')."'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI,array('FINAL_GRADE_PERCENT'=>'_makePercent'));
		$RET = $RET[1];
		$title = _('New Assignment Type');
	}

	if($_REQUEST['assignment_id'])
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&assignment_type_id=$_REQUEST[assignment_type_id]";
		if($_REQUEST['assignment_id']!='new')
			echo "&assignment_id=$_REQUEST[assignment_id]";
		echo "&table=GRADEBOOK_ASSIGNMENTS method=POST>";

		DrawHeader($title,$delete_button.'<INPUT type=submit value="'._('Save').'">');
		$header .= '<TABLE cellpadding=3 width=100%>';
		$header .= '<TR>';

		$header .= '<TD>' . TextInput($RET['TITLE'],'tables['.$_REQUEST['assignment_id'].'][TITLE]',($RET['TITLE']?'':'<FONT color=red>')._('Title').($RET['TITLE']?'':'</FONT>')) . '</TD>';
		$header .= '<TD>' . TextInput($RET['POINTS'],'tables['.$_REQUEST['assignment_id'].'][POINTS]',($RET['POINTS']!=''?'':'<FONT color=red>')._('Points').($RET['POINTS']?'':'</FONT>'),' size=4 maxlength=4') . '</TD>';
		$header .= '<TD>' . CheckboxInput($RET['COURSE_ID'],'tables['.$_REQUEST['assignment_id'].'][COURSE_ID]',_('Apply to all Periods for this Course'),'',$_REQUEST['assignment_id']=='new') . '</TD>';
		foreach($types_RET as $type)
			$assignment_type_options[$type['ASSIGNMENT_TYPE_ID']] = $type['TITLE'];

		$header .= '<TD>' . SelectInput($RET['ASSIGNMENT_TYPE_ID']?$RET['ASSIGNMENT_TYPE_ID']:$_REQUEST['assignment_type_id'],'tables['.$_REQUEST['assignment_id'].'][ASSIGNMENT_TYPE_ID]',_('Assignment Type'),$assignment_type_options,false) . '</TD>';
		$header .= '</TR><TR>';
		$header .= '<TD valign=top>' . DateInput($new && Preferences('DEFAULT_ASSIGNED','Gradebook')=='Y'?DBDate():$RET['ASSIGNED_DATE'],'tables['.$_REQUEST['assignment_id'].'][ASSIGNED_DATE]',_('Assigned'),!$new) . '</TD>';
		$header .= '<TD valign=top>' . DateInput($new && Preferences('DEFAULT_DUE','Gradebook')=='Y'?DBDate():$RET['DUE_DATE'],'tables['.$_REQUEST['assignment_id'].'][DUE_DATE]',_('Due'),!$new) . '</TD>';
		$header .= '<TD rowspan=2 colspan=2>' . TextareaInput($RET['DESCRIPTION'],'tables['.$_REQUEST['assignment_id'].'][DESCRIPTION]',_('Description')) . '</TD>';
		$header .= '</TR>';
		$errors = ($RET['DATE_ERROR']=='Y'?'<FONT color=red>'._('Due date is earlier than assigned date!').'</FONT><BR>':'');
		$errors .= ($RET['ASSIGNED_ERROR']=='Y'?'<FONT color=red>'._('Assigned date is after end of quarter!').'</FONT><BR>':'');
		$errors .= ($RET['DUE_ERROR']=='Y'?'<FONT color=red>'._('Due date is after end of quarter!').'</FONT><BR>':'');
		$header .= '<TR><TD valign=top colspan=2>'.substr($errors,0,-4).'</TD></TR>';
		$header .= '</TABLE>';
	}
	elseif($_REQUEST['assignment_type_id'])
	{
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&table=GRADEBOOK_ASSIGNMENT_TYPES";
		if($_REQUEST['assignment_type_id']!='new')
			echo "&assignment_type_id=$_REQUEST[assignment_type_id]";
		echo " method=POST>";
		DrawHeader($title,$delete_button.'<INPUT type=submit value="'._('Save').'">');
		$header .= '<TABLE cellpadding=3 width=100%>';
		$header .= '<TR>';

		$header .= '<TD>' . TextInput($RET['TITLE'],'tables['.$_REQUEST['assignment_type_id'].'][TITLE]',_('Title')) . '</TD>';
		if(Preferences('WEIGHT','Gradebook')=='Y')
		{
			$header .= '<TD>' . TextInput($RET['FINAL_GRADE_PERCENT'],'tables['.$_REQUEST['assignment_type_id'].'][FINAL_GRADE_PERCENT]',($RET['FINAL_GRADE_PERCENT']!=0?'':'<FONT color=red>')._('Percent of Final Grade').($RET['FINAL_GRADE_PERCENT']!=0?'':'</FONT>')) . '</TD>';
			$header .= '<TD>' . NoInput($RET['TOTAL_PERCENT']==1?'100%':'<FONT COLOR=red>'.(100*$RET['TOTAL_PERCENT']).'%</FONT>',_('Percent Total')) . '</TD>';
		}
		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['assignment_type_id'].'][SORT_ORDER]',_('Sort Order')) . '</TD>';
		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666','#333366','#336633','purple','teal','firebrick','tan');
		foreach($colors as $color)
		{
			$color_select[$color] = array('<TABLE cellpadding=0 cellspacing=0 width=100% bgcolor='.$color.'><TR><TD>&nbsp;</TD></TR></TABLE>',"<TABLE cellpadding=1 cellspacing=0 width=30><TR><TD bgcolor=$color>&nbsp;</TD></TR></TABLE>");
		}
		$header .= '<TD>' .  RadioInput($RET['COLOR'],'tables['.$_REQUEST['assignment_type_id'].'][COLOR]','Color',$color_select) . '</TD>';

		$header .= '</TR>';
		$header .= '</TABLE>';
	}
	else
		$header = false;

	if($header)
	{
		DrawHeader($header);
		echo '</FORM>';
	}

	// DISPLAY THE MENU
	$LO_options = array('save'=>false,'search'=>false,'add'=>true);

	echo '<TABLE><TR>';

	if(count($types_RET))
	{
		if($_REQUEST['assignment_type_id'])
		{
			foreach($types_RET as $key=>$value)
			{
				if($value['ASSIGNMENT_TYPE_ID']==$_REQUEST['assignment_type_id'])
					$types_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	echo '<TD valign=top>';
	$columns = array('TITLE'=>_('Assignment Type'),'SORT_ORDER'=>_('Order'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
	$link['TITLE']['variables'] = array('assignment_type_id'=>'ASSIGNMENT_TYPE_ID');
	$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=new";
	$link['add']['first'] = 5; // number before add link moves to top

	ListOutput($types_RET,$columns,_('Assignment Type'),_('Assignment Types'),$link,array(),$LO_options);
	echo '</TD>';


	// ASSIGNMENTS
	if($_REQUEST['assignment_type_id'] && $_REQUEST['assignment_type_id']!='new' && count($types_RET))
	{
		$sql = "SELECT ASSIGNMENT_ID,TITLE,POINTS FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID='".User('STAFF_ID')."' AND (COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') OR COURSE_PERIOD_ID='".UserCoursePeriod()."') AND ASSIGNMENT_TYPE_ID='".$_REQUEST['assignment_type_id']."' AND MARKING_PERIOD_ID='".UserMP()."' ORDER BY ".Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC";
		$QI = DBQuery($sql);
		$assn_RET = DBGet($QI);

		if(count($assn_RET))
		{
			if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='new')
			{
				foreach($assn_RET as $key=>$value)
				{
					if($value['ASSIGNMENT_ID']==$_REQUEST['assignment_id'])
						$assn_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
				}
			}
		}

		echo '<TD valign=top>';
		$columns = array('TITLE'=>'Assignment','POINTS'=>'Points');
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=$_REQUEST[assignment_type_id]";
		$link['TITLE']['variables'] = array('assignment_id'=>'ASSIGNMENT_ID');
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=$_REQUEST[assignment_type_id]&assignment_id=new";
		$link['add']['first'] = 5; // number before add link moves to top

		ListOutput($assn_RET,$columns,_('Assignment'),_('Assignments'),$link,array(),$LO_options);

		echo '</TD>';
	}

	echo '</TR></TABLE>';
}

function _makePercent($value,$column)
{
	return Percent($value,2);
}
?>
