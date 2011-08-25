<?php
$menu['School_Setup']['admin'] = array(
						'School_Setup/PortalNotes.php'=>_('Portal Notes'),
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/Schools.php?new_school=true'=>_('Add a School'),
						'School_Setup/CopySchool.php'=>_('Copy School'),
						'School_Setup/MarkingPeriods.php'=>_('Marking Periods'),
						'School_Setup/Calendar.php'=>_('Calendars'),
						'School_Setup/Periods.php'=>_('Periods'),
						'School_Setup/GradeLevels.php'=>_('Grade Levels'),
						'School_Setup/Rollover.php'=>_('Rollover')
					);

$menu['School_Setup']['teacher'] = array(
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/MarkingPeriods.php'=>_('Marking Periods'),
						'School_Setup/Calendar.php'=>_('Calendars')
					);

$menu['School_Setup']['parent'] = array(
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/Calendar.php'=>_('Calendars')
					);

$exceptions['School_Setup'] = array(
						'School_Setup/PortalNotes.php'=>true,
						'School_Setup/Schools.php?new_school=true'=>true,
						'School_Setup/Rollover.php'=>true
					);
?>