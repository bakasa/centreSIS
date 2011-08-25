<?php
function UpdateSchoolArray($school_id=null){
    if(!$school_id) $school_id = UserSyear();
    $_SESSION['SchoolData'] = DBGet(DBQuery("SELECT * FROM SCHOOLS WHERE ID = '".$school_id."' AND SYEAR = '".UserSyear()."'"));
    $_SESSION['SchoolData'] = $_SESSION['SchoolData'][1];$_SESSION['SchoolData'];  
}
function SchoolInfo($field=null){
    if($field)
        return $_SESSION['SchoolData'][$field];
    else
        return $_SESSION['SchoolData'];
}
?>
