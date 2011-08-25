<?php
include 'modules/Students/config.inc.php';

if($_REQUEST['values'] && $_POST['values'])
{
	if(count($_REQUEST['month_values']))
	{
		foreach($_REQUEST['month_values'] as $table=>$month_values)
			foreach($month_values as $column=>$value)
			{
				$_REQUEST['values'][$table][$column] = $_REQUEST['day_values'][$table][$column].'-'.$_REQUEST['month_values'][$table][$column].'-'.$_REQUEST['year_values'][$table][$column];
				if($_REQUEST['values'][$table][$column]=='--')
					$_REQUEST['values'][$table][$column] = '';
				elseif(!VerifyDate($_REQUEST['values'][$table][$column]))
				{
					unset($_REQUEST['values'][$table][$column]);
					$note = _('This date is invalid and could not be saved.');
				}
			}
	}
	unset($_REQUEST['day_values']); unset($_REQUEST['month_values']); unset($_REQUEST['year_values']);

	if($_REQUEST['values']['EXISTING'])
	{
		if($_REQUEST['values']['EXISTING']['address_id'] && $_REQUEST['address_id']=='old')
		{
			$_REQUEST['address_id'] = $_REQUEST['values']['EXISTING']['address_id'];
			if(count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='$_REQUEST[address_id]' AND STUDENT_ID='".UserStudentID()."'")))==0)
			{
				DBQuery("INSERT INTO STUDENTS_JOIN_ADDRESS (ID,STUDENT_ID,ADDRESS_ID) values(".db_seq_nextval('STUDENTS_JOIN_ADDRESS_SEQ').",'".UserStudentID()."','$_REQUEST[address_id]')");
				DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION) SELECT DISTINCT ON (PERSON_ID) ".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'".UserStudentID()."',PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION FROM STUDENTS_JOIN_PEOPLE WHERE ADDRESS_ID='$_REQUEST[address_id]'");
			}
		}
		elseif($_REQUEST['values']['EXISTING']['person_id'] && $_REQUEST['person_id']=='old')
		{
			$_REQUEST['person_id'] = $_REQUEST['values']['EXISTING']['person_id'];
			if(count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]' AND STUDENT_ID='".UserStudentID()."'")))==0)
			{
				DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION) SELECT DISTINCT ON (PERSON_ID) ".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'".UserStudentID()."',PERSON_ID,'$_REQUEST[address_id]',CUSTODY,EMERGENCY,STUDENT_RELATION FROM STUDENTS_JOIN_PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]'");
				if($_REQUEST['address_id']=='0' && count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='0' AND STUDENT_ID='".UserStudentID()."'")))==0)
					DBQuery("INSERT INTO STUDENTS_JOIN_ADDRESS (ID,ADDRESS_ID,STUDENT_ID) values (".db_seq_nextval('STUDENTS_JOIN_ADDRESS_SEQ').",'0','".UserStudentID()."')");

			}
		}
	}

	if($_REQUEST['values']['ADDRESS'])
	{
		if($_REQUEST['address_id']!='new')
		{
			$sql = "UPDATE ADDRESS SET ";

			foreach($_REQUEST['values']['ADDRESS'] as $column=>$value)
			{
				if(!is_array($value))
					$sql .= $column."='".str_replace("\'","''",$value)."',";
				else
				{
					$sql .= $column."='||";
					foreach($value as $val)
					{
						if($val)
							$sql .= str_replace('&quot;','"',$val).'||';
					}
					$sql .= "',";
				}
			}
			$sql = substr($sql,0,-1) . " WHERE ADDRESS_ID='$_REQUEST[address_id]'";
			DBQuery($sql);
		}
		else
		{
			$id = DBGet(DBQuery('SELECT '.db_seq_nextval('ADDRESS_SEQ').' as SEQ_ID '.FROM_DUAL));
			$id = $id[1]['SEQ_ID'];

			$sql = "INSERT INTO ADDRESS ";

			$fields = 'ADDRESS_ID,';
			$values = "'".$id."',";

			$go = 0;
			foreach($_REQUEST['values']['ADDRESS'] as $column=>$value)
			{
				if($value)
				{
					$fields .= $column.',';
					$values .= "'".str_replace("\'","''",$value)."',";
					$go = true;
				}
			}
			$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';
			if($go)
			{
				DBQuery($sql);
				DBQuery("INSERT INTO STUDENTS_JOIN_ADDRESS (ID,STUDENT_ID,ADDRESS_ID,RESIDENCE,MAILING,BUS_PICKUP,BUS_DROPOFF) values(".db_seq_nextval('STUDENTS_JOIN_ADDRESS_SEQ').",'".UserStudentID()."','".$id."','".$_REQUEST['values']['STUDENTS_JOIN_ADDRESS']['RESIDENCE']."','".$_REQUEST['values']['STUDENTS_JOIN_ADDRESS']['MAILING']."','".$_REQUEST['values']['STUDENTS_JOIN_ADDRESS']['BUS_PICKUP']."','".$_REQUEST['values']['STUDENTS_JOIN_ADDRESS']['BUS_DROPOFF']."')");
				$_REQUEST['address_id'] = $id;
			}
		}
	}

	if($_REQUEST['values']['PEOPLE'])
	{
		if($_REQUEST['person_id']!='new')
		{
			$sql = "UPDATE PEOPLE SET ";

			foreach($_REQUEST['values']['PEOPLE'] as $column=>$value)
			{
				$sql .= $column."='".str_replace("\'","''",$value)."',";
			}
			$sql = substr($sql,0,-1) . " WHERE PERSON_ID='$_REQUEST[person_id]'";
			DBQuery($sql);
		}
		else
		{
			$id = DBGet(DBQuery('SELECT '.db_seq_nextval('PEOPLE_SEQ').' as SEQ_ID '.FROM_DUAL));
			$id = $id[1]['SEQ_ID'];

			$sql = "INSERT INTO PEOPLE ";

			$fields = 'PERSON_ID,';
			$values = "'".$id."',";

			$go = 0;
			foreach($_REQUEST['values']['PEOPLE'] as $column=>$value)
			{
				if($value)
				{
					$fields .= $column.',';
					$values .= "'".str_replace("\'","''",$value)."',";
					$go = true;
				}
			}
			$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($values,0,-1) . ')';
			if($go)
			{
				DBQuery($sql);
				DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,PERSON_ID,STUDENT_ID,ADDRESS_ID,CUSTODY,EMERGENCY) values(".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'$id','".UserStudentID()."','".$_REQUEST['address_id']."','".$_REQUEST['values']['STUDENTS_JOIN_PEOPLE']['CUSTODY']."','".$_REQUEST['values']['STUDENTS_JOIN_PEOPLE']['EMERGENCY']."')");
				if($_REQUEST['address_id']=='0' && count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='0' AND STUDENT_ID='".UserStudentID()."'")))==0)
					DBQuery("INSERT INTO STUDENTS_JOIN_ADDRESS (ID,ADDRESS_ID,STUDENT_ID) values (".db_seq_nextval('STUDENTS_JOIN_ADDRESS_SEQ').",'0','".UserStudentID()."')");
				$_REQUEST['person_id'] = $id;
			}
		}
	}

	if($_REQUEST['values']['PEOPLE_JOIN_CONTACTS'])
	{
		foreach($_REQUEST['values']['PEOPLE_JOIN_CONTACTS'] as $id=>$values)
		{
			if($id!='new')
			{
				$sql = "UPDATE PEOPLE_JOIN_CONTACTS SET ";

				foreach($values as $column=>$value)
				{
					$sql .= $column."='".str_replace("\'","''",$value)."',";
				}
				$sql = substr($sql,0,-1) . " WHERE ID='$id'";
				DBQuery($sql);
			}
			else
			{
				if($info_apd || $values['TITLE'] && $values['TITLE']!=_('Example Phone') && $values['VALUE'] && $values['VALUE']!='(xxx) xxx-xxxx')
				{
					$sql = "INSERT INTO PEOPLE_JOIN_CONTACTS ";

					$fields = 'ID,PERSON_ID,';
					$vals = db_seq_nextval('PEOPLE_JOIN_CONTACTS_SEQ').",'$_REQUEST[person_id]',";

					$go = 0;
					foreach($values as $column=>$value)
					{
						if($value)
						{
							$fields .= $column.',';
							$vals .= "'".str_replace("\'","''",$value)."',";
							$go = true;
						}
					}
					$sql .= '(' . substr($fields,0,-1) . ') values(' . substr($vals,0,-1) . ')';
					if($go)
						DBQuery($sql);
				}
			}
		}
	}

	if($_REQUEST['values']['STUDENTS_JOIN_PEOPLE'] && $_REQUEST['person_id']!='new')
	{
		$sql = "UPDATE STUDENTS_JOIN_PEOPLE SET ";

		foreach($_REQUEST['values']['STUDENTS_JOIN_PEOPLE'] as $column=>$value)
		{
			$sql .= $column."='".str_replace("\'","''",$value)."',";
		}
		$sql = substr($sql,0,-1) . " WHERE PERSON_ID='$_REQUEST[person_id]' AND STUDENT_ID='".UserStudentID()."'";
		DBQuery($sql);
	}

	if($_REQUEST['values']['STUDENTS_JOIN_ADDRESS'] && $_REQUEST['address_id']!='new')
	{
		$sql = "UPDATE STUDENTS_JOIN_ADDRESS SET ";

		foreach($_REQUEST['values']['STUDENTS_JOIN_ADDRESS'] as $column=>$value)
		{
			$sql .= $column."='".str_replace("\'","''",$value)."',";
		}
		$sql = substr($sql,0,-1) . " WHERE ADDRESS_ID='$_REQUEST[address_id]' AND STUDENT_ID='".UserStudentID()."'";
		DBQuery($sql);
	}

	unset($_REQUEST['modfunc']);
	unset($_REQUEST['values']);
}

