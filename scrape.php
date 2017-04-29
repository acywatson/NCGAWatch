<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'getBillActions.php';

$ch = $_GET["ch"];

echo "Scraping...";

for($i=0; $i<900; $i++){

  getBillActions($ch, $i);
  //echo "Scraped ".$ch.$i."!";

}

echo "Done scraping.";
echo "Errors:";
echo "<ul>";
foreach ($errors as $index => $value) {
  echo "<li>".$value."</li>";
}
echo "</ul>";

?>
