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

if($TAB == 2){

	$beginDate = date('Y-m-01');
	$endDate = date('Y-m-d');
	
	if(isset($_REQUEST['year_min']) && isset($_REQUEST['month_min']) && isset($_REQUEST['day_min']))
		$beginDate = date('Y-m-d',strtotime($_REQUEST['year_min'].'-'.$_REQUEST['month_min'].'-'.$_REQUEST['day_min']));
		
	if(isset($_REQUEST['year_max']) && isset($_REQUEST['month_max']) && isset($_REQUEST['day_max']))	
		$endDate   = date('Y-m-d',strtotime($_REQUEST['year_max'].'-'.$_REQUEST['month_max'].'-'.$_REQUEST['day_max']));

	$username = $_REQUEST['USERNAME'];


	echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left">';

	echo '<table class="tab_header_bg" cellspacing="0" style="cursor:pointer;" cellpadding="0" border="0" onclick="billing.showBalances();" align="left">
			    <tbody><tr id="tab[]" class="tab_header_bg"><td class="tab_header_left"/>
			    <td class="drawinactivetab_header" align="left" valign="middle">Balances</td>
			    <td class="tab_header_right"/></tr></tbody>
			  </table>
			  <table class="tab_header_bg_active" cellspacing="0" cellpadding="0" border="0" align="left">
			      <tbody><tr id="tab[]" class="tab_header_bg_active">
			  	  <td class="tab_header_left_active"/><td class="drawtab_header" align="left" valign="middle">DailyTransactions</td>
			  	  <td class="tab_header_right_active"/></tr></tbody>
			  </table></td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

	echo '<img style="float:left;cursor:pointer;" onclick="billing.showTransactionsPDF();" src="assets/icon-pdf.gif" /><div style="width:600px;" align="center">';

	echo '<form id="filterFrm"><font style="font-weight:bold;">Student</font>&nbsp;<input id="studentFilterTB" name="USERNAME" value="'.$username.'" type="text" size="30" />&nbsp;&nbsp;<input style="cursor:pointer;" type="button" onclick="billing.filterTransReport(2);" value="Filter Student" />&nbsp;&nbsp;<input style="cursor:pointer;" type="button" onclick="billing.filterTransReportAll(2);" value="All Students" />
		  <table><tr><td><font style="font-weight:bold;">Begin</font>&nbsp;'.PrepareDate($beginDate,'_min').'</td>
		  <td><font style="font-weight:bold;">End</font>&nbsp;'.PrepareDate($endDate, '_max').'</td><td><input style="cursor:pointer;" type="button" onclick="billing.filterTransReport(2);" value="Filter Date" /></td></tr>
		  </table></form>
		  <table style="width:550px;" cellspacing="0" cellpadding="1">
			<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
			<tr>
				<td style="color:#FFF;">Student</td>
				<td style="color:#FFF;">Fee</td>
				<td style="color:#FFF;">Payment</td>
				<td style="color:#FFF;">Date</td>
				<td style="color:#FFF;">Comment</td>
			</tr>
			</thead>';


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

		if($counter % 2 == 0){
			echo '<tr style="background-color:#FFFF99">';
		}
		else{
			echo '<tr>';
		}
		if($type == 'P'){
			echo '<td >'.$lName.', '.$fName.' '.$mName.'.</td>
					 <td></td>
					 <td>'.$amount.'</td>
					 <td>'.$date.'</td>
					 <td>'.$comment.'</td>
			 	  </tr>';
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$paymentBal += $amount;
		}
		else
		if($type =='F'){
			echo '<td >'.$lName.', '.$fName.' '.$mName.'.</td>
					 <td>'.$amount.'</td>
					 <td>&nbsp;</td>
					 <td>'.$date.'</td>
					 <td>'.$comment.'</td>
			 	  </tr>';
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$feeBal += $amount;
		}
		else
		if($type =='PR'){
			echo '<td >'.$lName.', '.$fName.' '.$mName.'.</td>
					 <td>&nbsp;</td>
					 <td>-'.$amount.'</td>
					 <td>'.$date.'</td>
					 <td>Refund</td>
			 	  </tr>';
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$paymentBal -= $amount;
		}
		else
		if($type =='FW'){
			echo '<td >'.$lName.', '.$fName.' '.$mName.'.</td>
					 <td>-'.$amount.'</td>
					 <td>&nbsp;</td>
					 <td>'.$date.'</td>
					 <td>Waived '.$comment.'</td>
			 	  </tr>';
			$amount = str_replace(",", "", $amount);
			$amount = substr($amount,1);
			$feeBal -= $amount;
		}
		$counter++;
	}

	echo '<tr><td style="font-weight:bold;">Total</td><td style="font-weight:bold;">$'.number_format($feeBal,2).'</td><td style="font-weight:bold;">$'.number_format($paymentBal,2).'</td><td>&nbsp;</td><td>&nbsp;</td></tr></table></div>';
}
else{
	$username = $_REQUEST['USERNAME'];

	echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left"><table class="tab_header_bg_active" cellspacing="0" cellpadding="0" border="0" align="left"><tbody><tr id="tab[]" class="tab_header_bg_active"><td class="tab_header_left_active"/><td class="drawtab_header" align="left" valign="middle">Balances</td><td class="tab_header_right_active"/></tr></tbody></table>';

	echo '<table class="tab_header_bg" style="cursor:pointer;" onclick="billing.showDaliyTrans();" cellspacing="0" cellpadding="0" border="0" align="left">
		  <tbody><tr id="tab[]" class="tab_header_bg"><td class="tab_header_left"/>
		  <td class="drawinactivetab_header" align="left" valign="middle">Daily Transactions</td>
		  <td class="tab_header_right"/></tr></tbody></table>
		  </td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

	echo '<img style="float:left;cursor:pointer;" onclick="billing.showBalancesPDF();" src="assets/icon-pdf.gif" /><div style="width:600px;" align="center"><form id="filterFrm"><font style="font-weight:bold;">Student</font>&nbsp;<input id="studentFilterTB" name="USERNAME" value="'.$username.'" type="text" size="30" />&nbsp;&nbsp;<input style="cursor:pointer;" type="button" onclick="billing.filterTransReport(1);" value="Filter Student" />&nbsp;&nbsp;<input style="cursor:pointer;" type="button" onclick="billing.filterTransReportAll(1);" value="All Students" /></form><br/>
		  <table style="width:550px;" cellspacing="0" cellpadding="1">
			<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
			<tr>
				<td style="color:#FFF;">Student</td>
				<td style="color:#FFF;">Student ID</td>
				<td style="color:#FFF;">Grade</td>
				<td style="color:#FFF;">Balance</td>
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
	$counter = 0;
	while($row = db_fetch_row($result)){

		$fName  = $row['FIRST_NAME'];
		$lName  = $row['LAST_NAME'];
		$middle = $row['MIDDLE_NAME'];
		$grade  = $row['TITLE'];
		$id     = $row['STUDENT_ID'];

		$query2 ="SELECT SUM(amount) as total_payment FROM BILLING_PAYMENT WHERE student_id = $id and refunded = 0;";
		$result2 = DBQuery($query2);
		$row2 = db_fetch_row($result2);
		$totalPayment = $row2['TOTAL_PAYMENT'];
		if($totalPayment == null){
			$totalPayment = "0";
		}

		$query3 ="SELECT SUM(amount) as total_fee FROM BILLING_FEE WHERE student_id = $id and waived = 0;";
		$result3 = DBQuery($query3);
		$row3 = db_fetch_row($result3);
		$totalFee = $row3['TOTAL_FEE'];
		if($totalFee == null){
			$totalFee = "0";
		}

		$totalPayment = str_replace(",", "", $totalPayment);
		$totalFee     = str_replace(",", "", $totalFee);
		$totalPayment = substr($totalPayment,1);
		$totalFee     = substr($totalFee,1);

		$balance = $totalFee - $totalPayment;
		$balance = number_format($balance, 2);

		if($counter % 2 == 0){
			echo '<tr style="background-color:#FFFF99">';
		}
		else{
			echo '<tr>';
		}
		echo '<td >'.$lName.', '.$fName.' '.$middle.'.</td>
				<td>'.$id.'</td>
				<td>'.$grade.'</td>
				<td>'.$balance.'</td>
			 </tr>';
		$counter++;
	}
	echo '</table></div>';
}

