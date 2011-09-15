<?php

function DrawTab($title,$link='',$tabcolor='',$textcolor='#FFFFFF',$type='',$rollover='')
{
    $title = ParseMLField($title);
	if(substr($title,0,1)!='<')
		$title = preg_replace("/ /","&nbsp;",$title);
	if(!$tabcolor)
		$tabcolor = Preferences('HEADER');

	$block_table = "<table border=0 cellspacing=0 cellpadding=0>";
	$block_table .= "  <tr style='background-color:$tabcolor' id=tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>";
	$block_table .= "    <td height=14><IMG SRC=assets/left_corner$type.gif border=0></td><td height=14 class=\"BoxHeading\" valign=middle>";
	if($link)
	{
		if(is_array($rollover))
			$rollover = " onmouseover=\"document.getElementById('tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.backgroundColor='".$rollover['tabcolor']."';document.getElementById('tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.color='".$rollover['textcolor']."';\" onmouseout=\"document.getElementById('tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.backgroundColor='$tabcolor';document.getElementById('tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.color='".$textcolor."';\" ";
		if(!isset($_REQUEST['_CENTRE_PDF']))
			$block_table .= "<A HREF='$link' class=BoxHeading style='color:$textcolor' $rollover id=tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>$title</A>";
		else
			$block_table .= "<font color=$textcolor face=Verdana,Arial,sans-serif size=-2><b>$title</b></font>";
	}
	else
	{
		if(!isset($_REQUEST['_CENTRE_PDF']))
			$block_table .= "<font color=$textcolor>" . $title . "</font>";
		else
			$block_table .= "<font color=$textcolor face=Verdana,Arial,sans-serif size=-2><b>" . $title . "</b></font>";
	}
	$block_table .= "</td><td height=14><IMG SRC=assets/right_corner$type.gif border=0></td>";
	$block_table .= "  </tr>";
	$block_table .= "</table>\n";
	return $block_table;
}

function DrawRoundedRect($title,$link='',$tabcolor='#333366',$textcolor='#FFFFFF',$type='',$rollover='')
{
	if(substr($title,0,1)!='<')
		$title = preg_replace("/ /","&nbsp;",$title);
	if(!$tabcolor)
		$tabcolor = Preferences('HEADER');

	$block_table .= "<table border=0 cellspacing=0 cellpadding=0>";
	$block_table .= "  <tr style='background-color:$tabcolor' id=tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>";
	$block_table .= "    <td height=5 width=5 valign=top><IMG SRC=assets/left_upper_corner.gif border=0></td><td rowspan=3 width=100% class=\"BoxHeading\" valign=middle>";
	if($link)
	{
		if(is_array($rollover))
			$rollover = " onmouseover=\"document.getElementById('tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.backgroundColor='".$rollover['tabcolor']."';document.getElementById('tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.color='".$rollover['textcolor']."';\" onmouseout=\"document.getElementById('tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.backgroundColor='$tabcolor';document.getElementById('tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]').style.color='".$textcolor."';\" ";
		if(!isset($_REQUEST['_CENTRE_PDF']))
			$block_table .= "<A HREF='$link' class=BoxHeading style='color:$textcolor' $rollover id=tab_link[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>".ParseMLField($title)."</A>";
		else
			$block_table .= "<font color=$textcolor face=Verdana,Arial,sans-serif size=-2><b>".ParseMLField($title)."</b></font>";
	}
	else
	{
		if(!isset($_REQUEST['_CENTRE_PDF']))
			$block_table .= "<font color=$textcolor>" . ParseMLField($title) . "</font>";
		else
			$block_table .= "<font color=$textcolor face=Verdana,Arial,sans-serif size=-2><b>" . ParseMLField($title) . "</b></font>";
	}
	$block_table .= "</td><td height=5 width=5 valign=top><IMG SRC=assets/right_upper_corner.gif border=0></td>";
	$block_table .= "  </tr>";

	// MIDDLE ROW
	$block_table .= "  <tr style='background-color:$tabcolor' id=tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>";
	$block_table .= "    <td width=5>&nbsp;</td>";
	$block_table .= "<td width=5>&nbsp;</td>";
	$block_table .= "  </tr>";


	// BOTTOM ROW
	$block_table .= "  <tr style='background-color:$tabcolor' id=tab[".preg_replace('/[^a-zA-Z0-9]/','_',$link)."]>";
	$block_table .= "    <td height=5 width=5 valign=bottom><IMG SRC=assets/left_lower_corner.gif border=0></td>";
	$block_table .= "<td height=5 width=5 valign=bottom><IMG SRC=assets/right_lower_corner.gif border=0></td>";
	$block_table .= "  </tr>";



	$block_table .= "</table>\n";
	return $block_table;
}
?>
