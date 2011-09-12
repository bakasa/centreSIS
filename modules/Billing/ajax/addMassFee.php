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
header("Content-Type: text/xml");

error_reporting(1);

session_name('CentreSIS');
session_start();

require '../../../config.inc.php';
require '../../../database.inc.php';
require '../../../functions/Current.php';
require '../../../functions/PopTable.php';
require '../../../functions/DrawTab.fnc.php';
require '../../../functions/DBGet.fnc.php';
require '../../../functions/User.fnc.php';
require '../../../functions/ParseML.fnc.php';
require '../../../functions/ProgramTitle.fnc.php';

require '../classes/Auth.php';
require '../classes/Fee.php';

$auth = new Auth();
$staffId = User('STAFF_ID');
$profile = User('PROFILE');

if($auth->checkAdmin($profile, $staffId))
{
	$studentIds = $_REQUEST['STUDENT_ID'];
	$amount     = $_REQUEST['AMOUNT'];
	$title      = $_REQUEST['TITLE'];
	$comment    = $_REQUEST['COMMENT'];
	$module     = $_REQUEST['MODULE'];
	$assMon	    = $_REQUEST['ASSIGNED_Month'];
	$assDay	    = $_REQUEST['ASSIGNED_Day'];
	$assYr	    = $_REQUEST['ASSIGNED_Year'];
	$dueMon     = $_REQUEST['DUE_Month'];
	$dueDay     = $_REQUEST['DUE_Day'];
	$dueYr      = $_REQUEST['DUE_Year'];
	$username   = User('USERNAME');

    $monthnames = array(1 => 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
    $dueMon = array_search($dueMon,$monthnames);
	$dueDate = $dueMon.'/'.$dueDay.'/'.$dueYr;
    $assMon = array_search($assMon,$monthnames);
	$assignedDate = $assMon.'/'.$assDay.'/'.$assYr;

	Fee::addMassFee($amount,$title,$studentIds,$dueDate,$assignedDate,$comment,$module,$username);
	echo '{"result":[{"success":true}]}';

}
else
{
	echo '{"result":[{"success":false}]}';
}
?>
