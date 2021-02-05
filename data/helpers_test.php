<?php
require 'DataHelpers.php';

function output($query){
	$query = 'publications?' . urldecode($query);
	echo '<h2>' . $query . '</h2>';
	echo '<p><a href="https://dev.npeu.ox.ac.uk/data/' . $query . '">https://dev.npeu.ox.ac.uk/data/' . $query . '</a></p><hr />';
}

$human_readable = "authors: Knight";
$query = DataHelpers::formatQuery($human_readable);
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
echo '<h3>Author is Knight</h3>';
output($query);


$human_readable = "authors: Knight OR Hollowell";
$query = DataHelpers::formatQuery($human_readable);
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
echo '<h3>Author is Knight OR Hollowell</h3>';
output($query);


$human_readable = "authors: Knight AND Kurinczuk";
$query = DataHelpers::formatQuery($human_readable);
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
echo '<h3>Author is Knight AND Kurinczuk</h3>';
output($query);


$human_readable = "authors: Knight AND Kurinczuk
year: 2010";
$query = DataHelpers::formatQuery($human_readable);
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
echo '<h3>Author is Knight AND Kurinczuk, AND year is 2010</h3>';
output($query);


$human_readable = "authors: (Knight AND Kurinczuk) NOT Brocklehurst";
$query = DataHelpers::formatQuery($human_readable);
echo '<h3>Author is Knight AND Kurinczuk but NOT Brocklehurst</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$human_readable = "year: >=2000";
$query = DataHelpers::formatQuery($human_readable);
echo '<h3>Year is >= 2000</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$human_readable = "year: >=2000 AND <2010";
$query = DataHelpers::formatQuery($human_readable);
echo '<h3>Year is between 2000 and 2009 inclusive</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$human_readable = "authors: Hollowell
year: 2010";
$query = DataHelpers::formatQuery($human_readable);
echo '<h3>Author is Hollowell AND Year 2010</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$human_readable = "authors: Hollowell
OR: year: 2010";
$query = DataHelpers::formatQuery($human_readable);
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
echo '<h3>Author is Hollowell OR Year 2010 (unlikely perhaps  - but it works!)</h3>';
output($query);

$human_readable = "call_number: 2012-01,2011-24,2011-06,NPEU-90,NPEU-88,NPEU-87,NPEU-85
ORDER: as call_number";
$query = DataHelpers::formatQuery($human_readable);
echo '<h3>List of call numbers, ordered by call number</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$query = 'type=Journal+Article&collect=year_desc';
echo '<h3>All article collected by year</h3>';
#echo "<pre>\n";var_dump($query);echo "</pre>\n";
output($query);


$human_readable = 'authors: Knight
collect: year desc';
echo '<h3>Temp</h3>';
$query = DataHelpers::formatQuery($human_readable);
output($query);


//https://dev.npeu.ox.ac.uk/data/publications?call_number=NPEU-90,NPEU-91,2010-43,2011-01,2011-35,2012-11&order=+FIELD(call_number,'NPEU-90','NPEU-91','2010-43','2011-01','2011-35','2012-11')&collect=type

/*
authors: Hollowell
collect: type

2011-24
2011-06
NPEU-89
NPEU-88
NPEU-87
NPEU-86
NPEU-85
NPEU-84
NPEU-83
NPEU-82
NPEU-81
NPEU-80
NPEU-79
*/






/*
   $human_readable = "authors: (Knight OR Hollowell) NOT Kurinczuk
   OR: year: >=2012
   tag: A_tag
   ";
   $query = DataHelpers::formatQuery($human_readable);
   #echo "<pre>\n";var_dump($query);echo "</pre>\n";
*/

exit;
?>