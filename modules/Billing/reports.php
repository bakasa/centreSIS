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
$TAB = $_REQUEST['TAB'];
// 2 Daily Transactions
// 1 Balance

DrawHeader(ProgramTitle());
DrawHeader(SubmitButton("Balances","","onclick=billing.showBalances()")
	.SubmitButton("Daily Transaction","","onclick=billing.showDaliyTrans()"));

if($TAB == 2){

	$beginDate = date('Y-m-01');
	$endDate = date('Y-m-d');
	
	if(isset($_REQUEST['year_min']) && isset($_REQUEST['month_min']) && isset($_REQUEST['day_min']))
		$beginDate = date('Y-m-d',strtotime($_REQUEST['year_min'].'-'.$_REQUEST['month_min'].'-'.$_REQUEST['day_min']));
		
	if(isset($_REQUEST['year_max']) && isset($_REQUEST['month_max']) && isset($_REQUEST['day_max']))	
		$endDate   = date('Y-m-d',strtotime($_REQUEST['year_max'].'-'.$_REQUEST['month_max'].'-'.$_REQUEST['day_max']));

	$username = $_REQUEST['USERNAME'];

	if($beginDate == null){
		$beginDate = Date('Y-m-01');
		$endDate   = Date('Y-m-d');
	}

	$query = "SELECT
			  F.AMOUNT,
			  F.TITLE,
			  F.COMMENT,
			  F.INSERTED_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'F' AS TYPE
			  FROM
              BILLING_FEE F,
              STUDENTS S
			  WHERE
              S.STUDENT_ID = F.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	$query .= ")
			  AND F.INSERTED_DATE >'".$beginDate."'
			  AND F.INSERTED_DATE <='". $endDate."'
			  UNION
			  SELECT
			  P.AMOUNT,
			  P.PAYMENT_TYPE AS TITLE,
			  P.COMMENT,
			  P.PAYMENT_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'P' AS TYPE
			  FROM
              BILLING_PAYMENT P,
              STUDENTS S
			  WHERE
              S.STUDENT_ID = P.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	$query .= ")
			  AND P.PAYMENT_DATE >='".$beginDate."'
			  AND P.PAYMENT_DATE <='".$endDate."'
			  UNION
			  SELECT
			  P.AMOUNT,
			  P.PAYMENT_TYPE AS TITLE,
			  P.COMMENT,
			  P.REFUND_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'PR' AS TYPE
			  FROM
              BILLING_PAYMENT P, STUDENTS S
			  WHERE
              S.STUDENT_ID = P.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	 $query .= ")
			  AND P.REFUND_DATE >= '".$beginDate."'
			  AND P.REFUND_DATE <='".$endDate."'
			  AND P.REFUNDED = 1
			  UNION
			  SELECT
			  F.AMOUNT,
			  F.TITLE,
			  F.COMMENT,
			  F.WAIVED_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'PR' AS TYPE
			  FROM
              BILLING_FEE F, STUDENTS S
			  WHERE
              S.STUDENT_ID = F.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	 $query .= ")
			  AND F.WAIVED_DATE >= '".$beginDate."'
			  AND F.WAIVED_DATE <= '".$endDate."'
			  AND F.WAIVED = 1
			  ORDER BY DATE;";
	
	$transaction_RET = DBGet(DBQuery($query));
	
	$totalFee = "0";
	$totalPayment = "0";
	$transReport = array();
	$i = 1;
	foreach ($transaction_RET as $trans)
	{
		$type = $trans['TYPE'];
		$amount = $trans['AMOUNT'];
		
		$transReport[$i]['STUDENT'] = $trans['LAST_NAME'].' '.$trans['FIRST_NAME'].' '.$trans['MIDDLE_NAME'];
		$transReport[$i]['DATE'] = $trans['DATE'];
		
		$fee = '-';
		$payment = '-';
		$comment = "";
		
		if ($type == 'P')
		{
			$totalPayment += $amount;
			$payment = number_format($amount,2);
			$comment = $trans['COMMENT'];
		}
		else if ($type == 'PR')
		{
			$totalPayment -= $amount;
			$payment = number_format(-$amount,2);
			$comment = 'Refund';
		}
		else if ($type == 'F')
		{
			$totalFee += $amount;
			$fee = number_format($amount,2);
			$comment = $trans['COMMENT'];
		}
		else
		{
			$totalFee -= $amount;
			$fee = number_format(-$amount,2);
			$comment = 'Waived';
		}
		
		$transReport[$i]['FEE'] = $fee;
		$transReport[$i]['PAYMENT'] = $payment;
		$transReport[$i]['COMMENT'] = $comment;
		
		$i++;
	}
	
	if ($i > 1)
	{
		$transReport[$i]['STUDENT'] = '<b>TOTAL</b>';
		$transReport[$i]['FEE'] = '<b>'.number_format($totalFee,2).'</b>';
		$transReport[$i]['PAYMENT'] = '<b>'.number_format($totalPayment,2).'</b>';
		$transReport[$i]['DATE'] = '<b>'.date('Y-m-d').'</b>';
	}
	
	echo '<p>';
	ListOutput($transReport,array('STUDENT'=>'Student','FEE'=>'Fee','PAYMENT'=>'Payment','DATE'=>'Date','COMMENT'=>'Comment'),
		'Transaction','Transactions',false,array(),array('center'=>true));
	echo '</p>';
}
else{
	$username = $_REQUEST['USERNAME'];

	$query = "SELECT
			  S.last_name,
			  S.first_name,
			  S.middle_name,
			  S.student_id,
			  GL.title
			  FROM
              SCHOOL_GRADELEVELS GL,
			  STUDENTS S,
			  STUDENT_ENROLLMENT SE
			  WHERE
			  S.student_id = SE.student_id
			  and SE.grade_id = GL.id
			  AND
			  (
			  	lower(S.last_name) like lower('%$username%')
			  	or lower(S.first_name) like lower('%$username%')
			  	or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}
	$query .= ") and SE.school_id = ".UserSchool()." order by S.last_name";
	
	$student_RET = DBGet(DBQuery($query));
	
	foreach ( $student_RET as &$student)
	{
		$studentID = $student['STUDENT_ID'];
		$student['STUDENT'] = $student['LAST_NAME'].' '.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'];
		
		$payment_RET = DBGet(DBQuery("SELECT SUM(amount) as total_payment FROM BILLING_PAYMENT WHERE student_id = $studentID and refunded = 0;"));
		$fee_RET = DBGet(DBQuery("SELECT SUM(amount) as total_fee FROM BILLING_FEE WHERE student_id = $studentID and waived = 0;"));

		$totalPayment = "0";
		if (isset($payment_RET[1]['TOTAL_PAYMENT']))
			$totalPayment = $payment_RET[1]['TOTAL_PAYMENT'];
		
		$totalFee = "0";
		if (isset($fee_RET[1]['TOTAL_FEE']))
			$totalFee = $fee_RET[1]['TOTAL_FEE'];

		$totalPayment = str_replace(",", "", $totalPayment);
		$totalFee     = str_replace(",", "", $totalFee);
		$totalPayment = substr($totalPayment,1);
		$totalFee     = substr($totalFee,1);

		$balance = $totalFee - $totalPayment;
		$balance = number_format($balance, 2);
		
		$student['BALANCE'] = $balance;
	}
	
	echo '<p>';
	ListOutput($student_RET,array('STUDENT'=>'Student','STUDENT_ID'=>'Student ID','TITLE'=>'Grade','BALANCE'=>'Balance'),
		'Student','Students',array('save'=>1,));
	echo '</p>';
}


?>
