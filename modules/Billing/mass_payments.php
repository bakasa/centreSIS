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
	 	echo '<BR><CENTER><INPUT type=submit value="Add Mass Payment"></CENTER>';
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
			PopTable('header','Add Payment');
			echo '<form id="newMassPaymentFrm" action=Modules.php?modname='."$_REQUEST[modname]&modfunc=save&students=$students".' method=POST>
		  	<table>
		  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>Type:</td><td><select name="TYPE">';
		  	
			$query = "SELECT type_desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc";
			$result = DBQuery($query);
			while($row = db_fetch_row($result)){
				echo '<option value="'.$row['TYPE_DESC'].'">'.$row['TYPE_DESC'].'</option>';
			}

		   echo'</select></td></tr>
			<tr><td>Date:</td><td>'.PrepareDate(date('Y-m-d'),'_date').'</td></tr>
			<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	<tr><td colspan="4" align="center"><input type=submit name=button style="cursor:pointer;" value="Add Selected Payments" /></td></tr>
			</table></form>';
		  	PopTable('footer');
		}
		else if ($_REQUEST['modfunc'] == 'save')
		{
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
