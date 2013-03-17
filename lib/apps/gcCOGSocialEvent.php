<?php
/**
gcCOGSocialEvent class - a class for creating and managing social events
**/

require_once("lib/classes/Form.inc");
class gcCOGSocialEvent extends gcForm implements Application
{
   public $id = 0;
   public $eventName;
   public $events = array();
   public $eventDescription;
   public $creator_id;
   public $mode = "form";
   
   function gcCOGSocialEvent($event_id=0)
   {
      $this->init();
      $this->id = $event_id;
      $this->page->addJavascriptFile("js/jquery.js");
      $this->setFormProps("title", "id", "eventName", "eventDescription", "creator_id");
      $this->setFormTitle("Create a New Event");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("creator_id", "type", "hidden");
      $this->setInputAttr("eventName", "type", "text");
      $this->setInputAttr("eventName", "size", "50");
      $this->setInputAttr("eventDescription", "type", "textarea");
      $this->setInputAttr("eventDescription", "cols", "60");
      $this->setInputAttr("eventDescription", "rows", "6");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setControlButtons("submitButton", "cancelButton");
      
      $this->setValidationRule("eventName", "notEmpty");
      $this->setValidationRule("eventName", "maxLength", "100");
      $this->setValidationRule("eventDescription", "maxLength", "500");
   }
   
   
   function determineRequiredPermission()
   {
      switch ($this->getMode())
      {
         case "display":
            $this->auth->setReqPermWeight(GC_AUTH_USER);
         break;
      
         case "form":
            $this->auth->setReqPermWeight(GC_AUTH_ADMIN);
         break;
      }
   }
   
   
   function locateEventID()
   {
      if ($this->id != 0)
      {
         return true;
      }
      
      $possibleLocations = array($_GET, $_POST);
      $possibleNames = array("id", "event_id");
      
      foreach ($possibleLocations as $location)
      {
         foreach ($possibleNames as $name)
         {
            if (IsSet($location[$name]))
            {
               $this->id = $location[$name];
               break;
            }
         }
      }
      
      if ($this->id === 0)
      {
         $this->addSkipProp("id");
      }
   }
   
   
   function getAllMyEvents($guest_id)
   {
      $columns = array("id", "eventName");
      $query = $this->db->getSelectQuery(array("gcCOGSocialEvents", "gcCOGInvitations"), $columns);
      $query->addWhereClause("guest_id", $guest_id, "=", "gcCOGInvitations");
      $query->addInnerJoin("gcCOGSocialEvents.id", "gcCOGInvitations.event_id");
      $query->addOrderByClause("gcCOGSocialEvents.id DESC");
      $result = $this->db->runQuery($query);
      $rows = $this->db->getHash($result);
      foreach ($rows as $row)
      {
         $data[$row['id']] = $row;
      }
      return $row;
   }
   
   
   function getAllEvents()
   {
      if (!$this->events)
      {
         $result = $this->getAllEventsResult();
         $data = $this->db->getHash($result);
         foreach ($data as $index => $row)
         {
            $this->events[$row['id']] = $row;
         }
      }
      return $this->events;
   }
   
   
   function getAllEventsResult()
   {
      $result = $this->db->runSelectQuery("gcCOGSocialEvents", array("id", "id as event_id", "eventName"), "", array("id DESC"));
      
      if (!$result)
      {
         $this->msgCenter->addError("Could not query social events. Please try again later.");
         return false;
      }
      return $result;
   }
   
   
   function getEventData()
   {
      $eventID = $this->id;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $eventID);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $data = array();
      $result = $this->db->runSelectQuery("gcCOGSocialEvents", "*", array("id"=>$eventID));
      
      if ($result)
      {
         if ($this->db->getNumRecords($result) == 1)
         {
            $data = $this->db->getRow($result);
         }
      }
      else
      {
         $this->msgCenter->addError("Could not retrieve event data.");
      }
      
