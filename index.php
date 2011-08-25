<?php
error_reporting(1);
require_once('Warehouse.php');
if($_REQUEST['modfunc']=='logout')
{
	if($_SESSION)
	{
		session_destroy();
		header("Location: $_SERVER[PHP_SELF]?modfunc=logout".(($_REQUEST['reason'])?'&reason='.$_REQUEST['reason']:''));
	}
}
elseif($_REQUEST['modfunc']=='create_account')
{
	if(!$ShowCreateAccount)
		unset($_REQUEST['modfunc']);
}

if($_REQUEST['USERNAME'] && $_REQUEST['PASSWORD'])
{
	$_REQUEST['USERNAME'] = DBEscapeString($_REQUEST['USERNAME']);
	$_REQUEST['PASSWORD'] = DBEscapeString($_REQUEST['PASSWORD']);

	$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN FROM STAFF WHERE SYEAR='$DefaultSyear' AND UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]') AND UPPER(PASSWORD)=UPPER('$_REQUEST[PASSWORD]')"));
	if(!$login_RET)
		$student_RET = DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN FROM STUDENTS s,STUDENT_ENROLLMENT se WHERE UPPER(s.USERNAME)=UPPER('$_REQUEST[USERNAME]') AND UPPER(s.PASSWORD)=UPPER('$_REQUEST[PASSWORD]') AND se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='$DefaultSyear' AND CURRENT_DATE>=se.START_DATE AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)"));
	if(!$login_RET && !$student_RET && $CentreAdmins)
	{
		$admin_RET = DBGet(DBQuery("SELECT STAFF_ID FROM STAFF WHERE PROFILE='admin' AND SYEAR='$DefaultSyear' AND STAFF_ID IN ($CentreAdmins) AND UPPER(PASSWORD)=UPPER('$_REQUEST[PASSWORD]')"));
		if($admin_RET)
		{
			$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN FROM STAFF WHERE SYEAR='$DefaultSyear' AND UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]')"));
			if(!$login_RET)
				$student_RET = DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN FROM STUDENTS s,STUDENT_ENROLLMENT se WHERE UPPER(s.USERNAME)=UPPER('$_REQUEST[USERNAME]') AND se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='$DefaultSyear' AND CURRENT_DATE>=se.START_DATE AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)"));
		}
	}
	if($login_RET && ($login_RET[1]['PROFILE']=='admin' || $login_RET[1]['PROFILE']=='teacher' || $login_RET[1]['PROFILE']=='parent'))
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];
		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];
		$failed_login = $login_RET[1]['FAILED_LOGIN'];
		if($admin_RET)
			DBQuery("UPDATE STAFF SET LAST_LOGIN=CURRENT_TIMESTAMP WHERE STAFF_ID='".$admin_RET[1]['STAFF_ID']."'");
		else
			DBQuery("UPDATE STAFF SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL WHERE STAFF_ID='".$login_RET[1]['STAFF_ID']."'");

		if(Config('LOGIN')=='No')
		{
			if(!$_REQUEST['submit'])
			{
				Warehouse('header');
				echo "<FORM action=index.php method=POST><INPUT type=hidden name=USERNAME value='$_REQUEST[USERNAME]'><INPUT type=hidden name=PASSWORD value='$_REQUEST[PASSWORD]'>";
				PopTable('header',_('Confirm Successful Installation'));
				echo '<CENTER>';
				echo '<h4>'._('You have successfully installed Centre/SIS Student Information System.').'<BR>'._('Since this is your first login, Centre/SIS would like to tell our servers that you have successfully installed the software.').' '._('Is this OK?').'</h4>'._('You will not see this message again.').'<BR>';
				echo '<BR><INPUT type=submit name=submit value="'._('OK').'"><INPUT type=submit name=submit value="'._('Cancel').'">';
				echo '</CENTER>';
				PopTable('footer');
				echo '</FORM>';
				Warehouse('footer_plain');
				exit;
			}
			elseif($_REQUEST['submit']==_('OK'))
			{
				DBQuery("UPDATE CONFIG SET LOGIN='Yes'");
				@mail('info@centresis.org','NEW CENTRE INSTALL',"INSERT INTO CENTRE_LOG (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME) values('$_SERVER[SERVER_NAME]','$_SERVER[SERVER_ADDR]','".date('Y-m-d')."','$CentreVersion','$_SERVER[PHP_SELF]','$_SERVER[DOCUMENT_ROOT]','$_SERVER[SCRIPT_NAME]')");
				if(function_exists('mysql_query'))
				{
					$link = @mysql_connect('go.centresis.org','centre_log','centre_log');
					@mysql_select_db('centre_log');
					@mysql_query("INSERT INTO install_log (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME) values('$_SERVER[SERVER_NAME]','$_SERVER[SERVER_ADDR]','".date('Y-m-d')."','$CentreVersion','$_SERVER[PHP_SELF]','$_SERVER[DOCUMENT_ROOT]','$_SERVER[SCRIPT_NAME]')");
					@mysql_close($link);
				}
			}
			elseif($_REQUEST['submit']==_('Cancel'))
				DBQuery("UPDATE CONFIG SET LOGIN='Y'");
		}
	}
	elseif($login_RET && $login_RET[1]['PROFILE']=='none')
		$error[] = _('Your account has not yet been activated.').' '._('You will be notified when it has been verified by a school administrator.');
	elseif($student_RET)
	{
		$_SESSION['STUDENT_ID'] = $student_RET[1]['STUDENT_ID'];
		$_SESSION['LAST_LOGIN'] = $student_RET[1]['LAST_LOGIN'];
		$failed_login = $student_RET[1]['FAILED_LOGIN'];
		if($admin_RET)
			DBQuery("UPDATE STAFF SET LAST_LOGIN=CURRENT_TIMESTAMP WHERE STAFF_ID='".$admin_RET[1]['STAFF_ID']."'");
		else
			DBQuery("UPDATE STUDENTS SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL WHERE STUDENT_ID='".$student_RET[1]['STUDENT_ID']."'");
	}
	else
	{
		DBQuery("UPDATE STAFF SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]') AND SYEAR='$DefaultSyear'");
		DBQuery("UPDATE STUDENTS SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]')");
		$error[] = _('Incorrect username or password.').'<BR><CENTER>'._('Please try logging in again.').'</CENTER>';
	}
}

