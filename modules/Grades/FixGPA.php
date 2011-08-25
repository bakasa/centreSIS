<?php
DrawHeader(ProgramTitle());

$mps = GetAllMP('PRO',UserMP());
$mps = explode(',',str_replace("'",'',$mps));
$message = '<TABLE><TR><TD colspan=7 align=center>';
foreach($mps as $mp)
{
	if($mp && $mp!='0')
		$message .= '<INPUT type=radio name=marking_period_id value='.$mp.($mp==UserMP()?' CHECKED':'').'>'.GetMP($mp).'<BR>';
}

$message .= '</TD></TR></TABLE>';
if(Prompt('Confirm','When do you want to recalculate the running GPA numbers?',$message))
{
	$students_RET = GetStuList($extra);

	foreach($students_RET as $student)
	{
		CalcGPA($student['STUDENT_ID'],$_REQUEST['marking_period_id']);
	}

	unset($_REQUEST['modfunc']);
	DrawHeader('<IMG SRC=assets/check.gif> '._('The grades for that marking period have been recalculated.'));
}

?>