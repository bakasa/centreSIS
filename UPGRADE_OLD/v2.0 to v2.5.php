<?php
include('Warehouse.php');
ini_set('max_execution_time',0);
error_reporting(0);

Warehouse('header');
$fields = DBGet(DBQuery("SELECT ID,TYPE,SELECT_OPTIONS FROM CUSTOM_FIELDS"));
foreach($fields as $field)
{
	$sql = "ALTER TABLE STUDENTS ADD COLUMN CUSTOM_".$field['ID'];
	switch($field['TYPE'])
	{
		case 'radio':
			$sql .= ' VARCHAR(1)';
		break;
		
		case 'text':
		case 'select':
			$sql .= ' VARCHAR(255)';
		break;
		
		case 'date':
			$sql .= ' DATE';
		break;
	}
	DBQuery($sql);
	//DBQuery("DROP INDEX CUSTOM_IND".$field['ID']);
	DBQuery("CREATE INDEX STUDENTS_UD_IND".$field['ID']." ON STUDENTS (CUSTOM_".$field['ID'].')');
	DBQuery("UPDATE STUDENTS SET CUSTOM_".$field['ID'].'=(SELECT CUSTOM_'.$field['ID'].' FROM CUSTOM WHERE CUSTOM.STUDENT_ID=STUDENTS.STUDENT_ID)');
}
echo 'done.';
Warehouse('footer');

?>