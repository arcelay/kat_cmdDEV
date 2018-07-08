<?php

function help(){

echo "
************************************************
 KITBLITZ AUTOMATION TOOL
************************************************

Usage: katcmd.php [cmd]

  Commands:
  [auto][server/local]  Run all process automatically. Export Option.
  [cat]                 (Step 1) Acquire categories from Supplier.
  [url]                 (Step 2) Acquire products page URL from Supplier.
  [det]                 (Step 3) Acquire products Details from Supplier.
  [ichk]                (Step 4) Mark images for download.
  [idown]               (Step 5) Download images from Supplier.
  [clean]               (Step 6) Product name and Description data sanitation before export.
  [expcat]              (Step 7) Export Categories to Excel 'Import/Export' format.
  [expprod]             (Step 8) Export Products to Excel 'Import/Export' format.
  [zip]                 Zip new Images from imagestoUpload folder to the Desktop.
  [serverupdate]        (Optional) Export Products/Images directly to KitBlitz.
  [reset]               Reset entire process / Start over.
  [install]             Database fresh installation.
  [openexp]             Discover folder with export data files.

  [h]                   Help.

-------------------------------------------------
  Updated: July 21, 2017; Dec 16, 2017.
-------------------------------------------------\r\n\r\n";

}


function resetProcess(){

    global $colors, $db_name, $duplicateDB, $tbl_raw_sitemapcat, $tbl_raw_productdetails, $tbl_updatechangetracker;

        //Backup currentdatabase
        foreach ($duplicateDB as $key => $value) {
        mysql_query($value) or die("\r\n".mysql_error()."\r\n\r\n");
    }
        
        mysql_select_db ($db_name);
        //catqty = NULL in tbl_raw_sitemapcat
        $sql_prep = "UPDATE $tbl_raw_sitemapcat SET `catqty`= NULL;";
        mysql_query($sql_prep);
        
        //Delete stock_allowBuy values from tbl_raw_productdetails
        $sql_prep = "UPDATE $tbl_raw_productdetails SET `stock_allowBuy` = '';";
        mysql_query($sql_prep);

        //Delete all from tbl_updatechangetracker
        $sql_prep = "DELETE FROM $tbl_updatechangetracker;";
        mysql_query($sql_prep) or die(mysql_error()." delete track");

        echo"\r\n\033[".$colors["light_yellow"]."mPROCESS RESET AND READY TO START.\033[0m\r\n\r\n";



}



function connectDB() 
{ 
    global $host, $username, $password;

    $conn = mysql_connect("$host", "$username", "$password") or die(mysql_error());

    return $conn;

} 

function createDB($conn, $db_name, $databasestructuresql){

    mysql_select_db ($db_name);
    
    foreach ($databasestructuresql as $key => $value) {
        mysql_query($value) or die("\r\n".mysql_error()."\r\n\r\n");
     }

     if (mysql_error()==""){

        echo "\r\nDatabase '$db_name' created.\r\n\r\n";
     
     } 
 }


function explode_cats_and_insert_db($item, $key)
{
    
global $tbl_raw_sitemapcat, $rundatetimestamp;

$item     = str_replace('ca-', '', $item);
$item     = str_replace('</a>', '' , $item);
$item     = str_replace('.html\'>', ',' , $item);
$item     = str_replace('.html">', ',' , $item);

list($catid, $catname) = explode(",", $item);

//Data clean
$catname = htmlspecialchars_decode($catname);

$sql    = "INSERT INTO $tbl_raw_sitemapcat (datetimestamp, logicstatus_smap, catid, catname, catqty) VALUES ('$rundatetimestamp', 'sitemap_catidname', '$catid', '$catname', NULL);";
$result = mysql_query($sql);

}


function timehumanread ($time){

$seconds =  $time % 60;
$time    = ($time - $seconds) / 60;
$minutes =  $time % 60;
$hoursh  = ($time - $minutes) / 60;
$hoursh  = floor($hoursh);
$humanread = "$hoursh h $minutes min $seconds sec";

return $humanread;
}



function show_status($color, $processingitem,$done, $total, $size=25) {

    static $start_time;

    // if we go over our bound, just ignore it
    if($done > $total) return;

    if(empty($start_time)) $start_time=time();
    $now = time();

    $perc=(double)($done/$total);

    $bar=floor($perc*$size);

    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }

    $disp=number_format($perc*100, 0);

    $status_bar.="] $disp%  $done/$total";

    $rate = ($now-$start_time)/$done;
    $left = $total - $done;
    
    $eta = round($rate * $left, 2);
    $elapsed = ($now - $start_time);
    
    $elapsed_backtothefuture = ($now - $start_time)/60;
    $etafuturemin = round($eta/60);

    $hours_eta = floor($eta / 3600);
    $minutes_eta = floor(($eta / 60) % 60);
    $seconds_eta = $eta % 60;

    $hours_elapsed = floor($elapsed / 3600);
    $minutes_elapsed = floor(($elapsed / 60) % 60);
    $seconds_elapsed = $elapsed % 60;
  
$newTime = date("d/m/Y h:i:s", strtotime("+$etafuturemin minutes"));
$starDate = date("d/m/Y h:i:s", $start_time);

   
//$status_bar.= " $processingitem REM:[".number_format($hours_eta).":".number_format($minutes_eta).":".number_format($seconds_eta)."] ELA:[".number_format($hours_elapsed).":".number_format($minutes_elapsed).":".number_format($seconds_elapsed)."]/Str:[$starDate]->ETA:[$newTime]";

