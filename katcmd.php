<?php

//*********************************************************
// KATCMD - KitBlitz Automation Tool Command Line version
// Programmer: Angel Arcelay
// Date: July 21,2017
// Handy Commands
// cd /Users/arcelay/Sites/kat_cmd
// katcmd.php auto categories productsurl productdetails imagecheck imagedownload exportcategories exportproducts serverupdate
//*********************************************************
// cd /Applications/opencartkitblitz/apache2/htdocs/kat_cmd
// /Applications/opencartkitblitz/php/bin/php filetorun.php

include_once("kat_config.php");
include_once("kat_processfunctions.php");
include_once("kat_exportfunctions.php");

$conn=connectDB();


$processStep = $argv[1];

if(isset($processStep)){


//Data Acquisition Process
if ($processStep=="auto"){ //All steps

	grabSiteMapCategories();       // Grab categories from Supplier, KitBlitz Menu, All Categories
	grabProductsUrlsandCats();    // Grab each product page URL and link it to a category from Supplier
	grabProductsDetails();       // Grab products details from Supplier
	imagesCheckExist();         // Mark images for download in DB
	downloadImages();          // Download images from Supplier
	categoryExportToExcel();  // Export Categories to Excel: Import Export Excel Sheet module format
	productsExportToExcel(); //Export Products to Excel: Import Export Excel Sheet module format
        
} elseif ($processStep=="cat")       { 
	grabSiteMapCategories();

} elseif ($processStep=="url")      { 
	grabProductsUrlsandCats();

} elseif ($processStep=="det")   { 
	grabProductsDetails();

} elseif ($processStep=="ichk")       { 
	imagesCheckExist();

} elseif ($processStep=="idown")    { 
	downloadImages();

} elseif ($processStep=="clean")   { 
	ProductDetailCleaner();

//Export Process
} elseif ($processStep=="expcat") {
	
    categoryExportToExcel();

} elseif ($processStep=="expprod")   { 
	
	productsExportToExcel();

} elseif ($processStep=="zip")   { 
	prepareUpload();

} elseif ($processStep=="changeme")   { 
	system('/Applications/opencartkitblitz/php/bin/php /Applications/opencartkitblitz/apache2/htdocs/kat_cmd/changesomethingnames.php');

} elseif ($processStep=="nameana")   { 
	system('/Applications/opencartkitblitz/php/bin/php /Applications/opencartkitblitz/apache2/htdocs/kat_cmd/kat_nameanalysis.php');
	//newProductsNames();
} elseif ($processStep=="openexp")   { 
	system('open ' .$pathToCategoriesExportFile);

} elseif ($processStep=="serverupdate")     { // Once all supplier data is complete export data directly to KitBlitz


	//Inside auto as 
	// if $argv[2] == "server"
	// Export categories to Kitblitz
	// Export product details to Kitblitz
	// Mark images to download / check exist
	// Download images

} elseif ($processStep=="reset")            { //Reset entire process to start over logic
    resetProcess();


} elseif ($processStep=="install") { //Reset entire process to start over logic
	createDB($conn, $db_name, $databasestructuresql);
	

} elseif ($processStep=="h")                { //Help Section
	help();

} 


} else { 

	echo "\r\nKITBLIT AUTOMATION TOOL (command line version) \r\n * h for 'Help'\r\n\r\n"; 


}


mysql_close($conn);

?>