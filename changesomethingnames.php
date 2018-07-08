<?php


// LOCAL DATABASE

$host     = "localhost:/tmp/mysql.sock"; // Host name 
$username = "root"; // Mysql username 

$password = "Amicida3935!"; // Mysql password for Dev Laptop

//ONLINE DATABASE

//$host     = "localhost"; // Host name 
//$username = "root"; // Mysql username 

//$password = "M1cWqwqj"; // Mysql password for Online
$password = "amicida"; // Mysql password for 

$db_name  = "katcmd_db"; // Database name 


$conn = mysql_connect("$host", "$username", "$password") or die(mysql_error());

mysql_select_db ($db_name);


$sql_addhttp = "SELECT sku, productname FROM tbl_raw_productdetails ORDER by id;"; 
$result_addhttp=mysql_query($sql_addhttp) or die(mysql_error()." select id and productname");
$allp = mysql_num_rows($result_addhttp);

$prodnum = 1;
while ($rowdel=mysql_fetch_assoc($result_addhttp)){

$productname = $rowdel['productname'];
$sku = $rowdel['sku'];


//$producturladd = str_replace("http//www", "http://www", $producturl);

$newproductname=preg_replace('/(\w)(\w)(\d+)/','',$productname);
$newproductname=preg_replace('/^(\d+)/','',$newproductname);
$newproductname=preg_replace('/(\w+)(-)(\d+)/','',$newproductname);
$newproductname=preg_replace('/(\w+)(\d+)/','',$newproductname);
$newproductname=preg_replace('/(\d+)(\w+)/','',$newproductname);
$newproductname=preg_replace('/Women\'s/is','',$newproductname);
$newproductname=preg_replace('/Women+/is','',$newproductname);
$newproductname=preg_replace('/NEW/is','',$newproductname);
$newproductname=preg_replace('/OREKA/is','',$newproductname);
$newproductname=preg_replace('/Unisex/is','',$newproductname);
$newproductname=preg_replace("/[^a-zA-Z]+/", " ", $newproductname);
//$newproductname=preg_replace("/\s(a-zA-Z)\s+/", " ", $newproductname);

//echo $productname."=>".$newproductname."\r\n";

//Sanitize
$newproductname=html_entity_decode(mysql_real_escape_string(trim($newproductname)));


$sql_updatenewname = "UPDATE tbl_raw_productdetails SET newproductname = '$newproductname' WHERE sku = '$sku'";
mysql_query($sql_updatenewname)or die(mysql_error()." update");

}
//submensaje("Clean complete.");
//echo"HTTP added to producturl complete.";





?>