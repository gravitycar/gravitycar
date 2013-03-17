<?php
require_once("lib/apps/gcUser.php");
class gcListUsers extends gcApplication implements Application
{
   public $list;
   public $data = array();
   public $table = null;
   public $fields = array();
   
   function gcListUsers()
   {
      $this->init();
   }
   
   
   function getAllUsers()
   {
      $result = $this->db->runSelectQuery("gcUsers", "*", "", array("lastName"));
      $recordCount = $this->db->getNumRecords();
      for ($i = 0; $i < $recordCount; $i++)
      {
         $this->data[$i] = $this->db->getRow($result);
         if ($i === 0)
         {
            $this->fields = array_keys($this->data[$i]);
         }
      }
   }
   
   
   function run()
   {
      $userData = $this->getAllUsers();
   }
   
   
   function canEdit($userRecordID)
   {
      static $isAdmin;
      
      if (IsSet($isAdmin))
      {
         if ($isAdmin === true)
         {
            return $isAdmin;
         }
      }
      else
      {
         $isAdmin = $this->auth->isAdmin();
      }
      
      $ownsRecord = $this->auth->userOwnsRecord($userRecordID);
      
      if ($isAdmin || $ownsRecord)
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   function render()
   {
      $this->table = $this->renderer->createTableTag();
      $this->table->setAttribute("class", "tabularData");
      $this->table->setTableAttributes();
      $rowClass = "light";
      
      $headerRow = $this->table->addRow();
      $headerRow->isHeader = true;
      $headerDone = false;
      foreach ($this->data as $userData)
      {
         $user = new gcUser();
         $user->importFromHash($userData);
         $user->addFormProp("edit");
         $user->addSkipProp("perm_id");
         $user->addSkipProp("title");
         
         $row = $this->table->addRow();
         $row->setAttribute("class", $rowClass);
         
         $formProps = $user->getFormProps();
         $skipProps = $user->getSkipProps();
         foreach($formProps as $propName)
         {
            if (in_array($propName, $skipProps))
            {
               continue;
            }
            
            if ($user->getInputAttr($propName, "type") == "hidden")
            {
               continue;
            }
            
            if ($user->getInputAttr($propName, "type") == "password")
            {
               continue;
            }
            
            if (!$headerDone)
            {
               $label = $user->getPrettyPropName($propName);
               $headerRow->addCell($label);
            }
            
            if ($propName == "edit")
            {
               if ($this->canEdit($user->id))
               {
                  $user->$propName = $this->renderer->createATag("edit");
                  $user->$propName->setAttribute("href", "editUser.php?id=" . $user->id);
               }
               else
               {
                  $user->$propName = $this->renderer->createSpanTag("");
               }
            }
            
            $row->addCell($user->$propName);
         }
         $headerDone = true;
         $rowClass = $rowClass == "light" ? "dark" : "light";
      }
      
      $this->htmlObjects[] = $this->table;
      return $this->htmlObjects;
   }
   
}
?>
