<?php
if($_CENTRE['modules_search'] && $extra['force_search'])
	$_REQUEST['search_modfunc'] = '';

if(Preferences('SEARCH')!='Y' && !$extra['force_search'])
	$_REQUEST['search_modfunc'] = 'list';
if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
{
	//if($_SESSION['student_id'] && User('PROFILE')!='parent' && User('PROFILE')!='student' && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
	switch(User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if($_SESSION['student_id'] && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
			if($_SESSION['student_id'] && $_REQUEST['student_id']=='new')
			{
				unset($_SESSION['student_id']);
				echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
			}

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['List_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
			echo '<BR>';
			PopTable('header',$extra['search_title']?$extra['search_title']:_('Find a Student'));
			echo "<FORM name=search action=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&search_modfunc=list&next_modname=$_REQUEST[next_modname]&advanced=$_REQUEST[advanced]$extra[action] method=POST>";
			echo '<TABLE border=0>';

			echo '<TR valign=top><TD>';
			echo '<TABLE bgcolor=#f8f8f9 width=100% id=general_table>';
			Search('general_info',$extra['grades']);
			if(!isset($extra))
				$extra = array();
			Widgets('user',$extra);
			if($extra['search'])
				echo $extra['search'];
			if($extra['extra_search'])
				echo $extra['extra_search'];
			Search('student_fields',is_array($extra['student_fields'])?$extra['student_fields']:array());
			echo '</TABLE>';
			echo '</TD><TD>';
			echo '<TABLE width=100%><TR><TD align=center><BR>';
			if($extra['search_second_col'])
				echo $extra['search_second_col'];
			if(User('PROFILE')=='admin')
			{
				echo '<INPUT type=checkbox name=address_group value=Y'.(Preferences('DEFAULT_FAMILIES')=='Y'?' CHECKED':'').'><font color=black>'._('Group by Family').'</font><BR>';
				echo '<INPUT type=checkbox name=_search_all_schools value=Y'.(Preferences('DEFAULT_ALL_SCHOOLS')=='Y'?' CHECKED':'').'><font color=black>'._('Search All Schools').'</font><BR>';
			}
			echo '<INPUT type=checkbox name=include_inactive value=Y><font color=black>'._('Include Inactive Students').'</font><BR>';
			echo '<BR>';
			echo Buttons(_('Submit'),_('Reset'));
			echo '</TD></TR>';
			echo '</TABLE>';
			if($extra['second_col'])
				echo '<BR><TABLE>'.$extra['second_col'].'</TABLE>';
			echo '</TD></TR>';

			echo '<TR valign=top><TD><TABLE cellpadding=0 cellspacing=0 width=100%>';
			if($_REQUEST['advanced']=='Y')
			{
				$extra['search'] = '';
				Widgets('all',$extra);
				echo '<TR><TD>';
				echo '<FONT COLOR='.Preferences('HEADER').'><B>'._('Widgets').'</B></FONT><BR>';
				echo $extra['search'];
				echo '</TD></TR>';

				echo '<TR><TD>';
				echo '<FONT COLOR='.Preferences('HEADER').'><B>'._('Student Fields').'</B></FONT><BR>';
				Search('student_fields_all',is_array($extra['student_fields'])?$extra['student_fields']:array());
				echo '</TD></TR>';
				echo '<TR><TD><BR><A href='.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'N')).'>'._('Basic Search').'</A></TD></TR>';
			}
			else
				echo '<TR><TD><BR><A href='.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'Y')).'>'._('Advanced Search').'</A></TD></TR>';
			echo '</TABLE></TD>';
			//if($extra['second_col'])
			//	echo '<TD>'.$extra['second_col'].'</TD>';
			echo '</TR>';

			echo '</TABLE>';
			echo '</FORM>';
			// set focus to last name text box
			echo '<script type="text/javascript"><!--
				document.search.last.focus();
				--></script>';
			PopTable('footer');
		break;

		case 'parent':
		case 'student':
			echo '<BR>';
			PopTable('header',_('Search'));
			echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&search_modfunc=list&next_modname=$_REQUEST[next_modname]".$extra['action']." method=POST>";
			echo '<TABLE border=0>';
			if($extra['search'])
				echo $extra['search'];
			echo '<TR><TD colspan=2 align=center>';
			echo '<BR>';
			echo Buttons(_('Submit'),_('Reset'));
			echo '</TD></TR>';
			echo '</TABLE>';
			echo '</FORM>';
			PopTable('footer');
		break;
	}
}
//if($_REQUEST['search_modfunc']=='list')
else
{
	if(!$_REQUEST['next_modname'])
		$_REQUEST['next_modname'] = 'Students/Student.php';

	if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		if(!isset($extra))
			$extra = array();
		Widgets('user',$extra);
		if($_REQUEST['advanced']=='Y')
			Widgets('all',$extra);
	}

	if(!$extra['NoSearchTerms'])
	{
		if($_REQUEST['_search_all_schools']=='Y')
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Search All Schools').'</b></font><BR>';
		if($_REQUEST['include_inactive']=='Y')
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Include Inactive Students').'</b></font><BR>';
	}
	if($_REQUEST['address_group'])
	{
		$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y'),-ssm.STUDENT_ID) AS FAMILY_ID";
		$extra['group'] = $extra['LO_group'] = array('FAMILY_ID');
	}
	$extra['WHERE'] .= appendSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$extra['WHERE'] .= CustomFields('where','student',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$students_RET = GetStuList($extra);
	if($extra['array_function'] && function_exists($extra['array_function']))
		if($_REQUEST['address_group'])
			foreach($students_RET as $id=>$student_RET)
				$students_RET[$id] = $extra['array_function']($student_RET);
		else
			$students_RET = $extra['array_function']($students_RET);

	$name_link['FULL_NAME']['link'] = "Modules.php?modname=$_REQUEST[next_modname]";
	$name_link['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');
	if($_REQUEST['_search_all_schools'])
		$name_link['FULL_NAME']['variables']['school_id'] = 'SCHOOL_ID';
	if(is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if(is_array($extra['columns']))
		$columns = $extra['columns'];
	else
		$columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>_('Centre ID'),'GRADE_ID'=>_('Grade'));
	if(is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;
	if(is_array($extra['columns_after']))
		$columns += $extra['columns_after'];

	if(count($students_RET)>1 || $link['add'] || !$link['FULL_NAME'] || $extra['columns_before'] || $extra['columns'] || $extra['columns_after'] || ($extra['BackPrompt']==false && count($students_RET)==0) || (($extra['Redirect']===false || $_REQUEST['address_group']) && count($students_RET)==1))
	{
		if($_REQUEST['expanded_view']!='true')
			$header_left = '<A HREF='.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'true')).'>'._('Expanded View').'</A>';
		else
			$header_left = '<A HREF='.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'false')).'>'._('Original View').'</A>';
		if(!$_REQUEST['address_group'])
			$header_left .= ' | <A HREF='.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'Y')).'>'._('Group by Family').'</A>';
		else
			$header_left .= ' | <A HREF='.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'')).'>'._('Ungroup by Family').'</A>';
		DrawHeader($header_left,$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
		DrawHeader(str_replace('<BR>','<BR> &nbsp;',substr($_CENTRE['SearchTerms'],0,-4)));
		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
		}
		if($_REQUEST['address_group'])
		{
            ListOutput($students_RET,$columns,_('Family'),_('Families'),$link,$extra['LO_group'],$extra['options']);
		}
		else
		{
            ListOutput($students_RET,$columns,_('Student'),_('Students'),$link,$extra['LO_group'],$extra['options']);
		}
	}
	elseif(count($students_RET)==1)
	{
		if(count($link['FULL_NAME']['variables']))
		{
			foreach($link['FULL_NAME']['variables'] as $var=>$val)
				$_REQUEST[$var] = $students_RET['1'][$val];
		}
		if(!is_array($students_RET[1]['STUDENT_ID']))
		{
			$_SESSION['student_id'] = $students_RET[1]['STUDENT_ID'];
			$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];
			echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
			unset($_REQUEST['search_modfunc']);
		}
		if($_REQUEST['modname']!=$_REQUEST['next_modname'])
		{
			$modname = $_REQUEST['next_modname'];
			if(strpos($modname,'?'))
				$modname = substr($_REQUEST['next_modname'],0,strpos($_REQUEST['next_modname'],'?'));
			if(strpos($modname,'&'))
				$modname = substr($_REQUEST['next_modname'],0,strpos($_REQUEST['next_modname'],'&'));
			if($_REQUEST['modname'])
				$_REQUEST['modname'] = $modname;
			include('modules/'.$modname);
		}
	}
	else
		BackPrompt(_('No Students were found.'));
}
?>
