<?php

function DateInput($value,$name,$title='',$div=true,$allow_na=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		if($value=='' || $div==false)
			return PrepareDate($value,"_$name",$allow_na).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
		else
			return "<DIV id='div$name'><div onclick='javascript:addHTML(\"".str_replace('"','\"',PrepareDate($value,"_$name",$allow_na,array('Y'=>1,'M'=>1,'D'=>1))).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'')."\",\"div$name\",true)'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".($value!=''?ProperDate($value):'-').'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'').'</div></DIV>';
	}
	else
		return ($value!=''?ProperDate($value):'-').($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
}

function TextInput($value,$name,$title='',$options='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	// mab - support array style $option values
	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		$value1 = is_array($value) ? $value[1] : $value;
		$value = is_array($value) ? $value[0] : $value;

		if(strpos($options,'size')===false && $value!='')
			$options .= ' size='.strlen($value);
		elseif(strpos($options,'size')===false)
			$options .= ' size=10';

		if(trim($value)=='' || $div==false)
			return "<INPUT type=text name=$name ".($value || $value==='0'?'value="'.str_replace('"','&quot;',$value).'"':'')." $options>".($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
		else
			return "<DIV id='div$name'><div onclick='javascript:addHTML(\"<INPUT type=text id=input$name name=$name ".($value||$value==='0'?'value=\"'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$value).'\"':'').' '.$options.'>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'')."\",\"div$name\",true); document.getElementById(\"input$name\").focus();'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".($value!=''?$value1:'-').'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').'</small>':'').'</div></DIV>';
	}
	else
		return (((is_array($value)?$value[1]:$value)!='')?(is_array($value)?$value[1]:$value):'-').($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
}

