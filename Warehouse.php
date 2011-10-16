<?php
if(!isset($WAREHOUSE_PHP))
{
	$WAREHOUSE_PHP = 1;
    $CentreVersion = '3.0.1';
	$staticpath = dirname(__FILE__).'/';

    if (!file_exists ($staticpath."config.inc.php"))
        die ('config.inc.php not found. Please read the configuration guide at <a href="http://doc.centresis.org">http://doc.centresis.org</a>');
	require_once($staticpath."config.inc.php");
    $CentrePath = $staticpath;
	require_once("database.inc.php");
    
	// Load functions.
	if($handle = opendir("$CentrePath/functions"))
	{
		if(!is_array($IgnoreFiles))
			$IgnoreFiles=Array();

		while (false !== ($file = readdir($handle)))
		{
			// if filename isn't '.' '..' or in the Ignore list... load it.
			if($file!='.' && $file!='..' && !in_array($file,$IgnoreFiles))
				require_once($CentrePath.'/functions/'.$file);
		}
	}

	// Start Session.
    session_name('CentreSIS');
    if ($_SERVER['SCRIPT_NAME']!='/index.php')
        session_set_cookie_params(0,dirname($_SERVER['SCRIPT_NAME']).'/'); //,'',$false,$true);
	session_start();
	if(!isset($_SESSION['STAFF_ID']) && !isset($_SESSION['STUDENT_ID']) && strpos($_SERVER['PHP_SELF'],'index.php')===false)
	{
		header('Location: index.php');
		exit;
	}

    // Internationalization
    if (!empty($_GET['locale']))
    { 
    	$_SESSION['locale'] = $_GET['locale'];
    }
    else
    {
    	if (empty($_SESSION['locale']))
    		$_SESSION['locale'] = $CentreLocales[0];
    }
    
    putenv("LC_ALL=$_SESSION[locale]");
    setlocale(LC_ALL, $_SESSION['locale']);
    bindtextdomain("centre", $LocalePath);    //binds the messages domain to the locale folder
    bind_textdomain_codeset("centre","UTF-8");     //ensures text returned is utf-8, quite often this is iso-8859-1 by default
    textdomain("centre");    //sets the domain name, this means gettext will be looking for a file called centre.mo

	function Warehouse($mode)
	{	global $__SHOW_FOOTER,$_CENTRE,$CentreTitle;

		switch($mode)
		{
			case 'header':
                echo '<HTML'.(substr($_SESSION['locale'],0,2)=='he'?' dir="RTL"':'').'>';
                echo "<HEAD><TITLE>$CentreTitle</TITLE>";
                echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />";
				if(basename($_SERVER['PHP_SELF'])!='index.php')
					echo "<noscript><META http-equiv=REFRESH content='0;url=index.php?modfunc=logout&reason=javascript' /></noscript>";
				echo "<script language=\"JavaScript\" type=\"text/javascript\">";
				if(basename($_SERVER['PHP_SELF'])=='index.php')
					echo "
							if(parent.frames.length > 0)
							{
								parent.location.href = 'index.php?modfunc=logout';
							}";
				echo "
							var locked;
							function doOnload()
							{
								var layer;
								if(layer = document.getElementById('LOx_layer'))
								{
									var right,left = getLeft(1);
									layer.style.left = left;
									layer.style.top = 0;
									document.getElementById('LOx_table').left = left;

									for(i=2;column = document.getElementById('LOx'+i);i++)
									{
										right = getLeft(i);
										document.getElementById('LO_col'+(i-1)).width = right-left-14;
										left = right;
									}
									document.getElementById('LO_col'+(i-2)).width = left-getLeft(i-2)-10;
									total_width = getLeft(i-1)-getLeft(1) + 7;
									document.getElementById('LOx_table').width = total_width;
									if(navigator.userAgent.indexOf('Safari')==-1&&navigator.userAgent.indexOf('Microsoft Internet Explorer')==-1)
										window.setInterval('doOnscroll()',50);
									else
										window.onscroll = doOnscroll;
									window.onresize = doOnresize;
								}

								if(layer = document.getElementById('LOy_layer'))
								{
									var bottom,top = getTop(1);
									layer.style.left = getLeft(1);
									layer.style.top = top;
									document.getElementById('LOy_table').left = left;

									for(i=2;row = document.getElementById('LOy'+i);i++)
									{
										bottom = getTop(i);
										document.getElementById('LO_row'+(i-1)).height = bottom-top-2;
										top = bottom;
									}
									document.getElementById('LOy_table').width = getLeft(2)-getLeft(1);
									if(navigator.userAgent.indexOf('Safari')==-1&&navigator.userAgent.indexOf('Microsoft Internet Explorer')==-1)
										window.setInterval('doOnscroll()',50);
									else
										window.onscroll = doOnscroll;
									window.onresize = doOnresize;
								}
							}

							function doOnresize()
							{
								doOnload();
								doOnscroll();
							}

							function doOnscroll()
							{
								var layer;
								if(layer = document.getElementById(\"LOx_layer\"))
								{
									var pY = (window.document.body.scrollTop ? window.document.body.scrollTop : window.pageYOffset);
									if(pY > getListTop())
										layer.style.visibility = \"visible\";
									else if(pY < getListTop())
										layer.style.visibility = \"hidden\";
									if(pY)
										layer.style.top = (window.document.body.scrollTop ? window.document.body.scrollTop : window.pageYOffset);
								}

								if(layer = document.getElementById(\"LOy_layer\"))
								{
									var pX = (window.document.body.scrollLeft ? window.document.body.scrollLeft : window.pageXOffset);
									if(pX > 0)
										layer.style.visibility = \"visible\";
									else if(pX == 0)
										layer.style.visibility = \"hidden\";
									if(pX)
										layer.style.left = (window.document.body.scrollLeft ? window.document.body.scrollLeft : window.pageXOffset);
								}
							}

							function getLeft(column_number)
							{
								var x = 0;
								if(document.layers)
									x = document.layers['LOx'+column_number].pageX;
								else if(document.all || document.getElementById)
								{
									var cell = document.all ? document.all['LOx'+column_number] : document.getElementById('LOx'+column_number);
									while(cell)
									{
										x += cell.offsetLeft;
										cell = cell.offsetParent;
									}
								}
								return x-8;
							}

							function getTop(row_number)
							{
								var y = 0;
								if(document.layers)
									y = document.layers['LOy'+row_number].pageY;
								else if(document.all || document.getElementById)
								{
									var cell = document.all ? document.all['LOy'+row_number] : document.getElementById('LOy'+row_number);
									while(cell)
									{
										y += cell.offsetTop;
										cell = cell.offsetParent;
									}
								}
								return y-8;
							}

							function getListTop()
							{
								var y = 0;
								if(document.layers)
									y = document.layers['LOx1'].pageY;
								else if(document.all || document.getElementById)
								{
									var cell = document.all ? document.all['LOx1'] : document.getElementById('LOx1');
									while(cell)
									{
										y += cell.offsetTop;
										cell = cell.offsetParent;
									}
								}
								return y;
							}

							function putFocus()
							{
								if(document.forms.length > 0)
								{
									document.forms[0].elements[0].focus();
								}
							}

							function addHTML(html,id,replace)
							{
								if(locked!=false)
								{
									if(replace==true)
										document.getElementById(id).innerHTML = html;
									else
										document.getElementById(id).innerHTML = document.getElementById(id).innerHTML + html;
								}
							}

							function changeHTML(show,hide)
							{
								for(key in show)
									document.getElementById(key).innerHTML = document.getElementById(show[key]).innerHTML;
								for(i=0;i<hide.length;i++)
									document.getElementById(hide[i]).innerHTML = '';
							}

							function checkAll(form,value,name_like)
							{
								if(value==true)
									checked = true;
								else
									checked = false;

								for(i=0;i<form.elements.length;i++)
								{
									if(form.elements[i].type=='checkbox' && form.elements[i].name!='controller' && form.elements[i].name.substr(0,name_like.length)==name_like)
										form.elements[i].checked = checked;
								}
							}

							function switchMenu(id)
							{
								if(document.getElementById(id).style.display=='none')
								{
									document.getElementById(id).style.display = 'block';
									document.getElementById(id+'_arrow').src = 'assets/arrow_down.gif';
								}
								else
								{
									document.getElementById(id).style.display = 'none';
									document.getElementById(id+'_arrow').src = 'assets/arrow_right.gif';
								}
							}
                            
                            function setMLvalue(id,loc,value)
                            {
                                res = document.getElementById(id).value.split('|');
                                if(loc=='') {
                                    if (value == '') {
                                        alert('"._('The first translation string cannot be empty.')."');
                                        value = 'Something';
                                    }
                                    res[0] = value;
                                } else {
                                    found = 0;
                                    for (i=1;i<res.length;i++) {
                                        if (res[i].substring(0,loc.length) == loc) {
                                            found = 1;
                                            if (value == '') {
                                                for (j=i+1;j<res.length;j++)
                                                    res[j-1] = res[j];
                                                res.pop();
                                            } else {
                                                res[i] = loc+':'+value;
                                            }
                                        }
                                    }    
                                    if ((found == 0) && (value != '')) res.push(loc+':'+value);
                                }
                                document.getElementById(id).value = res.join('|');                                
                            }
						</script>
					</HEAD>
					<link rel=stylesheet type=text/css href=assets/themes/".Preferences('THEME')."/stylesheet.css>";
echo '  <link rel="stylesheet" type="text/css" media="all" href="assets/jscalendar/calendar-blue2.css" />
  <script type="text/javascript" src="assets/jscalendar/calendar.js"></script>
  <script type="text/javascript" src="assets/jscalendar/lang/calendar-en.js"></script>
  <script type="text/javascript" src="assets/jscalendar/calendar-setup.js"></script>
  <script type="text/javascript" src="assets/js/jquery.js"></script>
  <script type="text/javascript" src="assets/js/prototype.js"></script>
  <script type="text/javascript" src="assets/js/billing.js"></script>
  <script type="text/javascript" src="assets/tipmessage/main15.js"></script>';

			break;
			case "footer":
				echo '</TD></TR></TABLE>';

				if (isset($_CENTRE['PrepareDate']))
				{
					for($i=1;$i<=$_CENTRE['PrepareDate'];$i++)
					{
						echo '<script type="text/javascript">
							Calendar.setup({
								monthField     :    "monthSelect'.$i.'",
								dayField       :    "daySelect'.$i.'",
								yearField      :    "yearSelect'.$i.'",
								ifFormat       :    "%d-%b-%y",
								button         :    "trigger'.$i.'",
								align          :    "Tl",
								singleClick    :    true
							});
						</script>';
					}
				}
				echo '</BODY>';
				echo '</HTML>';
			break;
		}
	}
}
?>
