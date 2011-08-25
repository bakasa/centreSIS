<?php

// config variables for include/Address.inc.php
// set this to false to disable auto-pull-downs for the contact info Description field
$info_apd = true;
// set this to false to disable mailing address display
$use_mailing = true;
// set this to false to disable bus pickoff/dropoff defaulting checked
$use_bus = true;
// set this to false to disable legacy contact info
$use_contact = true;
// these are the static items for the dynamic select lists in the format
// $options = array('Item 1'=>'Item 1','Item 2'=>'Item2');
$city_options = array('Kokomo'=>'Kokomo');
$state_options = array('IN'=>'IN');
$zip_options = array('46901'=>'46901','46902'=>'46902');
$relation_options = array('Father'=>_('Father'),'Mother'=>_('Mother'),'Emergency'=>_('Emergency'));
if($info_apd)
	$info_options_x = array('Phone'=>_('Phone'),'Cell Phone'=>_('Cell Phone'),'Work Phone'=>_('Work Phone'),'Employer'=>_('Employer'));

?>
