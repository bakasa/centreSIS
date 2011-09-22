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
class Fee{

    public static function waiveFee($feeId, $username){
        $query = "UPDATE BILLING_FEE
                  SET waived_date = current_timestamp,
                  waived_by = '$username',
                  waived = 1
                  WHERE fee_id = $feeId";
        if(DBQuery($query)){
            return true;
        }
        else{
            return false;
        }
    }

    public static function addMassFee($amount,
        $title,
        $studentIds,
        $dueDate,
        $assignedDate,
        $comment,
        $module,
        $username
    ){
        $amount   = mysql_escape_string($amount);
        $title    = mysql_escape_string($title);
        $comment  = mysql_escape_string($comment);
        $module   = mysql_escape_string($module);
        $username = mysql_escape_string($username);

        foreach($studentIds as $id){

            $query = "INSERT INTO BILLING_FEE
                      (fee_id, student_id, amount, title, assigned_date, due_date, comment, module, inserted_by, inserted_date)
                      VALUES
                      (".db_seq_nextval('BILLING_FEE_SEQ').",
                      $id,
                      '$amount',
                      '$title',
                      '".date('Y-m-d',strtotime($assignedDate))."',
                      '".date('Y-m-d',strtotime($dueDate))."',
                      '$comment',
                      '$module',
                      '$username',
                      current_timestamp);";
            DBQuery($query);
        }
    }

    public static function addFee($amount,
        $title,
        $studentId,
        $dueDate,
        $assignedDate,
        $comment,
        $module,
        $username
    ){
        $amount   = mysql_escape_string($amount);
        $title    = mysql_escape_string($title);
        $comment  = mysql_escape_string($comment);
        $module   = mysql_escape_string($module);
        $username = mysql_escape_string($username);

        $query = "INSERT INTO BILLING_FEE
                  (fee_id, student_id, amount, title, assigned_date, due_date, comment, module, inserted_by, inserted_date)
                  VALUES
                  (".db_seq_nextval('BILLING_FEE_SEQ').",
                  $studentId,
                  $amount,
                  '$title',
                  '".date('Y-m-d',strtotime($assignedDate))."',
                  '".date('Y-m-d',strtotime($dueDate))."',
                  '$comment',
                  '$module',
                  '$username',
                  current_timestamp);";
        if(DBQuery($query)){
            return true;
        }
        else{
            return false;
        }

    }

    public static function getFees($studentId){
        $json = '"fees":[';
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

        $result = DBQuery($query);
        $counter = 1;
        while($row = db_fetch_row($result)){
            $feeId        = $row['FEE_ID'];
            $amount       = $row['AMOUNT'];
            $title        = $row['TITLE'];
            $assignedDate = $row['ASSIGNED_DATE'];
            $comment      = $row['COMMENT'];
            $waived       = $row['WAIVED'];
            $dueDate      = $row['DUE_DATE'];

            $json = $json.'{
                            "id":"'.$feeId.'",
                            "amount":"'.$amount.'",
                            "title":"'.$title.'",
                            "assignedDate":"'.$assignedDate.'",
                            "dueDate":"'.$dueDate.'",
                            "waived":"'.$waived.'",
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
