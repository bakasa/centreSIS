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
  
  echo '</td></tr></tbody></table></td></tr><tr><td class="block_left_corner"/><td class="block_middle"/><td class="block_right_corner"/></tr><tr><td class="clear" colspan="3"/></tr></tbody></table>';
  
 ?>
