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

if (isset($_REQUEST['modfunc']))
{
	if ($_REQUEST['modfunc'] == 'detail')
	{
		echo '<br>';
		PopTable('header','Add new payment option');
		echo '<form id="PayOptionForm" action='."Modules.php?modname=$_REQUEST[modname]&modfunc=new".' method=post>
			<table>
			<tr><td>Description</td><td><input type="text" size="30" name="type" /></td></tr>
			<tr><td colspan="2" align="center">
				<input type=submit name=button value="Save" />&nbsp;&nbsp;
				<input type="button" name=button onclick="window.close();" value="Cancel" />
			</td></tr>
			</table></form>';
		PopTable('footer');
	}
	else if ($_REQUEST['modfunc'] == 'new')
	{
	}
}
else
{
	$type_RET = DBGet(DBQuery("SELECT type_id, type_desc as desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc"));

	$buttonAdd = button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail\",
		\"blank\",\"width=400,height=150\"); return false;'");

	$link['add']['html'] = array('DESC'=>$buttonAdd,'ACTION'=>'');
			
	$columns = array('DESC'=>'Description','ACTION'=>'Action');

	ListOutput($type_RET,$columns,'Payment Option','Payment Options',$link);
}

?>
