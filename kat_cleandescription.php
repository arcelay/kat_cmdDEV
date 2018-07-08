<?php

require_once ("$scriptroot/fgc_resources/htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);



function ProductDetailCleaner(){

global $tbl_raw_productdetails, $purifier;

$sql_clean = "SELECT sku, productname, description FROM $tbl_raw_productdetails ORDER by id"; 
$result_clean=mysql_query($sql_clean) or die(mysql_error()." select sku and description");
$allp = mysql_num_rows($result_clean);

$prodnum = 1;
while ($rowdel=mysql_fetch_assoc($result_clean)){

$descriptoclean = $rowdel['description'];
$skur = $rowdel['sku'];
$productname = $rowdel['productname'];
$prodnamecleannewline = str_replace("\\n", "", $productname);
$prodtoclean = str_replace("&amp;", "and", $prodnamecleannewline);



//echo ":::::DIRTY::::<br>$descriptoclean";

$descriptoclean = str_replace("<span>", "", $descriptoclean);
$descriptoclean = str_replace("</span>", "", $descriptoclean);

$descriptoclean = str_replace("<col>", "", $descriptoclean);
$descriptoclean = str_replace("</col>", "", $descriptoclean);
$descriptoclean = str_replace("<colgroup>", "", $descriptoclean);
$descriptoclean = str_replace("<colgroup>", "", $descriptoclean);

$descriptoclean = str_replace("<!--?xml:namespace prefix = o ns = \"urn:schemas-microsoft-com:office:office\" /--><o:p></o:p>", "", $descriptoclean);
$descriptoclean = str_replace("<o:p>", "", $descriptoclean);
$descriptoclean = str_replace("</o:p>", "", $descriptoclean);
$descriptoclean = str_replace("<p>", "", $descriptoclean);
$descriptoclean = str_replace("</p>", "", $descriptoclean);
$descriptoclean = str_replace("<img>", "", $descriptoclean);
$descriptoclean = str_replace("<p>&nbsp;</p>", "", $descriptoclean);
$descriptoclean = str_replace("&nbsp;", " ", $descriptoclean);
$descriptoclean = str_replace("<colgroup><col><col></colgroup>", "", $descriptoclean);
$descriptoclean = str_replace("<?xml:namespace prefix = o ns = \"urn:schemas-microsoft-com:office:office\" />", "", $descriptoclean);

//exit();

$prodtoclean= $purifier->purify($prodtoclean);
$prodtoclean=mysql_real_escape_string($prodtoclean);

$cleanhtml= $purifier->purify($descriptoclean);
$cleanhtml=mysql_real_escape_string($cleanhtml);



$sql_updatedescription = "UPDATE $tbl_raw_productdetails SET description = '$cleanhtml', productname = '$prodtoclean' WHERE sku = '$skur'";
mysql_query($sql_updatedescription)or die(mysql_error()." update");

barradeprogreso('progress', 'information',$prodnum, $allp, "Cleaning PRODUCT NAME and DESCRIPTION data...");
$prodnum++;
//echo "<br><br><br><br>:::::CLEAN::::<br>$cleanhtml";
//if ($prodnum == 200){exit();}

}
//submensaje("Clean complete.");
informacionparaprogressbar("Clean complete.");



}


?>