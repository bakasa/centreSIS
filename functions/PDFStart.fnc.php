<?php

function PDFStart($options="--webpage --quiet -t pdf14 --jpeg --no-links --portrait --footer t --header . --left 0.5in --top 0.5in")
{
	$_REQUEST['_CENTRE_PDF'] = true;
	$pdfitems['options'] = $options;
	ob_start();
	return $pdfitems;
}
?>