      $this->importFromHash($data);
      return $data;
   }
   
   
   /**
   :Function: run()
   
   :Description:
   Determines what action the user is taking and performs the necessary tasks
   to complete that action.
   
   :Parameters:
   none.
   
   :Return Value:
   none.
   
   :Notes:
   **/
   function run()
   {
      $this->locateEventID();
      switch ($this->determineAction("id"))
      {
         case "entering":
            $this->setFormTitle("Create a new Event");
            $this->setInputAttr("creator_id", "value", $this->auth->getUserID());
         break;
      
         case "viewing":
            $this->setFormTitle("Update Article");
            $this->setIDValidation("The ID of the event you are trying to view is not valid.");
            $this->getEventData();
            $this->addControlButton("deleteButton");
         break;
      
         case "creating":
            $this->setFormTitle("Event was Created");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setFormTitle("Article has been deleted");
            $this->setIDValidation("The ID of the event you are trying to delete is not valid.");
            $this->delete();
         break;
      
         case "updating":
            $this->setFormTitle("Event was Updated");
            $this->setIDValidation("The ID of the event you are trying to modify is not valid.");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
         break;
      }
   }
   
   
   /**
   :Function: save()
   
   :Description:
   Checks to see if validation passed before doing anything. IF validate is OK,
   saves the event by either creating it or updating it, depending on the
   presence of an ID in the POST data.
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if create/update succeeds, false if validation fails or
      create/update fails.
   
   :Notes:
   **/
   function save()
   {
      if (!$this->validationPassed)
      {
         $this->msgCenter->addError("Could not save event: validation failed.");
         return false;
      }
      
      $this->addSkipProp("id"); // NEVER update the event id!
      $data = $this->exportToHash();
      
      if (empty($_POST['id']))
      {
         $query = $this->db->getQuery("insert", "gcCOGSocialEvents", $data);
         $confirmMsg = "New Event Created Successfully";
         $needRowID = true;
      }
      else
      {
         $where = array("id"=>$this->id);
         $query = $this->db->getQuery("update", "gcCOGSocialEvents", $data, $where);
         $confirmMsg = "Event Updated Successfully";
         $needRowID = false;
      }
      
      $queryOK = $this->db->runQuery($query);
      $this->removeSkipProp("id");
      if ($queryOK)
      {
         $this->msgCenter->addAlert($confirmMsg);
         if ($needRowID)
         {
            $this->id = $this->db->getNewRowID();
         }
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   /**
   :Function: delete()
   
   :Description:
   Deletes an event from the database.
   
   :Parameters:
   None.
   
   :Return Value:
   booelean - true if delete is successful, false otherwise.
   
   :Notes:
   **/
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
      $query = $this->db->getQuery("delete", "gcCOGSocialEvents", "", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Event Deleted Successfully");
      }
      return $deleteOK;
   }
   
   
   /**
   :Function: render()
   
   :Description:
   Creates the object heirarchy necessary to render this object as HTML. Social
   Events can be rendered as a simple form or as plain text.
   
   :Parameters:
   string $mode - the render mode. Optional, default is "text".
   
   :Return Value:
   array - an array of HTML objects to render this object instance.
   
   :Notes:
   **/
   function render($mode="")
   {
      if ($mode != "")
      {
         $this->setMode($mode);
      }
      
      switch ($this->getMode())
      {
         case "form":
            $html =& $this->buildForm();
         break;
      
         case "text":
            $html =& $this->renderAsText();
         break;
         
         case "list":
            $html =& $this->renderAsList();
         break;
      }
      $this->htmlObjects[] =& $html;
      return $this->htmlObjects;
   }
   
   
   function &renderAsList()
   {
      $result = $this->getAllEventsResult();
      
      switch ($_GET['task'])
      {
         case "invitations":
            $url = "inviteUsersToEvent.php";
            $varName = "event_id";
            $title = "Invite People to your Event";
         break;
         
         case "proposeDates":
            $url = "proposeEventDates.php";
            $varName = "event_id";
            $title = "Propose Dates for your Event";
         break;
         
         case "event":
         default:
            $url = "editEvent.php";
            $varName = "id";
            $title = "Modify your Event";
         break;
      }
      
      if ($result)
      {
         $this->page->setTitle($title);
         $this->page->setPageTitle($title);
         $html = $this->buildEditList(&$result, $url, $varName, "eventName");
      }
      return $html;
   }
   
   
   
}
?>
