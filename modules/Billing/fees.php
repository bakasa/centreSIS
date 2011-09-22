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

if ($_REQUEST['search_modfunc'] == 'list')
{
	Search('student_id');
}
else
{
	$displayList = false;
	$displaySearch = false;
			
	if ($_REQUEST['modfunc'] == 'detail')
	{
		$title = 'New Fee';
		
		echo '<br>';
		PopTable('header',$title);
		echo '<div id="addFeeDiv" align=center>
		  	<form id="newFeeFrm" action='."Modules.php?modname=$_REQUEST[modname]&modfunc=new&student_id=$_REQUEST[student_id]".' method=post>
		  	<table>
		  	<tr><td>Title:</td><td><input type="text" size="20" id="title" name="TITLE" /></td></tr>
		  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>Assigned:</td><td>'.PrepareDate(date('Y-m-d'), '_assigned').'</td></tr>
		  	<tr><td>Due Date:</td><td>'.PrepareDate(date('Y-m-d'), '_due').'</td></tr>
		  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	<tr><td colspan="2" align="center">
		  		<input type=submit name=button;" style="cursor:pointer;" value="Add Fee" /> 
		  	</td></tr>
		  	</table>
		  	</form>
		  	</div>';
		  PopTable('footer');
	}
	else if ($_REQUEST['modfunc'] == 'new')
	{
		include 'modules/Billing/classes/Auth.php';
		include 'modules/Billing/classes/Fee.php';

		$studentId = $_REQUEST['student_id'];
		
		$auth = new Auth();
		$staffId = User('STAFF_ID');
		$profile = User('PROFILE');

		if($auth->checkAdmin($profile, $staffId))
		{
			$module    = "Billing";
			$amount    = $_REQUEST['AMOUNT'];
			$title     = $_REQUEST['TITLE'];
			$comment   = $_REQUEST['COMMENT'];
			$assMon	   = $_REQUEST['month_assigned'];
			$assDay	   = $_REQUEST['day_assigned'];
			$assYr	   = $_REQUEST['year_assigned'];
			$dueMon    = $_REQUEST['month_due'];
			$dueDay    = $_REQUEST['day_due'];
			$dueYr     = $_REQUEST['year_due'];
			$username  = User('USERNAME');

			$monthnames = array(1 => 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
			$dueMon = array_search($dueMon,$monthnames);
			$dueDate = $dueMon.'/'.$dueDay.'/'.$dueYr;
			$assMon = array_search($assMon,$monthnames);
			$assignedDate = $assMon.'/'.$assDay.'/'.$assYr;

			Fee::addFee($amount,$title,$studentId,$dueDate,$assignedDate,$comment,$module,$username);
		}

		echo '<SCRIPT language=javascript>opener.document.location = "Modules.php?modname='.$_REQUEST['modname']
			."&student_id=$studentId".'"; window.close();</script>';
	}
	else if ($_REQUEST['modfunc'] == 'remove')
	{
		if (DeletePrompt('fee','waive'))
		{
			include 'modules/Billing/classes/Auth.php';
			include 'modules/Billing/classes/Fee.php';

			$auth = new Auth();
			$staffId = User('STAFF_ID');
			$profile = User('PROFILE');

			if($auth->checkAdmin($profile, $staffId))
			{
				$feeId = $_REQUEST['fee_id'];
				$username  = User('USERNAME');
			
				Fee::waiveFee($feeId,$username);
			}
			
			$displayList = true;
		}
	}
	else if (isset($_REQUEST['student_id']))
		$displayList = true;
	else
		$displaySearch = true;
		
	if ($displayList)
	{
		$studentId = $_REQUEST['student_id'];
		
		$query = "SELECT
                  fee_id,
                  amount,
                  title,
                  inserted_by,
                  assigned_date AS assigned_date,
                  due_date AS due_date,
                  comment,
                  waived
                  FROM
                  BILLING_FEE
                  WHERE
                  student_id = $studentId

                  ORDER BY fee_id";

		$trans_RET = DBGet(DBQuery($query));

		$query = "SELECT SUM(amount) AS total_fee FROM BILLING_FEE WHERE student_id = $studentId and waived = 0;";
		
		$totalFee = "0";
		$fee_RET = DBGet(DBQuery($query));
		
		if (!empty($fee_RET) && $fee_RET[1]['TOTAL_FEE'] != NULL)
			$totalFee = $fee_RET[1]['TOTAL_FEE'];
			
		/// Add a new action column to display if the fee is reversed or display the option to reverse it.
		foreach ($trans_RET as &$trans)
		{
			$action = "";
			
			if ($trans['WAIVED'])
				$action = '<b><font color=red>Waived</font></b>';
			else
			{
				$action = button('x','',
					"# onclick=javascript:window.location='Modules.php?modname=$_REQUEST[modname]&modfunc=remove&student_id=$studentId&fee_id=$trans[FEE_ID]'");
			}
			
			$trans['ACTION'] = $action;
		}
		
		$buttonAdd = button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail&student_id=$studentId\",
			\"blank\",\"width=500,height=300\"); return false;'");
		
		$link['add']['html'] = array('TITLE'=>$buttonAdd,'AMOUNT'=>'','INSERTED_BY'=>'','ASSIGNED_DATE'=>'',
			'DUE_DATE'=>'','COMMENT'=>'','ACTION'=>'');
			
		//array_push($trans_RET,array('TITLE'=>$buttonAdd));
		
		echo '<p><b>Student: </b></p><p><b>Fee Balance: </b>'.number_format($totalFee,2).'</p>';
		ListOutput($trans_RET,array('TITLE'=>'Title','AMOUNT'=>'Amount','INSERTED_BY'=>'Inserted By','ASSIGNED_DATE'=>'Assigned Date',
			'DUE_DATE'=>'Due Date','COMMENT'=>'Comment','ACTION'=>'Action'),'Fee','Fees',$link);
	}
	
	if ($displaySearch)
		Search('student_id');
}

?>
