<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
echo "<h1>NCGA Watch</h1>";
echo "<a href='scrape.php?ch=H'>Scrape House Bill History</a><br>";
echo "<a href='scrape.php?ch=S'>Scrape Senate Bill History</a>";


$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));

//871 house
//

$billChamber = "S";
$billNumber = 423;
$billId = $billChamber.$billNumber;

$url = "http://ncleg.net/gascripts/BillLookUp/BillLookUp.pl";
$url .= "?Session=2017&BillID=".$billId."&view=history_rss";

$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
$xml = file_get_contents($url, false, $context);
$xml = simplexml_load_string($xml);

//stuff XML into variable
$items = $xml;

//declare placeholder
$testKeys;

$ns_atom = $items->channel->item;
$xmlKeys = get_object_vars($ns_atom);

//compare the counts, to see if we need to add new actions to DB
$actionCount = count($ns_atom);

function getLastAction($number, $chamber){

  $pdo = new PDO('mysql:host=localhost;dbname=ncgaWatch','root','root');

  if($chamber == "H"){
    $stmt = $pdo->prepare("SELECT MAX(actionNumber) FROM tblHouseBills WHERE billNumber = :billNumber");
  }else if($chamber == "S"){
    $stmt = $pdo->prepare("SELECT MAX(actionNumber) FROM tblSenateBills WHERE billNumber = :billNumber");
  }

  $stmt->bindParam(':billNumber', $number);
  $ex = $stmt->execute();
  $result = $stmt->fetch();

  //cast to INT and return
  $count = (int) $result[0];
  return $count;

}

getLastAction($billId, $billChamber);

function pushBillActionToDb($array, $chamber){

  $guid = $array["guid"];
  $guidArr = explode("|",$guid);
  $billNumber = $guidArr[0];
  $actionNumber = $guidArr[1];
  $billTitle = $guidArr[2];
  $title = $array["title"];

  $companionBillNumber = '123';

  $hash = md5($guid);

  $date = DateTime::createFromFormat('D\, j M Y H:i:s T', $array["pubDate"]);
  $date = $date->format('Y-m-d H:i:s');

  $pdo = new PDO('mysql:host=localhost;dbname=ncgaWatch','root','root');

  if($chamber == "H"){
    $stmt = $pdo->prepare("INSERT IGNORE INTO tblHouseBills (billNumber, action, actionNumber, billTitle, billUrl, pubDate, companionBillNumber, billId) VALUES (:billNumber, :action, :actionNumber, :billTitle, :billUrl, :pubDate, :companionBillNumber, :billId)");
  }else if($chamber == "S"){
    $stmt = $pdo->prepare("INSERT IGNORE INTO tblSenateBills (billNumber, action, actionNumber, billTitle, billUrl, pubDate, companionBillNumber, billId) VALUES (:billNumber, :action, :actionNumber, :billTitle, :billUrl, :pubDate, :companionBillNumber, :billId)");
  }
  $stmt->bindParam(':billNumber', $billNumber);
  $stmt->bindParam(':action', $array["title"]);
  $stmt->bindParam(':actionNumber', $actionNumber);
  $stmt->bindParam(':billTitle', $billTitle);
  $stmt->bindParam(':billUrl', $array["link"]);
  $stmt->bindParam(':pubDate', $date);
  $stmt->bindParam(':companionBillNumber', $companionBillNumber);
  $stmt->bindParam(':billId', $hash);

  $ex = $stmt->execute();

}

/* Build Array of Preview Data */
$previewData = [];
foreach($ns_atom as $item){

  $a = $item->children();
  $tempArrayA = get_object_vars($a);

  pushBillActionToDb($tempArrayA, $billChamber);

  array_push($previewData, $tempArrayA);

}
/* End Build Preview Data */
?>

<table id="previewData" border = "1" style="margin-top: 20px; border:1px solid black; width: 100%; overflow-x: scroll; overflow-y: scroll; font-size: 10px;">

<?php
  echo "<thead>";
  //echo "<th></th>";
  foreach ($xmlKeys as $key => $value) {
    echo "<th>";
    echo $key;
    echo "</th>";
  }
  echo "</thead>";
  echo "<tbody>";
  $rowCount = 0;
  foreach ($previewData as $data) {
    
      foreach($xmlKeys as $xmlK => $xmlV){
      echo "<td>";
      //handle empty nodes - insert placeholder for table alignment.
      if(!array_key_exists($xmlK, $data)){
        echo "no data";
      }else{
      //handle arrays -  we can handle these here by just looping again
      if(gettype($data[$xmlK]) != "array"){
      echo $data[$xmlK];
      }else{
      $previewString = "";
      foreach ($data[$xmlK] as $aKey => $aVal) {
        $previewString .= $aVal.", ";
      }
      echo $previewString;
      }///arrayCheck else
    } //column data check
        echo "</td>";
  } //xmlKeys iterator
  //  }
    echo "</tr>";
    $rowCount++;
  }
  echo "</tbody>";
?>

</table>
