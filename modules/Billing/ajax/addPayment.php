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

session_start();

require '../../../config.inc.php';
require '../../../functions/User.fnc.php';
require '../../../functions/DBGet.fnc.php';
require '../../../functions/Current.php';

require '../classes/Auth.php';
require '../classes/Payment.php';

$auth = new Auth();
$staffId = User('STAFF_ID');
$profile = User('PROFILE');

if($auth->checkAdmin($profile, $staffId))
{
	$studentId = $_REQUEST['STUDENT_ID'];
	$amount    = $_REQUEST['AMOUNT'];
	$comment   = $_REQUEST['COMMENT'];
	$mon	   = $_REQUEST['DATE_Month'];
	$day	   = $_REQUEST['DATE_Day'];
	$yr	       = $_REQUEST['DATE_Year'];
	$type_     = $_REQUEST['TYPE'];

    $monthnames = array(1 => 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
    $mon = array_search($mon,$monthnames);

     $date_      =$mon.'/'.$day.'/'.$yr;

	if(Payment::addPayment($amount,$type_,$studentId,$date_,$comment)){
		echo '{"result":[{"success":true}]}';
	}
	else{
		echo '{"result":[{"success":false}]}';
	}
}
else
{
	echo '{"result":[{"success":false}]}';
}
?>