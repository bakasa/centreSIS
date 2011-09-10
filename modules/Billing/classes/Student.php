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
class Student{

	public static function searchStudents($value){
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
				  and
				  (
				  lower(S.last_name) like lower('%$value%')
				  or lower(S.first_name) like lower('%$value%')
				  or lower(S.first_name || ' ' || S.last_name) like lower('%$value%')
                  )";

				  if(is_numeric($value)){
				  		$query = $query." or S.student_id = $value";
				  }

		$result = DBQuery($query);
		$json = '"users":[';
        
        while($row = db_fetch_row($result)){
			$lastName  = $row['LAST_NAME'];
			$firstName = $row['FIRST_NAME'];
			$middle    = $row['MIDDLE_NAME'];
			$id        = $row['STUDENT_ID'];
			$grade     = $row['TITLE'];

			$json = $json.'{
							"id":"'.$id.'",
						    "first":"'.$firstName.'",
						    "last":"'.$lastName.'",
						    "middle":"'.$middle.'",
						    "grade":"'.$grade.'"
                            },';
		}
        
        $json = rtrim($json, ",");
		$json = $json.']';
		return $json;
	}
}
?>
