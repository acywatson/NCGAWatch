<?php
$errors = [];

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

  return $ex;

}

function getBillActions($chamber, $number){

  $billChamber = $chamber;
  $billNumber = $number;
  $billId = $billChamber.$billNumber;

  $url = "http://ncleg.net/gascripts/BillLookUp/BillLookUp.pl";
  $url .= "?Session=2017&BillID=".$billId."&view=history_rss";

  $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
  $xmlFile = file_get_contents($url, false, $context);
  if($xmlFile === FALSE){
    //echo "Bill ".$billChamber.$billNumber." NOT FOUND!";
    array_push($errors, $billChamber.$billNumber);
    return;
  }else{
    $xml = simplexml_load_string($xmlFile);

    //stuff XML into variable
    $items = $xml;

    //declare placeholder
    $testKeys;

    $ns_atom = $items->channel->item;
    //$xmlKeys = get_object_vars($ns_atom);

    foreach($ns_atom as $item){

      $a = $item->children();
      $tempArrayA = get_object_vars($a);

      pushBillActionToDb($tempArrayA, $billChamber);

      //array_push($previewData, $tempArrayA);
    }//end foreach

  }//end if (bill is found)

}



?>
