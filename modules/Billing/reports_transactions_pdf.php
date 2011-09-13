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
require_once('fpdf.php');

error_reporting(1);

class PDF extends FPDF
{

	function createReport($head, $records){
		$this->SetFillColor(0,0,205);
		$this->SetTextColor(255);
		$this->SetDrawColor(105,105,105);
		$this->SetLineWidth(.3);
		$this->SetFont('','B','12');

		$counter = 0;
		foreach($head as $row){
			/*if($counter == 4){
				$this->Cell(60,7,$row,1,0,'C',1);
			}
			$counter++;*/
			$this->Cell(57,7,$row,1,0,'C',1);
		}
		$this->Ln();

		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont('');

		$bg = 0;
		$counter = 0;
		foreach($records as $record){
			if($counter % 2 == 0){
				$bg = 1;
			}
			else{
				$bg = 0;
			}
			$this->Cell(57,6,$record['STUDENT'],'LR',0,'L',$bg);
			$this->Cell(57,6,$record['FEE'],'LR',0,'L',$bg);
			$this->Cell(57,6,$record['PAYMENT'],'LR',0,'L',$bg);
			$this->Cell(57,6,$record['DATE'],'LR',0,'L',$bg);
			$this->Cell(57,6,$record['COMMENT'],'LR',0,'L',$bg);
			$this->Ln();
			$counter++;
		}
		$this->Cell(285,0,'','T');
	}
}

session_name("CentreSIS");
session_start();

require '../../config.inc.php';
require '../../database.inc.php';
require '../../functions/Current.php';
require '../../functions/PopTable.php';
require '../../functions/DrawTab.fnc.php';
require '../../functions/DBGet.fnc.php';
require '../../functions/User.fnc.php';
require '../../functions/ParseML.fnc.php';
require '../../functions/ProgramTitle.fnc.php';

require 'classes/Auth.php';
require 'classes/Fee.php';

$auth = new Auth();
$staffId = User('STAFF_ID');
$profile = User('PROFILE');

if($auth->checkAdmin($profile, $staffId))
{
	$records = array();

	if(isset($_REQUEST['month_begin'])){
		$beginDate = $_REQUEST['month_begin'].'/'.$_REQUEST['day_begin'].'/'.$_REQUEST['year_begin'];
		$endDate   = $_REQUEST['month_end'].'/'.$_REQUEST['day_end'].'/'.$_REQUEST['year_end'];
	}
	$username = $_REQUEST['USERNAME'];

	if($beginDate == null){
		$beginDate = Date('M/01/Y');
		$endDate   = Date('M/d/Y');
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
			  AND F.INSERTED_DATE >= '".date('Y-m-d',strtotime($beginDate))."'
			  AND F.INSERTED_DATE <= '".date('Y-m-d',strtotime($endDate))."'
			  UNION
			  select
			  P.AMOUNT,
			  P.PAYMENT_TYPE AS TITLE,
			  P.COMMENT,
			  P.PAYMENT_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'P' AS TYPE
			  FROM BILLING_PAYMENT P, STUDENTS S
			  WHERE S.STUDENT_ID = P.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	$query .= ")
			  AND P.PAYMENT_DATE >= '".date('Y-m-d',strtotime($beginDate))."'
			  AND P.PAYMENT_DATE <= '".date('Y-m-d',strtotime($endDate))."'
			  UNION
			  select
			  P.AMOUNT,
			  P.PAYMENT_TYPE AS TITLE,
			  P.COMMENT,
			  P.REFUND_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'PR' AS TYPE
			  FROM BILLING_PAYMENT P, STUDENTS S
			  WHERE S.STUDENT_ID = P.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	$query .= ")
			  AND P.REFUND_DATE >= '".date('Y-m-d',strtotime($beginDate))."'
			  AND P.REFUND_DATE <= '".date('Y-m-d',strtotime($endDate))."'
			  AND P.REFUNDED = 1
			  UNION
			  select
			  F.AMOUNT,
			  F.TITLE,
			  F.COMMENT,
			  F.WAIVED_DATE AS DATE,
			  S.FIRST_NAME,
			  S.LAST_NAME,
			  S.MIDDLE_NAME,
			  'PR' AS TYPE
			  FROM BILLING_FEE F, STUDENTS S
			  WHERE S.STUDENT_ID = F.STUDENT_ID
			  AND
			  (
			   lower(S.last_name) like lower('%$username%')
			   or lower(S.first_name) like lower('%$username%')
		 	   or lower(S.first_name || ' ' || S.last_name) like lower('%$username%')";
	if(is_numeric($username)){
		$query .= " or S.student_id = $username ";
	}

	$query .= ")
			  AND F.WAIVED_DATE >= '".date('Y-m-d',strtotime($beginDate))."'
			  AND F.WAIVED_DATE <= '".date('Y-m-d',strtotime($endDate))."'
			  AND F.WAIVED = 1
			  ORDER BY DATE;";

	$paymentBal = 0;
	$feeBal     = 0;
	$result = DBQuery($query);
	$counter = 0;
	while($row = db_fetch_row($result)){

		$amount     = $row['AMOUNT'];
		$type       = $row['TYPE'];
		$date       = $row['DATE'];
		$fName      = $row['FIRST_NAME'];
		$lName      = $row['LAST_NAME'];
		$mName      = $row['MIDDLE_NAME'];
		$comment    = $row['COMMENT'];

		if($type == 'P'){

			$row = array(
							'STUDENT' => $lName.', '.$fName.' '.$mName.'.',
							'FEE'     => ' ',
							'PAYMENT' => $amount,
							'DATE'    => $date,
							'COMMENT' => $comment
						);
			array_push($records,$row);

			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$paymentBal += $amount;
		}
		else
		if($type =='F'){
			$row = array(
							'STUDENT' => $lName.', '.$fName.' '.$mName.'.',
							'FEE'     => $amount,
							'PAYMENT' => ' ',
							'DATE'    => $date,
							'COMMENT' => $comment
						);
			array_push($records,$row);
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$feeBal += $amount;
		}
		else
		if($type =='PR'){
			$row = array(
							'STUDENT' => $lName.', '.$fName.' '.$mName.'.',
							'FEE'     => ' ',
							'PAYMENT' => '-'.$amount,
							'DATE'    => $date,
							'COMMENT' => 'Refund'
						);
			array_push($records,$row);
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$paymentBal -= $amount;
		}
		else
		if($type =='FW'){
			$row = array(
							'STUDENT' => $lName.', '.$fName.' '.$mName.'.',
							'FEE'     => '-'.$amount,
							'PAYMENT' => ' ',
							'DATE'    => $date,
							'COMMENT' => 'Waiver '.$comment
						);
			array_push($records,$row);
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$feeBal -= $amount;
		}
		$counter++;
	}

	$row = array(
					'STUDENT' => 'Total',
					'FEE'     => '$'.$feeBal,
					'PAYMENT' => '$'.$paymentBal,
					'DATE'    => ' ',
					'COMMENT' => ' '
				);
	array_push($records,$row);

	$tableHead = array('Student',
					   'Fee',
					   'Payment',
					   'Date',
					   'Comment');
	//print_r($records);


	$pdf=new PDF(l);
	$pdf->SetFont('Arial','',12);
	$pdf->SetMargins(7,7,7);
	$pdf->AddPage();
	$pdf->createReport($tableHead, $records);
	$pdf->Output();
}
else
{
	echo 'INVALID USER';
}

?>
