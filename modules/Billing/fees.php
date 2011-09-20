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
	if (isset($_REQUEST['student_id']))
	{
		$studentId = $_REQUEST['student_id'];
		
		$query = "SELECT
                  fee_id,
                  amount,
                  title,
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
			
		$buttonAdd = button('add','',"# onclick='javascript:window.open(\"Modules.php?modname=$_REQUEST[modname]&modfunc=detail\",
			\"blank\",\"width=500,height=300\"); return false;'");
		
		array_push($trans_RET,array('TITLE'=>$buttonAdd));
		
		echo '<p><b>Student: </b></p><p><b>Fee Balance: </b>'.number_format($totalFee,2).'</p>';
		ListOutput($trans_RET,array('TITLE'=>'Title','AMOUNT'=>'Amount','ASSIGNED_DATE'=>'Assigned Date',
			'DUE_DATE'=>'Due Date','COMMENT'=>'Comment','WAIVED'=>'Waived'),'Fee','Fees');
	}
	else if ($_REQUEST['modfunc'] == 'detail')
	{
	}
	else
	{
		Search('student_id');
	}
}

?>