if($_REQUEST['modfunc']=='delete')
{
	if($_REQUEST['contact_id'])
	{
		if(DeletePrompt('contact information'))
		{
			DBQuery("DELETE FROM PEOPLE_JOIN_CONTACTS WHERE ID='$_REQUEST[contact_id]'");
			unset($_REQUEST['modfunc']);
		}
	}
	elseif($_REQUEST['person_id'])
	{
		if(DeletePrompt('contact'))
		{
			DBQuery("DELETE FROM STUDENTS_JOIN_PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]' AND ADDRESS_ID='$_REQUEST[address_id]' AND STUDENT_ID='".UserStudentID()."'");
			if(count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]'")))==0)
			{
				DBQuery("DELETE FROM PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]'");
				DBQuery("DELETE FROM PEOPLE_JOIN_CONTACTS WHERE PERSON_ID='$_REQUEST[person_id]'");
			}
			if($_REQUEST['address_id']=='0' && count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_PEOPLE WHERE ADDRESS_ID='0' AND STUDENT_ID='".UserStudentID()."'")))==0)
			{
				DBQuery("DELETE FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='0' AND STUDENT_ID='".UserStudentID()."'");
				unset($_REQUEST['address_id']);
			}
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['person_id']);
		}
	}
	elseif($_REQUEST['address_id'])
	{
		if(DeletePrompt('address'))
		{
			DBQuery("UPDATE STUDENTS_JOIN_PEOPLE SET ADDRESS_ID='0' WHERE STUDENT_ID='".UserStudentID()."' AND ADDRESS_ID='$_REQUEST[address_id]'");
			if(count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_PEOPLE WHERE STUDENT_ID='".UserStudentID()."' AND ADDRESS_ID='0'"))) && count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='0' AND STUDENT_ID='".UserStudentID()."'")))==0)
				DBQuery("UPDATE STUDENTS_JOIN_ADDRESS SET ADDRESS_ID='0',RESIDENCE=NULL,MAILING=NULL,BUS_PICKUP=NULL,BUS_DROPOFF=NULL WHERE STUDENT_ID='".UserStudentID()."' AND ADDRESS_ID='$_REQUEST[address_id]'");
			else
				DBQuery("DELETE FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID='".UserStudentID()."' AND ADDRESS_ID='$_REQUEST[address_id]'");
			if(count(DBGet(DBQuery("SELECT '' FROM STUDENTS_JOIN_ADDRESS WHERE ADDRESS_ID='$_REQUEST[address_id]'")))==0)
				DBQuery("DELETE FROM ADDRESS WHERE ADDRESS_ID='$_REQUEST[address_id]'");
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['address_id']);
		}
	}
}

