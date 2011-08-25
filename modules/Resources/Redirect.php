<?php
$redir_url = 'http://go.centresis.org/redirect.php?to='.$_REQUEST['to'];
echo '<form name="CentreArgs" method="post"  action="'.$redir_url.'">
        <input type="hidden" name="version" value="'.$CentreVersion.'" />
        <input type="hidden" name="instkey" value="'.$CentreInstallKey.'" />
        <input type="hidden" name="profile" value="'.$profile.'" />
        <input type="hidden" name="locale" value="'.$locale.'" />';
if (AllowEdit()) echo '<input type="hidden" name="user_id" value="'.($profile=='student'?User('STUDENT_ID'):User('STAFF_ID')).'" />';
?>
</form>
<script type="text/javascript">document.CentreArgs.submit();</script>
<noscript>You must have JavaScript enabled to use the <a href="http://www.centresis.org">Centre SIS</a> Online Resources.</noscript>