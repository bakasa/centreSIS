<?php

function BackPrompt($message)
{
	echo "<SCRIPT language=javascript>history.back();alert(\"$message\");</SCRIPT>";
	exit();
}
?>