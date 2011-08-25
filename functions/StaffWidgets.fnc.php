<?php

function StaffWidgets($item,&$myextra)
{	global $extra,$_CENTRE,$CentreModules;

	if(isset($myextra))
		$extra =& $myextra;

	if(!is_array($_CENTRE['StaffWidgets']))
		$_CENTRE['StaffWidgets'] = array();

	if(!is_array($extra['functions']))
		$extra['functions'] = array();

	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && !$_CENTRE['StaffWidgets'][$item])
	{
		switch($item)
		{
			case 'all':
				$extra['search'] .= '<TR><TD>';

				if($CentreModules['Users'] && (!$_CENTRE['StaffWidgets']['permissions']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'users_table\');"><IMG SRC=assets/arrow_right.gif id=users_table_arrow> <B>'._('Users').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=users_table style="display:none;">';
					StaffWidgets('permissions',$extra);
					$extra['search'] .= '</TABLE>';
				}
				if($CentreModules['Food_Service'] && (!$_CENTRE['StaffWidgets']['fsa_balance'] || !$_CENTRE['StaffWidgets']['fsa_status'] || !$_CENTRE['StaffWidgets']['fsa_barcode']))
				{
					$extra['search'] .= '<A onclick="switchMenu(\'food_service_table\');"><IMG SRC=assets/arrow_right.gif id=food_service_table_arrow> <B>'._('Food Service').'</B></A><BR><TABLE bgcolor=#f8f8f9 width=100% id=food_service_table style="display:none;">';
					StaffWidgets('fsa_balance',$extra);
					StaffWidgets('fsa_status',$extra);
					StaffWidgets('fsa_barcode',$extra);
					StaffWidgets('fsa_exists',$extra);
					$extra['search'] .= '</TABLE>';
				}
				$extra['search'] .= '</TD></TR>';
			break;

			case 'user':
				$widgets_RET = DBGet(DBQuery("SELECT TITLE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='StaffWidgetsSearch'".(count($_CENTRE['StaffWidgets'])?" AND TITLE NOT IN ('".implode("','",array_keys($_CENTRE['StaffWidgets']))."')":'')));
				foreach($widgets_RET as $widget)
					StaffWidgets($widget['TITLE'],$extra);
			break;

			case 'permissions_Y':
			case 'permissions_N':
				$value = substr($item,12);
				$item = 'permissions';
			case 'permissions':
				if($CentreModules['Users'])
				{
				if($_REQUEST['permissions'])
				{
					$extra['WHERE'] .= " AND s.PROFILE_ID IS ".($_REQUEST['permissions']=='Y'?'NOT':'')." NULL AND s.PROFILE!='none'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Permissions')).' </b></font>'.($_REQUEST['permissions']=='Y'?_('Profile'):_('Custom')).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>'._('Permissions').'</TD><TD><INPUT type=radio name=permissions value=""'.(!$value?' CHECKED':'').'>'._('All').' <INPUT type=radio name=permissions value=Y'.($value=='Y'?' CHECKED':'').'>'._('Profile').' <INPUT type=radio name=permissions value=N'.($value=='N'?' CHECKED':'').'>'._('Custom').'</TD></TR>';
				}
			break;

			case 'fsa_balance_warning':
				$value = $GLOBALS['warning'];
				$item = 'fsa_balance';
			case 'fsa_balance':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_balance']!='')
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					$extra['WHERE'] .= " AND fssa.BALANCE".($_REQUEST['fsa_bal_gt']=='Y'?'>=':'<')."'".round($_REQUEST['fsa_balance'],2)."'";
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Food Service Balance')).' </b></font>'.($_REQUEST['fsa_bal_ge']=='Y'?'&ge;':'&lt;').number_format($_REQUEST['fsa_balance'],2).'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>'._('Balance').'</TD><TD><table cellpadding=0 cellspacing=0><tr><td>&lt;<INPUT type=radio name=fsa_bal_ge value="" CHECKED></td><td rowspan=2><INPUT type=text name=fsa_balance size=10'.($value?' value="'.$value.'"':'').'></td></tr><tr><td>&ge;<INPUT type=radio name=fsa_bal_ge value=Y></td></tr></table></TD></TR>';
				}
			break;

			case 'fsa_status_active':
				$value = 'active';
				$item = 'fsa_status';
			case 'fsa_status':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_status'])
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					if($_REQUEST['fsa_status']=='Active')
						$extra['WHERE'] .= ' AND fssa.STATUS IS NULL';
					else
						$extra['WHERE'] .= ' AND fssa.STATUS=\''.$_REQUEST['fsa_status'].'\'';
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Food Service Status')).' </b></font>'.$_REQUEST['fsa_status'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>'._('Account Status').'</TD><TD><SELECT name=fsa_status><OPTION value="">'._('Not Specified').'</OPTION><OPTION value="Active"'.($value=='active'?' SELECTED':'').'>'._('Active').'</OPTION><OPTION value="Inactive">'._('Inactive').'</OPTION><OPTION value="Disabled">'._('Disabled').'</OPTION><OPTION value="Closed">'._('Closed').'</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_barcode':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_barcode'])
				{
					if (!strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					$extra['WHERE'] .= ' AND fssa.BARCODE=\''.$_REQUEST['fsa_barcode'].'\'';
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Food Service Barcode')).' </b></font>'.$_REQUEST['fsa_barcode'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>'._('Barcode').'</TD><TD><INPUT type="text" name=fsa_barcode size="15"></TD></TR>';
				}
			break;

			case 'fsa_exists_N':
			case 'fsa_exists_Y':
				$value = substr($item,11);
				$item = 'fsa_exists';
			case 'fsa_exists':
				if($CentreModules['Food_Service'])
				{
				if($_REQUEST['fsa_exists'])
				{
					$extra['WHERE'] .= ' AND '.($_REQUEST['fsa_exists']=='N'?'NOT ':'').'EXISTS (SELECT \'exists\' FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID)';
					if(!$extra['NoSearchTerms'])
						$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Food Service Account Exists')).' </b></font>'.$_REQUEST['fsa_exists'].'<BR>';
				}
				$extra['search'] .= '<TR><TD align=right width=120>'._('Has Account').'</TD><TD><INPUT type=radio name=fsa_exists value=""'.(!$value?' CHECKED':'').'>'._('All').' <INPUT type=radio name=fsa_exists value=Y'.($value=='Y'?' CHECKED':'').'>'._('Yes').' <INPUT type=radio name=fsa_exists value=N'.($value=='N'?' CHECKED':'').'>'._('No').'</TD></TR>';
				}
			break;
		}
		$_CENTRE['StaffWidgets'][$item] = true;
	}
}
?>
