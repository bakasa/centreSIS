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
			$this->Cell(71,7,$row,1,0,'C',1);
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
			$this->Cell(71,6,$record['STUDENT'],'LR',0,'L',$bg);
			$this->Cell(71,6,$record['ID'],'LR',0,'L',$bg);
			$this->Cell(71,6,$record['GRADE'],'LR',0,'L',$bg);
			$this->Cell(71,6,$record['BALANCE'],'LR',0,'L',$bg);
			$this->Ln();
			$counter++;
		}
		$this->Cell(285,0,'','T');
	}
}

session_start();

require '../../config.inc.php';
require '../../functions/User.fnc.php';
require '../../functions/DBGet.fnc.php';
require '../../functions/Current.php';

require 'classes/Auth.php';
require 'classes/Fee.php';

$auth = new Auth();
$staffId = User('STAFF_ID');
$profile = User('PROFILE');

if($auth->checkAdmin($profile, $staffId))
{
	$records = array();

	$username = $_REQUEST['USERNAME'];

	$query = "SELECT
				  S.last_name,
				  S.first_name,
				  S.middle_name,
				  S.student_id,
				  GL.title
				  FROM
				  SCHOOL_GRADELEVELS GL,
				  students S,
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

	$result = DBQuery($query);
	while($row = db_fetch_row($result)){

		$fName  = $row['FIRST_NAME'];
		$lName  = $row['LAST_NAME'];
		$middle = $row['MIDDLE_NAME'];
		$grade  = $row['TITLE'];
		$id     = $row['STUDENT_ID'];

		$query2 ="SELECT SUM(amount) AS total_payment FROM BILLING_PAYMENT WHERE student_id = $id and refunded = 0;";
		$result2 = DBQuery($query2);
		$row2 = db_fetch_row($result2);
		$totalPayment = $row2['TOTAL_PAYMENT'];
		if($totalPayment == null){
			$totalPayment = "$0.00";
		}

		$query3 ="select SUM(amount) AS total_fee from BILLING_FEE where student_id = $id and waived = 0;";
		$result3 = DBQuery($query3);
		$row3 = db_fetch_row($result3);
		$totalFee = $row3['TOTAL_FEE'];
		if($totalFee == null){
			$totalFee = "$0.00";
		}

		$totalPayment = str_replace(",", "", $totalPayment);
		$totalFee     = str_replace(",", "", $totalFee);
		$totalPayment = substr($totalPayment,1);
		$totalFee = substr($totalFee,1);

		$balance = $totalFee - $totalPayment;
		$balance = '$'.number_format($balance,2);

		$row_ = array(
						'STUDENT'   => $lName.', '.$fName.' '.$middle.'.',
						'ID'        => $id,
						'GRADE'     => $grade,
						'BALANCE'   => $balance
					);
		array_push($records,$row_);
	}

	$tableHead = array('Student',
					   'Student Id',
					   'Grade',
					   'Balance');
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
