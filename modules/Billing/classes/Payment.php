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
class Payment{

	public static function refundPayment($id, $user){
			$query = "UPDATE BILLING_PAYMENT SET refunded = 1, refund_date = current_timestamp, refund_by = '$user' WHERE payment_id = $id";

			if(DBQuery($query)){
				return true;
			}
			else{
				return false;
			}
	}

	public static function addPayment($amount,$type_,$studentId,$date_,$comment,$user){
			$amount   = mysql_escape_string($amount);
			$comment  = mysql_escape_string($comment);
			$type_    = mysql_escape_string($type_);

			$query = "INSERT INTO BILLING_PAYMENT
					  (payment_id, student_id, amount, payment_type, payment_date, comment, inserted_by)
					  VALUES
					  (".db_seq_nextval('BILLING_PAYMENT_SEQ').",
					  $studentId,
					  '$amount',
					  '$type_',
                      '".date('Y-m-d',strtotime($date_))."',
					  '$comment',
					  '$user');";
			if(DBQuery($query)){
				return true;
			}
			else{
				return false;
			}

	}

	public static function addMassPayment($amount,$type_,$studentIds,$date_,$comment,$user){
				$amount   = mysql_escape_string($amount);
				$comment  = mysql_escape_string($comment);
				$type_    = mysql_escape_string($type_);

				foreach($studentIds as $id){

					$query = "INSERT INTO BILLING_PAYMENT
							  (payment_id, student_id, amount, payment_type, payment_date, comment, inserted_by)
							  VALUES
							  (".db_seq_nextval('BILLING_PAYMENT_SEQ').",
							  $id,
							  '$amount',
							  '$type_',
                              '".date('Y-m-d',strtotime($date_))."',
							  '$comment',
							  '$user');";

					DBQuery($query);
				}

	}

	public static function getPayments($studentId){
		$json = '"payments":[';
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

		$result = DBQuery($query);
		$counter = 1;
		while($row = db_fetch_row($result)){
			$paymentId   = $row['PAYMENT_ID'];
			$amount      = $row['AMOUNT'];
			$date        = $row['PAYMENT_DATE'];
			$comment     = $row['COMMENT'];
			$refunded    = $row['REFUNDED'];
			$paymentType = $row['PAYMENT_TYPE'];

			$json = $json.'{
							"id":"'.$paymentId.'",
						    "amount":"'.$amount.'",
						    "date":"'.$date.'",
						    "refunded":"'.$refunded.'",
						    "type":"'.$paymentType.'",
						    "comment":"'.$comment.'"
                            },';
			$counter++;
		}

        $json = rtrim($json, ",");

		$query2 ="SELECT SUM(amount) AS total_payment FROM BILLING_PAYMENT WHERE student_id = $studentId and refunded = 0;";
		$result2 = DBQuery($query2);
		$row2 = db_fetch_row($result2);
		$totalPayment = $row2['TOTAL_PAYMENT'];
		if($totalPayment == null){
			$totalPayment = "$0.00";
		}

		$query3 ="SELECT SUM(amount) AS total_fee FROM BILLING_FEE WHERE student_id = $studentId and waived = 0;";
		$result3 = DBQuery($query3);
		$row3 = db_fetch_row($result3);
		$totalFee = $row3['TOTAL_FEE'];
		if($totalFee == null){
			$totalFee = "$0.00";
		}

		$json = $json.'],"balance":[{"totalFee":"'.$totalFee.'","totalPayment":"'.$totalPayment.'"}]';

		return $json;
	}

}
?>