$status_bar.= " $processingitem REM:[".number_format($hours_eta).":".number_format($minutes_eta)."] ELA:[".number_format($hours_elapsed).":".number_format($minutes_elapsed)."]/STR:[$starDate]->ETA:[$newTime]";

    echo "\033[".$color."m $status_bar \033[0m";

    ob_flush();

    // when done, send a newline
    if($done == $total) {

        echo "\n";
    }

}



//@@@@@@@@@@@@@@@@@@@@@@@@@
// PROCESS STEP 1
//@@@@@@@@@@@@@@@@@@@@@@@@@

function grabSiteMapCategories() {

    global $conn, $colors, $catsitemap, $tbl_raw_sitemapcat, $tbl_systemlog, $rundatetimestamp, $tbl_allcategoriesformenu, $db_name;
    
    $callStartTime = microtime(true);
    
    $exactimestamp = date("Y-m-d H:i:s", microtime(true));
    $array = file_get_contents($catsitemap);

    //Grab product qty per category
    $sql_visitpage = "SELECT catid FROM $tbl_raw_sitemapcat WHERE active IS NULL;";
    mysql_select_db($db_name);
    $result_visitpage = mysql_query($sql_visitpage) or die(mysql_error());
    $num_ids = mysql_num_rows($result_visitpage);


    //Visit categories sitemap online
    if ($num_ids < 1 || $num_ids == "") {

        $homepage = file_get_contents($catsitemap);

        //Grab all categories ids and category name
        preg_match_all('/ca-[^-].*<\/a>/', $homepage, $matches);

        //Insert in database with status catok
        array_walk_recursive($matches, 'explode_cats_and_insert_db');

    } //End visit categories sitemap online


    //Grab product qty per category
    $result_visitpage = mysql_query($sql_visitpage);
    $num_ids = mysql_num_rows($result_visitpage);
    $c = 1;

    while ($row = mysql_fetch_array($result_visitpage, MYSQL_ASSOC)) {
        $exactimestamp = date("Y-m-d H:i:s", microtime(true));
        
         show_status($colors["light_purple"],"CATEGORIES.",$c, $num_ids, $size=30); 
        
        $catid = $row["catid"];
        $urltovisit = "http://dynamic.focalprice.com/categorylist/$catid?pagesize=72&filter=all";
        $cathomepage = file_get_contents($urltovisit);

        //GET Total Items qty
        $grabPattern = "|<span id=\"all_item_count\">\(<em>(.*)</em>\)|";
        preg_match($grabPattern, $cathomepage, $Items_qty);
        $qty = $Items_qty[1];

        //Second change
        if ($cathomepage == false) {

            $cathomepage = file_get_contents($urltovisit);
            //GET Total Items qty
            preg_match($grabPattern, $cathomepage, $Items_qty);
            $qty = $Items_qty[1];

        }

        //Third change
        if ($cathomepage == false) {

            $cathomepage = file_get_contents($urltovisit);
            //GET Total Items qty
            preg_match($grabPattern, $cathomepage, $Items_qty);
            $qty = $Items_qty[1];

        }


        if ($cathomepage == true) {

            if ($qty>0){
                
                $active = 1;

            } else { 

                $active=0; 
            }


            $sql_insertqty = "UPDATE $tbl_raw_sitemapcat SET active = $active, catqty = %d, logicstatus_smap = 'sitemapcat_catidnameqty', datetimestamp = '%s'  WHERE catid = '%s'";
            $sql_insertqtyfixed = sprintf($sql_insertqty, $qty, $exactimestamp, $catid);
            $result_insertqty = mysql_query($sql_insertqtyfixed) or die(mysql_error());

        } else {
            //SOMETHING WENT WRONG OBTAINING CATID
            $sql_insertqty = "UPDATE $tbl_raw_sitemapcat SET active = $active , catqty = %d, logicstatus_smap = 'error', datetimestamp = '%s'  WHERE catid = '%s'";
            $sql_insertqtyfixed = sprintf($sql_insertqty, $qty, $exactimestamp, $catid);
            $result_insertqty = mysql_query($sql_insertqtyfixed) or die(mysql_error());

            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabSiteMapCategories','$exactimestamp: Sitemap CADID $catid: ERROR')";
            $resultlog = mysql_query($syslog);
            echo"\r\n\033[".$colors["light_red"]."mSOMETHING WENT WRONG: CATID: $catid\033[0m\r\n\r\n";

        }



        $c++;
        $qty = 0;
    }

    $callEndTime = microtime(true);
    $callTime = $callEndTime - $callStartTime;

    echo "\033[".$colors["light_gray"]."m CATEGORY SITEMAP Completed in [".timehumanread($callTime)."]\033[0m \r\n\r\n";

}



//@@@@@@@@@@@@@@@@@@@@@@@@@
// PROCESS STEP 2
//@@@@@@@@@@@@@@@@@@@@@@@@@

