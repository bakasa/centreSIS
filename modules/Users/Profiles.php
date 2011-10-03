<?php
DrawHeader(ProgramTitle());

include 'Menu.php';

if($_REQUEST['profile_id']!='')
{
	$exceptions_RET = DBGet(DBQuery("SELECT PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]'"),array(),array('MODNAME'));
	$profile_RET = DBGet(DBQuery("SELECT PROFILE FROM USER_PROFILES WHERE ID='$_REQUEST[profile_id]'"));
	$xprofile = $profile_RET[1]['PROFILE'];
	if($xprofile=='student')
	{
		$xprofile = 'parent';
		unset($menu['Users']);
	}
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	$profile_RET = DBGet(DBQuery("SELECT TITLE FROM USER_PROFILES WHERE ID='$_REQUEST[profile_id]'"));

	if(Prompt(_('Confirm Delete'),sprintf(_('Are you sure you want to delete the user profile <i>%s</i>?'), $profile_RET[1]['TITLE']),sprintf(_('Users of that profile will retain their permissions as a custom set which can be modified on a per-user basis through %s.'), _('User Permissions'))))
	{
		DBQuery("DELETE FROM USER_PROFILES WHERE ID='".$_REQUEST['profile_id']."'");
		DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE PROFILE_ID='".$_REQUEST['profile_id']."')");
		DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT) SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT FROM STAFF s,PROFILE_EXCEPTIONS e WHERE s.PROFILE_ID='$_REQUEST[profile_id]' AND s.PROFILE_ID=e.PROFILE_ID");
		DBQuery("DELETE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".$_REQUEST['profile_id']."'");
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['profile_id']);
		unset($_SESSION['_REQUEST_vars']['profile_id']);
	}
}

if($_REQUEST['modfunc']=='update' && !$_REQUEST['new_profile_title'] && AllowEdit())
{
	$tmp_menu = $menu;
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES"));
	foreach($categories_RET as $category)
	{
		$file = 'Students/Student.php&category_id='.$category['ID'];
		$tmp_menu['Students'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES"));
	foreach($categories_RET as $category)
	{
		$file = 'Users/User.php&category_id='.$category['ID'];
		$tmp_menu['Users'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}

	foreach($tmp_menu as $modcat=>$profiles)
	{
		$values = $profiles[$xprofile];
		foreach($values as $modname=>$title)
		{
			if(!is_numeric($modname))
			{
				if(!count($exceptions_RET[$modname]) && ($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)]))
					DBQuery("INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME) values('$_REQUEST[profile_id]','$modname')");
				elseif(count($exceptions_RET[$modname]) && !$_REQUEST['can_edit'][str_replace('.','_',$modname)] && !$_REQUEST['can_use'][str_replace('.','_',$modname)])
					DBQuery("DELETE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]' AND MODNAME='$modname'");

                if ($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)])
                {
                    $update = "UPDATE PROFILE_EXCEPTIONS SET ";
                    if($_REQUEST['can_edit'][str_replace('.','_',$modname)])
                        $update .= "CAN_EDIT='Y',";
                    else
                        $update .= "CAN_EDIT=NULL,";
                    if($_REQUEST['can_use'][str_replace('.','_',$modname)])
                        $update .= "CAN_USE='Y'";
                    else
                        $update .= "CAN_USE=NULL";
                    $update .= " WHERE PROFILE_ID='$_REQUEST[profile_id]' AND MODNAME='$modname'";
                    DBQuery($update);
                }
			}
		}
	}
	$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]'"),array(),array('MODNAME'));
	unset($tmp_menu);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['can_edit']);
	unset($_SESSION['_REQUEST_vars']['can_edit']);
	unset($_REQUEST['can_use']);
	unset($_SESSION['_REQUEST_vars']['can_use']);
}

if($_REQUEST['new_profile_title'] && AllowEdit())
{
	$id = DBGet(DBQuery("SELECT ".db_seq_nextval('USER_PROFILES_SEQ')." AS ID".FROM_DUAL));
	$id = $id[1]['ID'];
	$exceptions_RET = array();
	DBQuery("INSERT INTO USER_PROFILES (ID,TITLE,PROFILE) values('$id','".$_REQUEST['new_profile_title']."','".$_REQUEST['new_profile_type']."')");
	$_REQUEST['profile_id'] = $id;
	$xprofile = $_REQUEST['new_profile_type'];
	unset($_REQUEST['new_profile_title']);
	unset($_SESSION['_REQUEST_vars']['new_profile_title']);
	unset($_REQUEST['new_profile_type']);
	unset($_SESSION['_REQUEST_vars']['new_profile_type']);
}

