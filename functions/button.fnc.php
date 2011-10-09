<?php

function button($type,$text='',$link='',$width=15)
{
	$button = '';
	
	if($type=='dot')
	{
		$button = '<TABLE border=0 cellpadding=0 cellspacing=0 height='.$width.' width='.$width.' bgcolor='.$text.'><TR><TD>';
		$button .= '<IMG SRC=assets/dot.gif height='.$width.' width='.$width.' border=0 vspace=0 hspace=0>';
		$button .= '</TD></TR></TABLE>';
	}
	else
	{
		if($text)
			$button = '<TABLE border=0 cellpadding=0 cellspacing=0 height=10><TR><TD>';
		if($link)
			$button .= "<A HREF=".$link.">";
		$button .= "<IMG SRC='assets/".$type."_button.gif' ".($width?"width=$width":'')." border=0 vspace=0>";
		if($link)
			$button .= '</A>';

		if($text)
		{
			$button .= "</TD><TD valign=middle>";
			$button .= "<font face=verdana size=1 color=#000000><b>";
			if($link)
				$button .= "<A HREF=".$link.">";
			$button .= $text;
			if($link)
				$button .= '</A>';
			$button .= "</b></font>";
			$button .= "</TD>";
			$button .= "</TR></TABLE>";
		}
	}

	return $button;
}
?>
