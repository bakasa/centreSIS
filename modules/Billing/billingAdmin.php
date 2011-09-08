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
echo '<table cellspacing="0" cellpadding="0"><tbody><tr><td width="9"/><td class="block_stroke" align="left"><table class="tab_header_bg_active" cellspacing="0" cellpadding="0" border="0" align="left"><tbody><tr id="tab[]" class="tab_header_bg_active"><td class="tab_header_left_active"/><td class="drawtab_header" align="left" valign="middle">Payment Types</td><td class="tab_header_right_active"/></tr></tbody></table></td></tr><tr><td class="block_topleft_corner"/><td class="block_topmiddle"/><td class="block_topright_corner"/></tr><tr><td class="block_left" rowspan="2"/><td class="block_bg"/><td class="block_right" rowspan="2"/></tr><tr><td><table class="block_bg" width="100%" cellspacing="0" cellpadding="5"><tbody><tr><td class="block_bg">';

echo '<div align="center" style="width:600px;" id="main"><div align="center" id="edit_new_Area"</div>';
echo '<table style="width:300px;" cellspacing="0" cellpadding="1">
			<thead style="border:solid 2px black;background-color:#09C;font-weight:bold;">
			<tr>
				<td style="color:#FFF;">Description</td>
				<td style="color:#FFF;">Delete</td>
			</tr>
			</thead>';

$query = "SELECT type_id, type_desc FROM BILLING_PAYMENT_TYPE ORDER BY type_desc";
$result = DBQuery($query);
$counter = 0;
while($row = db_fetch_row($result)){

	if($counter % 2 == 0){
		echo '<tr style="background-color:#FFFF99">';
	}
	else{
		echo '<tr>';
	}

	echo '<td>'.$row['TYPE_DESC'].'</td>
		  <td><a href="javascript:billing.deletePaymentType('.$row['TYPE_ID'].');">Delete</a></td>
		  </tr>';

}


echo '<tr><td colspan="3"><a align="left" href="javascript:billing.showNewPaymentType();">[+] Add New</a></td></tr></table></div>';


echo '</td></tr></tbody></table></td></tr><tr><td class="block_left_corner"/><td class="block_middle"/><td class="block_right_corner"/></tr><tr><td class="clear" colspan="3"/></tr></tbody></table>';
?>