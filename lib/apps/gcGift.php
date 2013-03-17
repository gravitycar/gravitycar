<?php
/**
gcGift - a class for representing a gift on a gift list for someone.


**/
require_once("lib/classes/Form.inc");
class gcGift extends gcForm implements Application
{
   public $id = false;
   public $name = "";
   public $url = "";
   public $purchased = false;
   public $description = "";
   public $userID = false;
   public $userObj = false;
   public $renderAsForm = true;
   public static $allUserRecords = array();
   
   function gcGift()
   {
      $this->init();
      $this->allUserRecords = $this->getAllUserRecords();
      $this->setFormProps("id", "title", "userID", "name", "url", "description", "purchased");
      $this->setFormTitle("Enter a Gift");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("title", "value", $this->getFormTitle());
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("name", "type", "text");
      $this->setInputAttr("url", "type", "text");
      $this->setInputAttr("url", "size", "30");
      $this->setInputAttr("description", "type", "textarea");
      $this->setInputAttr("description", "rows", "3");
      $this->setInputAttr("description", "cols", "30");
      $this->setInputAttr("purchased", "type", "checkbox");
      $this->setInputAttr("purchased", "value", "1");
      $this->setInputAttr("userID", "type", "select");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      //$this->setInputAttribute("", "", "");
      
      $this->setControlButtons("submitButton", "cancelButton");
      $this->setInputParams("userID", "label", "Recipient");
      $this->setInputParams("userID", "options", $this->allUserRecords);
      
      $this->setValidationRule("name", "notEmpty");
      $this->setIDValidation("The recipient you selected is not valid.", "userID");
   }
   
   function determineRequiredPermission()
   {
      $this->auth->setReqPermWeight(GC_AUTH_USER);
   }
   
   function getAllUserRecords()
   {
      if (!$this->allUserRecords)
      {
         $result = $this->db->runSelectQuery("gcUsers", "id, concat(firstName, ' ', lastName) as name", "", array("lastName ASC", "firstName ASC"));
         $hash = $this->db->getHash($result);
         foreach ($hash as $index => $assocArray)
         {
            $this->allUserRecords[$assocArray['id']] = $assocArray['name'];
         }
      }
      return $this->allUserRecords;
   }
   
   
   function run()
   {
      switch($this->determineAction())
      {
         case "entering":
            $this->setFormTitle("Create a new gift for someone.");
         break;
      
         case "updating":
            // editing existing gift. Validate ID.
            $this->setIDValidation("The ID of the gift you are trying to modify is not valid.");
            $this->setFormTitle("Update Gift");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
         break;
      
         case "creating":
            $this->setFormTitle("Update Gift");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setIDValidation("The ID of the gift you are trying to delete is not valid.");
            $this->setFormTitle("Gift was deleted");
            $this->delete();
         break;
      
         case "viewing":
            $this->setIDValidation("The ID of the gift you are trying to view is not valid.");
            $this->setFormTitle("Update Gift");
            $this->getGiftData($_GET['id']);
            $this->addControlButton("deleteButton");
         break;
      }
   }
   
   
   function save()
   {
      // save to db.
      if (!$this->validationPassed)
      {
         $this->msgCenter->addError("Could not save: validation failed.");
         return false;
      }
      
      if (empty($_POST['id']))
      {
         // new gift - skip id property, new gifts don't have them.
         $this->addSkipProp("id");
         return $this->createNewGift();
      }
      else
      {
         return $this->updateGift();
      }
   }
   
   
   /**
   :Function: getGiftData()
   
   :Description:
   Checks to make sure that we've been passed a valid ID. Then, reads gift data
   from database into a hash and then imports that hash into the gift object.
   
   :Parameters:
   int $id - the row ID for this gift.
   
   :Return Value:
   hash - the data for the passed in gift.
   
   :Notes:
   **/
   function getGiftData($id=false)
   {
      $id = $this->id ? $this->id : $id;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $query = $this->db->getSelectQuery("gcGifts", "*");
      $query->addWhereClause("id", $id);
      $result = $this->db->runQuery($query);
      
      if ($result)
      {
         if ($this->db->getNumRecords($result) == 1)
         {
            $data = $this->db->getRow($result);
         }
         else
         {
            $this->msgCenter->addError("There is no gift with the ID you have requested.");
         }
      }
      else
      {
         $this->msgCenter->addError("Requested gift cannot be displayed.");
         $data = array();
      }
      
      $this->importFromHash($data);
      return $data;
   }
   
   
   private function createNewGift()
   {
      $this->addSkipProp("id"); // NEVER update the ID property!
      $data = $this->exportToHash();
      $query = $this->db->getInsertQuery("gcGifts", $data);
      $createOK = $this->db->runQuery($query);
      if ($createOK)
      {
         $this->msgCenter->addAlert("New gift added successfully!");
         $this->id = $this->db->getNewRowID();
         // remove the skip prop for ID, we will want to include it in the form.
         $this->removeSkipProp("id");
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   private function updateGift()
   {
      $this->addSkipProp("id"); // NEVER update the ID property!
      $data = $this->exportToHash();
      $where = array('id' => $this->id);
      $query = $this->db->getUpdateQuery("gcGifts", $data, $where);
      $updateOK = $this->db->runQuery($query);
      if ($updateOK)
      {
         $this->msgCenter->addAlert("Gift has been updated successfully!");
         // remove the skip prop for ID, we will want to include it in the form.
         $this->removeSkipProp("id");
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   function delete()
   {
      $id = $_POST['id'];
      
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $where = array('id'=>$id);
      $query = $this->db->getDeleteQuery("gcGifts", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Gift was deleted successfully.");
      }
   }
   
   
   function renderAsForm()
   {
      return $this->buildForm();
   }
   
   function render()
   {
      $this->htmlObjects[] = $this->renderAsForm();
      return $this->htmlObjects;
   }
}
?>
