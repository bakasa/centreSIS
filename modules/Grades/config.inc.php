<?php

// --- config variables for InputFinalGrades.php
// set to <0 for letter grades only, >0 for percent grades only, 0 for both
// note if using letters only, percents should still be assigned to the letter grades in ReportCardGrades
$does_letter_percent = 0;
// set to true to hide grade comment except for attendance period courses
$hide_non_attendance_comment = false;
// length to truncate comments in the add comment pull-down, set to 0 for no truncate
$commentsB_len = 20;
// set to true to force allow_edit for teachers
// note admins will still follow their permissions/profile setting
// note this is mostly for testing but could be useful if for some reason teachers needed to edit grades after grading ended
$teacher_allow_edit = false;

// --- config variables for StudentGrades.php
// set this to true to enable anonamous grade statistics for all users including parents and students
$do_stats = false;
// remove this line if you don't want teachers and admins to have 'em either
$do_stats |= User('PROFILE')=='teacher' || User('PROFILE')=='admin';


// --- config variables for HonorRoll.php
// names for honor roll and high honor roll
$honor = _('A/B');
$high_honor = _('All A');
// student field column number for custom student fields that specifies newspaper and internet publishing permission
// should be ceckbox type, checked to allow student's name in newspaper and internet
//$for_news_web = '26';
?>
