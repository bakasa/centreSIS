<?php
include "./Warehouse.php";

if (isset($_REQUEST['modfunc']))
{
	if($_REQUEST['modfunc']=='print')
	{
		$_REQUEST = $_SESSION['_REQUEST_vars'];
		$_REQUEST['_CENTRE_PDF'] = true;
		if(strpos($_REQUEST['modname'],'?')!==false)
			$modname = substr($_REQUEST['modname'],0,strpos($_REQUEST['modname'],'?'));
		else
			$modname = $_REQUEST['modname'];
		if(!$htmldocPath)
			$_CENTRE['allow_edit'] = false;
		ob_start();
		include('languages/English/'.$modname);
		include('modules/'.$modname);
		if($htmldocPath)
		{
			if($htmldocAssetsPath)
				$html = eregi_replace('</?CENTER>','',str_replace('assets/',$htmldocAssetsPath,ob_get_contents()));
			else
				$html = eregi_replace('</?CENTER>','',ob_get_contents());
			ob_end_clean();

			// get a temp filename, and then change its extension from .tmp to .html to make htmldoc happy.
			$temphtml=tempnam('','html');
			$temphtml_tmp=$temphtml.'.html';
			rename($temphtml_tmp, $temphtml);

			$fp=@fopen($temphtml,"w+");
			if (!$fp)
				die("Can't open $temphtml");
			fputs($fp,'<HTML><HEAD><TITLE></TITLE></HEAD><BODY>'.$html.'</BODY></HTML>');
			@fclose($fp);

			header("Cache-Control: public");
			header("Pragma: ");
			header("Content-Type: application/pdf");
			header("Content-Disposition: inline; filename=\"".ProgramTitle().".pdf\"\n");

			$orientation = 'portrait';
			if($_REQUEST['expanded_view'] || $_SESSION['orientation'] == 'landscape')
			{
				$orientation = 'landscape';
				unset($_SESSION['orientation']);
			}
			passthru("$htmldocPath --webpage --quiet -t pdf14 --jpeg --no-links --$orientation --footer t --header . --left 0.5in --top 0.5in \"$temphtml\"");
			@unlink($temphtml);
		}
		else
		{
			$html = eregi_replace('</?CENTER>','',ob_get_contents());
			ob_end_clean();
			echo '<HTML><HEAD><TITLE></TITLE></HEAD><BODY>'.$html.'</BODY></HTML>';
		}
	}
}
else
{
	echo "
	<HTML>
		<HEAD><TITLE>"._('Centre School Software')."</TITLE>
        <meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />
		<SCRIPT>
		size = 30;
		function expandFrame()
		{
			if(size==30)
			{
				parent.document.getElementById('mainframeset').rows=\"*,200\";
				size = 200;
			}
			else
			{
				parent.document.getElementById('mainframeset').rows=\"*,30\";
				size = 30;
			}
		}
		</SCRIPT>
		<link rel=stylesheet type=text/css href=assets/themes/".Preferences('THEME')."/stylesheet.css>
		</HEAD>
		<BODY background=assets/themes/".Preferences('THEME')."/bg.jpg leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>";
	echo '<CENTER>';

	echo '<TABLE><TR>';
	if(isset($_SESSION['List_PHP_SELF']) && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_button = 'back.gif';   $back_text = _('Back to Student List'); break;
            case 'staff':   $back_button = 'back_g.gif'; $back_text = _('Back to User List'); break;
            case 'course':  $back_button = 'back_y.gif'; $back_text = _('Back to Course List'); break;
            default: $back_button = 'back_r.gif'; $back_text = sprintf(_('Back to %s List'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<TD width=24><A HREF='.$_SESSION['List_PHP_SELF'].'&bottom_back=true target=body><IMG SRC=assets/'.$back_button.' border=0 vspace=0></A></TD><TD valign=middle class=BottomButton><A HREF='.$_SESSION['List_PHP_SELF'].'&bottom_back=true target=body>'.$back_text.'</A></TD>';
    }
	if(isset($_SESSION['Search_PHP_SELF']) && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_button = 'back.gif';   $back_text = _('Back to Student Search'); break;
            case 'staff':   $back_button = 'back_g.gif'; $back_text = _('Back to User Search'); break;
            case 'course':  $back_button = 'back_y.gif'; $back_text = _('Back to Course Search'); break;
            default: $back_button = 'back_r.gif'; $back_text = sprintf(_('Back to %s Search'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<TD width=24><A HREF='.$_SESSION['Search_PHP_SELF'].'&bottom_back=true target=body><IMG SRC=assets/'.$back_button.' border=0 vspace=0></A></TD><TD valign=middle class=BottomButton><A HREF='.$_SESSION['Search_PHP_SELF'].'&bottom_back=true target=body>'.$back_text.'</A></TD>';
	}
    echo '<TD width=24><A HREF=Bottom.php?modfunc=print target=body><IMG SRC=assets/print.gif border=0 vspace=0></A></TD><TD valign=middle class=BottomButton><A HREF=Bottom.php?modfunc=print target=body>'._('Print').'</A></TD>';
//    echo '<TD><A HREF=# onclick=expandFrame();return false;><IMG SRC=assets/help.gif border=0 vspace=0></A></TD><TD valign=middle class=BottomButton><A HREF=# onclick="expandFrame();return false;">'._('Help').'</A></TD>';
    echo '<TD><A HREF=index.php?modfunc=logout&locale='.$locale.' target=_top><IMG SRC=assets/logout.gif border=0 vspace=0 hspace=0></A></TD><TD valign=middle class=BottomButton><A HREF=index.php?modfunc=logout&locale='.$locale.' target=_top>'._('Logout').'</A></TD></TR></TABLE>';
	echo '<BR><BR>';
	echo '</CENTER>';

//	include 'Help.php';
	include 'Menu.php';

	$profile = User('PROFILE');

	echo '<DIV class=BottomButton>';
	if($_REQUEST['modcat'])
	{
		echo '<b>'.str_replace('_',' ',$_REQUEST['modcat']);
		echo ' : '.$_CENTRE['Menu'][$_REQUEST['modcat']][$_REQUEST['modname']];
		echo '</b>';
	}
	else
		echo '<b>'._('Welcome to Centre Help').'</b>';

	if($help[$_REQUEST['modname']])
	{
		if($student==true)
			$help[$_REQUEST['modname']] = str_replace('your child','yourself',str_replace('your child\'s','your',$help[$_REQUEST['modname']]));

		echo $help[$_REQUEST['modname']];
	}
	else
		echo $help['default'];
	echo '</DIV>';
	echo '</BODY>';
	echo '</HTML>';
}
?>
