<?php
include_once("kat_config.php");
include_once("kat_processfunctions.php");


$allwords = array();
$keywords_ready_for_sql = array();
$trends = array();

//Requirements

//Grab all names

connectDB();

mysql_select_db('katcmd_db');

$sql =  "SELECT productname FROM tbl_raw_productdetails";
$results = mysql_query($sql) or die(mysql_error());
$allp = mysql_num_rows($results);

$namecount = 1;
$trends[$words] = 0;
while ($row = mysql_fetch_assoc($results)){

$productname = $row['productname'];

//echo $productname ."<br>";

$allwords=removeStopWords($productname);

//print_r($allwords);



foreach ($allwords as $id => $word) {
	$trends[$word] += 1;

}

show_status($colors["yellow"],"PROD NAMES WORKED.",$namecount, $allp, $size=30);
$namecount++;

}



//ksort($trends);

arsort($trends);
echo "<br>";

echo "<pre>";
print_r($trends);
echo "</pre>";



foreach ($trends as $key => $value) {
  

$sqlinsert= "INSERT INTO tbl_prodname_stopword (stopword, trend) VALUES ('$key', $value)";
mysql_query($sqlinsert);



    /*if($value == 1){

$uniquew[] = $key;

    } else {

$nouniquew[] = $key;
    }
*/


}
/*
arsort($uniquew);
arsort($nouniquew);

echo "<br>";

echo "<pre> Unique Words \r\n";
print_r($uniquew);
echo "</pre>";

echo "<br>";

echo "<pre> No Unique Words \r\n";
print_r($nouniquew);
echo "</pre>";
*/

mysql_close();



function removeStopWords($stringdata)
{
    
     $stopwords = stopWords();
    
    
    //Words to lowercase
    $stringdata_lowercase = strtolower(trim($stringdata));
    
    //Tokenization
    $tokenization = explode(' ', $stringdata_lowercase);
    
    //Strip Punctuation
    $no_punctuation = strip_punctuation($tokenization);
    
    //Delete stop words
    $tokenization_with_no_stopwords = array_diff($no_punctuation, $stopwords);
    
    //Stem words
    //$tokenization_stem = stem_words($tokenization_with_no_stopwords);
    
    //Unset blank array items on the fly if any
    $clean = array_values(array_filter($tokenization_with_no_stopwords));
    
    //Unique Words
    $results = array_unique($clean);
    
    
    return ($results);
    
}


function strip_punctuation($array)
{
    
    $newarray = array();
    //This function make use of the PorterStemmer class
    
    foreach ($array as $key => $value) {
        
        //$newarray[] = preg_replace("/[^a-zA-Z0-9]+/", "", $value);
        $newarray[] = $value;
    }
    
    return $newarray;
    
}




function stopWords(){

$stopwords = array( 
"A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",
"a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z",
"about",
"above",
"above",
"across",
"after",
"afterwards",
"again",
"against",
"all",
"almost",
"alone",
"along",
"already",
"also",
"although",
"always",
"am",
"among",
"amongst",
"amoungst",
"amount",
"an",
"and",
"another",
"any",
"anyhow",
"anyone",
"anything",
"anyway",
"anywhere",
"are",
"around",
"as",
"at",
"back",
"be",
"became",
"because",
"become",
"becomes",
"becoming",
"been",
"before",
"beforehand",
"behind",
"being",
"below",
"beside",
"besides",
"between",
"beyond",
"bill",
"both",
"bottom",
"but",
"by",
"call",
"can",
"cannot",
"cant",
"co",
"con",
"could",
"couldnt",
"couldn\'t",
"cry",
"de",
"describe",
"detail",
"do",
"does",
"doesnt",
"doesn't",
"doesn\'t",
"done",
"down",
"due",
"during",
"each",
"eg",
"eight",
"either",
"eleven","else",
"elsewhere",
"empty",
"enough",
"etc",
"even",
"ever",
"every",
"everyone",
"everything",
"everywhere",
"except",
"few",
"fifteen",
"fify",
"fill",
"find",
"fire",
"first",
"five",
"for",
"former",
"formerly",
"forty",
"found",
"four",
"from",
"front",
"full",
"further",
"get",
"give",
"go",
"had",
"has",
"hasnt",
"hasn\'t",
"have",
"haven\'t",
"he",
"he's",
"he\'s",
"hence",
"her",
"here",
"hereafter",
"hereby",
"herein",
"hereupon",
"hers",
"herself",
"him",
"himself",
"his",
"how",
"however",
"hundred",
"ie",
"if",
"in",
"isn t",
"isnt",
"isn\'t",
"inc",
"indeed",
"interest",
"into",
"is",
"it",
"its",
"itself",
"keep",
"last",
"latter",
"latterly",
"least",
"less",
"ltd",
"made",
"many",
"may",
"me",
"meanwhile",
"might",
"mill",
"mine",
"more",
"moreover",
"most",
"mostly",
"move",
"much",
"must",
"my",
"myself",
"name",
"namely",
"neither",
"never",
"nevertheless",
"next",
"nine",
"no",
"nobody",
"none",
"noone",
"nor",
"not",
"nothing",
"now",
"nowhere",
"of",
"off",
"often",
"on",
"once",
"one",
"only",
"onto",
"or",
"other",
"others",
"otherwise",
"our",
"ours",
"ourselves",
"out",
"over",
"own","part",
"per",
"perhaps",
"please",
"put",
"rather",
"re",
"same",
"see",
"seem",
"seemed",
"seeming",
"seems",
"serious",
"several",
"she",
"should",
"show",
"side",
"since",
"sincere",
"six",
"sixty",
"so",
"some",
"somehow",
"someone",
"something",
"sometime",
"sometimes",
"somewhere",
"still",
"such",
"system",
"take",
"ten",
"than",
"that",
"the",
"their",
"them",
"themselves",
"then",
"thence",
"there",
"thereafter",
"thereby",
"therefore",
"therein",
"thereupon",
"these",
"they",
"thickv",
"thin",
"third",
"this",
"those",
"though",
"three",
"through",
"throughout",
"thru",
"thus",
"to",
"together",
"too",
"top",
"toward",
"towards",
"twelve",
"twenty",
"two",
"un",
"under",
"until",
"up",
"upon",
"us",
"very",
"via",
"was",
"we",
"well",
"were",
"what",
"whatever",
"when",
"whence",
"whenever",
"where",
"whereafter",
"whereas",
"whereby",
"wherein",
"whereupon",
"wherever",
"whether",
"which",
"while",
"whither",
"who",
"whoever",
"whole",
"whom",
"whose",
"why",
"will",
"with",
"within",
"without",
"would",
"yet",
"you",
"your",
"yours",
"yourself",
"yourselves",
"the");




return $stopwords;


}





?>