function grabProductsUrlsandCats(){

    
    global $colors, $db_name, $tbl_systemlog, $tbl_raw_sitemapcat, $tbl_raw_productloc, $tbl_raw_catforproducts;

    $catidnum = array();
    $prodqtynum = array();
    $pageqtynum = array();

    $callStartTime = microtime(true);

    mysql_select_db($db_name);

    $sql_cat = "SELECT * FROM $tbl_raw_sitemapcat WHERE catqty>0";
    //logicstatus_smap = 'sitemapcat_catidnameqty' AND catqty <> 0;";
    $result_cat = mysql_query($sql_cat);
    $total_cat = mysql_num_rows($result_cat);

while ($rowcatok = mysql_fetch_array($result_cat, MYSQL_ASSOC) or mysql_error()) {
    
    $catidnum[] = $rowcatok["catid"];
    $prodqtynum[] = $rowcatok["catqty"];
    $pageqtynum[] = ceil($rowcatok["catqty"]/72);
        
}
    

foreach ($catidnum as $key => $value) {
    
    $catid = $value;
    $prodqty = $prodqtynum[$key];
    $pageqty = $pageqtynum[$key];
    
    show_status($colors["cyan"],"PRODUCTS LINKED TO CATEGORY [$catid]",$key+1, $total_cat, $size=30);


    for($i=1;$i<=$pageqty;$i++){
        $catspages[] = "http://dynamic.focalprice.com/categorylist/$catid?pagesize=72&page=$i";
    }
    
    //voy por cada paginas
    foreach ($catspages as $keycatpages => $valuecatpages) {
        
        $exactimestamp   = date("Y-m-d H:i:s", microtime(true));

        //show_status($colors["yellow"],"$catid category / product PAGES.",$keycatpages+1, $pageqty, $size=30);
        
        $productlistpage = file_get_contents($valuecatpages, "r");

        //Second Change
        if ($productlistpage == false){
            //sleep(2);
            $productlistpage = file_get_contents($valuecatpages, "r");
            $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
            //SYSLOG
            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Second Change file_get_contents for catid: $catid: ERROR')";
            $resultlog = mysql_query($syslog);

        }
        //Third Change
        if ($productlistpage == false){
            //sleep(3);
            $productlistpage = file_get_contents($valuecatpages, "r");
            //SYSLOG
            $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Third Change file_get_contents for catid: $catid: ERROR')";
            $resultlog = mysql_query($syslog);
        }

        if ($productlistpage <> false){
       
        //GET All Productos URL
        preg_match_all("| class=\"proImg\">(.*?)\">|s", $productlistpage, $matches); 
        $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
        

        foreach ($matches[0] as $key => $value) {

                    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
                        
                    preg_match_all('/href="([^\s"]+)/', $value, $urls);

                    $parts   = explode('/', $urls[1][0]);
                    $count   = count($parts);
                    $sku     = $parts[$count-2];
                    //Fix Dec 16,2017 - Added http:
                    $producturl = "http:".$urls[1][0];

                   

                    if (strlen($sku)<=10){
                    //Insert Product URL 
                    $exactimestamp   = date("Y-m-d H:i:s", microtime(true));    
                    $sql_url    = "INSERT INTO $tbl_raw_productloc (datetimestamp, logicstatus_prodloc, sku, producturl) VALUES ('$exactimestamp', 'productseturlcat', '$sku', '$producturl');";
                    $repagessult_cat = mysql_query($sql_url);
                    //Insert Product Catid
                    $sql_catids    = "INSERT INTO $tbl_raw_catforproducts (sku, catid) VALUES ('$sku', '$catid');";
                    $result_cat = mysql_query($sql_catids);
                    }
 
            
        } //foreach url found

    } else {
    //SOMETHING WENT WRONG
    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
    //SYSLOG
    $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Product Url: $valuecatpages  :ERROR')";
    $resultlog = mysql_query($syslog);
    echo"\r\n\033[".$colors["light_red"]."mERROR: $valuecatpages\033[0m\r\n\r\n";
    
    }
        

    }//voy por cada pagina

   
unset($matches);
unset($urls);
unset($catspages);

$sql_catdat    = "UPDATE $tbl_raw_sitemapcat SET logicstatus_smap='sitemap_done' WHERE catid='$catid';";        
mysql_query($sql_catdat);


$catid = "";
$prodqty="";
$pageqty="";

//PROCESS STEPS PRESENTATION
//$steppresentation = ProcessStepsPresentation($conn, 'grabProductsUrlsandCats');
//stepsinfo($steppresentation);

}//foreach cat 


$callEndTime = microtime(true);
$callTime    = $callEndTime - $callStartTime;
echo "\033[".$colors["light_gray"]."m PRODUCT PAGE URLS / CATEGORY LINK Completed in [".timehumanread($callTime)."]\033[0m \r\n\r\n";
$exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
$syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats',':PROCESS COMPLETE: ".timehumanread($callTime)."' );";
mysql_query($syslog); 
    
  
}


//@@@@@@@@@@@@@@@@@@@@@@@@@
// PROCESS STEP 3
//@@@@@@@@@@@@@@@@@@@@@@@@@

