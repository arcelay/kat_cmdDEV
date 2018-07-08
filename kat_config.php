<?php



set_time_limit(0); // run without timeout limit
error_reporting(1); // Turn off all error reporting
ini_set('memory_limit', '-1');

date_default_timezone_set('America/Puerto_Rico');
ini_set('SMTP', "gmail.com");
ini_set('smtp_port', "25");
ob_start();



// Mysql datetime format: YYYY-MM-DD HH:MM:SS
$rundatetimestamp = date("Y-m-d H:i:s", time());
//$scriptroot = "/Users/arcelay/Sites/kat_cmd";
$scriptroot = "/Users/arcelaymini/Scripts/kat_cmd";

require_once ("$scriptroot/kat_resources/Classes/htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// LOCAL DATABASE

//$host     = "localhost:/tmp/mysql.sock"; // Host name 
$host = "localhost";
$username = "root"; // Mysql username 

#$password = "Amicida3935!"; // Mysql password for Dev Laptop

//ONLINE DATABASE

//$host     = "localhost"; // Host name 
//$username = "root"; // Mysql username 

//$password = "M1cWqwqj"; // Mysql password for Online
//$password = "amicida"; // Mysql password for 
$password = "Amicida3935!"; // Mysql password for
$db_name  = "katcmd_db"; // Database name 


//$kbz_db_name  = "bitnami_opencart";
$kbz_db_name  = "localhost";


//GLOBAL variables

//Sales Fees
$paypalfee = 1.35;
$paypalpercent = .035;

//Online locations
$pathtoimage = "catalog/productsimages/";

//Database tables
$tbl_raw_sitemapcat       = "tbl_raw_sitemapcat";
$tbl_raw_productloc       = "tbl_raw_productloc";
$tbl_raw_imagesloc        = "tbl_raw_imagesloc";
$tbl_raw_catforproducts   = "tbl_raw_catforproducts";
$tbl_raw_productdetails   = "tbl_raw_productdetails";
$tbl_raw_pricehistory     = "tbl_raw_pricehistory";
$tbl_systemlog            = "tbl_systemlog";
$tbl_updatechangetracker  = "tbl_updatechangetracker";
$tbl_prodname_stopword            = "tbl_prodname_stopword";



//Focalprice links
$catsitemap  = "http://dynamic.focalprice.com/SiteMap";
$xmlsitemap  = "http://dynamic.focalprice.com/Sitemap_Category.xml";
$newArrivalsUrl = "http://dynamic.focalprice.com/new-arrivals?pagesize=72";
//$topSellerUrl = "http://dynamic.focalprice.com/topsellers";

//Folder Locations


$rootfolderimagenes = $scriptroot . "/kat_images/productsimages";
$pathToCategoriesExportFile = $scriptroot ."/kat_exportdata/";
$pathToProductExportFile = $scriptroot."/kat_exportdata/";
$pathimagestoUpload = $scriptroot . "/kat_images/imagestoUpload/";
$ZipfileSaveFolder = $scriptroot ."/kat_exportdata/";



//DATABASE Structure Array

$databasestructuresql[0] = "CREATE DATABASE $db_name;";
$databasestructuresql[1] = "USE $db_name;";

$databasestructuresql[2] = "
CREATE TABLE `tbl_raw_sitemapcat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `logicstatus_smap` varchar(25) DEFAULT NULL,
  `catid` varchar(50) DEFAULT NULL,
  `catname` varchar(50) NOT NULL DEFAULT '',
  `catqty` int(11) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catid_cat` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";


$databasestructuresql[3] = "
CREATE TABLE `tbl_raw_productloc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `logicstatus_prodloc` varchar(25) DEFAULT NULL,
  `sku` varchar(25) DEFAULT NULL,
  `producturl` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producturl` (`producturl`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[4] = "
CREATE TABLE `tbl_raw_imagesloc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `logicstatus_ima` varchar(25) DEFAULT NULL,
  `sku` varchar(25) DEFAULT NULL,
  `imaurl` varchar(255) DEFAULT '',
  `priorityima` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `imaurl` (`imaurl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[5] = "
CREATE TABLE `tbl_raw_catforproducts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(25) NOT NULL,
  `catid` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`,`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[6] = "
CREATE TABLE `tbl_raw_productdetails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `logicstatus_proddetails` varchar(25) DEFAULT NULL,
  `sku` varchar(25) DEFAULT NULL,
  `stock_allowBuy` varchar(25) DEFAULT NULL,
  `productname` text,
  `summary` text,
  `description` text,
  `stock_update` datetime DEFAULT NULL,
  `newproductname` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_tmp` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=16419 DEFAULT CHARSET=utf8;
";

$databasestructuresql[7] = "
CREATE TABLE `tbl_raw_pricehistory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `logicstatus_price` varchar(25) DEFAULT NULL,
  `sku` varchar(25) NOT NULL DEFAULT '',
  `statusinstore` varchar(50) DEFAULT NULL,
  `UnitPrice` decimal(11,2) DEFAULT NULL,
  `MarketPrice` decimal(11,2) DEFAULT NULL,
  `Rate` double DEFAULT NULL,
  `PromotionExpireTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[8] = "
CREATE TABLE `tbl_systemlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetimestamp` datetime DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[9] = "
CREATE TABLE `tbl_updatechangetracker` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reportrundate` datetime DEFAULT NULL,
  `logicstatus` varchar(25) DEFAULT NULL,
  `sku` varchar(25) DEFAULT NULL,
  `changetype` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$databasestructuresql[9] = "
CREATE TABLE `tbl_prodname_stopword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `stopword` varchar(256) NOT NULL DEFAULT '',
  `trend` int(11) DEFAULT NULL,
  `enable` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `stopword` (`stopword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

//Command Line colors
// regular
$colors['gray']   = '0;30';
$colors['red' ]   = '0;31';
$colors['green']  = '0;32';
$colors['yellow'] = '0;33';
$colors['blue']   = '0;34';
$colors['purple'] = '0;35';
$colors['cyan']   = '0;36';
$colors['white']  = '0;37';
// light
$colors['light_gray']   = '0;90';
$colors['light_red']    = '0;91';
$colors['light_green']  = '0;92';
$colors['light_yellow'] = '0;93';
$colors['light_blue']   = '0;94';
$colors['light_purple'] = '0;95';
$colors['light_cyan']   = '0;96';
$colors['light_white']  = '0;97';

$date = date("Y-m-d_H_i_s", microtime(true));

$duplicateDB[0] = "CREATE DATABASE `katcmd_db_$date` DEFAULT CHARACTER SET = `latin1` DEFAULT COLLATE = `latin1_swedish_ci`;";
$duplicateDB[1] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_catforproducts` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `sku` varchar(25) NOT NULL,   `catid` varchar(25) NOT NULL DEFAULT '',   PRIMARY KEY (`id`),   UNIQUE KEY `sku` (`sku`,`catid`) ) ENGINE=InnoDB AUTO_INCREMENT=25718 DEFAULT CHARSET=utf8;";
$duplicateDB[2] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_catforproducts` SELECT * FROM `katcmd_db`.`tbl_raw_catforproducts`;";
$duplicateDB[3] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_imagesloc` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `logicstatus_ima` varchar(25) DEFAULT NULL,   `sku` varchar(25) DEFAULT NULL,   `imaurl` varchar(255) DEFAULT '',   `priorityima` int(11) unsigned DEFAULT NULL,   PRIMARY KEY (`id`),   UNIQUE KEY `imaurl` (`imaurl`) ) ENGINE=InnoDB AUTO_INCREMENT=53522 DEFAULT CHARSET=utf8;";
$duplicateDB[4] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_imagesloc` SELECT * FROM `katcmd_db`.`tbl_raw_imagesloc`;";
$duplicateDB[5] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_pricehistory` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `logicstatus_price` varchar(25) DEFAULT NULL,   `sku` varchar(25) NOT NULL DEFAULT '',   `statusinstore` varchar(50) DEFAULT NULL,   `UnitPrice` decimal(11,2) DEFAULT NULL,   `MarketPrice` decimal(11,2) DEFAULT NULL,   `Rate` double DEFAULT NULL,   `PromotionExpireTime` datetime DEFAULT NULL,   PRIMARY KEY (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=11107 DEFAULT CHARSET=utf8;";
$duplicateDB[6] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_pricehistory` SELECT * FROM `katcmd_db`.`tbl_raw_pricehistory`;";
$duplicateDB[7] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_productdetails` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `logicstatus_proddetails` varchar(25) DEFAULT NULL,   `sku` varchar(25) DEFAULT NULL,   `stock_allowBuy` varchar(25) DEFAULT NULL,   `productname` text,   `summary` text,   `description` text,   `stock_update` datetime DEFAULT NULL,   PRIMARY KEY (`id`),   UNIQUE KEY `sku_tmp` (`sku`) ) ENGINE=InnoDB AUTO_INCREMENT=11133 DEFAULT CHARSET=utf8;";
$duplicateDB[8] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_productdetails` SELECT * FROM `katcmd_db`.`tbl_raw_productdetails`;";
$duplicateDB[9] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_productloc` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `logicstatus_prodloc` varchar(25) DEFAULT NULL,   `sku` varchar(25) DEFAULT NULL,   `producturl` varchar(255) NOT NULL,   PRIMARY KEY (`id`),   UNIQUE KEY `producturl` (`producturl`),   UNIQUE KEY `sku` (`sku`) ) ENGINE=InnoDB AUTO_INCREMENT=20630 DEFAULT CHARSET=utf8;";
$duplicateDB[10] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_productloc` SELECT * FROM `katcmd_db`.`tbl_raw_productloc`;";
$duplicateDB[11] = "CREATE TABLE `katcmd_db_$date`.`tbl_raw_sitemapcat` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `logicstatus_smap` varchar(25) DEFAULT NULL,   `catid` varchar(50) DEFAULT NULL,   `catname` varchar(50) NOT NULL DEFAULT '',   `catqty` int(11) DEFAULT NULL,   PRIMARY KEY (`id`),   UNIQUE KEY `catid_cat` (`catid`) ) ENGINE=InnoDB AUTO_INCREMENT=518 DEFAULT CHARSET=utf8;";
$duplicateDB[12] = "INSERT INTO `katcmd_db_$date`.`tbl_raw_sitemapcat` SELECT * FROM `katcmd_db`.`tbl_raw_sitemapcat`;";
$duplicateDB[13] = "CREATE TABLE `katcmd_db_$date`.`tbl_systemlog` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `datetimestamp` datetime DEFAULT NULL,   `source` varchar(100) DEFAULT NULL,   `message` text,   PRIMARY KEY (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=68530 DEFAULT CHARSET=utf8;";
$duplicateDB[14] = "INSERT INTO `katcmd_db_$date`.`tbl_systemlog` SELECT * FROM `katcmd_db`.`tbl_systemlog`;";
$duplicateDB[15] = "CREATE TABLE `katcmd_db_$date`.`tbl_updatechangetracker` (   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,   `reportrundate` datetime DEFAULT NULL,   `logicstatus` varchar(25) DEFAULT NULL,   `sku` varchar(25) DEFAULT NULL,   `changetype` text,   PRIMARY KEY (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=11107 DEFAULT CHARSET=utf8;";
$duplicateDB[16] = "INSERT INTO `katcmd_db_$date`.`tbl_updatechangetracker` SELECT * FROM `katcmd_db`.`tbl_updatechangetracker`;";
$duplicateDB[17] = "CREATE TABLE `tbl_prodname_stopword` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `stopword` varchar(256) NOT NULL DEFAULT '', `trend` int(11) DEFAULT NULL, `enable` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `stopword` (`stopword`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$duplicateDB[18] = "INSERT INTO `katcmd_db_$date`.`tbl_prodname_stopword` SELECT * FROM `katcmd_db`.`tbl_prodname_stopword`;";





?>