<?php
//if($_REQUEST['modfunc']=='search_fnc' || !$_REQUEST['modfunc'])
if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
{
	switch(User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if($_SESSION['staff_id'] && ($_REQUEST['modname']!='Users/Search.php' || $_REQUEST['student_id']=='new'))
			if($_SESSION['staff_id'] && User('PROFILE')=='admin' && $_REQUEST['staff_id']=='new')
			{
				unset($_SESSION['staff_id']);
				echo '<script language=JavaScript>parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
			}

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if($_SESSION['Back_PHP_SELF']!='staff')
			{
				$_SESSION['Back_PHP_SELF'] = 'staff';
				unset($_SESSION['List_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
			echo '<BR>';
			PopTable('header',$extra['search_title']?$extra['search_title']:_('Find a User'));
			//echo "<FORM name=search action=Modules.php?modname=$_REQUEST[modname]&modfunc=list&next_modname=$_REQUEST[next_modname]$extra[action]&advanced=$_REQUEST[advanced] method=POST>";
			echo "<FORM name=search action=Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&search_modfunc=list&next_modname=$_REQUEST[next_modname]$extra[action]&advanced=$_REQUEST[advanced] method=POST>";
			echo '<TABLE border=0>';

			echo '<TR valign=top><TD>';
			echo '<TABLE bgcolor=#f8f8f9 width=100% id=general_table>';
			echo '<TR><TD align=right width=120>'._('Last Name').'</TD><TD><INPUT type=text name="last" size=30></TD></TR>';
			echo '<TR><TD align=right width=120>'._('First Name').'</TD><TD><INPUT type=text name="first" size=30></TD></TR>';
			echo '<TR><TD align=right width=120>'._('User ID').'</TD><TD><input type=text name="usrid" size=30></TD></TR>';
			echo '<TR><TD align=right width=120>'._('Username').'</TD><TD><INPUT type=text name="username" size=30></TD></TR>';
			if(User('PROFILE')=='admin')
				$options = array(''=>'N/A','admin'=>_('Administrator'),'teacher'=>_('Teacher'),'parent'=>_('Parent'),'none'=>_('No Access'));
			else
				$options = array(''=>'N/A','teacher'=>_('Teacher'),'parent'=>_('Parent'));
			if($extra['profile'])
				$options = array($extra['profile']=>$options[$extra['profile']]);
			echo '<TR><TD align=right width=120>'._('Profile').'</TD><TD><SELECT name=profile>';
			foreach($options as $key=>$val)
				echo '<OPTION value="'.$key.'">'.$val;
			echo '</SELECT></TD></TR>';
			if(!isset($extra))
				$extra = array();
			StaffWidgets('user',$extra);
			if($extra['search'])
				echo $extra['search'];
			if($extra['extra_search'])
				echo $extra['extra_search'];
			Search('staff_fields',is_array($extra['staff_fields'])?$extra['staff_fields']:array());
			echo '</TABLE>';
			echo '</TD><TD>';
			echo '<TABLE width=100%><TR><TD align=center><BR>';
			if($extra['search_second_col'])
				echo $extra['search_second_col'];
			if(User('PROFILE')=='admin')
				echo '<INPUT type=checkbox name=_search_all_schools value=Y'.(Preferences('DEFAULT_ALL_SCHOOLS')=='Y'?' CHECKED':'').'>'._('Search All Schools').'<BR>';
			else
				echo '<INPUT type=checkbox name=include_inactive value=Y>'._('Include Parents of Inactive Students').'<BR>';
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
				StaffWidgets('all',$extra);
				echo '<TR><TD>';
				echo '<FONT COLOR='.Preferences('HEADER').'><B>'._('Widgets').'</B></FONT><BR>';
				echo $extra['search'];
				echo '</TD></TR>';

				echo '<TR><TD>';
				echo '<FONT COLOR='.Preferences('HEADER').'><B>'._('User Fields').'</B></FONT><BR>';
				Search('staff_fields_all',is_array($extra['staff_fields'])?$extra['staff_fields']:array());
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

		default:
			echo User('PROFILE');
	}
}
//if($_REQUEST['search_modfunc']=='list')
else
{
	if(!$_REQUEST['next_modname'])
		$_REQUEST['next_modname'] = 'Users/User.php';

	if(User('PROFILE')=='admin')
	{
		if(!isset($extra))
			$extra = array();
		StaffWidgets('user',$extra);
		if($_REQUEST['advanced']=='Y')
			StaffWidgets('all',$extra);
	}

	if(!$extra['NoSearchTerms'])
	{
		if($_REQUEST['_search_all_schools']=='Y')
			$_CENTRE['SearchTerms'] .= '<font color=gray><b>'._('Search All Schools').'</b></font><BR>';
	}
	$extra['WHERE'] .= appendStaffSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$extra['WHERE'] .= CustomFields('where','staff',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	if(!isset($_CENTRE['DrawHeader'])) DrawHeader(_('Choose A User'));
	$staff_RET = GetStaffList($extra);
	if($extra['profile'])
	{
        // DO NOT translate those strings since they will be passed to ListOutput ultimately
		$options = array('admin'=>'Administrator','teacher'=>'Teacher','parent'=>'Parent','none'=>'No Access');
		$singular = $options[$extra['profile']];
		$plural = $singular.($options[$extra['profile']]=='none'?'':'s');
		$columns = array('FULL_NAME'=>$singular,'STAFF_ID'=>_('Centre ID'));
	}
	else
	{
        // DO NOT translate those strings since they will be passed to ListOutput ultimately
		$singular = 'User'; $plural = 'Users';
		$columns = array('FULL_NAME'=>_('Staff Member'),'PROFILE'=>_('Profile'),'STAFF_ID'=>_('Centre ID'));
	}

	$name_link['FULL_NAME']['link'] = "Modules.php?modname=$_REQUEST[next_modname]";
	$name_link['FULL_NAME']['variables'] = array('staff_id'=>'STAFF_ID');
	if(is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if(is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;
	if(is_array($extra['columns_after']))
		$columns += $extra['columns_after'];

	if(count($staff_RET)>1 || $link['add'] || !$link['FULL_NAME'] || $extra['columns_before'] || $extra['columns_after'] || ($extra['BackPrompt']==false && count($staff_RET)==0) || ($extra['Redirect']===false && count($staff_RET)==1))
	{
		if($_REQUEST['expanded_view']!='true')
			DrawHeader("<A HREF=".PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'true')) . ">"._('Expanded View')."</A>",$extra['header_right']);
		else
			DrawHeader("<A HREF=".PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'false')) . ">"._('Original View')."</A>",$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
		DrawHeader(str_replace('<BR>','<BR> &nbsp;',substr($_CENTRE['SearchTerms'],0,-4)));
		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='staff')
			{
				$_SESSION['Back_PHP_SELF'] = 'staff';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script language=JavaScript>parent.help.location.reload();</script>';
		}
		ListOutput($staff_RET,$columns,$singular,$plural,$link,false,$extra['options']);
	}
	elseif(count($staff_RET)==1)
	{
		if(count($link['FULL_NAME']['variables']))
		{
			foreach($link['FULL_NAME']['variables'] as $var=>$val)
				$_REQUEST[$var] = $staff_RET['1'][$val];
		}
		if(!is_array($staff_RET[1]['STAFF_ID']))
		{
			$_SESSION['staff_id'] = $staff_RET[1]['STAFF_ID'];
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
		BackPrompt(_('No Users were found.'));
}
?>