function grabProductsDetails() {

global $colors, $db_name, $tbl_systemlog, $tbl_raw_imagesloc,$tbl_raw_productloc, $tbl_raw_productdetails, $rundatetimestamp;

$callStartTime = microtime(true);
  $runDate   = date("Y-m-d H:i:s", microtime(true)); 
    
//Obtengo data de las localizaciones URL de los productos
  mysql_select_db($db_name);
$skufromloc = "SELECT * FROM $tbl_raw_productloc WHERE logicstatus_prodloc = 'productseturlcat'";
$result_allsku = mysql_query($skufromloc);
$allproducts_qty = mysql_num_rows($result_allsku);
$i=1;

//Me aprovecho de la propiedad de la tabla de tbl_raw_productdetails de que no guarda sku duplicados.
while ($rowsku = mysql_fetch_array($result_allsku, MYSQL_ASSOC)) {
$sku_loc = $rowsku["sku"];
$sql_insertsku = "INSERT INTO $tbl_raw_productdetails (sku) VALUES ('$sku_loc');";
mysql_query($sql_insertsku) or mysql_error()."\r\n\r\n";
$sql_prodloc_complete = "UPDATE $tbl_raw_productloc SET logicstatus_prodloc = 'usecompleted' WHERE sku = '$sku_loc' ";
mysql_query($sql_prodloc_complete) or mysql_error()."\r\n\r\n";

//Progress
//show_status($colors["yellow"],"PRODUCT DETAIL PROCESS PREPARATION",$i, $allproducts_qty, $size=30);

$i++;
}

//De la tabla de tbl_productos_tmp selecciono solo aquellos que le falta informacion
$sql_allproductsnodata = "SELECT * FROM $tbl_raw_productdetails WHERE (stock_allowBuy = '' OR  productname IS NULL OR productname='' OR description='')";
$result_allproductsnodata = mysql_query($sql_allproductsnodata) or die(mysql_error());
$allproducts_ove = mysql_num_rows($result_allproductsnodata);
$prodnum = 1;


//Visito todos los productos que le falta informacion para ir recogiendo y actulizando la misma
while ($rowprod = mysql_fetch_array($result_allproductsnodata, MYSQL_ASSOC)) {
//Con el sku de tbl_productos_tmp
$sku_tmp = $rowprod["sku"];

//Activate
show_status($colors["yellow"],"PRODUCT DETAILS",$prodnum, $allproducts_ove, $size=30);

//Obtengo tan solo el url de la pagina del producto de la tbl_raw_productloc
$sql_locfinder = "SELECT * FROM $tbl_raw_productloc WHERE sku = '$sku_tmp';";
$result_locfinder = mysql_query($sql_locfinder) or die(mysql_error());
$rowloc = mysql_fetch_row($result_locfinder);

$produrl_loc = $rowloc[4]; //URL del producto verify uRl process @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

//Debug only
//echo $produrl_loc."\r\n";
//exit;

//URL del producto limpio
$produrl_loc = preg_replace('/(&#39;)/s','\'',$produrl_loc); //&#39; Este caracter me fastidia la busqueda. Existen errores ademas de este que existe en el source data focalprice
$produrl_loc = preg_replace('/(&#174;)/s','C2 AE',$produrl_loc);
$produrl_loc = preg_replace('/(&#95;)/s','5F',$produrl_loc);

$produrl_loc = filter_var($produrl_loc, FILTER_SANITIZE_URL);

// Validate url
if (!filter_var($produrl_loc, FILTER_VALIDATE_URL) === false) {

$produrl_loc = html_entity_decode($produrl_loc);

//Visito la pagina del producto y de no funcionar la primera llamada le doy tres opornidades.
$productpage = file_get_contents($produrl_loc);



//Segunda oportunidad para obtener el source de la pagina del producto
if($productpage == false){
    $productpage = file_get_contents($produrl_loc);
}

//Tercera oportunidad
if($productpage == false){
    
    $productpage = file_get_contents($produrl_loc);
    
}

if($productpage <> false){ 
//Grab Product Information

//Debug
//echo $productpage;
//exit;
    
    //arcelay - Tiende a cambiar
    preg_match('|<h1 id="productName" class="c00">(.*?)\s*</h1>|', $productpage, $name);

//IMPORTANT about missing error [AllowBuy] http://dynamic.focalprice.com/QueryStockStatus?sku=MT0691B
//IMPORTANT about missing error [Price] http://dynamic.focalprice.com/AjaxPrice?sku=MT0691B

//STOCK CHECK
$stockConv = stockStatus($sku_tmp);

if ($stockConv==1){

$stock_allowBuy = "True";

} else {

  $stock_allowBuy = "False";
}

//preg_match("|\"Pcat\":\[\"(.*?)\"\]|", $productpage, $cat_name);
preg_match("|<div class=\"description_m\">(.*?)<div class=\"description_m hide\">|s", $productpage, $description);
preg_match("|<div id=\"summary\">(.*?)<|s", $productpage, $summary);

//Some cleaning due to found issues
$cleansummary = preg_replace('/[^(\x20-\x7f)]*/s','',$summary);
//Remove format
$cleandescriptionwithimages = preg_replace('/<\s*(\w+) [^>]+>/i', '<$1>',$description[1]);
 //Remove images
$cleandescriptionwchar = preg_replace("/<img[^>]+\>/i", " ", $cleandescriptionwithimages);
//Remove all ascii characters from the string use this.
$cleandescriptionwspan=preg_replace('/[^(\x20-\x7f)]*/s','',$cleandescriptionwchar);
$cleandescriptiononespanleft = str_replace("<span>", "", $cleandescriptionwspan);
$cleandescription = str_replace("</span>", "", $cleandescriptiononespanleft);



$sku_r = $sku_tmp;
$name_r= trim($name[1]);

$stock_allowBuy_r = $stock_allowBuy;
$cleansummary_r = trim($cleansummary[1]);
$cleandescription_r = trim($cleandescription);


$sku_r=mysql_real_escape_string($sku_r);
$name_r=mysql_real_escape_string($name_r);
//$price_r=mysql_real_escape_string($price);

$cleansummary_r      =mysql_real_escape_string($cleansummary_r);
$cleandescription_r  =mysql_real_escape_string($cleandescription_r);

//Updated 7/11/2017
$cleandescription_r = html_entity_decode($cleandescription_r);
$cleansummary_r     = html_entity_decode($cleansummary_r);
$name_r             = html_entity_decode($name_r);


//Debugging
/*
echo $sku_r."<br>";
echo $name_r."<br>";
echo $price_r."<br>";
echo $cat_name_r."<br>";
echo $cleansummary_r."<br><br><br>";
echo $cleandescription_r."<br><br><br><br>";
//exit();
*/

//Grab Images URLS
preg_match_all("|jqimg=\"(.*?)\"|", $productpage, $allproductimages);

//Borro del array busquedas que no necesito para luego interactuar con las que si me interesan
unset($allproductimages[0]);
unset($allproductimages[1][0]);

$imacount=count($allproductimages[1]);
//echo $imacount;
//exit();

$priority = 1;
//foreach url in allproduct image insert in database
foreach ($allproductimages[1] as $key => $imageurl) {

//Fix Dec 16, 2017 - Added http:
$imageurl = "http:".$imageurl;
 $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
$sql_insertima = "INSERT INTO $tbl_raw_imagesloc (`datetimestamp`, `sku`, `imaurl`, `priorityima`) VALUES ('$exactimestamp', '$sku_r', '$imageurl', $priority)";
  mysql_query($sql_insertima);

//Debugging
//echo $imageurl."<br>";

  //Image URLs Progress
  //barradeprogreso('progress', 'information',$priority, $imacount, "url of images added to database.<br>Url Imagen: [$imageurl]....");
  
  $priority++;
}
$imacount=0;

//Echo "Price: $price_r";
//exit();

//if (($price_r == '' || $price_r == NULL) || ($name_r == '' || $name_r == NULL)){

if (($name_r == '' || $name_r == NULL)){
//Insert Product Information in db
    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
    $sql_insertprodinfo = "UPDATE $tbl_raw_productdetails SET 
    productname='$name_r',  
    summary='$cleansummary_r',  
    description='$cleandescription_r',
    stock_allowBuy='$stock_allowBuy_r',   
    logicstatus_proddetails='no data error',    
    datetimestamp='$exactimestamp' WHERE  sku = '$sku_r'";

//SOMETHING WENT WRONG
    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
    //SYSLOG
    $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Product Url: $produrl_loc  :NO DATA ERROR')";
    $resultlog = mysql_query($syslog);
    //echo"\r\n\033[".$colors["light_red"]."mERROR: $produrl_loc\033[0m\r\n\r\n"; 


}else{
//Insert Product Information in db
$sql_insertprodinfo = "UPDATE $tbl_raw_productdetails SET 
    productname='$name_r',  
    summary='$cleansummary_r',  
    description='$cleandescription_r',
    stock_allowBuy='$stock_allowBuy_r',   
    logicstatus_proddetails='datagrabbed',    
    datetimestamp='$rundatetimestamp' WHERE  sku = '$sku_r'";

    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    //GRAB PRICE DATA
    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            updateprice($sku_r); 
    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


}



mysql_query($sql_insertprodinfo) or mysql_error("$sql_insertprodinfo");

$updatetbllocstatus = "UPDATE $tbl_raw_productloc SET logicstatus_loc='data_downloaded' WHERE sku = '$sku_r'";
mysql_query($updatetbllocstatus);

//$produrl_loc_display = preg_replace('/www.focalprice.com/s','',$produrl_loc);





} else {
//SOMETHING WENT WRONG
    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
    //SYSLOG
    $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Product Url: $produrl_loc  :URL ERROR')";
    $resultlog = mysql_query($syslog);
    //echo"\r\n\033[".$colors["light_red"]."mERROR: $produrl_loc\033[0m\r\n\r\n";    

}


} else {
    $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
    //SYSLOG
    $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsUrlsandCats','$exactimestamp: Product Url: $produrl_loc  :NOT VALID URL ERROR')";
    $resultlog = mysql_query($syslog);
    //echo"\r\n\033[".$colors["light_red"]."mInvalid URL ERROR: $produrl_loc\033[0m\r\n\r\n";
    
}


//Clear all variables
unset($allproductimages);
$sku_r = "";
$name_r = "";
$price_r = "";
$brand_r = "";
$cleansummary_r = "";
$cleandescription_r = "";
$stock_allowBuy_r="";
$produrl_loc = "";
$id_locx = "";
$prodnum++; 
//exit();

} //while url products



$callEndTime = microtime(true);
$callTime    = $callEndTime - $callStartTime;
echo "\033[".$colors["light_gray"]."m PRODUCT DETAILS Completed in [".timehumanread($callTime)."]\033[0m \r\n\r\n";

$exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
$syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'grabProductsDetails',':PROCESS COMPLETE: ".timehumanread($callTime)."' );";
mysql_query($syslog); 

}



