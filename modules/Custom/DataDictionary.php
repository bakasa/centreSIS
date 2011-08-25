<?php
$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
					a.attlen AS length,a.atttypmod AS lengthvar,
					a.attnotnull AS notnull,c.relname
				FROM pg_class c, pg_attribute a, pg_type t 
				WHERE
					a.attnum > 0 and a.attrelid = c.oid 
					and c.relkind='r' and c.relname not like 'pg\_%' and a.attname not like '...%'
					and a.atttypid = t.oid ORDER BY c.relname";
$RET = DBGet(DBQuery($sql),array(),array('RELNAME'));

$PDF = PDFStart();
echo '<TABLE>';
foreach($RET as $table=>$columns)
{
	if($i%2==0)
		echo '<TR><TD valign=top>';
	echo '<b>'.$table.'</b>';
	echo '<TABLE>';
	foreach($columns as $column)
		echo '<TR><TD width=15>&nbsp; &nbsp; </TD><TD>'.$column['FIELD'].'</TD><TD>'.$column['TYPE'].'</TD></TR>';
	echo '</TABLE>';
	if($i%2==0)
		echo '</TD><TD valign=top>';
	else
		echo '</TD></TR>';
	$i++;
}
echo '</TABLE>';
PDFStop($PDF);
?>