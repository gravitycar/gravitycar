<?php

require_once("lib/classes/Form.inc");
class gcUser extends gcForm implements Application
{
   public $id;
   public $firstName = "";
   public $middleName = "";
   public $lastName = "";
   public $email = "";
   public $phone = "";
   public $password = "";
   public $formTitle = "";
   public $perm_id = 2;
   
   function gcUser()
   {
      $this->init();
      $this->setFormProps("title", "id", "firstName", "middleName", 
            "lastName", "email", "perm_id", "password");//, "phone");
      //$this->setSkipProps("password");
      
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("title", "value", $this->getFormTitle());
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("firstName", "type", "text");
      $this->setInputAttr("middleName", "type", "text");
      $this->setInputAttr("lastName", "type", "text");
      $this->setInputAttr("email", "type", "text");
      $this->setInputAttr("phone", "type", "text");
      $this->setInputAttr("perm_id", "type", "select");
      $this->setInputAttr("password", "type", "password");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setControlButtons("submitButton", "cancelButton");
      
      $this->setInputParams("firstName", "label", "First");
      $this->setInputParams("middleName", "label", "Middle");
      $this->setInputParams("lastName", "label", "Last");
      $this->setInputParams("perm_id", "label", "User Type");
      $this->setInputParams("perm_id", "options", $this->getPermissionLevels());
      
      $this->setValidationRule("firstName", "notEmpty");
      $this->setValidationRule("lastName", "notEmpty");
      $this->setValidationRule("email", "email");
      $this->setValidationRule("perm_id", "enum", array_keys($this->levels));
      //$this->setValidationRule("phone", "phone");
      
      
      if (!$this->isUpdate())
      {
         $pwShortRule = &$this->setValidationRule("password", "minLength", "6");
         $pwLongRule = &$this->setValidationRule("password", "maxLength", "24");
         $pwShortRule->setCustomErrorMessage("The password you have entered is too short. Please enter a longer password.");
         $pwLongRule->setCustomErrorMessage("The password you have entered is too long. Please enter a shorter password.");
      }
      else
      {
         // if this is an update, and the password is empty, don't change it.
         if ($_POST['password'] == "")
         {
            //$this->addSkipProp("password");
         }
      }
      
      // only admins can set the user type property.
      if (!$this->auth->isAdmin())
      {
         $this->addSkipProp("perm_id");
         //$this->setInputAttr("perm_id", "type", "plainText");
      }
      
   }
   
   
   function setPassword($newPass)
   {
      $this->password = $newPass;
   }
   
   
   function getPassword()
   {
      return $this->password;
   }
   
   
   function determineRequiredPermission()
   {
      $permRequired = GC_AUTH_ADMIN;
      $action = $this->determineAction("id");
      if ($action == "updating" || $action == "viewing")
      {
         if (IsSet($_GET['id']))
         {
            $recordOwnerID = $_GET['id'];
         }
         else if (Isset($_POST['id']))
         {
            $recordOwnerID = $_POST['id'];
         }
         
         if ($this->auth->userOwnsRecord($recordOwnerID))
         {
            $permRequired = GC_AUTH_USER;
         }
      }
      $this->auth->setReqPermWeight($permRequired);
   }
   
   
   /**
   :Function: render()
   
   :Description:
   Renders this object as an HTML form.
   
   :Parameters:
   None
   
   :Return Value:
   gcTableTag - a table tag object containing all of the html to render the form
      for this object.
   
   :Notes:
   **/
   function render()
   {
      $this->htmlObjects[] = $this->buildForm();
      return $this->htmlObjects;
   }

   
   /**
   :Function: getUserData()
   
   :Description:
   Checks to make sure that we've been passed a valid ID. Then, reads user data
   from database.
   
   :Parameters:
   int $id - the row ID for this user.
   
   :Return Value:
   hash - the data for the passed in user.
   
   :Notes:
   **/
   function getUserData($id=false)
   {
      $id = $this->id ? $this->id : $id;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $query = $this->db->getSelectQuery(array("gcUsers", "gcPerms"), "*");
      $query->addWhereClause("id", $id);
      $query->addInnerJoin("gcUsers.perm_id", "gcPerms.id");
      $result = $this->db->runQuery($query);
      
      if ($result)
      {
         if ($this->db->getNumRecords($result) == 1)
         {
            $data = $this->db->getRow($result);
         }
         else
         {
            $this->msgCenter->addError("There is no user with the ID you have requested.");
         }
      }
      else
      {
         $this->msgCenter->addError("Requested user cannot be displayed.");
         $data = array();
      }
      
      $this->importFromHash($data);
      $this->db->close_result($result);
      return $data;
   }
      
   
   /**
   :Function: run()
   
   :Description:
   Determines what actions to take based on the state of POST and GET variables.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function run()
   {
      switch ($this->determineAction())
      {
         case "updating":
            // editing existing user. Validate ID.
            $this->setIDValidation("The ID of the user you are trying to modify is not valid.");
            $this->setFormTitle("User Updated");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
         break;
         
         case "creating":
            $this->setFormTitle("User Created");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
         break;
         
         case "deleting":
            $this->delete();
            $this->setFormTitle("User Deleted");
         break;
         
         case "entering":
            $this->setFormTitle("Create User");
         break;
         
         case "viewing":
         default:
            $this->setIDValidation("Sorry, we could not locate the requested record.");
            $this->setFormTitle("Update User");
            $this->getUserData($_GET['id']);
            $this->addControlButton("deleteButton");
         break;
      }
   }
   
   
   function delete()
   {
      $this->setIDValidation("The ID of the user you are trying to delete is not valid.");
      $id = $_POST['id'];
      
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $where = array('id'=>$id);
      $query = $this->db->getDeleteQuery("gcUsers", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("User was deleted successfully.");
      }
      $this->db->close_result($deleteOK);
   }
  
   
   /**
   :Function: createNewUser()
   
   :Description:
   Takes data from POST and builds an SQL INSERT statement. If the
   statement is run successfully, it returns the new user ID.
   
   :Parameters:
   None (but data must be submitted via POST)
   
   :Return Value:
   int - the row ID of the new user.
   
   :Notes:
   NOTE: the data validation process should have already taken place in 
   the importFromPost() method. If data validation failed, this function should
   NOT be called!
   **/
   public function createNewUser()
   {
      $data = $this->exportToHash();
      $query = $this->db->getInsertQuery("gcUsers", $data);
      $createOK = $this->db->runQuery($query);
      if ($createOK)
      {
         $this->msgCenter->addAlert("New user added successfully!");
         $this->id = $this->db->getNewRowID();
         // remove the skip prop for ID, we will want to include it in the form.
         $this->removeSkipProp("id");
         $this->db->close_result($createOK);
         return true;
      }
      else
      {
         return false;
      }
   }

   
   /**
   :Function: updateUser()
   
   :Description:
   Takes data from POST and builds an SQL UPDATE statement. If the
   statement is run successfully, it returns the new user ID.
   
   :Parameters:
   None (but data must be submitted via POST)
   
   :Return Value:
   int - the row ID of the new user.
   
   :Notes:
   NOTE: the data validation process should have already taken place in 
   the importFromPost() method. If data validation failed, this function should
   NOT be called!
   **/
   public function updateUser()
   {
      $this->addSkipProp("id"); // NEVER update the ID property!
      
      // if the password field is empty, skip it.
      if ($this->password == "")
      {
         $this->addSkipProp("password");
      }
      
      $data = $this->exportToHash();
      $where = array('id' => $this->id);
      $query = $this->db->getUpdateQuery("gcUsers", $data, $where);
      $updateOK = $this->db->runQuery($query);
      
      // remove the skip prop for ID, we will want to include it in the form.
      $this->removeSkipProp("id");
      $this->removeSkipProp("password");
      if ($updateOK)
      {
         $this->msgCenter->addAlert("User has been updated successfully!");
         $this->db->close_result($updateOK);
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   /**
   :Function: save()
   
   :Description:
   Saves the user object to the mysql database.
   
   :Parameters:
   None
   
   :Return Value:
   boolean - true if update succeeds, false if it fails.
   
   :Notes:
   No params BUT submitted form data should be imported into this object via
   importFromPost() before this method is called.
   **/
   function save()
   {
      // save to mysql db.
      if (!$this->validationPassed)
      {
         $this->msgCenter->addError("Could not save user: validation failed.");
         return false;
      }
      if (empty($_POST['id']))
      {
         // new user - skip id property, new users don't have them.
         $this->addSkipProp("id");
         return $this->createNewUser();
      }
      else
      {
         return $this->updateUser();
      }
   }
   
   
   /**
   :Function: getPermissionLevels()
   
   :Description:
   Returns an array of user permission levels, with the permission row ID as 
   the array key and the name of the permission level as the value.
   
   :Parameters:
   None
   
   :Return Value:
   hash - permission id's as key and permission name as value.
   
   :Notes:
   **/
   function getPermissionLevels()
   {
      $this->levels = array();
      $query = $this->db->getSelectQuery("gcPerms");
      $query->addWhereClause("permWeight", 10, ">=", "gcPerms");
      $query->setOrderByClauses(array("permWeight ASC"));
      $this->db->runQuery($query);
      $data = $this->db->getHash();
      foreach ($data as $row)
      {
         $this->levels[$row['id']] = $row['userType'];
      }
      return $this->levels;
   }
   
   
   /**
   :Function: 
   
   :Description:
   None.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
}
?>