function MLTextInput($value,$name,$title='',$options='',$div=true)
{   global $CentreLocales;

    if (sizeof($CentreLocales) < 2)
        return TextInput($value,$name,$title,$options,$div);
        
    if(Preferences('HIDDEN')!='Y')
        $div = false;

    // mab - support array style $option values
    if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
    {
        $value1 = is_array($value) ? $value[1] : $value;
        $value = is_array($value) ? $value[0] : $value;

        if(strpos($options,'size')===false && $value!='')
            $options .= ' size='.strlen($value);
        elseif(strpos($options,'size')===false)
            $options .= ' size=10';

        // ng - foreach possible language
        $ret = "<DIV><INPUT type=hidden id=$name name=$name value='$value'>";
        
        foreach ($CentreLocales as $id=>$loc) {
            $ret .= "<IMG src='assets/flags/$loc.png' height=20px width=20px />";
            $ret .= TextInput(ParseMLField($value, $loc),'ML_'.$name.'['.$loc.']','',$options." onchange=\"javascript:setMLvalue('$name','".($id==0?'':$loc)."',this.value);\"",false);
            $ret .= "<BR>";
        }
        $ret .= "</DIV>";
    }
    $ret .= ($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
    return $ret;
}

function TextAreaInput($value,$name,$title='',$options='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		if(strpos($options,'cols')===false)
			$options .= ' cols=30';
		if(strpos($options,'rows')===false)
			$options .= ' rows=4';
		$rows = substr($options,strpos($options,'rows')+5,2)*1;
		$cols = substr($options,strpos($options,'cols')+5,2)*1;

		if($value=='' || $div==false)
			return "<TEXTAREA name=$name $options>$value</TEXTAREA>".($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
		else
			return "<DIV id='div$name'><div onclick='javascript:addHTML(\"<TEXTAREA id=textarea$name name=$name $options>".ereg_replace("[\n\r]",'\u000D\u000A',str_replace("\r\n",'\u000D\u000A',str_replace(array("'",'"'),array('&#39;','\"'),$value)))."</TEXTAREA>".($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'')."\",\"div$name\",true); document.getElementById(\"textarea$name\").value=unescape(document.getElementById(\"textarea$name\").value);'><TABLE class=LO_field height=100%><TR><TD>".((substr_count($value,"\r\n")>$rows)?'<DIV style="overflow:auto; height:'.(15*$rows).'px; width:'.($cols*9).'; padding-right: 16px;border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';">'.nl2br($value).'</DIV>':'<DIV style="overflow:auto; width:'.($cols*9).'; padding-right: 16px;border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';">'.nl2br($value).'</DIV>').'</TD></TR></TABLE>'.($title!=''?'<small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'').'</div></DIV>';
	}
	else
		return ($value!=''?nl2br($value):'-').($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
}

function CheckboxInput($value,$name,$title='',$checked='',$new=false,$yes='Yes',$no='No',$div=true,$extra='')
{
	// $checked has been deprecated -- it remains only as a placeholder
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if($div==false || $new==true)
	{
		if($value && $value!='N')
			$checked = 'CHECKED';
		else
			$checked = '';
	}

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		if($new || $div==false)
			return "<INPUT type=checkbox name=$name value=Y $checked $extra>".($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
		else
			return "<DIV id='div$name'><div onclick='javascript:addHTML(\"<INPUT type=hidden name=$name value=\\\"\\\"><INPUT type=checkbox name=$name ".($value?'checked':'')." value=Y ".str_replace('"','\"',$extra).">".($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'')."\",\"div$name\",true)'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".($value?$yes:$no).'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'')."</div></DIV>";
	}
	else
		return ($value?$yes:$no).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
}

function SelectInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	// mab - support array style $option values
	// mab - append current val to select list if not in list
	if ($value!='' && $options[$value]=='')
		$options[$value] = array($value,'<FONT color=red>'.$value.'</FONT>');

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		if($value!='' && $div)
		{
			$return = "<DIV id='div$name'><div onclick='javascript:addHTML(\"";
			$extra = str_replace('"','\"',$extra);
		}

		$return .= "<SELECT name=$name $extra>";
		if($allow_na!==false)
		{
			if($value!='' && $div)
				$return .= '<OPTION value=\"\">'.$allow_na;
			else
				$return .= '<OPTION value="">'.$allow_na;
		}
		if(count($options))
		{
			foreach($options as $key=>$val)
			{
				$key .= '';
				if($value!='' && $div)
					$return .= '<OPTION value=\"'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$key).'\"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED':'').'>'.str_replace(array("'",'"'),array('&#39;','\"'),(is_array($val)?$val[0]:$val));
				else
					$return .= '<OPTION value="'.$key.'"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED':'').'>'.(is_array($val)?$val[0]:$val);
			}
		}
		$return .= "</SELECT>";
		if($title!='')
			$return .= '<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'<FONT>':'').'</small>';
		if($value!='' && $div)
			$return .="\",\"div$name\",true)'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".(is_array($options[$value])?$options[$value][1]:$options[$value]).'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'').'</div></DIV>';
	}
	else
		$return = (((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?$allow_na:'-'):'-')).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');

	return $return;
}

function MLSelectInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
   global $CentreLocales, $locale;

    if (sizeof($CentreLocales) < 2)
        return SelectInput($value,$name,$title,$options,$div);
        
    if(Preferences('HIDDEN')!='Y')
        $div = false;

    // mab - support array style $option values
    // mab - append current val to select list if not in list
    if ($value!='' && $options[$value]=='')
        $options[$value] = array($value,'<FONT color=red>'.$value.'</FONT>');

    if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
    {
        if($value!='' && $div)
        {
            $return = "<DIV id='div$name'><div onclick='javascript:addHTML(\"";
            $extra = str_replace('"','\"',$extra);
        }

        $return .= "<SELECT name=$name $extra>";
        if($allow_na!==false)
        {
            if($value!='' && $div)
                $return .= '<OPTION value=\"\">'.$allow_na;
            else
                $return .= '<OPTION value="">'.$allow_na;
        }
        if(count($options))
        {
            foreach($options as $key=>$val)
            {
                $key .= '';
                if($value!='' && $div)
                    $return .= '<OPTION value=\"'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$key).'\"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED':'').'>'.str_replace(array("'",'"'),array('&#39;','\"'),(is_array($val)?ParseMLField($val[0], $locale):ParseMLField($val, $locale)));
                else
                    $return .= '<OPTION value="'.$key.'"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED':'').'>'.(is_array($val)?ParseMLField($val[0], $locale):ParseMLField($val, $locale));
            }
        }
        $return .= "</SELECT>";
        if($title!='')
            $return .= '<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(strpos(strtolower($title),'<font ')===false?'<FONT>':'').'</small>';
        if($value!='' && $div)
            $return .="\",\"div$name\",true)'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".ParseMLField((is_array($options[$value])?$options[$value][1]:$options[$value]), $locale).'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'').'</div></DIV>';
    }
    else
        $return = ParseMLField((((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?$allow_na:'-'):'-')),$locale).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');

    return $return;
}

function RadioInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if ($value!='' && $options[$value]=='')
		$options[$value] = array($value,'<FONT color=red>'.$value.'</FONT>');

	if(AllowEdit() && !$_REQUEST['_CENTRE_PDF'])
	{
		if($value!='' && $div)
		{
			$return = "<DIV id='div$name'><div onclick='javascript:addHTML(\"";
			$extra = str_replace('"','\"',$extra);
		}

		$return .= '<TABLE cellspacing=0 cellpadding=0><TR align=center>';
		if($allow_na!==false)
		{
			if($value!='' && $div)
				$return .= '<TD><INPUT type=radio name='.$name.' value=\"\"'.($value==''?' CHECKED':'').'><BR><small>'.$allow_na.'</small></TD><TD>&nbsp;</TD>';
			else
				$return .= '<TD><INPUT type=radio name='.$name.' value=""'.($value==''?' CHECKED':'').'><BR><small>'.$allow_na.'</small></TD><TD>&nbsp;</TD>';
		}
		if(count($options))
		{
			foreach($options as $key=>$val)
			{
				$key .= '';
				if($value!='' && $div)
					$return .= '<TD><INPUT type=radio name=$name value=\"'.str_replace(array("'",'"'),array('&#39;','&rdquo;'),$key).'\" '.($value==$key && (!($value==false && $value!==$key) || ($value==='0' && $key===0))?'CHECKED':'').'><BR><small>'.str_replace(array("'",'"'),array('&#39;','\"'),(is_array($val)?$val[0]:$val)).'</small></TD><TD>&nbsp;</TD>';
				else
					$return .= "<TD><INPUT type=radio name=$name value=\"$key\" ".(($value==$key && !($value==false && $value!==$key))?'CHECKED':'').'><BR><small>'.(is_array($val)?$val[0]:$val).'</small></TD><TD>&nbsp;</TD>';
			}
		}
		$return .= '</TR></TABLE>';
		if($title!='')
			$return .= '<small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'<FONT>':'').'</small>';
		if($value!='' && $div)
			$return .="\",\"div$name\",true)'><span style='border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:".Preferences('TITLES').";'>".(is_array($options[$value])?$options[$value][1]:$options[$value]).'</span>'.($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'').'</div></DIV>';
	}
	else
		$return = (((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?$allow_na:'-'):'-')).($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');

	return $return;
}

function NoInput($value,$title='')
{
	return ($value!=''?$value:'-').($title!=''?'<BR><small>'.(strpos(strtolower($title),'<font ')===false?'<FONT color='.Preferences('TITLES').'>':'').$title.(strpos(strtolower($title),'<font ')===false?'</FONT>':'').'</small>':'');
}

function CheckBoxOnclick($name)
{
	return '<INPUT type=checkbox name='.$name.' value=Y'.($_REQUEST[$name]=='Y'?" CHECKED onclick='document.location.href=\"".PreparePHP_SELF($_REQUEST,array(),array($name=>''))."\";'":" onclick='document.location.href=\"".PreparePHP_SELF($_REQUEST,array(),array($name=>'Y'))."\";'").'>';
}
?>