function updateprice($sku) {

global $colors, $tbl_raw_pricehistory, $tbl_raw_productdetails, $tbl_systemlog, $tbl_updatechangetracker, $rundatetimestamp;

$exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 

$jsonpage = "http://dynamic.focalprice.com/AjaxPrice?sku=".$sku;
$jasondownload = file_get_contents($jsonpage);

 
    if  ($jasondownload<>false) {
      
      $grabbedjason = json_decode($jasondownload);

      //Data from json ->{'foo-bar'}
      $currencyValue = $grabbedjason->{'currencyValue'};
      $UnitPrice = $grabbedjason->{'UnitPrice'};
      $Rate = $grabbedjason->{'Rate'};
      $MarketPrice = $grabbedjason->{'MarketPrice'};

     
      $endTime = $grabbedjason->{'endTime'};

      if ($endTime == ""){
      $PromotionExpireTime = "1975-03-22 00:00:00";//date("Y-m-d H:i:s", '1975'); 
     } else {
          $PromotionExpireTime = date("Y-m-d H:i:s", $endTime); 
         
      }


      //Set to zero if blank
      if ($Rate == ""){ $Rate = 0; } 
      if ($MarketPrice == ""){ $MarketPrice = 0; }



      //Capture price missing data or that currency value is not US
      if (($UnitPrice == "" && $MarketPrice=="") || $currencyValue <> "US$"){
      
      $errormsg = "ERROR PRICE OR CURRENCY  ($sku) :  ";

        if($Unitprice==""){
        //Log and Error Message
        $errormsg .= "Unitprice: $Unitprice, MarketPrice: $MarketPrice";
        
        }

        if ($Unitprice == "" && $MarketPrice=="" && $currencyValue <> "US$"){ $errormsg .= ", ";}
        
        if($currencyValue<>"US$"){
        //Log and Error Message
        $errormsg .= "currencyValue: $currencyValue";
        }

        echo"\r\n\033[".$colors["light_red"]."mERROR: $errormsg \033[0m\r\n\r\n";
        $exactimestamp = date("Y-m-d H:i:s", microtime(true));
        $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'updateprices', '$errormsg');";
    
        mysql_query($syslog);

      } else {
        //Main of the logic

        $sqlfromtblprice    = "SELECT * FROM $tbl_raw_pricehistory WHERE sku = '$sku' ORDER BY id DESC LIMIT 1;";
        $resultfromtblprice = mysql_query($sqlfromtblprice); //or die(mysql_error());
        $num_rows           = mysql_num_rows($resultfromtblprice);
        $currDBprice        = mysql_fetch_assoc($resultfromtblprice);

        //Obtained data DB Unitprice and Rate
        $DBUnitprice  = $currDBprice['UnitPrice'];
        $DBMarketprice  = $currDBprice['MarketPrice'];
        //$DBRate       = $currDBprice['Rate'];

        

        if ($num_rows == 0) {
            //$op="New";
            //stepsinfo("NEW PRODUCT: $sku");
            $sql    = "INSERT INTO $tbl_raw_pricehistory (`datetimestamp`,`logicstatus_price`,`sku`,`statusinstore`,`UnitPrice`,`MarketPrice`,`Rate`,`PromotionExpireTime`) VALUES ( '$rundatetimestamp','initialprice','$sku','outdated',$UnitPrice,$MarketPrice,$Rate,'$PromotionExpireTime');";

            //echo "New: ".$sql;
            $sql_change = "INSERT INTO $tbl_updatechangetracker (`reportrundate`, `sku`, `changetype`) VALUES ('$rundatetimestamp', '$sku','newproduct');";
            
         } else if (($UnitPrice <> $DBUnitprice) || ($MarketPrice <> $DBMarketprice)) {
            //$op="change";
            //stepsinfo("PRICE CHANGE: $sku");
            $sql    = "INSERT INTO $tbl_raw_pricehistory (`datetimestamp`,`logicstatus_price`,`sku`,`statusinstore`,`UnitPrice`,`MarketPrice`,`Rate`,`PromotionExpireTime`) VALUES ( '$rundatetimestamp','pricechange','$sku','outdated',$UnitPrice,$MarketPrice,$Rate,'$PromotionExpireTime');";
            $sql_change = "INSERT INTO $tbl_updatechangetracker (`reportrundate`, `sku`, `changetype`) VALUES ('$rundatetimestamp', '$sku','pricechange');";
            //echo "Change: ".$sql;
            
         } else if (($UnitPrice == $DBUnitprice) && ($MarketPrice == $DBMarketprice)) {
            //$op="No change";
            //stepsinfo("NO PRICE CHANGE: $sku");
            $sql    = "UPDATE $tbl_raw_pricehistory SET datetimestamp = '$exactimestamp' WHERE sku = '$sku' ORDER BY id DESC LIMIT 1;";
            $sql_change = "INSERT INTO $tbl_updatechangetracker (`reportrundate`, `sku`, `changetype`) VALUES ('$rundatetimestamp', '$sku','nochange');";
            //echo "No Change: ".$sql;
          }

          mysql_query($sql);  //or die (mysql_error()." SQL query fail.");
          mysql_query($sql_change); //or die (mysql_error()." Log query fail");

          if (mysql_error()){

            //echo"\r\n\033[".$colors["light_red"]."mERROR: sku: $sku, CV: $currencyValue, UP: $UnitPrice, MP: $MarketPrice, R: $Rate, endTime: $endTime, ProT: $PromotionExpireTime\033[0m\r\n\r\n";
          
          }

          } //price not empty

      } //End of if  ($jasondownload<>false) 
      
} // End of updateprices function


