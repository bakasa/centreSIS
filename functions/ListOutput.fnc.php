<?php

function ListOutput($result,$column_names,$singular='.',$plural='.',$link=false,$group=array(),$options=array())
{
	if(!isset($options['save']))
		$options['save'] = '1';
	if(!isset($options['print']))
		$options['print'] = true;
	if(!isset($options['search']))
		$options['search'] = true;
	if(!isset($options['center']))
		$options['center'] = true;
	if(!isset($options['count']))
		$options['count'] = true;
	if(!isset($options['sort']))
		$options['sort'] = true;
	if(!isset($options['cellpadding']))
		$options['cellpadding'] = '6';
	if(!isset($options['header_color']))
		$options['header_color'] = Preferences('HEADER');
	if(!$link)
		$link = array();

	if(!isset($options['add']))
	{
		if(!AllowEdit() || isset($_REQUEST['_CENTRE_PDF']))
		{
			if($link)
			{
				unset($link['add']);
				unset($link['remove']);
			}
		}
	}

	// PREPARE LINKS ---
	$result_count = $display_count = count($result);
	$num_displayed = 100000;
	
	$page = 0;
	if (isset($_REQUEST['page']))
		$page = $_REQUEST['page'];
		
	$search = '';
	if (isset($_REQUEST['LO_search']))
		$search = $_REQUEST['LO_search'];
		
	$sortColumn = '';
	if (isset($_REQUEST['LO_sort']))
		$sortColumn = $_REQUEST['LO_sort'];

	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('page','LO_sort','LO_direction','LO_search','LO_save','remove_prompt','remove_name','PHPSESSID'));

	// END PREPARE LINKS ---

	// UN-GROUPING
	if(!is_array($group))
		$group_count = false;
	else
		$group_count = count($group);

	$side_color = Preferences('COLOR');

	if($group_count && $result_count)
	{
		$color = '#F8F8F9';
		$group_result = $result;
		unset($result);
		$result[0] = '';

		foreach($group_result as $item1)
		{
			if($group_count==1)
			{
				if($color=='#F8F8F9')
					$color = $side_color;
				else
					$color = '#F8F8F9';
			}

			foreach($item1 as $item2)
			{
				if($group_count==1)
				{
					$i++;
					if(count($group[0]) && $i!=1)
					{
						foreach($group[0] as $column)
							$item2[$column] = str_replace('<!-- <!--','<!--','<!-- '.str_replace('-->','--><!--',$item2[$column])).' -->';
					}
					$item2['row_color'] = $color;
					$result[] = $item2;
				}
				else
				{
					if($group_count==2)
					{
						if($color=='#F8F8F9')
							$color = $side_color;
						else
							$color = '#F8F8F9';
					}

					foreach($item2 as $item3)
					{
						if($group_count==2)
						{
							$i++;
							if(count($group[0]) && $i!=1)
							{
								foreach($group[0] as $column)
									$item3[$column] = '<!-- '.$item3[$column].' -->';
							}
							if(count($group[1]) && $i!=1)
							{
								foreach($group[1] as $column)
									$item3[$column] = '<!-- '.$item3[$column].' -->';
							}
							$item3['row_color'] = $color;
							$result[] = $item3;
						}
						else
						{
							if($group_count==3)
							{
								if($color=='#F8F8F9')
									$color = $side_color;
								else
									$color = '#F8F8F9';
							}

							foreach($item3 as $item4)
							{
								if($group_count==3)
								{
									$i++;
									if(count($group[2]) && $i!=1)
									{
										foreach($group[2] as $column)
											unset($item4[$column]);
									}
									$item4['row_color'] = $color;
									$result[] = $item4;
								}
							}
						}
					}
				}
			}
			$i = 0;
		}
		unset($result[0]);
		$result_count = count($result);

		$sortColumn='';
	}
	// END UN-GROUPING
	$_LIST['output'] = true;


	// PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
	if($_LIST['output']!=false)
	{
		if($result_count != 0)
		{
			$count = 0;
			$remove = isset($link['remove']['variables']) ? count($link['remove']['variables']) : 0;
			$cols = count($column_names);

			// HANDLE SEARCHES ---
			if($result_count && $search && $search !='Search')
			{
				$search = $search_term = str_replace('\\\"','"',$search);
				$search = $search_term = ereg_replace('[^a-zA-Z0-9 _"]*','',strtolower($search_term));

				if(substr($search_term,0,0)!='"' && substr($search_term,-1)!='"')
				{
					$search_term = ereg_replace('"','',$search_term);
					while($space_pos = strpos($search_term,' '))
					{
						$terms[strtolower(substr($search_term,0,$space_pos))] = 1;
						$search_term = substr($search_term,($space_pos+1));
					}
					$terms[trim($search_term)] = 1;
				}
				else
				{
					$search_term = ereg_replace('"','',$search_term);
					$terms[trim($search_term)] = 1;
				}

                /* TRANSLATORS: List of words ignored during search operations */
                foreach (explode(',',_('of, the, a, an, in')) as $word)
				    unset($terms[trim($word)]);

				foreach($result as $key=>$value)
				{
					$values[$key] = 0;
					foreach($value as $name=>$val)
					{
						$val = ereg_replace('[^a-zA-Z0-9 _]+','',strtolower($val));
						if(strtolower($search)==$val)
							$values[$key] += 25;
						foreach($terms as $term=>$one)
						{
							if(ereg($term,$val))
								$values[$key] += 3;
						}
					}
					if($values[$key]==0)
					{
						unset($values[$key]);
						unset($result[$key]);
						$result_count--;
						$display_count--;
					}
				}
				if($result_count)
				{
					array_multisort($values,SORT_DESC,$result);
					$result = ReindexResults($result);
					$values = ReindexResults($values);

					$last_value = 1;
					$scale = (100/$values[$last_value]);

					for($i=$last_value;$i<=$result_count;$i++)
						$result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i]*$scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i]*$scale)) . ' height=10>';
				}
				$column_names['RELEVANCE'] = _('Relevance');

				if(is_array($group) && count($group))
				{
					$options['count'] == false;
					$display_zero = true;
				}
			}

			// END SEARCHES ---

			if($sortColumn)
			{
				foreach($result as $sort)
				{
					if(substr($sort[$sortColumn],0,4)!='<!--')
						$sort_array[] = $sort[$sortColumn];
					else
						$sort_array[] = substr($sort[$sortColumn],4,strpos($sort[$sortColumn],'-->')-5);
				}
				if($_REQUEST['LO_direction']==-1)
					$dir = SORT_DESC;
				else
					$dir = SORT_ASC;

				if($result_count>1)
				{
					if(is_int($sort_array[1]) || is_double($sort_array[1]))
						array_multisort($sort_array,$dir,SORT_NUMERIC,$result);
					else
						array_multisort($sort_array,$dir,$result);
					for($i=$result_count-1;$i>=0;$i--)
						$result[$i+1] = $result[$i];
					unset($result[0]);
				}
			}
		}

        // HANDLE SAVING THE LIST ---
        if($options['save'] && isset($_REQUEST['LO_save']) && $_REQUEST['LO_save']==$options['save'])
		{
			if(!$options['save_delimiter'] && Preferences('DELIMITER')=='CSV')
				$options['save_delimiter'] = 'comma';
			switch($options['save_delimiter'])
			{
				case 'comma':
					$extension = 'csv';
				break;
				case 'xml':
					$extension = 'xml';
				break;
				default:
					$extension = 'xls';
				break;
			}
			ob_end_clean();
			if($options['save_delimiter']!='xml')
			{
				foreach($column_names as $key=>$value)
				{
					if($options['save_delimiter']=='comma' && !$options['save_quotes'])
						$value = str_replace(',',';',$value);
					$output .= ($options['save_quotes']?'"':'') . str_replace('&nbsp;',' ',str_replace('<BR>',' ',ereg_replace('<!--.*-->','',$value))) . ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='comma'?',':"\t");
				}
				$output .= "\n";
			}
			foreach($result as $item)
			{
				foreach($column_names as $key=>$value)
				{
					$value = $item[$key];
					if($options['save_delimiter']=='comma' && !$options['save_quotes'])
						$value = str_replace(',',';',$value);
					$value = eregi_replace('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>','\\1',$value);
					$value = eregi_replace('<SELECT.*</SELECT\>','',$value);
					$output .= ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='xml'?'<'.str_replace(' ','',$value).'>':'') . ereg_replace('<[^>]+>','',ereg_replace("<div onclick='[^']+'>",'',ereg_replace(' +',' ',ereg_replace('&[^;]+;','',str_replace('<BR>&middot;',' : ',str_replace('&nbsp;',' ',$value)))))) . ($options['save_delimiter']=='xml'?'</'.str_replace(' ','',$value).'>'."\n":'') . ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='comma'?',':"\t");
				}
				$output .= "\n";
			}
			header("Cache-Control: public");
			header("Pragma: ");
			header("Content-Type: application/$extension");
			header("Content-Disposition: inline; filename=\"".ProgramTitle().".$extension\"\n");
			if($options['save_eval'])
				eval($options['save_eval']);
			echo $output;
			exit();
		}
		// END SAVING THE LIST ---
		if($options['center'])
			echo '<CENTER>';
		if(($result_count>$num_displayed) || (($options['count'] || $display_zero) && ((($result_count==0 || $display_count==0) && $plural) || ($result_count==0 || $display_count==0))))
		{
			echo "<TABLE border=0";
			if(isset($_REQUEST['_CENTRE_PDF']))
				echo " width=100%";
			echo "><TR><TD align=center>";
		}

		if($options['count'] || $display_zero)
		{
			if($result_count==0 || $display_count==0)
                echo "<b>".sprintf(_('No %s were found.'),ngettext($singular, $plural, 0))."</b> &nbsp; &nbsp;";
		}
		if($result_count!=0 || $search !=_('Search'))
		{
			$where_message = '';
			
			if(!isset($_REQUEST['_CENTRE_PDF']))
			{
				if(!$page)
					$page = 1;
					
				if(!isset($_REQUEST['LO_direction']))
					$_REQUEST['LO_direction'] = 1;
					
				$start = ($page - 1) * $num_displayed + 1;
				$stop = $start + ($num_displayed-1);
				if($stop > $result_count)
					$stop = $result_count;

				if($result_count > $num_displayed)
				{
					$where_message = "<SMALL>".sprintf(_('Displaying %d through %d'),$start,$stop)."</SMALL>";
					if(ceil($result_count/$num_displayed) <= 10)
					{
						for($i=1;$i<=ceil($result_count/$num_displayed);$i++)
						{
							if($i!=$page)
								$pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$sortColumn&LO_direction=$_REQUEST[LO_direction]&LO_search=".urlencode($search)."&page=$i>$i</A>, ";
							else
								$pages .= "$i, ";
						}
						$pages = substr($pages,0,-2) . "<BR>";
					}
					else
					{
						for($i=1;$i<=7;$i++)
						{
							if($i!=$page)
								$pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$sortColumn&LO_direction=$_REQUEST[LO_direction]&LO_search=".urlencode($search)."&page=$i>$i</A>, ";
							else
								$pages .= "$i, ";
						}
						$pages = substr($pages,0,-2) . " ... ";
						for($i=ceil($result_count/$num_displayed)-2;$i<=ceil($result_count/$num_displayed);$i++)
						{
							if($i!=$page)
								$pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$sortColumn&LO_direction=$_REQUEST[LO_direction]&LO_search=".urlencode($search)."&page=$i>$i</A>, ";
							else
								$pages .= "$i, ";
						}
						$pages = substr($pages,0,-2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$sortColumn&LO_direction=$_REQUEST[LO_direction]&LO_search=".urlencode($search)."&page=" . ($page +1) . ">Next Page</A><BR>";
					}
					echo sprintf(_('Go to Page %s'),$pages);
					echo '</TD></TR></TABLE>';
					echo '<BR>';
				}
			}
			else
			{
				$start = 1;
				$stop = $result_count;
				if($cols>8 || $_REQUEST['expanded_view'])
				{
					$_SESSION['orientation'] = 'landscape';
					$repeat_headers = 17;
				}
				else
					$repeat_headers = 28;
				if($options['print'])
				{
					$html = explode('<!-- new page -->',strtolower(ob_get_contents()));
					$html = $html[count($html)-1];
					echo '</TD></TR></TABLE>';
					$br = (substr_count($html,'<br>')) + (substr_count($html,'</p>')) + (substr_count($html,'</tr>')) + (substr_count($html,'</h1>')) + (substr_count($html,'</h2>')) + (substr_count($html,'</h3>')) + (substr_count($html,'</h4>')) + (substr_count($html,'</h5>'));
					if($br%2!=0)
					{
						$br++;
						echo '<BR>';
					}
				}
				else
					echo '</TD></TR></TABLE>';
			}
			// END MISC ---

			// WIDTH = 100%
			echo '<TABLE cellspacing=0 cellpadding=0 width=100%>';

			// SEARCH BOX & MORE HEADERS
			if($where_message || (($singular!='.') && ($plural!='.')) || (!isset($_REQUEST['_CENTRE_PDF']) && $options['search']))
			{
				echo '<TR><TD width=100%>';
				echo '<TABLE cellpadding=1 width=100%>';
				echo '<TR><TD align=left>';
				if(($singular!='.') && ($plural!='.') && $options['count'])
				{
					if($display_count>0)
						echo "<b>".sprintf(ngettext('%d %s was found.','%d %s were found.', $display_count), $display_count, ngettext($singular, $plural, $display_count))."</b> &nbsp; &nbsp;";
					if($where_message)
						echo '<BR>'.$where_message;
				}
				if($options['save'] && !isset($_REQUEST['_CENTRE_PDF']) && $result_count>0)
				{
					$extra = "page=$page&LO_sort=$sortColumn&LO_direction=$_REQUEST[LO_direction]&LO_search=".urlencode($search);
					echo "<A HREF=$PHP_tmp_SELF&$extra&LO_save=$options[save]&_CENTRE_PDF=true><IMG SRC=assets/download.gif border=0 vspace=0 hspace=0></A>";
				}
				
				echo '</TD>';
				$colspan = 1;
				if(!isset($_REQUEST['_CENTRE_PDF']) && $options['search'])
				{
					echo '<TD align=right>';
					echo "<INPUT type=text id=LO_search name=LO_search value='".(($search && $search !=_('Search'))? $search:_('Search')."' style='color:BBBBBB'"),"' onfocus='if(this.value==\""._('Search')."\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\""._('Search')."\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"".PreparePHP_SELF($_REQUEST,array('LO_search','page'))."&LO_search=\"+this.value; return false;} '><INPUT type=button value='"._('Go')."' onclick='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('LO_search','page'))."&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
					$colspan++;
				}
				echo "</TR>";
				echo '<TR style="height:0;"><TD width=100% style="height:0;" height=0 align=right colspan='.$colspan.'><DIV id=LOx'.(count($column_names)+(($result_count!=0 && $cols && !isset($_REQUEST['_CENTRE_PDF']))?1:0)+(($remove && !isset($_REQUEST['_CENTRE_PDF']))?1:0)).' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
			}
			else
				echo '<TR style="height:0;"><TD width=100% style="height:0;" height=0 align=right><DIV id=LOx'.(count($column_names)+(($result_count!=0 && $cols && !isset($_REQUEST['_CENTRE_PDF']))?1:0)+(($remove && !isset($_REQUEST['_CENTRE_PDF']))?1:0)).' style="width:0; position: relative; height:0;"></DIV>';
			// END SEARCH BOX ----
			echo '</TD></TR>';
			if(!empty($options['header']))
				echo '<TR><TD align=center>'.$options['header'].'</TD></TR>';
			echo '<TR><TD>';

			// SHADOW
			if(!isset($_REQUEST['_CENTRE_PDF']))
				echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD background=assets/left_shadow.gif width=4 height=100% rowspan=2>&nbsp;</TD><TD></TD><TD background=assets/right_shadow.gif width=4 height=100% rowspan=2></TD></TR><TR><TD>';
			echo "<TABLE cellpadding=$options[cellpadding] width=100%>";
			if(!isset($_REQUEST['_CENTRE_PDF']) && ($stop-$start)>10)
				echo '<THEAD>';
			if(!isset($_REQUEST['_CENTRE_PDF']))
				echo '<TR>';

			$i = 1;
			if($remove && !isset($_REQUEST['_CENTRE_PDF']) && $result_count!=0)
			{
				echo "<TD bgcolor=$options[header_color]><DIV id=LOx$i style='position: relative;'></DIV></TD>";
				$i++;
			}

			if($result_count!=0 && $cols && !isset($_REQUEST['_CENTRE_PDF']))
			{
				foreach($column_names as $key=>$value)
				{
					if($sortColumn==$key)
						$direction = -1 * $_REQUEST['LO_direction'];
					else
						$direction = 1;
					echo "<TD align=center bgcolor=".(isset($options['header_colors'][$key])?$options['header_colors'][$key]:$options['header_color'])."><DIV id=LOx$i style='position: relative;'></DIV>";
					echo "<A ";
					if($options['sort'])
						echo "HREF=$PHP_tmp_SELF&page=$page&LO_sort=$key&LO_direction=$direction&LO_search=".urlencode($search);
					echo " class=column_heading><b>$value</b></A>";
					if($i==1)
						echo "<DIV id=LOy0 style='position: relative;'></DIV>";
					echo "</TD>";
					$i++;
				}
				//echo '<TD width=0><DIV id=LO'.$i.'></DIV></TD>';
				echo "</TR>";
			}

			$color = '#F8F8F9';
			//style="height: 300px; overflow: auto; padding-right: 16px;"
			if(!isset($_REQUEST['_CENTRE_PDF']) && ($stop-$start)>10)
				echo '</THEAD><TBODY>';

			// mab - enable add link as first or last
			if($result_count!=0 && $link['add']['first'] && ($stop-$start+1)>=$link['add']['first'])
			{
				//if($remove && !isset($_REQUEST['_CENTRE_PDF']))
				//	$cols++;
				if($link['add']['link'] && !isset($_REQUEST['_CENTRE_PDF']))
					echo "<TR><TD colspan=".($remove?$cols+1:$cols)." align=left bgcolor=#FFFFFF>".button('add',$link['add']['title'],$link['add']['link'])."</TD></TR>";
				elseif($link['add']['span'] && !isset($_REQUEST['_CENTRE_PDF']))
					echo "<TR><TD colspan=".($remove?$cols+1:$cols)." align=left bgcolor=#FFFFFF>".button('add').$link['add']['span']."</TD></TR>";
				elseif($link['add']['html'] && $cols)
				{
					echo "<TR bgcolor=$color>";
					if($remove && !isset($_REQUEST['_CENTRE_PDF']) && $link['add']['html']['remove'])
						echo "<TD bgcolor=$color>".$link['add']['html']['remove']."</TD>";
					elseif($remove && !isset($_REQUEST['_CENTRE_PDF']))
						echo "<TD bgcolor=$color>".button('add')."</TD>";

					foreach($column_names as $key=>$value)
					{
						echo "<TD bgcolor=$color class=LO_field>".$link['add']['html'][$key]."</TD>";
					}
					echo "</TR>";
					$count++;
				}
			}

			for($i=$start;$i<=$stop;$i++)
			{
				$item = $result[$i];
				if(isset($_REQUEST['_CENTRE_PDF']) && $options['print'] && count($item))
				{
					foreach($item as $key=>$value)
					{
						$value = eregi_replace('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>','\\1',$value);
						$value = eregi_replace('<SELECT.*</SELECT\>','',$value);

						$item[$key] = ereg_replace("<div onclick='[^']+'>",'',$value);
					}
				}

				if(isset($item['row_color']))
					$color = $item['row_color'];
				elseif($color=='#F8F8F9')
					$color = $side_color;
				else
					$color = '#F8F8F9';
					//$color = '#EDF3FE';
				if(isset($_REQUEST['_CENTRE_PDF']) && $count%$repeat_headers==0)
				{
					if($count!=0)
					{
						echo "</TABLE><TABLE cellpadding=$options[cellpadding] width=100%>";
						echo '<!-- NEW PAGE -->';
					}
					echo "<TR>";
					if($remove && !isset($_REQUEST['_CENTRE_PDF']))
						echo "<TD bgcolor=$options[header_color]></TD>";

					if($cols)
					{
						foreach($column_names as $key=>$value)
						{
							echo "<TD bgcolor=".($options['header_colors'][$key]?$options['header_colors'][$key]:$options['header_color'])." class=LO_field><FONT color=#FFFFFF size=-1><b>" . $value . "</b></FONT></TD>";
						}
					}
					echo "</TR>";
				}
				if($count==0)
					$count = $br;

				echo "<TR bgcolor=$color>";
				$count++;
				if($remove && !isset($_REQUEST['_CENTRE_PDF']))
				{
					$button_title = $link['remove']['title'];
					//if(!$button_title)
					//	$button_title = 'Remove';
					$button_link = $link['remove']['link'];
					if(count($link['remove']['variables']))
					{
						foreach($link['remove']['variables'] as $var=>$val)
							$button_link .= "&$var=" . urlencode($item[$val]);
					}

					echo "<TD bgcolor=$color>" . button('remove',$button_title,$button_link) . "</TD>";
				}
				if($cols)
				{
					foreach($column_names as $key=>$value)
					{
						if(!empty($link[$key]) && $item[$key]!==false && !isset($_REQUEST['_CENTRE_PDF']))
						{
							echo "<TD bgcolor=$color class=LO_field>";
							if($key=='FULL_NAME')
								echo '<DIV id=LOy'.($count-$br).' height=100% style="height: 100%; min-height: 100%; position: relative;">';
							if($link[$key]['js']===true)
							{
								echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
								if(count($link[$key]['variables']))
								{
									foreach($link[$key]['variables'] as $var=>$val)
										echo "&$var=".urlencode($item[$val]);
								}
								echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
								if($link[$key]['extra'])
									echo ' '.$link[$key]['extra'];
								echo ">";
							}
							else
							{
								echo "<A HREF={$link[$key][link]}";
								if(count($link[$key]['variables']))
								{
									foreach($link[$key]['variables'] as $var=>$val)
										echo "&$var=".urlencode($item[$val]);
								}
								if($link[$key]['extra'])
									echo ' '.$link[$key]['extra'];
								echo '>';
							}
							if($color==Preferences('HIGHLIGHT'))
								echo '<font color=#FFFFFF>';
							else
								echo '<font color=#0000FF>';
							echo $item[$key];
							if(!$item[$key])
								echo '***';
							echo '</font>';
							echo '</A>';
							if($key=='FULL_NAME')
								echo '</DIV>';
							echo '</TD>';
						}
						else
						{
							echo "<TD bgcolor=$color class=LO_field align=center>";
							if($key=='FULL_NAME')
								echo '<DIV id=LOy'.($count-$br).' height=100% style="position: relative;">';
							if($color==Preferences('HIGHLIGHT'))
								echo '<font color=white>';
							echo $item[$key];
							if(!$item[$key])
								echo '&nbsp;';
							if($color==Preferences('HIGHLIGHT'))
								echo '</font>';
							if($key=='FULL_NAME')
								echo '<DIV>';
							echo '</TD>';
						}
					}
				}
				echo "</TR>";
			}

			if($result_count!=0 && (!$link['add']['first'] || ($stop-$start+1)<$link['add']['first']))
			{
				//if($remove && !isset($_REQUEST['_CENTRE_PDF']))
				//	$cols++;
				if($link['add']['link'] && !isset($_REQUEST['_CENTRE_PDF']))
					echo "<TR><TD colspan=".($remove?$cols+1:$cols)." align=left bgcolor=#FFFFFF>".button('add',$link['add']['title'],$link['add']['link'])."</TD></TR>";
				elseif($link['add']['span'] && !isset($_REQUEST['_CENTRE_PDF']))
					echo "<TR><TD colspan=".($remove?$cols+1:$cols)." align=left bgcolor=#FFFFFF>".button('add').$link['add']['span']."</TD></TR>";
				elseif($link['add']['html'] && $cols)
				{
					if($color!='#F8F8F9') //$count%2)
						$color = '#F8F8F9';
					else
						$color = $side_color;

					echo "<TR bgcolor=$color>";
					if($remove && !isset($_REQUEST['_CENTRE_PDF']) && $link['add']['html']['remove'])
						echo "<TD bgcolor=$color>".$link['add']['html']['remove']."</TD>";
					elseif($remove && !isset($_REQUEST['_CENTRE_PDF']))
						echo "<TD bgcolor=$color>".button('add')."</TD>";

					foreach($column_names as $key=>$value)
					{
						echo "<TD bgcolor=$color class=LO_field>".$link['add']['html'][$key]."</TD>";
					}
					echo "</TR>";
				}
			}
			if($result_count!=0)
			{
				if(!isset($_REQUEST['_CENTRE_PDF']) && ($stop-$start)>10)
					echo '</TBODY>';
				echo "</TABLE>";
				// SHADOW
				if(!isset($_REQUEST['_CENTRE_PDF']))
					echo '</TD></TR><TR><TD background=assets/left_corner_shadow.gif height=6 width=4></TD><TD background=assets/bottom_shadow.gif height=6></TD><TD height=6 width=4 background=assets/right_corner_shadow.gif></TD></TR></TABLE>';
				echo "</TD></TR>";
				echo "</TABLE>";

				if($options['center'])
					echo '</CENTER>';
			}

		// END PRINT THE LIST ---
		}
		if($result_count==0)
		{
			// mab - problem with table closing if not opened above - do same conditional?
			if(($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count==0 || $display_count==0) && $plural) || ($result_count==0 || $display_count==0))))
			{
				echo '</TD></TR>';
				if($options['header'])
					echo '<TR><TD align=center>'.$options['header'].'</TD></TR>';
				echo '</TABLE>';
			}
			else
				if($options['header'])
					echo '<TR><TD align=center>'.$options['header'].'</TD></TR>';

			if($link['add']['link'] && !isset($_REQUEST['_CENTRE_PDF']))
				echo '<center>' . button('add',$link['add']['title'],$link['add']['link']) . '</center>';
			elseif(($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_CENTRE_PDF']))
			{
				$color = $side_color;

				if($options['center'])
					echo '<CENTER>';
				// WIDTH=100%
				echo "<TABLE cellpadding=1 bgcolor=#f8f8f9 width=100%><TR><TD>";
				// SHADOW
				echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD background=assets/left_shadow.gif width=4 height=100% rowspan=2></TD><TD></TD><TD background=assets/right_shadow.gif width=4 height=100% rowspan=2></TD></TR><TR><TD>';
				if($link['add']['html'])
				{
					echo "<TABLE cellpadding=$options[cellpadding] width=100%><TR><TD bgcolor=$options[header_color]></TD>";
					foreach($column_names as $key=>$value)
					{
						echo "<TD bgcolor=".($options['header_colors'][$key]?$options['header_colors'][$key]:$options['header_color'])."><A class=column_heading><b>" . str_replace(' ','&nbsp;',$value) . "</b></A></TD>";
					}
					echo "</TR>";

					echo "<TR bgcolor=$color>";

					if($link['add']['html']['remove'])
						echo "<TD bgcolor=$color>".$link['add']['html']['remove']."</TD>";
					else
						echo "<TD bgcolor=$color>".button('add')."</TD>";

					foreach($column_names as $key=>$value)
					{
						echo "<TD bgcolor=$color class=LO_field>".$link['add']['html'][$key]."</TD>";
					}
					echo "</TR>";
					echo "</TABLE>";
				}
				elseif($link['add']['span'] && !isset($_REQUEST['_CENTRE_PDF']))
					echo "<TABLE><TR><TD align=left>".button('add').$link['add']['span']."</TD></TR></TABLE>";

				// SHADOW
				echo '</TD></TR><TR><TD background=assets/left_corner_shadow.gif height=6 width=4></TD><TD background=assets/bottom_shadow.gif height=6></TD><TD height=6 width=4 background=assets/right_corner_shadow.gif></TD></TR></TABLE>';
				echo "</TD></TR></TABLE>";
				if($options['center'])
					echo '</CENTER>';
			}
		}
		if($result_count!=0)
		{
			if(isset($options['yscroll']) && $options['yscroll'])
			{
				echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
				echo "<TABLE cellpadding=$options[cellpadding] id=LOy_table>";
				$i = 1;

				if($cols && !isset($_REQUEST['_CENTRE_PDF']))
				{
					$color = $side_color;
					foreach($result as $item)
					{
						echo "<TR><TD bgcolor=$color class=LO_field id=LO_row$i>";
						if($color==Preferences('HIGHLIGHT'))
							echo '<font color=white>';
						echo $item['FULL_NAME'];
						if(!$item['FULL_NAME'])
							echo '&nbsp;';
						if($color==Preferences('HIGHLIGHT'))
							echo '</font>';
						echo '</TD></TR>';
						$i++;

						if($item['row_color'])
							$color = $item['row_color'];
						elseif($color=='#F8F8F9')
							$color = $side_color;
						else
							$color = '#F8F8F9';
					}
				}
				echo '</TABLE>';
				echo '</div>';
			}

			echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
			echo "<TABLE cellpadding=$options[cellpadding] id=LOx_table><TR>";
			$i = 1;
			if($remove && !isset($_REQUEST['_CENTRE_PDF']) && $result_count!=0)
			{
				echo "<TD bgcolor=$options[header_color] id=LO_col$i></TD>";
				$i++;
			}

			if($cols && !isset($_REQUEST['_CENTRE_PDF']))
			{
				foreach($column_names as $key=>$value)
				{
					echo "<TD bgcolor=".($options['header_colors'][$key]?$options['header_colors'][$key]:$options['header_color'])." id=LO_col$i><A class=column_heading><b>".str_replace('controller','',$value).'</b></A></TD>';
					$i++;
				}
			}
			echo '</TR></TABLE>';
			echo '</div>';
		}
	}
}
?>
