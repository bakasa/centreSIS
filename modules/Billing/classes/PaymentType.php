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
class PaymentType{

	public static function deleteType($id){
			$query = "DELETE FROM BILLING_PAYMENT_TYPE WHERE type_id = $id";
			if(DBQuery($query)){
				return true;
			}
			else{
				return false;
			}
	}

	public static function addType($desc){
			$desc  = mysql_escape_string($desc);

			$query = "INSERT INTO BILLING_PAYMENT_TYPE (type_id,type_desc) values (".db_seq_nextval('BILLING_PAYMENT_TYPE_SEQ').",'$desc')";
			if(DBQuery($query)){
				return true;
			}
			else{
				return false;
			}
	}

	public static function getTypes(){
		$json = '"types":[';

		$query = "SELECT type_id, type_desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc";
		$result = DBQuery($query);
		$counter = 1;
		$size = mysql_num_rows($result);
		while($row = db_fetch_row($result)){
			$id    = $row['TYPE_ID'];
			$desc  = $row['TYPE_DESC'];

			$json = $json.'{
							"id":"'.$id.'",
						    "desc":"'.$desc.'"';
			if($counter == $size){
				$json = $json.'}';
			}
			else{
				$json = $json.'},';
			}
			$counter++;
		}
		$json = $json.']';
		return $json;
	}
}
?>