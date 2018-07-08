<?php

require_once ("$scriptroot/fgc_resources/htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);



function ProductDetailCleanerforKBZdb(){


global $purifier;

$sql_clean = "SELECT product_id, name, description FROM oc_product_description ORDER by product_id;"; 
$result_clean=mysql_query($sql_clean);// or die(mysql_error()." select product_id and description");
$allp = mysql_num_rows($result_clean);

$prodnum = 1;
while ($rowdel=mysql_fetch_assoc($result_clean)){

$descriptoclean = $rowdel['description'];
$skur = $rowdel['product_id'];
$productname = $rowdel['name'];

$productname_htmlent = html_entity_decode($productname);
$prodnamecleannewline = str_replace("\\n", "", $productname_htmlent);

$prodtocleanampquote = str_replace("&amp;quot;", "", $prodnamecleannewline);
$prodtoclean_andamp = str_replace("andamp;", "", $prodtocleanampquote);


$prodtoclean = str_replace("&amp;", "", $prodtoclean_andamp);



//echo ":::::DIRTY::::<br>$descriptoclean"; 

//Findings &amp; &AMP;
$descriptoclean = trim($descriptoclean);
//$descriptoclean = htmlentities($descriptoclean);
//$descriptoclean = htmlspecialchars($descriptoclean);
$descriptoclean = html_entity_decode($descriptoclean);
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

//exit(); $b = html_entity_decode($a);

$prodtoclean= $purifier->purify($prodtoclean);
$prodtoclean=mysql_real_escape_string($prodtoclean);

$cleanhtml= $purifier->purify($descriptoclean);
$cleanhtml=mysql_real_escape_string($cleanhtml);



$sql_updatedescription = "UPDATE oc_product_description SET description = '$cleanhtml', name = '$prodtoclean' WHERE product_id = '$skur'";
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