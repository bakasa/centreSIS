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

Class Auth
{
	var $ADMIN = 'admin';
	var $PARENT = 'parent';
	var $TEACHER = 'teacher';
	var $STUDENT = 'student';
	var $STUDENT_ID = 0;
	var $ADMIN_ID = 1;
	var $TEACHER_ID = 2;
	var $PARENT_ID = 3;

	function Auth()
	{
	}

	function checkAdmin($profile, $staffId)
	{
		if($profile == $this->ADMIN)
		{
			if($staffId == $this->ADMIN_ID)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function checkParent($profile, $staffId)
	{
		if($profile == $this->PARENT)
		{
			if($staffId == $this->PARENT_ID)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function checkStudent($profile, $staffId)
	{
		if($profile == $this->STUDENT)
		{
			if($staffId == $this->STUDENT_ID)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function checkTeacher($profile, $staffId)
	{
		if($profile == $this->TEACHER)
		{
			if($staffId == $this->TEACHER_ID)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

}
?>