if($_REQUEST['modfunc']!='delete')
{
	echo "<FORM action=Modules.php?modname=$_REQUEST[modname]&modfunc=update&profile_id=$_REQUEST[profile_id] method=POST>";
	DrawHeader(_('Select the programs that users of this profile can use and which programs those users can use to save information.'),SubmitButton(_('Save')));
	echo '<BR>';
	echo '<TABLE><TR><TD valign=top>';
	echo '<TABLE border=0 cellpadding=0 cellspacing=0>';
	$style = ' style="border:1; border-style: dashed none none none;"';
	//$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE,PROFILE FROM USER_PROFILES"));
	$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE,PROFILE FROM USER_PROFILES ORDER BY ID"),array(),array('PROFILE','ID'));
	echo '<TR><TD colspan=2 style="border:1; border-style: none none solid none;"><small><b>'._('Profiles').'</b></small></TD></TR>';
	foreach(array('admin','teacher','parent','student') as $profiles)
	{
		foreach($profiles_RET[$profiles] as $id=>$profile)
		{
			if($_REQUEST['profile_id']!='' && $id==$_REQUEST['profile_id'])
				echo '<TR id=selected_tr onmouseover="" onmouseout="" bgcolor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\'><TD width=20 align=right'.$style.'>'.(AllowEdit()&&$id>3?button('remove','',"Modules.php?modname=$_REQUEST[modname]&modfunc=delete&profile_id=$id",20):'').'</TD><TD '.$style.' onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&profile_id='.$id.'\';">';
			else
				echo '<TR onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="background-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.(AllowEdit()&&$id>3?button('remove','',"Modules.php?modname=$_REQUEST[modname]&modfunc=delete&profile_id=$id",20):'').'</TD><TD'.$style.' onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&profile_id='.$id.'\';">';
			echo '<A style="cursor: pointer; cursor:hand;">'.($id>3?'':'<b>').'<small>'.$profile[1]['TITLE'].' &nbsp; </small>'.($id>3?'':'</b>').'</A>';
			echo '</TD>';
			echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
			echo '</TR>';
		}
	}
	if($_REQUEST['profile_id']=='')
		echo '<TR id=selected_tr><TD height=0></TD></TR>';

	if(AllowEdit())
	{
	echo '<TR id=new_tr onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="background-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
	echo '<A style="cursor: pointer; cursor:hand;" onclick=\'document.getElementById("selected_tr").onmouseover="this.style.backgroundColor=\"'.Preferences('HIGHLIGHT').'\"; this.style.color=\"white\";"; document.getElementById("selected_tr").onmouseout="this.style.cssText=\"background-color:transparent; color:black;\";"; document.getElementById("selected_tr").style.cssText="background-color:transparent; color:black;"; changeHTML({"new_id_div":"new_id_content"},["main_div"]);document.getElementById("new_tr").onmouseover="";document.getElementById("new_tr").onmouseout="";this.onclick="";\'><small>'._('Add a User Profile').'&nbsp;<BR><DIV id=new_id_div></DIV> </small></A>';
	echo '</TD>';
	echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
	echo '</TR>';
	}

	echo '</TABLE>';
	echo '</TD><TD width=20></TD><TD>';
	echo '<DIV id=main_div>';
	if($_REQUEST['profile_id']!='')
	{
		PopTable('header',_('Permissions'));
		echo '<TABLE border=0 cellspacing=0>';
		foreach($menu as $modcat=>$profiles)
		{
			$values = $profiles[$xprofile];

			echo '<TR><TD valign=top align=right>';
			echo "<BR><b><font color=gray>".str_replace('_',' ',$modcat)."</font></b></TD><TD width=3>&nbsp;</TD>";
			echo "<TH bgcolor=#FFFFFF><small><font color=gray>"._('Can Use').(AllowEdit()?"<INPUT type=checkbox name=can_use_$modcat onclick='checkAll(this.form,this.form.can_use_$modcat.checked,\"can_use[$modcat\");'>":'')."</font></small></TH>";
			if($xprofile=='admin' || $modcat=='Students' || $modcat=='Resources')
				echo"<TH bgcolor=#FFFFFF> &nbsp;<small><font color=gray>"._('Can Edit').(AllowEdit()?"<INPUT type=checkbox name=can_edit_$modcat onclick='checkAll(this.form,this.form.can_edit_$modcat.checked,\"can_edit[$modcat\");'>":'')."</font></small></TH>";
			else
				echo"<TH bgcolor=#FFFFFF></TH>";
			echo "<TH bgcolor=#FFFFFF></TH></TR>";
			if(count($values))
			{
				foreach($values as $file=>$title)
				{
					if(!is_numeric($file))
					{
						$can_use = $exceptions_RET[$file][1]['CAN_USE'];
						$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

						echo "<TR><TD></TD><TD></TD>";

						echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_use[".str_replace('.','_',$file)."] value=true".($can_use=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
						if($xprofile=='admin' || $modcat=='Resources')
								echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_edit[".str_replace('.','_',$file)."] value=true".($can_edit=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
						else
							echo "<TD align=center bgcolor=#DDDDDD></TD>";
						echo "<TD bgcolor=#DDDDDD> &nbsp; &nbsp;$title</TD></TR><TR><TD></TD><TD></TD><TD colspan=3 height=1 bgcolor=#000000></TR>";

						if($modcat=='Students' && $file=='Students/Student.php')
						{
							$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
							foreach($categories_RET as $category)
							{
								$file = 'Students/Student.php&category_id='.$category['ID'];
								$title = ' &nbsp; &nbsp; &rsaquo; '.ParseMLField($category['TITLE']);
								$can_use = $exceptions_RET[$file][1]['CAN_USE'];
								$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

								echo "<TR><TD></TD><TD></TD>";
								echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_use[".str_replace('.','_',$file)."] value=true".($can_use=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
								echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_edit[".str_replace('.','_',$file)."] value=true".($can_edit=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
								echo "<TD bgcolor=#DDDDDD> &nbsp; &nbsp;$title</TD></TR><TR><TD></TD><TD></TD><TD colspan=3 height=1 bgcolor=#000000></TR>";
							}
						}
						elseif($modcat=='Users' && $file=='Users/User.php')
						{
							$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
							foreach($categories_RET as $category)
							{
								$file = 'Users/User.php&category_id='.$category['ID'];
								$title = ' &nbsp; &nbsp; &rsaquo; '.ParseMLField($category['TITLE']);
								$can_use = $exceptions_RET[$file][1]['CAN_USE'];
								$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

								echo "<TR><TD></TD><TD></TD>";
								echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_use[".str_replace('.','_',$file)."] value=true".($can_use=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
								echo "<TD align=center bgcolor=#DDDDDD><INPUT type=checkbox name=can_edit[".str_replace('.','_',$file)."] value=true".($can_edit=='Y'?' CHECKED':'').(AllowEdit()?'':' DISABLED')."></TD>";
								echo "<TD bgcolor=#DDDDDD> &nbsp; &nbsp;$title</TD></TR><TR><TD></TD><TD></TD><TD colspan=3 height=1 bgcolor=#000000></TR>";
							}
						}
					}
					else
						echo '<TR><TD></TD><TD></TD><TD bgcolor=#FFFFFF colspan=3 align=center><small><b>- '.$title.' -</b></small></TD></TR>';

				}
			}
			echo '<TR><TD colspan=5 align=center height=20></TD></TR>';
		}
		echo '</TABLE>';
		PopTable('footer');
		echo '<CENTER>'.SubmitButton(_('Save')).'</CENTER>';
	}
	echo '</DIV>';
	echo '</TD></TR></TABLE>';
	echo '</FORM>';
	echo '<DIV id=new_id_content style="position:absolute;visibility:hidden;">'._('Title').' <INPUT type=text name=new_profile_title><BR>';
	echo _('Type').' <SELECT name=new_profile_type><OPTION value=admin>'._('Administrator').'<OPTION value=teacher>'._('Teacher').'<OPTION value=parent>'._('Parent').'</SELECT></DIV>';
}
?>