if(!$_REQUEST['modfunc'])
{
	$addresses_RET = DBGet(DBQuery("SELECT a.ADDRESS_ID,             sjp.STUDENT_RELATION,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,a.MAIL_ZIPCODE,  sjp.CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,sja.BUS_DROPOFF,".db_case(array('a.ADDRESS_ID',"'0'",'1','0'))."AS SORT_ORDER FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS_JOIN_PEOPLE sjp WHERE a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID='".UserStudentID()."' AND a.ADDRESS_ID=sjp.ADDRESS_ID AND sjp.STUDENT_ID=sja.STUDENT_ID
				  UNION SELECT a.ADDRESS_ID,'No Contacts' AS STUDENT_RELATION,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,a.MAIL_ZIPCODE,'' AS CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,sja.BUS_DROPOFF,".db_case(array('a.ADDRESS_ID',"'0'",'1','0'))." AS SORT_ORDER FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja                          WHERE a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID='".UserStudentID()."' AND NOT EXISTS (SELECT '' FROM STUDENTS_JOIN_PEOPLE sjp WHERE sjp.STUDENT_ID=sja.STUDENT_ID AND sjp.ADDRESS_ID=a.ADDRESS_ID) ORDER BY SORT_ORDER,RESIDENCE,CUSTODY,STUDENT_RELATION"),array(),array('ADDRESS_ID'));
	//echo '<pre>'; var_dump($addresses_RET); echo '</pre>';

	if(count($addresses_RET)==1 && $_REQUEST['address_id']!='new' && $_REQUEST['address_id']!='old' && $_REQUEST['address_id']!='0')
		$_REQUEST['address_id'] = key($addresses_RET).'';

	echo '<TABLE><TR><TD valign=top>';
	echo '<TABLE border=0 cellpadding=0 cellspacing=0>';
	if(count($addresses_RET) || $_REQUEST['address_id']=='new' || $_REQUEST['address_id']=='0')
	{
		$i = 1;
		if($_REQUEST['address_id']=='')
			$_REQUEST['address_id'] = key($addresses_RET).'';

		if(count($addresses_RET))
		{
			foreach($addresses_RET as $address_id=>$addresses)
			{
				echo '<TR>';

				if($address_id!='0')
				{
				// find other students associated with this address
				$xstudents = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,RESIDENCE,BUS_PICKUP,BUS_DROPOFF,MAILING FROM STUDENTS s,STUDENTS_JOIN_ADDRESS sja WHERE s.STUDENT_ID=sja.STUDENT_ID AND sja.ADDRESS_ID='$address_id' AND sja.STUDENT_ID!='".UserStudentID()."'"));
				if(count($xstudents))
				{
					$warning = Localize('colon',_('Other students associated with this address')).'<BR>';
					foreach($xstudents as $xstudent)
					{
						$ximages = '';
						if($xstudent['RESIDENCE']=='Y')
							$ximages .= ' <IMG SRC=assets/house_button.gif>';
						if($xstudent['BUS_PICKUP']=='Y' || $xstudent['BUS_DROPOFF']=='Y')
							$ximages .= ' <IMG SRC=assets/bus_button.gif>';
						if($xstudent['MAILING']=='Y')
							$ximages .= ' <IMG SRC=assets/mailbox_button.gif>';
						$warning .= '<b>'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$xstudent['FULL_NAME']).'</b>'.$ximages.'<BR>';
					}
					echo '<TD>'.button('warning','','# onMouseOver=\'stm(["Warning","'.$warning.'"],["white","#006699","","","",,"black","#e8e8ff","","","",,,,2,"#006699",2,,,,,"",,,,]);\' onMouseOut=\'htm()\'').'</TD>';
				}
				else
					echo '<TD></TD>';
				}
				else
					echo '<TD></TD>';

				$relation_list = '';
				foreach($addresses as $address)
					$relation_list .= ($address['STUDENT_RELATION']&&strpos($address['STUDENT_RELATION'].', ',$relation_list)==false?$address['STUDENT_RELATION']:'---').', ';
				$address = $addresses[1];
				$relation_list = substr($relation_list,0,-2);

				$images = '';
				if($address['RESIDENCE']=='Y')
					$images .= ' <IMG SRC=assets/house_button.gif>';
				if($address['BUS_PICKUP']=='Y' || $address['BUS_DROPOFF']=='Y')
					$images .= ' <IMG SRC=assets/bus_button.gif>';
				if($address['MAILING']=='Y')
					$images .= ' <IMG SRC=assets/mailbox_button.gif>';
				echo '<TD colspan=2 style="border:1; border-style: none none solid none;"><B>'.$relation_list.'</B>'.($relation_list&&$images?'<BR>':'').$images.'</TD>';

				echo '</TR>';

				//$address = $relations[key($relations)][1];
				$style = '';
				if($i!=count($addresses_RET))
					$style = ' style="border:1; border-style: none none dashed none;"';
				elseif($i!=1)
					$style = ' style="border:1; border-style: dashed none none none;"';
				$style .= ' ';

				if($address_id==$_REQUEST['address_id'] && $_REQUEST['address_id']!='0' && $_REQUEST['address_id']!='new')
					$this_address = $address;

				$i++;
				//echo '<A style="cursor: pointer; cursor:hand;">';
				$link = 'onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$address['ADDRESS_ID'].'\';"';
				if($_REQUEST['address_id']==$address['ADDRESS_ID'])
					echo '<TR bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.(($address['ADDRESS_ID']!='0' && AllowEdit())?button('remove','',"Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&address_id=$address[ADDRESS_ID]&modfunc=delete",20):'').'</TD><TD '.$link.' style="color:white;" '.$style.'>';
				else
					echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$address['ADDRESS_ID'].'\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.(($address['ADDRESS_ID']!='0' && AllowEdit())?button('remove','',"Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&address_id=$address[ADDRESS_ID]&modfunc=delete",20):'').'</TD><TD '.$link.' '.$style.'>';
				echo '<A style="cursor: pointer; cursor:hand;">'.$address['ADDRESS'].'<BR>'.($address['CITY']?$address['CITY'].', ':'').$address['STATE'].($address['ZIPCODE']?' '.$address['ZIPCODE']:'').'</A>';
				echo '</TD>';
				echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
				echo '</TR>';
			}
			echo '<TR><TD colspan=3 height=40></TD></TR>';
		}
	}
	else
		echo '<TR><TD colspan=3><small>'._('This student doesn\'t have an address.').'</small></TD></TR>';

	// New Address
	if(AllowEdit())
	{
		$style = '';
		if($_REQUEST['address_id']=='new')
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=new\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD style="color:white;" '.$style.'>';
		else
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=new\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
		echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add a <b>New</b> Address').' &nbsp; </small></A>';
		echo '</TD>';
		echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
		echo '</TR>';

		if($_REQUEST['address_id']=='old')
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=old\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD style="color:white;" '.$style.'>';
		else
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=old\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
		echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add an <b>Existing</b> Address').' &nbsp; </small></A>';
		echo '</TD>';
		echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
		echo '</TR>';

		if($_REQUEST['address_id']=='0' && $_REQUEST['person_id']=='new')
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=0&person_id=new\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD '.$link.' style="color:white;" '.$style.'>';
		else
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=0&person_id=new\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
		echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add a <b>New</b> Contact<BR>without an Address').' &nbsp; </small></A>';
		echo '</TD>';
		echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
		echo '</TR>';

		if($_REQUEST['address_id']=='0' && $_REQUEST['person_id']=='old')
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=0&person_id=old\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD '.$link.' style="color:white;" '.$style.'>';
		else
			echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id=0&person_id=old\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
		echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add an <b>Existing</b> Contact<BR>without an Address').' &nbsp; </small></A>';
		echo '</TD>';
		echo '<TD'.$style.'><A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
		echo '</TR>';
	}
	echo '</TABLE>';
	echo '</TD>';
	echo '<TD width=10 style="border:1; border-style: none dotted none none;">&nbsp;</TD><TD width=10></TD>';

	if(isset($_REQUEST['address_id']))
	{
		echo '<TD valign=top>';
		echo "<INPUT type=hidden name=address_id value=$_REQUEST[address_id]>";

		if($_REQUEST['address_id']!='new' && $_REQUEST['address_id']!='old')
		{
			echo '<TABLE width=100%><TR><TD>';
			echo '<FIELDSET><LEGEND><FONT color=gray>'.($_REQUEST['address_id']=='0'?_('Contacts without an Address'):_('Contacts at this Address')).'</FONT></LEGEND><TABLE border=0 cellpadding=0 cellspacing=0 width=100%>';
			$contacts_RET = DBGet(DBQuery("SELECT p.PERSON_ID,p.FIRST_NAME,p.MIDDLE_NAME,p.LAST_NAME,sjp.CUSTODY,sjp.EMERGENCY,sjp.STUDENT_RELATION FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp WHERE p.PERSON_ID=sjp.PERSON_ID AND sjp.STUDENT_ID='".UserStudentID()."' AND sjp.ADDRESS_ID='".$_REQUEST['address_id']."' ORDER BY sjp.STUDENT_RELATION"));
			$i = 1;
			if(count($contacts_RET))
			{
				foreach($contacts_RET as $contact)
				{
					$THIS_RET = $contact;
					if($contact['PERSON_ID']==$_REQUEST['person_id'])
						$this_contact = $contact;
					//if($i!=count($contacts_RET))
					//	$style = ' style="border-color: #BBBBBB; border: 1; border-style: none none dashed none;"';
					//else
					//	$style = ' style="border-color: #BBBBBB; border: 1; border-style: dashed none none none;"';
					$style .= ' ';

					$i++;
					$link = 'onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id='.$contact['PERSON_ID'].'\';"';
					if(AllowEdit())
						$remove_button = button('remove','',"Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&address_id=$_REQUEST[address_id]&person_id=$contact[PERSON_ID]",20);
					else
						$remove_button = '';
					if($_REQUEST['person_id']==$contact['PERSON_ID'])
						echo '<TR bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.$remove_button.'</TD><TD onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id='.$contact['PERSON_ID'].'\';" style="color:white;" '.$style.'>';
					else
						echo '<TR onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.$remove_button.'</TD><TD '.$link.' '.$style.'>';

					$images = '';

					// find other students associated with this person
					$xstudents = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,STUDENT_RELATION,CUSTODY,EMERGENCY FROM STUDENTS s,STUDENTS_JOIN_PEOPLE sjp WHERE s.STUDENT_ID=sjp.STUDENT_ID AND sjp.PERSON_ID='$contact[PERSON_ID]' AND sjp.STUDENT_ID!='".UserStudentID()."'"));
					if(count($xstudents))
					{
						$warning = Localize('colon',_('Other students associated with this person')).'<BR>';
						foreach($xstudents as $xstudent)
						{
							$ximages = '';
							if($xstudent['CUSTODY']=='Y')
								$ximages .= ' <IMG SRC=assets/gavel_button.gif>';
							if($xstudent['EMERGENCY']=='Y')
								$ximages .= ' <IMG SRC=assets/emergency_button.gif>';
							$warning .= '<b>'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$xstudent['FULL_NAME']).'</b> ('.($xstudent['STUDENT_RELATION']?str_replace(array("'",'"'),array('&#39;','&rdquo;'),$xstudent['STUDENT_RELATION']):'---').')'.$ximages.'<BR>';
						}
						$images .= ' '.button('warning','','# onMouseOver=\'stm(["Warning","'.$warning.'"],["white","#006699","","","",,"black","#e8e8ff","","","",,,,2,"#006699",2,,,,,"",,,,]);\' onMouseOut=\'htm()\'');
					}

					if($contact['CUSTODY']=='Y')
						$images .= ' <IMG SRC=assets/gavel_button.gif>';
					if($contact['EMERGENCY']=='Y')
						$images .= ' <IMG SRC=assets/emergency_button.gif>';

					echo '<A style="cursor:pointer; cursor:hand;"><TABLE><TR><TD>'.$contact['FIRST_NAME'].' '.($contact['MIDDLE_NAME']?$contact['MIDDLE_NAME'].' ':'').$contact['LAST_NAME'].'<BR><small><FONT color='.Preferences('TITLES').'>'.($contact['STUDENT_RELATION']?$contact['STUDENT_RELATION']:'---').'</FONT></small></TD><TD>'.$images.'</TD></TR></TABLE></A>';
					echo '</TD>';
					echo '<TD'.$style.' '.$link.' valign=middle align=right> &nbsp; <A style="cursor: pointer; cursor:hand;"><IMG SRC=assets/arrow_right.gif></A></TD>';
					echo '</TR>';
				}
			}
			else
				echo '<TR><TD colspan=3><small>'.($_REQUEST['address_id']=='0'?_('There are no contacts without an address.'):_('There are no contacts at this address.')).'</small></TD></TR>';

			// New Contact
			if(AllowEdit())
			{
				$style = ' style="border-color: gray; border:1; border-style: solid none none none;"';
				if($_REQUEST['person_id']=='new')
					echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id=new\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD style="color:white;" '.$style.'>';
				else
					echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id=new\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';

				echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add a <b>New</b> Contact').'</small></A>';
				echo '</TD>';
				echo '<TD'.$style.' valign=middle align=right> &nbsp; <A style="cursor: pointer; cursor:hand;" align=right><IMG SRC=assets/arrow_right.gif></A></TD>';
				echo '</TR>';

				$style = ' style="border-color: gray; border:1; border-style: none none none none;"';
				if($_REQUEST['person_id']=='old')
					echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id=old\';" bgcolor='.Preferences('HIGHLIGHT').'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD style="color:white;" '.$style.'>';
				else
					echo '<TR onclick="document.location.href=\'Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&address_id='.$_REQUEST['address_id'].'&person_id=old\';" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.color="white";\' onmouseout=\'this.style.cssText="backgroud-color:transparent; color:black;";\'><TD width=20 align=right'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';

				echo '<A style="cursor: pointer; cursor:hand;"><small>'._('Add an <b>Existing</b> Contact').'</small></A>';
				echo '</TD>';
				echo '<TD'.$style.' valign=middle align=right> &nbsp; <A style="cursor: pointer; cursor:hand;" align=right><IMG SRC=assets/arrow_right.gif></A></TD>';
				echo '</TR>';
			}

			echo '</TABLE></FIELDSET></TD></TR></TABLE>';
		}

		if($_REQUEST['address_id']!='0' && $_REQUEST['address_id']!='old')
		{
			if($_REQUEST['address_id']=='new')
				$size = true;
			else
				$size = false;

			$city_options = _makeAutoSelect('CITY','ADDRESS',array(array('CITY'=>$this_address['CITY']),array('CITY'=>$this_address['MAIL_CITY'])),$city_options);
			$state_options = _makeAutoSelect('STATE','ADDRESS',array(array('STATE'=>$this_address['STATE']),array('STATE'=>$this_address['MAIL_STATE'])),$state_options);
			$zip_options = _makeAutoSelect('ZIPCODE','ADDRESS',array(array('ZIPCODE'=>$this_address['ZIPCODE']),array('ZIPCODE'=>$this_address['MAIL_ZIPCODE'])),$zip_options);

			echo '<TABLE width=100%><TR><TD>';
			echo '<FIELDSET><LEGEND><FONT color=gray>'._('Address').'</FONT></LEGEND><TABLE width=100%>';
			echo '<TR><TD colspan=3>'.TextInput($this_address['ADDRESS'],'values[ADDRESS][ADDRESS]',_('Street'),$size?'size=20':'').'</TD>';
			if($_REQUEST['address_id']!='new' && $_REQUEST['address_id']!='0')
			{
				$display_address = urlencode($this_address['ADDRESS'].', '.($this_address['CITY']?' '.$this_address['CITY'].', ':'').$this_address['STATE'].($this_address['ZIPCODE']?' '.$this_address['ZIPCODE']:''));
				$link = 'http://google.com/maps?q='.$display_address;
				echo '<TD valign=top align=center rowspan=3><A HREF=# onclick=\'window.open("'.$link.'","","scrollbars=yes,resizable=yes,width=800,height=700");\'><IMG SRC=assets/compass_rose_button.gif border=0 width=30><BR><small>'._('Map It').'</small></A></TD>';
			}
			echo '</TR><TR><TD>'._makeAutoSelectInputX($this_address['CITY'],'CITY','ADDRESS',_('City'),$city_options).'</TD>';
			echo '<TD>'._makeAutoSelectInputX($this_address['STATE'],'STATE','ADDRESS',_('State'),$state_options).'</TD>';
			echo '<TD>'._makeAutoSelectInputX($this_address['ZIPCODE'],'ZIPCODE','ADDRESS',_('Zip'),$zip_options).'</TD></TR>';
			echo '<TD colspan=3>'.TextInput($this_address['PHONE'],'values[ADDRESS][PHONE]',_('Phone'),$size?'size=13':'').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
			echo'</TD></TR>';
			echo '</TABLE>';

			if($_REQUEST['address_id']=='new')
			{
				$new = true;
				$this_address['RESIDENCE'] = 'Y';
				$this_address['MAILING'] = 'Y';
				if($use_bus)
				{
					$this_address['BUS_PICKUP'] = 'Y';
					$this_address['BUS_DROPOFF'] = 'Y';
				}
			}

			echo '<TABLE><TR><TD>'.CheckboxInput($this_address['RESIDENCE'],'values[STUDENTS_JOIN_ADDRESS][RESIDENCE]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/house_button.gif></TD><TD>'._('Residence').'</TD></TR>';
			echo '<TR><TD>'.CheckboxInput($this_address['BUS_PICKUP'],'values[STUDENTS_JOIN_ADDRESS][BUS_PICKUP]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/bus_button.gif></TD><TD>'._('Bus Pickup').'</TD></TR>';
			echo '<TR><TD>'.CheckboxInput($this_address['BUS_DROPOFF'],'values[STUDENTS_JOIN_ADDRESS][BUS_DROPOFF]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/bus_button.gif></TD><TD>'._('Bus Dropoff').'</TD></TR>';
			if($use_mailing || $this_address['MAIL_CITY'] || $this_address['MAIL_STATE'] || $this_address['MAIL_ZIPCODE'])
			{
				echo '<script language=javascript> function show_mailing(checkbox){if(checkbox.checked==true) document.getElementById(\'mailing_address_div\').style.visibility=\'visible\'; else document.getElementById(\'mailing_address_div\').style.visibility=\'hidden\';}</script>';
				echo '<TR><TD>'.CheckboxInput($this_address['MAILING'],'values[STUDENTS_JOIN_ADDRESS][MAILING]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>',true,'onclick=show_mailing(this);').'</TD><TD><IMG SRC=assets/mailbox_button.gif></TD><TD>'._('Mailing Address').'</TD></TR></TABLE>';

				echo '<DIV id="mailing_address_div" style="visibility: '.(($this_address['MAILING']||$_REQUEST['address_id']=='new')?'visible':'hidden').';">';
				echo '<FIELDSET><LEGEND><FONT color=gray>'._('Mailing Address').'<BR><small>('._('If different than above').')</small></FONT></LEGEND><TABLE width=100%>';
				echo '<TR><TD colspan=2 style="border-style: solid none none none; border-width: 1;"></TD></TR>';
				echo '</TABLE>';

				echo '<TABLE>';
				echo '<TR><TD colspan=3>'.TextInput($this_address['MAIL_ADDRESS'],'values[ADDRESS][MAIL_ADDRESS]',_('Street'),!$this_address['MAIL_ADDRESS']?'size=20':'').'</TD></TR>';
				echo '<TR><TD>'._makeAutoSelectInputX($this_address['MAIL_CITY'],'MAIL_CITY','ADDRESS',_('City'),$city_options).'</TD>';
				echo '<TD>'._makeAutoSelectInputX($this_address['MAIL_STATE'],'MAIL_STATE','ADDRESS',_('State'),$state_options).'</TD>';
				echo '<TD>'._makeAutoSelectInputX($this_address['MAIL_ZIPCODE'],'MAIL_ZIPCODE','ADDRESS',_('Zip'),$zip_options).'</TD></TR>';
				echo '</TABLE></FIELDSET>';
				echo '</DIV>';
			}
			else
				echo '<TR><TD>'.CheckboxInput($this_address['MAILING'],'values[STUDENTS_JOIN_ADDRESS][MAILING]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/mailbox_button.gif></TD><TD>'._('Mailing Address').'</TD></TR></TABLE>';
		}

		if($_REQUEST['address_id']=='old')
		{
			$addresses_RET = DBGet(DBQuery("SELECT ADDRESS_ID,ADDRESS,CITY,STATE,ZIPCODE FROM ADDRESS WHERE ADDRESS_ID!='0' AND ADDRESS_ID NOT IN (SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID='".UserStudentID()."') ORDER BY ADDRESS,CITY,STATE,ZIPCODE"));
			foreach($addresses_RET as $address)
				$address_select[$address['ADDRESS_ID']] = $address['ADDRESS'].', '.$address['CITY'].', '.$address['STATE'].', '.$address['ZIPCODE'];
			echo SelectInput('','values[EXISTING][address_id]',_('Select Address'),$address_select);
		}

		echo '</TD>';

		if($_REQUEST['person_id'])
		{
			echo '<TD width=10 style="border:1; border-style: none dotted none none;">&nbsp;</TD><TD width=10></TD>';
			echo '<TD valign=top>';
			echo "<INPUT type=hidden name=person_id value=$_REQUEST[person_id]>";

			if($_REQUEST['person_id']!='old')
			{
				$relation_options = _makeAutoSelect('STUDENT_RELATION','STUDENTS_JOIN_PEOPLE',$this_contact['STUDENT_RELATION'],$relation_options);

				echo '<TABLE><TR><TD><FIELDSET><LEGEND><FONT color=gray>'._('Contact Information').'</FONT></LEGEND><TABLE width=100%>';
				if($_REQUEST['person_id']!='new')
				{
                    echo '<TR><TD><DIV id=person_'.$this_contact['PERSON_ID'].'><div onclick=\'addHTML("<TABLE><TR><TD>'.str_replace('"','\"',_makePeopleInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$this_contact['FIRST_NAME']),'FIRST_NAME',_('First')).'</TD><TD>'._makePeopleInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$this_contact['MIDDLE_NAME']),'MIDDLE_NAME',_('Middle')).'</TD><TD>'._makePeopleInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$this_contact['LAST_NAME']),'LAST_NAME',_('Last'))).'</TD></TR></TABLE>","person_'.$this_contact['PERSON_ID'].'",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$this_contact['FIRST_NAME'].' '.$this_contact['MIDDLE_NAME'].' '.$this_contact['LAST_NAME'].'</span><BR><small><FONT color='.Preferences('TITLES').'>Name</FONT></small></div></DIV></TD></TR>';
					echo '<TR><TD>'._makeAutoSelectInputX($this_contact['STUDENT_RELATION'],'STUDENT_RELATION','STUDENTS_JOIN_PEOPLE',_('Relation'),$relation_options).'</TD>';
					//echo '<TR><TD><TABLE><TR><TD><IMG SRC=assets/gavel_button.gif></TD><TD>'.CheckboxInput($this_contact['CUSTODY'],'values[STUDENTS_JOIN_PEOPLE][CUSTODY]','Custody','','','<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/emergency_button.gif></TD><TD>'.CheckboxInput($this_contact['EMERGENCY'],'values[STUDENTS_JOIN_PEOPLE][EMERGENCY]','Emergency','',false,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD></TR></TABLE></TD></TR>';
					echo '<TR><TD><TABLE><TR><TD>'.CheckboxInput($this_contact['CUSTODY'],'values[STUDENTS_JOIN_PEOPLE][CUSTODY]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/gavel_button.gif></TD><TD>'._('Custody').'</TD></TR>';
					echo '<TR><TD>'.CheckboxInput($this_contact['EMERGENCY'],'values[STUDENTS_JOIN_PEOPLE][EMERGENCY]','','CHECKED',$new,'<IMG SRC=assets/check.gif width=15>','<IMG SRC=assets/x.gif width=15>').'</TD><TD><IMG SRC=assets/emergency_button.gif></TD><TD>'._('Emergency').'</TD></TR></TABLE></TD></TR>';

					$info_RET = DBGet(DBQuery("SELECT ID,TITLE,VALUE FROM PEOPLE_JOIN_CONTACTS WHERE PERSON_ID='$_REQUEST[person_id]'"));
					if($info_apd)
						$info_options = _makeAutoSelect('TITLE','PEOPLE_JOIN_CONTACTS',$info_RET,$info_options_x);

					echo '<TR><TD>';

					echo '<TABLE border=0 cellpadding=3 cellspacing=0>';
					if(!$info_apd)
					{
						echo '<TR><TD style="border-color: #BBBBBB; border: 1; border-style: none none solid none;"></TD><TD style="border-color: #BBBBBB; border: 1; border-style: none solid solid none;"><font color=gray>Description</font> &nbsp; </TD><TD style="border-color: #BBBBBB; border: 1; border-style: none none solid none;"><font color=gray>Value</font></TD></TR>';
						if(count($info_RET))
						{
							foreach($info_RET as $info)
							{
							echo '<TR>';
							if(AllowEdit())
								echo '<TD width=20>'.button('remove','',"Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&address_id=$_REQUEST[address_id]&person_id=$_REQUEST[person_id]&contact_id=".$info['ID']).'</TD>';
							else
								echo '<TD></TD>';
							if($info_apd)
								echo '<TD style="border-color: #BBBBBB; border: 1; border-style: none solid none none;">'._makeAutoSelectInputX($info['TITLE'],'TITLE','PEOPLE_JOIN_CONTACTS','',$info_options,$info['ID']).'</TD>';
							else
								echo '<TD style="border-color: #BBBBBB; border: 1; border-style: none solid none none;">'.TextInput($info['TITLE'],'values[PEOPLE_JOIN_CONTACTS]['.$info['ID'].'][TITLE]','','maxlength=100').'</TD>';
							echo '<TD>'.TextInput($info['VALUE'],'values[PEOPLE_JOIN_CONTACTS]['.$info['ID'].'][VALUE]','','maxlength=100').'</TD>';
							echo '</TR>';
							}
						}
						if(AllowEdit() && $use_contact)
						{
							echo '<TR>';
							echo '<TD width=20>'.button('add').'</TD>';
							if($info_apd)
							{
								echo '<TD style="border-color: #BBBBBB; border: 1; border-style: none solid none none;">'.(count($info_options)>1?SelectInput('','values[PEOPLE_JOIN_CONTACTS][new][TITLE]','',$info_options,_('N/A')):TextInput('','values[PEOPLE_JOIN_CONTACTS][new][TITLE]','')).'</TD>';
								echo '<TD>'.TextInput('','values[PEOPLE_JOIN_CONTACTS][new][VALUE]','').'</TD>';
							}
							else
							{
								echo '<TD style="border-color: #BBBBBB; border: 1; border-style: none solid none none;"><INPUT size=15 type=TEXT value="Example Phone" style="color: #BBBBBB;" name=values[PEOPLE_JOIN_CONTACTS][new][TITLE] '."onfocus='if(this.value==\"Example Phone\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\""._('Example Phone')."\"; this.style.color=\"BBBBBB\";}'></TD>";
								echo '<TD><INPUT size=15 type=TEXT value="(xxx) xxx-xxxx" style="color: #BBBBBB;" name=values[PEOPLE_JOIN_CONTACTS][new][VALUE] '."onfocus='if(this.value==\"(xxx) xxx-xxxx\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"(xxx) xxx-xxxx\"; this.style.color=\"BBBBBB\";}'></TD>";
							}
							echo '</TR>';
						}
					}
					else
					{
						if(count($info_RET))
						{
							foreach($info_RET as $info)
							{
								echo '<TR>';
								if(AllowEdit())
									echo '<TD width=20>'.button('remove','',"Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&address_id=$_REQUEST[address_id]&person_id=$_REQUEST[person_id]&contact_id=".$info['ID']).'</TD>';
								else
									echo '<TD></TD>';
                                echo '<TD><DIV id=info_'.$info['ID'].'><div onclick=\'addHTML("<TABLE><TR><TD>'.str_replace('"','\"',TextInput(str_replace(array("'",'"'),array('&#39;','&rdquo;'),$info['VALUE']),'values[PEOPLE_JOIN_CONTACTS]['.$info['ID'].'][VALUE]','','',false).'<BR>'.str_replace("'",'&#39;',_makeAutoSelectInputX($info['TITLE'],'TITLE','PEOPLE_JOIN_CONTACTS','',$info_options,$info['ID'],false))).'</TD></TR></TABLE>","info_'.$info['ID'].'",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$info['VALUE'].'</span><BR><small><FONT color='.($info_options_x[$info['TITLE']]?Preferences('TITLES'):'blue').'>'.$info['TITLE'].'</FONT></small></div></DIV></TD>';
								echo '</TR>';
							}
						}
						if(AllowEdit() && $use_contact)
						{
							echo '<TR>';
							echo '<TD width=20 style="border-color: #BBBBBB; border: 1; border-style: solid none none none;">'.button('add').'</TD>';
							echo '<TD style="border-color: #BBBBBB; border: 1; border-style: solid none none none;">'.TextInput('','values[PEOPLE_JOIN_CONTACTS][new][VALUE]','Value').'<BR>';
							echo (count($info_options)>1?SelectInput('','values[PEOPLE_JOIN_CONTACTS][new][TITLE]','Description',$info_options,_('N/A')):TextInput('','values[PEOPLE_JOIN_CONTACTS][new][TITLE]',_('Description'))).'</TD>';
							echo '</TR>';
						}
					}
					echo '</TABLE>';
					echo '</TD></TR>';
					echo '</TABLE>';
					echo '</FIELDSET>';
					echo '</TD></TR>';

					$categories_RET = DBGet(DBQuery("SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,c.CUSTODY,c.EMERGENCY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED FROM PEOPLE_FIELD_CATEGORIES c,PEOPLE_FIELDS f WHERE f.CATEGORY_ID=c.ID ORDER BY c.SORT_ORDER,c.TITLE,f.SORT_ORDER,f.TITLE"),array(),array('CATEGORY_ID'));
					if($categories_RET)
					{
						$value = DBGet(DBQuery("SELECT * FROM PEOPLE WHERE PERSON_ID='$_REQUEST[person_id]'"));
						$value = $value[1];
						$request = 'values[PEOPLE]';
						foreach($categories_RET as $fields_RET)
						{
							if(!$fields_RET['CUSTODY']&&!$fields_RET['EMERGENCY'] || $fields_RET['CUSTODY']=='Y'&&$this_contact['CUSTODY']=='Y' || $fields_RET['EMERGENCY']=='Y'&&$this_contact['EMERGENCY']=='Y')
							{
								echo '<TR><TD>';
								echo '<FIELDSET><LEGEND><FONT color=gray>'.ParseMLField($fields_RET[1]['CATEGORY_TITLE']).'</FONT></LEGEND>';
								include('modules/Students/includes/Other_Fields.inc.php');
								echo '</FIELDSET>';
								echo '</TD></TR>';
							}
						}
					}
				}
				else
				{
					echo '<TR><TD><TABLE><TR><TD>'.str_replace('"','\"',_makePeopleInput('','FIRST_NAME','<FONT color=red>'._('First').'</FONT>')).'</TD><TD>'.str_replace('"','\"',_makePeopleInput($this_contact['MIDDLE_NAME'],'MIDDLE_NAME',_('Middle'))).'</TD><TD>'.str_replace('"','\"',_makePeopleInput($this_contact['LAST_NAME'],'LAST_NAME','<FONT color=red>'._('Last').'</FONT>')).'</TD></TR></TABLE></TD></TR>';
					echo '<TR><TD>'.SelectInput($this_contact['STUDENT_RELATION'],'values[STUDENTS_JOIN_PEOPLE][STUDENT_RELATION]',_('Relation'),$relation_options,'N/A').'</TD></TR>';
					echo '<TR><TD><TABLE><TR><TD><IMG SRC=assets/gavel_button.gif></TD><TD><INPUT type=checkbox name=values[STUDENTS_JOIN_PEOPLE][CUSTODY] value=Y><BR><small><FONT color='.Preferences('TITLES').'>'._('Custody').'</FONT></small></TD><TD><IMG SRC=assets/emergency_button.gif></TD><TD><INPUT type=checkbox name=values[STUDENTS_JOIN_PEOPLE][EMERGENCY] value=Y><BR><small><FONT color='.Preferences('TITLES').'>'._('Emergency').'</FONT></small></TD></TR></TABLE></TD></TR>';
					echo '</TABLE>';
					echo '</FIELDSET>';
					echo '</TD></TR>';
				}
				echo '</TABLE>';
			}
			elseif($_REQUEST['person_id']=='old')
			{
				$people_RET = DBGet(DBQuery("SELECT DISTINCT p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp WHERE sjp.PERSON_ID=p.PERSON_ID AND sjp.ADDRESS_ID".($_REQUEST['address_id']!='0'?'!=':'=')."'0' AND p.PERSON_ID NOT IN (SELECT PERSON_ID FROM STUDENTS_JOIN_PEOPLE WHERE STUDENT_ID='".UserStudentID()."') ORDER BY LAST_NAME,FIRST_NAME"));
				foreach($people_RET as $people)
					$people_select[$people['PERSON_ID']] = $people['LAST_NAME'].', '.$people['FIRST_NAME'];
				echo SelectInput('','values[EXISTING][person_id]',_('Select Person'),$people_select);
			}
		}
		elseif($_REQUEST['address_id']!='0' && $_REQUEST['address_id']!='new' && $_REQUEST['address_id']!='old')
		{
			$categories_RET = DBGet(DBQuery("SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,c.RESIDENCE,c.MAILING,c.BUS,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED FROM ADDRESS_FIELD_CATEGORIES c,ADDRESS_FIELDS f WHERE f.CATEGORY_ID=c.ID ORDER BY c.SORT_ORDER,c.TITLE,f.SORT_ORDER,f.TITLE"),array(),array('CATEGORY_ID'));
			if($categories_RET)
			{
				echo '<TD width=10 style="border:1; border-style: none dotted none none;">&nbsp;</TD><TD width=10></TD>';
				echo '<TD valign=top>';
				$value = DBGet(DBQuery("SELECT * FROM ADDRESS WHERE ADDRESS_ID='$_REQUEST[address_id]'"));
				$value = $value[1];
				$request = 'values[ADDRESS]';
				echo '<TABLE>';
				foreach($categories_RET as $fields_RET)
				{
					if(!$fields_RET[1]['RESIDENCE']&&!$fields_RET[1]['MAILING']&&!$fields_RET[1]['BUS'] || $fields_RET[1]['RESIDENCE']=='Y'&&$this_address['RESIDENCE']=='Y' || $fields_RET[1]['MAILING']=='Y'&&$this_address['MAILING']=='Y' || $fields_RET[1]['BUS']=='Y'&&($this_address['BUS_PICKUP']=='Y'||$this_address['BUS_DROPOFF']=='Y'))
					{
						echo '<TR><TD>';
						echo '<FIELDSET><LEGEND><FONT color=gray>'.ParseMLField($fields_RET[1]['CATEGORY_TITLE']).'</FONT></LEGEND>';
						include('modules/Students/includes/Other_Fields.inc.php');
						echo '</FIELDSET>';
						echo '</TD></TR>';
					}
				}
				echo '</TABLE>';
			}
		}
		echo '</TD>';
	}
	else
		echo '<TD></TD><TD></TD>';
	echo '</TR>';
	echo '</TABLE>';
	$separator = '<HR>';
	$_REQUEST['category_id'] = '3';
	include('modules/Students/includes/Other_Info.inc.php');
}