if($_REQUEST['modfunc']=='create_account')
{
	Warehouse('header');
	$_CENTRE['allow_edit'] = true;
	if(!$_REQUEST['staff']['USERNAME'])
	{
		$_REQUEST['staff_id'] = 'new';
		include('modules/Users/User.php');
		Warehouse('footer_plain');
	}
	else
	{
		$_REQUEST['modfunc'] = 'update';
		include('modules/Users/User.php');
		$note[] = _('Your account has been created.').' '._('You will be notified when it has been verified by a school administrator.').' '._('You will then be able to log in.');
		session_destroy();
	}
}

if(!$_SESSION['STAFF_ID'] && !$_SESSION['STUDENT_ID'] && $_REQUEST['modfunc']!='create_account')
{
	Warehouse('header');
	echo "<BODY leftmargin=2 marginwidth=2 background=assets/bg.gif onLoad='document.loginform.USERNAME.focus()'>";
	echo "<br><br>";
	PopTable("header",_('Centre Login'), "width=55%", "5");
	echo '<CENTER>';
	if($_REQUEST['reason'])
		$note[] = _('You must have javascript enabled to use Centre.');
	echo ErrorMessage($error,_('Error'));
	echo ErrorMessage($note,_('Note'));
	echo '</CENTER>';
	echo "<table border=0>
	<tr>
	<td align=right>".DrawPNG('themes/'.Preferences('THEME').'/logo.png','border=0 width=160')."</td>
	<td align=center><form name=loginform method='post' action='index.php'>
	<h4>".Config('TITLE')." </h4>
    <table border='0' cellspacing='0' cellpadding='2'>";

    // ng - choose language
    if (sizeof($CentreLocales) > 1) {
          echo "<tr><td align='right'><b>"._('Language')."</b></td>";
          echo "<td align='left'>";
          foreach ($CentreLocales as $loc)
              echo "<A href=$_SERVER[PHP_SELF]?locale=$loc><IMG src=assets/flags/$loc.png height=20px width=20px border=0 /></A>&nbsp;&nbsp;";
          echo "</td>";
    }
    
	echo "<tr>
		<td align='right'><b>"._('Username')."</b></td>
		<td align='left'><input type='text' name='USERNAME' size='25' maxlength='25' /></td>
	</tr>
	<tr>
		<td align='right'><b>"._('Password')."</b></td>
		<td align='left'><input type='password' name='PASSWORD' size='25' maxlength='25' /></td>
	</tr>
	</table>
	<p>
	<INPUT type=submit value='"._('Login')."'></p>";
	if($ShowCreateAccount)
		echo "<center><font size=-1>[ <A HREF=index.php?modfunc=create_account>"._('Create Account')."</A> ]</font></center>";
	echo "</form>
	</td></tr>
	";

	// System disclaimer.
	echo "
	<tr><td colspan=2>
	<font size=-3>".
	sprintf(_('This is a restricted network. Use of this network, its equipment, and resources is monitored at all times and requires explicit permission from the network administrator and %s. If you do not have this permission in writing, you are violating the regulations of this network and can and will be prosecuted to the full extent of the law. By continuing into this system, you are acknowledging that you are aware of and agree to these terms.'),Config('TITLE'))
	."</font>
	<br><br>
	</td></tr>
	</table>";
	echo "<center><small>"._('Centre SIS').' '.sprintf(_('version %s'),$CentreVersion);
    echo "<BR>&copy; 2004-2009 <A HREF=http://www.miller-group.net>The Miller Group, Inc</A>";
    echo "<br />&copy; 2009 <a href='http://www.glenn-abbey.com'>Glenn Abbey Software, Inc</a>";
    echo "<br />&copy; 2009 <a href='http://www.centresis.org'>Learners Circle, LLC</a>";   // date('Y')
    echo "</small></center>";
	PopTable("footer");
	echo "<br>";
	Warehouse("footer");
}
elseif($_REQUEST['modfunc']!='create_account')
{
	echo "
		<HTML>
			<HEAD><TITLE>".Config('TITLE')."</TITLE></HEAD>";
	echo "<noscript><META http-equiv=REFRESH content='0;url=index.php?modfunc=logout&reason=javascript' /></noscript>";
	echo "<frameset id=mainframeset rows='*,30' border=0 framespacing=0>
				<frameset cols='180,*' border=0>
					<frame name='side' src='Side.php' frameborder='0' />
					<frame name='body' src='Modules.php?modname=".($_REQUEST['modname']='misc/Portal.php')."&failed_login=$failed_login' frameborder='0' style='border: inset #C9C9C9 2px' />
				</frameset>
				<frame name='help' src='Bottom.php' />
			</frameset>
		</HTML>";
}
?>
