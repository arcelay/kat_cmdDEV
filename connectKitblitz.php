<?php

shell_exec("killall ssh");
shell_exec("ssh -N -L 8888:127.0.0.1:80 -i /Users/arcelay/Documents/KitblitzRealDealResources/sshCertificates/bitnami-google-bitnami-y16vqec2sq.pem bitnami@104.198.211.141 sleep 60 >> logfile");

$smysql = mysql_connect( "127.0.0.1:3306", "root", "U2hjBYBa" );
 mysql_select_db( "bitnami_opencart", $smysql ); 

$query="SELECT * FROM oc_journal2_newsletter;";
$result=mysql_query($query) or die(mysql_error());

 while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
 	echo $row["email"]."\r\n";
 }



?>