function stockStatus($sku){

$returnedpage=file_get_contents("http://dynamic.focalprice.com/QueryStockStatus?sku=$sku");
$returnedstatus=json_decode($returnedpage);
$status = $returnedstatus->{'allowBuy'};
return $status;

}




//@@@@@@@@@@@@@@@@@@@@@@@@@
// PROCESS STEP 4
//@@@@@@@@@@@@@@@@@@@@@@@@@

function imagesCheckExist(){
global $colors, $db_name, $tbl_raw_imagesloc,$rootfolderimagenes, $tbl_systemlog;

//UPDATE tbl_raw_imagesloc SET logicstatus_ima = 'urlima';

 
  $callStartTime = microtime(true);

  mysql_select_db($db_name);
  //Coger todos los productos y verificar si existe la imagen en el folder
    $sql = "SELECT id, sku, imaurl FROM $tbl_raw_imagesloc";  // WHERE logicstatus_ima = 'urlima'";
    $result = mysql_query($sql) or die(mysql_error());
    $checktotal=mysql_num_rows($result);


 $ine = 1;
    //Busco todos los url de las imagenes marcados con status de urlima
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        
        //Progress
        show_status($colors["blue"],"IMAGES MARKED FOR DOWNLOAD",$ine, $checktotal, $size=30);

        //Verificar si la imagen existe en el directorio
        $id = $row['id'];
        $urlparts = explode('/', $row['imaurl']);
        $count = count($urlparts);
        $image_name = $urlparts[$count-1];

        //echo "Id: $id, Image: $image_name <br>";
       
        //PATH Y NOMBRE DE LA IMAGEN LOCALMENTE, Verificar siempre ../focalpricedynamicleandev/images/fbfimagesgrabbed/
        //$localfilename = "../focalpricedynamicleandev/images/fbfimagesgrabbed/$ima_name";
        $localfilename = $rootfolderimagenes ."/".$image_name;
       //echo "localfilename: $localfilename<br>";
        //Si la imagen no existe la marco con un status_ima = 'imagenoexist' para luego buscarla online
        if (!file_exists($localfilename)) {
            //echo "Image DOES NOT exist for: $localfilename";
            $sqlupdatestatusima = "UPDATE $tbl_raw_imagesloc SET logicstatus_ima = 'imagenoexist' WHERE id = '$id'";
            mysql_query($sqlupdatestatusima);

            //SYSLOG
            $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'ImagesCheckExist','NOEXIST: $localfilename' );";
            mysql_query($syslog); 


        } else {
            //echo "Image DOES exist for: $localfilename";
            $sqlupdatestatusima = "UPDATE $tbl_raw_imagesloc SET logicstatus_ima = 'exists' WHERE id = '$id'";
            mysql_query($sqlupdatestatusima);
            
            //SYSLOG
            $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'ImagesCheckExist','EXISTS: $localfilename' );";
            mysql_query($syslog); 

        }

        $ine++;
    }


