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

function getStudentName ($studentID)
{
	$query = "select last_name,first_name,middle_name from students where student_id = $studentID;";
	
	$ret = DBGet(DBQuery($query));
	
	$name = "";
	if (!empty($ret))
		$name = $ret[1]['LAST_NAME'].','.$ret[1]['FIRST_NAME'].' '.$ret[1]['MIDDLE_NAME'];
	
	return $name;
}

?>
