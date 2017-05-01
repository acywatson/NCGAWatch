<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'getBillActions.php';

$ch = $_GET["ch"];

echo "Scraping...<br><br>";

for($i=0; $i<10; $i++){
  getBillActions($ch, $i);
}

echo "Done scraping.<br><br>";
echo "Errors:";
echo "<ul>";
foreach ($errors as $index => $value) {
  echo "<li>".$value."</li>";
}
echo "</ul>";

?>
