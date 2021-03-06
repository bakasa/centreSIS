<?php

DrawHeader(ProgramTitle());

$menus_RET = DBGet(DBQuery("SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"),array(),array('MENU_ID'));
if($_REQUEST['menu_id'])
{
	if($_REQUEST['menu_id']!='new')
		if($menus_RET[$_REQUEST['menu_id']])
			$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
		elseif(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	elseif(count($menus_RET))
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
	else
		ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
}
else
{
	if($_SESSION['FSA_menu_id'])
		if($menus_RET[$_SESSION['FSA_menu_id']])
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
		elseif(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	else
		if(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
}

$categories_RET = DBGet(DBQuery("SELECT MENU_ID,CATEGORY_ID,TITLE FROM FOOD_SERVICE_CATEGORIES WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"),array(),array('MENU_ID','CATEGORY_ID'));
if(!$_REQUEST['cat_id'] || !$categories_RET[$_REQUEST['menu_id']][$_REQUEST['cat_id']])
	$_REQUEST['cat_id'] = key($categories_RET[$_REQUEST['menu_id']]);

$meals = array();
foreach($menus_RET as $id=>$menu)
	$meals[] = array('title'=>$menu[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&menu_id=$id");

$cats = array();
foreach($categories_RET[$_REQUEST['menu_id']] as $category_id=>$category)
	$cats[] = array('title'=>$category[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&cat_id=$category_id");

$items_RET = DBGet(DBQuery("SELECT *,(SELECT ICON FROM FOOD_SERVICE_ITEMS WHERE ITEM_ID=fsmi.ITEM_ID) AS ICON FROM FOOD_SERVICE_MENU_ITEMS fsmi WHERE MENU_ID='$_REQUEST[menu_id]' AND CATEGORY_ID='$_REQUEST[cat_id]' ORDER BY (SELECT SORT_ORDER FROM FOOD_SERVICE_CATEGORIES WHERE CATEGORY_ID=fsmi.CATEGORY_ID),SORT_ORDER"));

echo '<BR>';

echo '<CENTER>'.WrapTabs($meals,"Modules.php?modname=$_REQUEST[modname]&menu_id=$_REQUEST[menu_id]").'</CENTER>';

if(count($items_RET))
{
	$per_row = ceil(sqrt(count($items_RET)));
	echo '<CENTER><TABLE border=1 bgcolor=white>';
	foreach($items_RET as $item)
	{
		if(!$i)
		{
			echo '<TR bgcolor=red>';
			$i = $per_row;
		}
		echo '<TD><IMG src='.$FS_IconsPath.'/'.$item['ICON'].' width=125></TD>';
		$i--;
		if(!$i)
			echo '</TR>';
	}
	if($i)
		echo '</TR>';
	echo '</TABLE></CENTER>';
}
echo '<CENTER>'.WrapTabs($cats,"Modules.php?modname=$_REQUEST[modname]&cat_id=$_REQUEST[cat_id]",'',false,'_lower').'</CENTER>';
?>
