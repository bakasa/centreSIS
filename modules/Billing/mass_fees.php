<?php
#**************************************************************************
#  Copyright (C) venccsralph@gmail.com
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

if (isset($_REQUEST['search_modfunc']))
{
	 if ($_REQUEST['search_modfunc'] == 'list')
	 {
	 	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
		$extra['link'] = array('FULL_NAME'=>false);
		$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller checked 
			onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
		$extra['options']['search'] = false;
		$extra['new'] = true;
	
		echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=detail method=POST>";
	 	Search('student_id',$extra);
	 	echo '<BR><CENTER><INPUT type=submit value='._('Add Mass Fee').'"></CENTER>';
	 	echo '</form>';
	 }
}
else
{
	$displaySearch = false;
	
	if (isset($_REQUEST['modfunc']))
	{
		if ($_REQUEST['modfunc'] == 'detail')
		{
			$students = serialize($_REQUEST['st_arr']);
			
			echo '<br>';
			PopTable('header',_('Add Selected Fees'));
			echo '<form id="newMassFeeFrm" action=Modules.php?modname='."$_REQUEST[modname]&modfunc=save&students=$students".' method=POST>
		  	<table>
		  	<tr><td>'._('Title').':</td><td><input type="text" size="20" id="title" name="TITLE" /></td></tr>
		  	<tr><td>'._('Amount').':</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>'._('Assigned').':</td><td>'.PrepareDate(date('Y-m-d'),'_assigned').'</td></tr>
		  	<tr><td>'._('Due Date').':</td><td>'.PrepareDate(date('Y-m-d'),'_due').'</td></tr>
		  	<tr><td>'._('Comment').':</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	<tr><td colspan="4" align="center">
		  		<input type=submit name=button style="cursor:pointer;" value='._('Add Fees').'" /></td></tr>
			</table>
		  	</form>';
		  	PopTable('footer');
		}
		else if ($_REQUEST['modfunc'] == 'save')
		{
			include 'modules/Billing/classes/Auth.php';
			include 'modules/Billing/classes/Fee.php';

			$auth = new Auth();
			$staffId = User('STAFF_ID');
			$profile = User('PROFILE');

			if($auth->checkAdmin($profile, $staffId))
			{
				$module     = 'Billing';
				$studentIds = unserialize(stripslashes($_REQUEST['students']));
				$amount     = $_REQUEST['AMOUNT'];
				$title      = $_REQUEST['TITLE'];
				$comment    = $_REQUEST['COMMENT'];
				$assMon	    = $_REQUEST['month_assigned'];
				$assDay	    = $_REQUEST['day_assigned'];
				$assYr	    = $_REQUEST['year_assigned'];
				$dueMon     = $_REQUEST['month_due'];
				$dueDay     = $_REQUEST['day_due'];
				$dueYr      = $_REQUEST['year_due'];
				$username   = User('USERNAME');

				$monthnames = array(1 => 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
				$dueMon = array_search($dueMon,$monthnames);
				$dueDate = $dueMon.'/'.$dueDay.'/'.$dueYr;
				$assMon = array_search($assMon,$monthnames);
				$assignedDate = $assMon.'/'.$assDay.'/'.$assYr;

				Fee::addMassFee($amount,$title,$studentIds,$dueDate,$assignedDate,$comment,$module,$username);
			}
			
			/// clear _REQUEST variable and only leave modname
			$modName = $_REQUEST['modname'];
			
			unset($_REQUEST);
			$_REQUEST['modname'] = $modName;
			
			$displaySearch = true;
		}
		else
			$displaySearch = true;
	}
	else
		$displaySearch = true;
	
	if ($displaySearch)
		Search('student_id');
}
	
function _makeChooseCheckbox($value,$title)
{
	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
}

?>
