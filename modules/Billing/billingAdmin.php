<?php
#**************************************************************************
#  openSIS is a free student information system for public and non-public
#  schools from Open Solutions for Education, Inc. It is  web-based,
#  open source, and comes packed with features that include student
#  demographic info, scheduling, grade book, attendance,
#  report cards, eligibility, transcripts, parent portal,
#  student portal and more.
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send
#  an email to info@os4ed.com.
#
#  Copyright (C) 2007-2008, Open Solutions for Education, Inc.
#
#*************************************************************************
#  This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, version 2 of the License. See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#**************************************************************************

DrawHeader(ProgramTitle());

$displayList = false;

if (isset($_REQUEST['modfunc']))
{
	if ($_REQUEST['modfunc'] == 'detail')
	{
		echo '<br>';
		PopTable('header',_('Add new payment option'));
		echo '<form id="PayOptionForm" action='."Modules.php?modname=$_REQUEST[modname]&modfunc=new".' method=post>
			<table>
			<tr><td>Description</td><td><input type="text" size="30" name="type" /></td></tr>
			<tr><td colspan="2" align="center">
				<input type=submit name=button value='._('Save').' />&nbsp;&nbsp;
				<input type="button" name=button onclick="window.close();" value='._('Cancel').' />
			</td></tr>
			</table></form>';
		PopTable('footer');
	}
	else if ($_REQUEST['modfunc'] == 'new')
	{
		/// TODO: SANATIZE INPUT AND CHECK IF ALL VALUES ARE SET & VALID
		
		require 'modules/Billing/classes/Auth.php';
		require 'modules/Billing/classes/PaymentType.php';

		$auth = new Auth();
		$staffId = User('STAFF_ID');
		$profile = User('PROFILE');

		if($auth->checkAdmin($profile, $staffId))
		{
			$type = $_REQUEST['type'];

			PaymentType::addType($type);
		}
		
		echo '<SCRIPT language=javascript>opener.document.location = "Modules.php?modname='.$_REQUEST['modname']
			.'"; window.close();</script>';
	}
	else if ($_REQUEST['modfunc'] == 'remove')
	{
		if (DeletePrompt(_('Payment option')))
		{
			/// TODO: SANATIZE INPUT AND CHECK IF ALL VALUES ARE SET & VALID
			
			require 'modules/Billing/classes/Auth.php';
			require 'modules/Billing/classes/PaymentType.php';

			$auth = new Auth();
			$staffId = User('STAFF_ID');
			$profile = User('PROFILE');

			if($auth->checkAdmin($profile, $staffId))
			{
				$id = $_REQUEST['id'];

				PaymentType::deleteType($id);
			}
	
			$displayList = true;
		}
	}
}
else
	$displayList = true;
	
if ($displayList)
{
	$type_RET = DBGet(DBQuery("SELECT type_id, type_desc as desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc"));

	/// Add a new action column to display if the fee is reversed or display the option to reverse it.
	foreach ($type_RET as &$type)
	{
		$type['ACTION'] = button('x','',
			"# onclick=javascript:window.location='Modules.php?modname=$_REQUEST[modname]&modfunc=remove&id=$type[TYPE_ID]'");
	}
		
	$buttonAdd = button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail\",
		\"blank\",\"width=400,height=150\"); return false;'");

	$link['add']['html'] = array('DESC'=>$buttonAdd,'ACTION'=>'');
			
	$columns = array('DESC'=>_('Description'),'ACTION'=>_('Action'));

	ListOutput($type_RET,$columns,_('Payment Option'),_('Payment Options'),$link);
}

?>
