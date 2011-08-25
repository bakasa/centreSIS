<?php
global $CentreInstallKey;

$menu['Resources']['admin'] = array(
            			'Resources/Redirect.php?to=doc'=>_('Documentation'),
                        'Resources/Redirect.php?to=videohelp'=>_('Video Help'),
                        'Resources/Redirect.php?to=forums'=>_('User Forums'),
                        'Resources/Redirect.php?to=translate'=>_('Translation'));
if (empty($CentreInstallKey))
	$menu['Resources']['admin'] = array_merge($menu['Resources']['admin'],array(
                    1=>_('Setup'),
                        'Resources/Redirect.php?to=getkey'=>_('Get Key')));

$menu['Resources']['teacher'] = array(
                        'Resources/Redirect.php?to=doc'=>_('Documentation'),
                        'Resources/Redirect.php?to=videohelp'=>_('Video Help'),
                        'Resources/Redirect.php?to=forums'=>_('User Forums'),
                        'Resources/Redirect.php?to=translate'=>_('Translation')
                    );
$menu['Resources']['parent'] = $menu['Resources']['teacher'];
$menu['Resources']['student'] = $menu['Resources']['parent'];
?>