$callEndTime = microtime(true);
$callTime    = $callEndTime - $callStartTime;

echo "\033[".$colors["light_gray"]."m IMAGE MARKED FOR DOWNLOAD Completed in [".timehumanread($callTime)."]\033[0m \r\n\r\n"; 

$exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
$syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'ImagesCheckExist',':PROCESS CHECK IMGES TO DWLD COMPLETE: ".timehumanread($callTime)."' );";
mysql_query($syslog); 
 
 
}






function downloadImages(){

global $colors, $db_name, $tbl_raw_imagesloc, $rootfolderimagenes, $tbl_systemlog, $pathimagestoUpload;



 $callStartTime = microtime(true);

    //Bajar las fotos de las imagenes no existentes
    mysql_select_db($db_name);
    $sqlnoexist = "SELECT id, sku, imaurl FROM $tbl_raw_imagesloc WHERE logicstatus_ima = 'imagenoexist' ORDER BY id DESC";
    $resultnoexist = mysql_query($sqlnoexist) or die(mysql_error());
    $rtotal = mysql_num_rows($resultnoexist);
 
    $rq=1;
    //Busco todos los url de las imagenes marcados con status de imagenoexist para bajarlas
    while ($rownoexist = mysql_fetch_array($resultnoexist, MYSQL_ASSOC)) {
        $id = $rownoexist['id'];
        $urlima=$rownoexist['imaurl'];

        $urlparts = explode('/', $urlima);
        $count = count($urlparts);
        $ima_name = $urlparts[$count-1];
        $displayurl = preg_replace('/focalprice/s','',$ima_name);
        
        //Downloadin Image
        $rawImage = file_get_contents($urlima);


        if ($rawImage) {
            //To keep all images in one place
            file_put_contents("$rootfolderimagenes/$ima_name", $rawImage);
            //To separe new images to upload
            file_put_contents("$pathimagestoUpload/$ima_name", $rawImage);
            //Si logre capturar la imagen la marco como downloaded
            $sqlupdatestatusima_downloaded = "UPDATE $tbl_raw_imagesloc SET logicstatus_ima = 'downloaded' WHERE id = '$id'";
            mysql_query($sqlupdatestatusima_downloaded);
            
           
        } else {
            //Si hubo algun problema la marco como downloaderror
           $sqlupdatestatusima_error = "UPDATE $tbl_raw_imagesloc SET logicstatus_ima = 'downloaderror' WHERE id = '$id'";
            mysql_query($sqlupdatestatusima_error);
            //submensaje("Download Error: $displayurl");
              //SYSLOG
            $exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
            $syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'DownloadImages','DOWLD ERROR: $urlima' );";
            mysql_query($syslog); 
           
        }
      
        //Progress     
        show_status($colors["light_blue"],"DOWNLOADED IMAGES",$rq, $rtotal, $size=30);

        $rq++;
    }


//downloadErrorAlternative();
  
$callEndTime = microtime(true);
$callTime    = $callEndTime - $callStartTime;

echo "\033[".$colors["light_gray"]."m IMAGE DOWNLOAD Completed in [".timehumanread($callTime)."]\033[0m \r\n\r\n";  

