<?php
if(!defined('CONFIG_INC'))
{
	define('CONFIG_INC',1);
	// IgnoreFiles should contain any names of files or folders
	// which should be ignored by the function inclusion system.
	$IgnoreFiles = Array('.DS_Store','CVS','.svn');

	// Database Setup
	$DatabaseType = 'postgres';		// oracle, postgres
	$DatabaseANSI = true;			// ANSI compliant flag.
	$DatabaseServer = '127.0.0.1';	// postgres = host, oracle=SID
	$DatabaseUsername = 'centre';
	$DatabasePassword = 'centre';
	$DatabaseName = 'centresis';
	$DatabasePort = '5432';

	// Server Names and Paths
	$CentrePath = dirname(__FILE__).'/';
	$htmldocPath = '/usr/bin/htmldoc';	// empty string means htmldoc will not be called and reports will be rendered in htlm instead of pdf
	$htmldocAssetsPath = '';		// way htmldoc accesses the assets/ directory, possibly different than user
							// empty string means no translation, reasonable examples follow
	//$htmldocAssetsPath = $CentrePath.'assets/';
    //$htmldocAssetsPath = "http://$_SERVER[HTTP_HOST]".substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/')).'/assets/';
	//$htmldocAssetsPath = 'http://127.0.0.1/centresis/assets/';
	$StudentPicturesPath = 'assets/StudentPhotos/';
	$UserPicturesPath = 'assets/UserPhotos/';
	$FS_IconsPath = 'assets/FS_icons/';

	$CentreTitle = 'Centre School Information System';
	$CentreAdmins = '1';			// can be list such as '1,23,50' - note, these should be id's in the DefaultSyear,
							// otherwise they can't login anyway
	$CentreNotifyAddress = 'soporte@multilink.com.ve';
	$DefaultSyear = '2009';
	$CentreLocales = array('es_VE.utf8','en_US');	// Add other languages you want to support here, ex: 'fr_FR', 'es_ES', 'it_IT', ...
						// Language packs can be obtained by sending an email to info@centresis.org
	$LocalePath = $CentrePath.'locale'; // Path were the language packs are stored. You need to restart Apache at each change in this directory

	// You get a CentreInstallKey when registering you installation on the centresis.org website in the Centre Directory
	// This will enable access to online resources (documentation, newsgroup, translations, etc.) directly from within Centre
	$CentreInstallKey = 'KWUGJ-MHT48-VUQ02-4C50A';

	$CentreModules = array(
		'School_Setup'=>true,
		'Students'=>true,
		'Users'=>true,
		'Scheduling'=>true,
		'Grades'=>true,
		'Attendance'=>true,
		'Eligibility'=>true,
		'Food_Service'=>false,
		'Resources'=>false,
		'Discipline'=>false,
		'Billing'=>true,
		'State_Reports'=>false,
		'Custom'=>false
	);

	// If session isn't started, start it.
	if(!isset($SessionStart))
		$SessionStart = 1;
}
?>
