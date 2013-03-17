<?php
include_once("lib/classes/Validator.inc");
include_once("lib/classes/MySQL.inc");
include_once("lib/classes/MessageCenter.inc");



function updatePurchased()
{
   $rules['id'] = array(new gcValidationRule('numeric'));
   $rules['purchased'] = array(new gcValidationRule('numeric'));
   $validator = new gcValidator($rules);
   
   if ($validator->validate($_POST))
   {
      $giftID = $_POST['giftID'];
      $purchased = $_POST['purchased'];
      $db = gcMySQLDB::Singleton();
      $data = array();
      $data['purchased'] = $_POST['purchased'];
      $where['id'] = $_POST['id'];
      $query = $db->getUpdateQuery("gcGifts", $data, $where);
      $db->runQuery($query);
   }
   die();
}


switch($_POST['task'])
{
   case "setGiftPurchasedState":
      updatePurchased();
   break;
}
?>