$exactimestamp   = date("Y-m-d H:i:s", microtime(true)); 
$syslog = "INSERT INTO $tbl_systemlog (`datetimestamp`, `source`, `message`) VALUES ('$exactimestamp', 'DownloadImages',':PROCESS DOWNLOAD COMPLETE: ".timehumanread($callTime)."' );";
mysql_query($syslog); 
 
}






function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}


function ProductDetailCleaner(){

global $tbl_raw_productdetails, $purifier, $db_name, $colors;



 mysql_select_db($db_name);
$sql_clean = "SELECT sku, productname, description FROM $tbl_raw_productdetails ORDER by id"; 
$result_clean=mysql_query($sql_clean) or die(mysql_error()." select sku and description");
$allp = mysql_num_rows($result_clean);

$prodnum = 1;
while ($rowdel=mysql_fetch_assoc($result_clean)){



$descriptoclean = $rowdel['description'];
$skur = $rowdel['sku'];
$productname = $rowdel['productname'];
$productname = str_replace("\\n", "", $productname);
$productname = str_replace("&amp;", "and", $productname);
$prodtoclean = str_replace("&#39;", "'", $productname);



//echo ":::::DIRTY::::<br>$descriptoclean";

$descriptoclean = str_replace("<span>", "", $descriptoclean);
$descriptoclean = str_replace("</span>", "", $descriptoclean);
$descriptoclean = str_replace("&#39;", "\'", $descriptoclean);

$descriptoclean = str_replace("&amp;", "and", $descriptoclean);

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

show_status($colors["yellow"],"RECORDS SANITIZED",$prodnum, $allp, $size=30);
$prodnum++;
//echo "<br><br><br><br>:::::CLEAN::::<br>$cleanhtml";
//if ($prodnum == 200){exit();}

}


}

function newProductsNames(){

global $tbl_prodname_stopword,$tbl_raw_productdetails, $db_name, $colors;

$stopword = array();

 mysql_select_db($db_name);

$sql_stopwords = "SELECT * FROM $tbl_prodname_stopword WHERE enable=1  ORDER BY stopword"; 
$result_stopwords =mysql_query($sql_stopwords) or die(mysql_error()."Error Message: ");
$all_stopwords = mysql_num_rows($result_stopwords);

$stopwords = 1;
while ($row_stopwords=mysql_fetch_assoc($result_stopwords)){

$stopword[] = $row_stopwords['stopword'];

//$ = $row_stopwords['$'];
//$ = $row_stopwords['$'];
//$ = str_replace("", "", $);
//$sql_update_  = "UPDATE $tbl_ SET field1 = '$var1', field2 = '$var2' WHERE id = '$id'";
//mysql_query($sql_update_ )or die(mysql_error()." update");

show_status($colors["light_cyan"],"PROCESSED STOPWORDS",$stopwords, $all_stopwords, $size=30);
$stopwords++;
}


//arsort($stopword);
//print_r($stopword);
$sql_changename = "SELECT sku, productname FROM $tbl_raw_productdetails"; 
$result_changename =mysql_query($sql_changename) or die(mysql_error()."Error Message: CHANGE PRODUCT NAME.");
$all_changename = mysql_num_rows($result_changename);

$changename = 1;
while ($row_changename=mysql_fetch_assoc($result_changename)){

$productname= $row_changename['productname'];
    
    //Words to lowercase
    $stringdata_lowercase = strtolower(trim($productname));
    
    //Tokenization
    $tokenization = explode(' ', $stringdata_lowercase);

    //Delete stop words
    $tokenization_with_no_stopwords = array_diff($tokenization, $stopword);

    //Unset blank array items on the fly if any
    $clean = array_values(array_filter($tokenization_with_no_stopwords));
    
    //Unique Words
    $newProductNameArray = array_unique($clean);

    $newProductName = implode(' ', $newProductNameArray);


//print_r($productname);
//echo $productname ." => ". $newProductName."\r\n\r\n";
//exit;

show_status($colors["light_cyan"],"PROCESSED STOPWORDS",$changename, $all_changename, $size=30);
$changename++;
}




}

//Not used yet.
function Delete($path)
{
    if (is_dir($path) === true)
    {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file)
        {
            Delete(realpath($path) . '/' . $file);
        }

        //return rmdir($path);
    }

    else if (is_file($path) === true)
    {
        return unlink($path);
    }

    return false;
}







//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//This function need to be copy in /system/library/cart/cart.php within Opencart
//I need to modify it to update price
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
function hasStock() {
        $stock = true;


        foreach ($this->getProducts() as $product) {
            
                // Start Process: Check 'Out of Stock' status ONLINE - Completado Feb 13, 2016 4:31pm

                $productonline_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id='" . (int)$product['product_id']. "'");
                
                if ($productonline_query->row['sku'] <> "" && ($productonline_query->row['quantity'] > 0 || $productonline_query->row['stock_status_id'] <> 5)) {
                        $pagina ="http://dynamic.focalprice.com/QueryStockStatus?sku=". $productonline_query->row['sku'];
                        $returnedpage = file_get_contents($pagina);
                        $returnedstatus = json_decode($returnedpage);
                        $allowBuy = $returnedstatus->{'allowBuy'};

                        if (!$allowBuy == 1) {
                            $this->db->query("UPDATE " . DB_PREFIX . "product SET stock_status_id = 5, quantity = 0 WHERE sku= '" . $productonline_query->row['sku'] . "'");
                            $stock = false;
                        }
                }
                // End Process: Check 'Out of Stock' status ONLINE
        
        if (!$product['stock']) {
                $stock = false;
            } 
        }

        return $stock;
    }


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@








?>