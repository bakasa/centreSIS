<?php
error_reporting(1);
require('Warehouse.php');

echo "<HTML><HEAD><TITLE>Centre SIS</TITLE>
<script language=javascript>
function resizeImages()
{
	var width;
	if(self.innerWidth)
		width = self.innerWidth;
	else if(document.documentElement && document.documentElement.clientWidth)
		width = document.documentElement.clientWidth;
	else if(document.body)
		width = document.body.clientWidth;

	var ratio = width / old_width;
	//image_count = ".count($_CENTRE['Menu']).";
	if(ratio!=0 && ratio!=null)
	{
		for(i=0;i<document.images.length;i++)
			document.images[i].width = Math.round(document.images[i].width * ratio);
	}
	
	old_width = width;
	return true;
}

function getSize()
{
	if(self.innerWidth)
		old_width = self.innerWidth;
	else if(document.documentElement && document.documentElement.clientWidth)
		old_width = document.documentElement.clientWidth;
	else if(document.body)
		old_width = document.body.clientWidth;

	return true;
}
</script>
</HEAD><BODY background=assets/themes/".Preferences('THEME')."/bg.jpg leftmargin=0 topmargin=4 onload='getSize();'>";
// System Information
echo '<TABLE cellpadding=0 cellspacing=0 border=0 height=100% width=100%>';
echo '<TR>';
echo '<TD valign=middle width=170><A HREF=index.php target=_top><IMG id=logo SRC="assets/themes/'.Preferences('THEME').'/logo.png" border=0></A></TD>';
echo '<TD width=15></TD><TD><TABLE width=100% border=0 cellpadding=2 cellspacing=0 style="border: 1px inset #999999"><TR bgcolor=#E8E8E9>';
require('Menu.php');
foreach($_CENTRE['Menu'] as $modcat=>$value)
{
	if($value)
	{
		echo '<TD width=5></TD><TD align=center>';
		echo "<A HREF=Side.php?modcat=$modcat target=side onclick='javascript:parent.body.location=\"Modules.php?modname=$modcat/Search.php\";'>";
		echo "<DIV style='border:1px solid #E8E8E9;' onmouseover='this.style.border=\"1px outset #969696\";' onmouseout='this.style.border=\"1px solid #E8E8E9\";' onmousedown='this.style.border=\"1px inset #999999\";' onmouseup='this.style.border=\"1px outset #999999\";'>";
		echo "<IMG SRC=assets/icons/$modcat.png border=0><BR><small><b>".str_replace('_',' ',$modcat)."</b></small>";
		echo '</DIV>';
		echo '</A>';
		echo '</TD>';
	}
}
echo "</TR></TABLE></TD></TR></TABLE>";

echo '</BODY></HTML>';

?>