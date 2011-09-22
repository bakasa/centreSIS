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

if ($_REQUEST['search_modfunc'] == 'list')
{
	Search('student_id');
}
else
{
	/// if a fee has been deleted just display selected student list.
	if ($_REQUEST['delete_ok'])
		unset($_REQUEST['modfunc']);
		
	if ($_REQUEST['modfunc'] == 'detail')
	{
		$title = "Add payment";
		
		echo '<br>';
		PopTable('header',$title);
		echo '<form id="newPaymentFrm" action='."Modules.php?modname=$_REQUEST[modname]&modfunc=new&student_id=$_REQUEST[student_id]".' method=post>
	  	<table>
	  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
	  	<tr><td>Type:</td><td><select name="TYPE">';
	  	
		$query = "select type_desc from BILLING_PAYMENT_TYPE order by type_desc";
		$result = DBQuery($query);
			while($row = db_fetch_row($result)){
				echo '<option value="'.$row['TYPE_DESC'].'">'.$row['TYPE_DESC'].'</option>';
		}
		
		echo '</select></td></tr>
			  	<tr><td>Date:</td><td>'.PrepareDate(date('Y-m-d'),'_date').'</td></tr>
			  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
			  	<tr><td colspan="2" align="center"><input type=submit name=button style="cursor:pointer;" 
			  		value="Add Payment" /></td></tr>
			  	</table>
			  	</form>';
			  	
		PopTable('footer');
	}
	else if ($_REQUEST['modfunc'] == 'new')
	{
	}
	else if ($_REQUEST['modfunc'] == 'remove')
	{
	}
	else if (isset($_REQUEST['student_id']))
	{
		$studentId = $_REQUEST['student_id'];
		
		$query = "SELECT
			  payment_id,
			  amount,
			  comment,
		      payment_date AS payment_date,
			  refunded,
			  payment_type
			  FROM
			  BILLING_PAYMENT
			  WHERE
			  student_id = $studentId
			  ORDER BY payment_id";

		$trans_RET = DBGet(DBQuery($query));

		$totalPayment = "0";
		$total_RET = DBGet(DBQuery("SELECT SUM(amount) AS total_payment FROM BILLING_PAYMENT WHERE student_id = $studentId and refunded = 0;"));
		
		if (!empty($total_RET) && $total_RET[1]['TOTAL_PAYMENT'] != NULL)
			$totalPayment = $total_RET[1]['TOTAL_PAYMENT'];
			
		/// Insert button to add new payments to the selected student.
		$buttonAdd = button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&student_id=$studentId\",
			\"blank\",\"width=500,height=300\"); return false;'");
		
		$link['add']['html'] = array('AMOUNT'=>$buttonAdd,'PAYMENT_TYPE'=>'',
			'PAYMENT_DATE'=>'','COMMENT'=>'','ACTION'=>'');
			
		$columns = array('AMOUNT'=>'Amount','PAYMENT_TYPE'=>'Type','PAYMENT_DATE'=>'Date','COMMENT'=>'Comment','ACTION'=>'Action');
		
		ListOutput($trans_RET,$columns,'payment','payments',$link);
	}
	else
	{
		Search('student_id');
	}
}
  
 ?>
