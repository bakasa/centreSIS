<?php

// example:
//
//	if(DeletePrompt('Title'))
//	{
//		DBQuery("DELETE FROM BOK WHERE id='$_REQUEST[benchmark_id]'");
//	}


function DeletePrompt($title,$action='delete')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'));

	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR>';
		PopTable('header',_('Confirm').(strpos($action,' ')===false?' '._(ucwords($action)):''));
		echo "<CENTER><h4>".sprintf(_('Are you sure you want to %s that %s?'),_($action),_($title))."</h4><FORM action=$PHP_tmp_SELF&delete_ok=1 METHOD=POST><INPUT type=submit value=\""._('OK')."\"><INPUT type=button name=delete_cancel value=\""._('Cancel')."\" onclick='javascript:history.go(-1);'></FORM></CENTER>";
		PopTable('footer');
		return false;
	}
	else
		return true;
}

function Prompt($title='Confirm',$question='',$message='',$pdf='')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'),$pdf==true?array('_CENTRE_PDF'=>true):array());

	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR>';
		PopTable('header',_($title));
		echo "<CENTER><h4>$question</h4><FORM action=$PHP_tmp_SELF&delete_ok=1 METHOD=POST>$message<BR><BR><INPUT type=submit value=\""._('OK')."\"><INPUT type=button name=delete_cancel value=\""._('Cancel')."\" onclick='javascript:history.go(-1);'></FORM></CENTER>";
		PopTable('footer');
		return false;
	}
	else
		return true;
}

?>