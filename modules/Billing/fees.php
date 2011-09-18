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
$tab = $_REQUEST['TAB'];
//1 or null = fee
//2 = payment

DrawHeader(ProgramTitle());
DrawHeader(SubmitButton("Fees","", "onclick=billing.showFees()")
	.SubmitButton("Payments","", "onclick=billing.showPayments()")
	.SubmitButton("Mass Fees","", "onclick=billing.showMassFees()")
	.SubmitButton("Mass Payments","","onclick=billing.showMassPayments()"));
	
if($tab == 3){
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
}
else
if($tab == 4){
	echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left">';

	echo '</td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

	echo '<div style="width:600px;" align="center">
			<form id="newMassPaymentFrm">
		  	<table>
		  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>Type:</td><td><select name="TYPE">';
	$query = "SELECT type_desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc";
	$result = DBQuery($query);
	while($row = db_fetch_row($result)){
		echo '<option value="'.$row['TYPE_DESC'].'">'.$row['TYPE_DESC'].'</option>';
	}

   echo'</select></td></tr>
		  	<tr><td>Date:</td><td>'.PrepareDate(date('Y-m-d'),'_date').'</td></tr>
		  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	</table>
		  	<table style="width:550px;" cellspacing="0" cellpadding="0">
				<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
				<tr>
					<td style="color:#FFF;" align="left"><input type="checkbox" onclick="billing.selectAll(\'newMassPaymentFrm\', this);" /></td>
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
			  and SE.school_id = ".UserSchool()." ORDER BY S.last_name";

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

	echo '<tr><td colspan="4" align="center"><input type="button" onclick="billing.submitMassPaymentForm();" style="cursor:pointer;" value="Add Selected Payments" /></td></tr>';
	echo '</table></form>';
}
else
if($tab == null || $tab == 1){
	echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left">';

	echo '</td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

	echo '<div style="width:600px;" align="center">
			<div>Search Students:<input type="text" id="studentSearchTB" size="30" /> <input style="cursor:pointer;" onclick="billing.searchStudents();" type="button" value="Search" /></div>
		  	<div id="searchResultsDiv"></div>
		  	<br/>
		  	<h3 id="selectedStuH">
		  	Student: No Student Selected
		  	</h3>
		  	<div id="addFeeDiv" style="display:none;">
		  	<form id="newFeeFrm">
		  	<table>
		  	<tr><td>Title:</td><td><input type="text" size="20" id="title" name="TITLE" /></td></tr>
		  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>Assigned:</td><td>'.PrepareDate(date('Y-m-d'), '_assigned').'</td></tr>
		  	<tr><td>Due Date:</td><td>'.PrepareDate(date('Y-m-d'), '_due').'</td></tr>
		  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	<tr><td colspan="2" align="center"><input type="button" onclick="billing.saveFee();" style="cursor:pointer;" value="Add Fee" /> <input type="button" value="Cancel" style="cursor:pointer;" onclick="billing.hideAddFee();" /></td></tr>
		  	</table>
		  	</form>
		  	</div>
		  	<div id="feesTblDiv">
		  	<table style="width:550px;" cellspacing="0" cellpadding="0">
				<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
				<tr align="center">
					<td style="color:#FFF;">Title</td>
					<td style="color:#FFF;">Amount</td>
					<td style="color:#FFF;">Assigned</td>
					<td style="color:#FFF;">Due</td>
					<td style="color:#FFF;">Comment</td>
					<td style="color:#FFF;">Action</td>
				</tr>
				</thead>
				<tr><td colspan="6" style="background-color:#FFFF99">No Student Selected</td></tr>
		  	</table>
		  	</div>
	  </div>';

}
else
if($tab == 2){
	echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left">';

	echo '</td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

	echo '<div style="width:600px;" align="center">
			<div>Search Students:<input type="text" id="studentSearchTB" size="30" /> <input style="cursor:pointer;" onclick="billing.searchStudents_payment();" type="button" value="Search" /></div>
		  	<div id="searchResultsDiv"></div>
		  	<br/>
		  	<h3 id="selectedStuH">
		  	Student: No Student Selected
		  	</h3>
		  	<div id="addPaymentDiv" style="display:none;">
		  	<form id="newPaymentFrm">
		  	<table>
		  	<tr><td>Amount:</td><td><input type="text" size="20" id="amount" name="AMOUNT" /></td></tr>
		  	<tr><td>Type:</td><td><select name="TYPE">';
	$query = "select type_desc from BILLING_PAYMENT_TYPE order by type_desc";
	$result = DBQuery($query);
		while($row = db_fetch_row($result)){
			echo '<option value="'.$row['TYPE_DESC'].'">'.$row['TYPE_DESC'].'</option>';
	}
	echo '</select></td></tr>
		  	<tr><td>Date:</td><td>'.PrepareDate(date('Y-m-d'),'_date').'</td></tr>
		  	<tr><td>Comment:</td><td><input type="text" size="20" id="comment" name="COMMENT" /></td></tr>
		  	<tr><td colspan="2" align="center"><input type="button" onclick="billing.savePayment();" style="cursor:pointer;" value="Add Payment" /> <input type="button" value="Cancel" style="cursor:pointer;" onclick="billing.hideAddPayment();" /></td></tr>
		  	</table>
		  	</form>
		  	</div>
		  	<div id="paymentTblDiv">
		  	<table style="width:550px;" cellspacing="0" cellpadding="0">
				<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
				<tr align="center">
					<td style="color:#FFF;">Amount</td>
					<td style="color:#FFF;">Type</td>
					<td style="color:#FFF;">Date</td>
					<td style="color:#FFF;">Comment</td>
					<td style="color:#FFF;">Action</td>
				</tr>
				</thead>
				<tr><td colspan="6" style="background-color:#FFFF99">No Student Selected</td></tr>
		  	</table>
		  	</div>
	  </div>';

}


echo '</td></tr></tbody></table></td></tr><tr><td class="block_left_corner"/><td class="block_middle"/><td class="block_right_corner"/></tr><tr><td class="clear" colspan="3"/></tr></tbody></table>';

?>
