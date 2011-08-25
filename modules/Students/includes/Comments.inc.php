<?php
$semester_comments = 0;
//$_CENTRE['allow_edit'] = true;
if($_REQUEST['modfunc']=='update')
{
	$existing_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($semester_comments?GetParentMP('SEM',UserMP()):UserMP())."'"));
	if(!$existing_RET)
		DBQuery("INSERT INTO STUDENT_MP_COMMENTS (SYEAR,STUDENT_ID,MARKING_PERIOD_ID) values('".UserSyear()."','".UserStudentID()."','".($semester_comments?GetParentMP('SEM',UserMP()):UserMP())."')");
	SaveData(array('STUDENT_MP_COMMENTS'=>"STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($semester_comments?GetParentMP('SEM',UserMP()):UserMP())."'"),'',array('COMMENT'=>_('Comment')));
	//unset($_SESSION['_REQUEST_vars']['modfunc']);
	//unset($_SESSION['_REQUEST_vars']['values']);
}
if(!$_REQUEST['modfunc'])
{
	$comments_RET = DBGet(DBQuery("SELECT COMMENT FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($semester_comments?GetParentMP('SEM',UserMP()):UserMP())."'"));
	echo '<TABLE>';
	echo '<TR>';
	echo '<TD valign=bottom>';
	echo '<b>'.$mp['TITLE'].' '._('Comments').'</b><BR>';
	echo '<TEXTAREA id=textarea name=values[STUDENT_MP_COMMENTS]['.UserStudentID().'][COMMENT] cols=66 rows=22'.(AllowEdit()?'':' readonly').' onkeypress="document.getElementById(\'chars_left\').innerHTML=(1121-this.value.length); if(this.value.length>1121) {document.getElementById(\'chars_left\').innerHTML=\''._('Fewer than 0').'\'}">';
	echo $comments_RET[1]['COMMENT'];
	echo '</TEXTAREA>';
	echo '<table><tr><td><IMG SRC=assets/comment_button.gif onload="document.getElementById(\'chars_left\').innerHTML=1121-document.getElementById(\'textarea\').value.length";></td><td><small><div id=chars_left>1121</div></small></td><td><small>'._('characters remaining.').'<small></td></tr></table>';
	echo '</TD>';
	//echo '<TR><TD align=center><INPUT type=submit value=Save></TD></TR>';
	echo '</TR></TABLE>';
	echo "<br><b>* ".Localize('colon',_('If more than one teacher will be adding comments for this student'))."</b><br>";
	echo "<ul><li>"._('Type your name above the comments you enter.')."</li>";
	echo "<li>"._('Leave space for other teachers to enter their comments.')."</li></ul>";

	$_REQUEST['category_id'] = '4';
	$separator = '<hr>';
	include('modules/Students/includes/Other_Info.inc.php');
}
?>