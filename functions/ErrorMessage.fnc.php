<?php

// If there are missing vals or similar, show them a msg.
//
// Pass in an array with error messages and this will display them
// in a standard fashion.
//
// in a program you may have:
/*
if(!$sch)
	$error[]="School not provided.";
if($count == 0)
	$error[]="Number of students is zero.";
ErrorMessage($error);
*/
// (note that array[], the brackets with nothing in them makes
// PHP automatically use the next index.

// Why use this?  It will tell the user if they have multiple errors
// without them having to re-run the program each time finding new
// problems.  Also, the error display will be standardized.

// If a 2ND is sent, the list will not be treated as errors, but shown anyway

function ErrorMessage($errors,$code='error')
{
	if($errors)
	{
		$return = "<TABLE border=0><TR><TD align=left>";
		if(count($errors)==1)
		{
			if($code=='error' || $code=='fatal')
				$return .= '<b><font color=#CC0000>'.Localize('colon',_('Error')).'</font></b> ';
			else
				$return .= '<b><font color=#00CC00>'.Localize('colon',_('Note')).'</font></b> ';
			$return .= ($errors[0]?$errors[0]:$errors[1]);
		}
		else
		{
			if($code=='error' || $code=='fatal')
				$return .= "<b><font color=#CC0000>".Localize('colon',_('Errors'))."</font></b>";
			else
				$return .= '<b><font color=#00CC00>'.Localize('colon',_('Note')).'</font></b>';
			$return .= '<ul>';
			foreach($errors as $value)
					$return .= "<LI><font size=-1>$value</font></LI>\n";
			$return .= '</ul>';
		}
		$return .= "</TD></TR></TABLE><br>";

		if($code=='fatal')
		{
			echo $return;
			if(!$_REQUEST['_CENTRE_PDF'])
				Warehouse('footer');
			exit;
		}

		return $return;
	}
}
?>