function _makePeopleInput($value,$column,$title='')
{	global $THIS_RET;

	if($column=='MIDDLE_NAME')
		$options = 'size=2';
	if($_REQUEST['person_id']=='new')
		$div = false;
	else
		$div = true;

	if($column=='STUDENT_RELATION')
		$table = 'STUDENTS_JOIN_PEOPLE';
	else
		$table = 'PEOPLE';

	return TextInput($value,"values[$table][$column]",$title,$options,false);
}

function _makeAutoSelect($column,$table,$values='',$options=array())
{
	// add the 'new' option, is also the separator
	$options['---'] = '---';
	if(AllowEdit()) // we don't really need the select list if we can't edit anyway
	{
		// add values already in table
		$options_RET = DBGet(DBQuery("SELECT DISTINCT $column,upper($column) AS SORT_KEY FROM $table ORDER BY SORT_KEY"));
		if(count($options_RET))
			foreach($options_RET as $option)
				if($option[$column]!='' && !$options[$option[$column]])
					$options[$option[$column]] = array($option[$column],'<FONT color=blue>'.$option[$column].'</FONT>');
	}
	// make sure values are in the list
	if(is_array($values))
	{
		foreach($values as $value)
			if($value[$column]!='' && !$options[$value[$column]])
				$options[$value[$column]] = array($value[$column],'<FONT color=blue>'.$value[$column].'</FONT>');
	}
	else
		if($values!='' && !$options[$values])
			$options[$values] = array($values,'<FONT color=blue>'.$values.'</FONT>');

	return $options;
}

function _makeAutoSelectInputX($value,$column,$table,$title,$select,$id='',$div=true)
{
	if($column=='CITY' || $column=='MAIL_CITY')
		$options = 'maxlength=60';
	if($column=='STATE' || $column=='MAIL_STATE')
		$options = 'size=3 maxlength=10';
	elseif($column=='ZIPCODE' || $column=='MAIL_ZIPCODE')
		$options = 'maxlength=10';
	else
		$options = 'maxlength=100';

	if($value!='---' && count($select)>1)
		return SelectInput($value,"values[$table]".($id?"[$id]":'')."[$column]",$title,$select,_('N/A'),'',$div);
	else
		return TextInput($value=='---'?array('---','<FONT color=red>---</FONT>'):$value,"values[$table]".($id?"[$id]":'')."[$column]",$title,$options,$div);
}
?>