echo '</td></tr></tbody></table></td></tr><tr><td class="block_left_corner"/><td class="block_middle"/><td class="block_right_corner"/></tr><tr><td class="clear" colspan="3"/></tr></tbody></table>';

function buildDateSelect($name, $id, $begin, $p_date){
	$months = array("January","February","March","April","May","June","July","August","September","October","November","December");
	$monthAbr = array("JAN","FEB","MAR","APR","MAY","JUN", "JUL", "AUG","SEP","OCT","NOV","DEC");

	$html = '<select size="1" id="monthSelect'.$id.'" name="'.$name.'_Month">';
	$p_yr  = null;
	$p_day = null;
	$p_mon = null;
	if($p_date != null){
		$p_mon = substr($p_date,0,3);
		$p_day = substr($p_date,4,2);
		$p_yr  = substr($p_date,7);
	}

	$counter = 1;
	$month = date('m');
	foreach($months as $month_loc){
		$abr = $monthAbr[$counter-1];
		if($counter < 10){
			$counter = '0'.$counter;
		}
		if($p_mon != null){
			if($p_mon == $abr){
				$html = $html.'<option selected="true" value="'.$abr.'">'.$month_loc.'</option>';
			}
			else{
				$html = $html.'<option value="'.$abr.'">'.$month_loc.'</option>';
			}
		}
		else{
			if($counter == $month){
				$html = $html.'<option selected="true" value="'.$abr.'">'.$month_loc.'</option>';
			}
			else{
				$html = $html.'<option value="'.$abr.'">'.$month_loc.'</option>';
			}
		}
		$counter++;
	}

	$html = $html.'</select><select size="1" id="daySelect'.$id.'" name="'.$name.'_Day">';

	for($i = 1; $i < 32; $i++)
	{
		if($i < 10){
			$i = "0".$i;
		}
		if($p_day != null){
			if($i == $p_day){
				$html = $html.'<option selected="true" value="'.$i.'">'.$i.'</option>';
			}
			else{
				$html = $html.'<option value="'.$i.'">'.$i.'</option>';
			}
		}
		else{
			if($i == date('d') && !$begin){
				$html = $html.'<option selected="true" value="'.$i.'">'.$i.'</option>';
			}
			else{
				$html = $html.'<option value="'.$i.'">'.$i.'</option>';
			}
		}
	}

	$html = $html.'</select><select size="1" id="yearSelect'.$id.'" name="'.$name.'_Year">';

	$dateObj = date('Y')+10;
	for($i = $dateObj; $i > (date('Y')-2); $i--){
		if($p_yr != null){
			if($p_yr == $i){
				$html = $html.'<option selected="true" value="'.$i.'">'.$i.'</option>';
			}
			else{
				$html = $html.'<option value="'.$i.'">'.$i.'</option>';
			}
		}
		else{
			if($i == date('Y')){
				$html = $html.'<option selected="true" value="'.$i.'">'.$i.'</option>';
			}
			else{
				$html = $html.'<option value="'.$i.'">'.$i.'</option>';
			}
		}
	}

	$html = $html.'</select><img onclick="MakeDate(\''.$id.'\',this);" id="calSelect_'.$id.'" style="cursor:pointer;" src="assets/jscalendar/img.gif"/>';
	return $html;
}
?>
