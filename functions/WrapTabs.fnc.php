<?php

function WrapTabs($tabs,$selected='',$title='',$use_blue=false,$type='',&$selected_key)
{
	if($color == '' || $color == '#FFFFFF')
		$color = "#FFFFCC";
	$valign = (substr($type,-6)=='_lower' ? 'top' : 'bottom');

	$row = 0;
     	$characters = 0;
	$rows[0] = "<TABLE border=0 cellpadding=0 cellspacing=0 bgcolor=#FFFFFF><TR>";
	if(count($tabs))
	{
		foreach($tabs as $key=>$tab)
		{
			if(substr($tab['title'],0,1)!='<')
				$tab_len = strlen($tab['title']);
			else
				$tab_len = 0;

			if($characters + $tab_len >= 120)
			{
				$rows[$row] .= "</TR>\n</TABLE>\n\n";
				$row++;
				$rows[$row] .= "<TABLE border=0 cellpadding=0 cellspacing=0 bgcolor=#FFFFFF>\n\t<TR>";
				$characters = 0;
			}

			if($tab['link']==PreparePHP_SELF() || $tab['link']==$selected)
			{
				if($use_blue!==true)
					$rows[$row] .= "<!--BOTTOM-->\n\t\t<TD valign=$valign>" . DrawTab($tab['title'],$tab['link'],$tab['color']?$tab['color']:Preferences('HEADER'),'#FFFFFF',$type.'_selected') . "</TD>";
				else
					$rows[$row] .= "<!--BOTTOM-->\n\t\t<TD valign=$valign>" . DrawTab($tab['title'],$tab['link'],'#333366','#FFFFFF',$type.'_selected') . "</TD>";
				$selected_key = $key;
			}
			elseif($use_blue!==true)
				$rows[$row] .= "\n\t\t<TD valign=$valign>" . DrawTab($tab['title'],$tab['link'],$tab['color']?$tab['color']:'#DDDDDD',$tab['color']?'#FFFFFF':'#000000',$type) . "</TD>";
			else
				$rows[$row] .= "\n\t\t<TD valign=$valign>" . DrawTab($tab['title'],$tab['link'],'#333366','#FFFFFF',$type) . "</TD>";

			$characters += $tab_len + 6;
		}
	}
	$rows[$row] .= "</TR>\n</TABLE>\n\n";

	$i = 0;
	$row_count = count($rows) - 1;
	if($use_blue===true)
		$table .= "<TABLE border=0 width=100% cellpadding=0 cellspacing=0 bgcolor=#FFFFFF><TR><TD width=100%></TD><TD align=right>";
	elseif($use_blue=='center')
		$table .= "<TABLE border=0 width=100% cellpadding=0 cellspacing=0 bgcolor=#FFFFFF align=center><TR><TD align=center>";

	for($key=$row_count;$key>=0;$key--)
	{
		if(!ereg("<!--BOTTOM-->",$rows[$key]))
		{
			$table .= "<TABLE border=0 width=0 cellpadding=0 cellspacing=0 bgcolor=#FFFFFF><TR><TD>";
			$table .= "<IMG SRC=assets/pixel_trans.gif width=" . (($row_count-$i)*6) . " height=1>";
			if($key != 0 || $bottom)
				$table .= "</TD><TD>$rows[$key]</TD><TD rowspan=2>&nbsp;</TD></TR><TR><TD height=1></TD><TD height=5 valign=top></TD></TR></TABLE>";
			else
				$table .= "</TD><TD>$rows[$key]</TD><TD rowspan=2></TD></TR><TR><TD height=0></TD><TD height=0 valign=top></TD></TR></TABLE>";
			$i++;
		}
		else
			$bottom = $key;
	}
	$table .= "<TABLE border=0 cellpadding=0 cellspacing=0 bgcolor=#FFFFFF><TR><TD></TD><TD>" . $rows[$bottom] . "</TD><TD></TD></TR></TABLE>";
	if($use_blue)
		$table .= "</TD></TR><TR><TD colspan=2>";

	if($title!='')
		$table .= "<TABLE width=100% bgcolor=$color border=0 cellpadding=0 cellspacing=0><TR><TD bgcolor=$color width=100%> &nbsp;<font class=FontBox>$title</font></TD></TR></TABLE>";

	if($use_blue)
		$table .= "</TD></TR></TABLE>";

	return $table;
}
?>
