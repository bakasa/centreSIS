<?php

DrawHeader(ProgramTitle());

$extra['header_left'] .= sprintf(_('Include courses active as of %s'),PrepareDate('','_include_active_date'));

MyWidgets('birthmonth');
include('modules/misc/Export.php');

function MyWidgets($item)
{	global $extra,$_CENTRE;

	switch($item)
	{
		case 'birthmonth':
			$options = array('1'=>_('January'),'2'=>_('February'),'3'=>_('March'),'4'=>_('April'),'5'=>_('May'),'6'=>_('June'),'7'=>_('July'),'8'=>_('August'),'9'=>_('September'),'10'=>_('October'),'11'=>_('November'),'12'=>_('December'));
			if($_REQUEST['birthmonth'])
			{
				$extra['SELECT'] .= ",to_char(s.CUSTOM_200000004,'Mon DD') AS BIRTHMONTH";
				$extra['WHERE'] .= " AND extract(month from s.CUSTOM_200000004)='$_REQUEST[birthmonth]'";
				$extra['columns_after']['BIRTHMONTH'] = _('Birth Month Day');
				if(!$extra['NoSearchTerms'])
					$_CENTRE['SearchTerms'] .= '<font color=gray><b>'.Localize('colon',_('Birth Month')).' </b></font>'.$options[$_REQUEST['birthmonth']].'<BR>';
			}
			$extra['search'] .= '<TR><TD align=right width=120>'._('Birth Month').'</TD><TD><SELECT name=birthmonth><OPTION value="">'._('N/A');
			foreach($options as $key=>$val)
				 $extra['search'] .= '<OPTION value="'.$key.'">'.$val;
			$extra['search'] .= '</SELECT></TD></TR>';
		break;
	}
}
?>