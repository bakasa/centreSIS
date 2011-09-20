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

echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left">';

echo '</td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

echo '<div style="width:600px;" align="center">
		<form id="newMassFeeFrm">
	  	<table>
	  	<tr><td>Title:</td><td><input type="text" size="20" id="title" name="TITLE" /></td></tr>
	  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
	  	<tr><td>Assigned:</td><td>'.PrepareDate(date('Y-m-d'),'_assigned').'</td></tr>
	  	<tr><td>Due Date:</td><td>'.PrepareDate(date('Y-m-d'),'_due').'</td></tr>
	  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
	    </table>
	  	<table style="width:550px;" cellspacing="0" cellpadding="0">
			<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
			<tr>
				<td style="color:#FFF;" align="left"><input type="checkbox" onclick="billing.selectAll(\'newMassFeeFrm\', this);" /></td>
				<td style="color:#FFF;">Student</td>
				<td style="color:#FFF;">Student ID</td>
				<td style="color:#FFF;">Grade</td>
			</tr>
			</thead>';

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
		  and SE.school_id = ".UserSchool()." order by S.last_name";

$result = DBQuery($query);
$counter = 0;
while($row = db_fetch_row($result)){
	$lastName  = $row['LAST_NAME'];
	$firstName = $row['FIRST_NAME'];
	$middle    = $row['MIDDLE_NAME'];
	$id        = $row['STUDENT_ID'];
	$grade     = $row['TITLE'];

	if($counter % 2 == 0){
		echo '<tr style="background-color:#FFFF99">';
	}
	else{
		echo '<tr>';
	}

	echo '<td align="left"><input type="checkbox" name="STUDENT_ID[]" value="'.$id.'" /></td>
		  <td>'.$lastName.', '.$firstName.' '.$middle.'.</td>
		  <td>'.$id.'</td>
		  <td>'.$grade.'</td>
	  	  </tr>';
	$counter++;
}

echo '<tr><td colspan="4" align="center"><input type="button" onclick="billing.submitMassFeeForm();" style="cursor:pointer;" value="Add Selected Fees" /></td></tr>';
echo '</table></form>';

echo '</td></tr></tbody></table></td></tr><tr><td class="block_left_corner"/><td class="block_middle"/><td class="block_right_corner"/></tr><tr><td class="clear" colspan="3"/></tr></tbody></table>';
	
?>
