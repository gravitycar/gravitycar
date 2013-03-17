<?php
/**
gcCOGInvitation class - a class for managing who is invited to which events.

Most of these applications are generally for managing a single object, like
an article or a comment or an event. However, this class needs to manage
many invitations to one event. So this class doesn't manage just one invitation,
it manages multiple invitations to a single event. 

When event invitations are updated, all previous invitations for that event are
deleted. This means we don't have to examine any of the previous event 
invitations, we just wipe the old invites away and add the new ones.
**/


require_once("lib/classes/Form.inc");
class gcCOGInvitation extends gcForm implements Application
{
   public $event_id = 0;
   public $usersList = array();
   public $invitedUsers = array();
   public $invitedGuestsIDs = array();
   public $eventsList;
   public $mode = "form";
   
   function gcCOGInvitation($event_id=0)
   {
      $this->init();
      $this->event_id = $event_id;
      $this->page->addJavascriptFile("js/jquery.js");
      $this->page->addJavascriptFile("js/jquery_plugins/comboselect_plugin/jquery.comboselect.js");
      $this->page->addJavascriptFile("js/jquery_plugins/comboselect_plugin/jquery.selso.js");
      $this->page->addStyleSheet("jquery.comboselect.css");
      $this->page->addJavascriptFile("js/cogInvitations.js");
      
      $this->setFormTitle("Invite Guests");
      $this->setFormProps("title", "event_id", "usersList");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("event_id", "type", "select");
      $this->setInputAttr("usersList", "type", "select");
      $this->setInputAttr("usersList", "multiple", true);
      $this->setInputAttr("usersList", "size", 15);
      $this->setInputAttr("usersList", "name", "usersList[]");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setControlButtons("submitButton", "cancelButton");
      
      $this->setInputParams("event_id", "label", "Event Name");
      
      $this->setValidationRule("event_id", "notEmpty");
      $this->setValidationRule("event_id", "numeric");
      $this->setValidationRule("event_id", "minVal", 1);
      
      $this->setValidationRule("user_id", "notEmpty");
      $this->setValidationRule("user_id", "numeric");
      $this->setValidationRule("user_id", "minVal", 1);
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
   
   
   function getAllUsers()
   {
      if (!$this->validateID("event_id"))
      {
         $this->msgCenter->addDebug("No event ID set for invitation search.");
         return false;
      }
      
      $query = $this->db->getSelectQuery("gcUsers", array("id"));
      $query->addColumn("concat(firstName, ' ', lastName) as name", " ");
      $query->addColumn("event_id as invited", "gcCOGInvitations");
      $joinClause = $query->addJoin("LEFT OUTER JOIN", "gcCOGInvitations");
      $joinClause->addCriterium("gcCOGInvitations.guest_id = gcUsers.id");
      $joinClause->addCriterium("gcCOGInvitations.event_id = " . $this->event_id);
      $query->addOrderByClause("lastName ASC, firstName ASC");
      $result = $this->db->runQuery($query);
      $rows = $this->db->getHash($result);
      
      $data = array();
      foreach ($rows as $row)
      {
         $data[$row['id']] = $row['name'];
         if ($row['invited'] != NULL)
         {
            $this->invitedGuestsIDs[$row['id']] = true;
         }
      }
      $this->usersList = $data;
      return $data;
   }
   
   
   function getInvitedUsers()
   {
      if (!$this->validateID("event_id"))
      {
         $this->msgCenter->addDebug("No event ID set for invitation search.");
         return false;
      }
      
      $query = $this->db->getSelectQuery(array("gcUsers", "gcCOGInvitations"), array("id as id"));
      $query->addColumn("concat(gcUsers.firstName, ' ', gcUsers.lastName) as name", " ");
      $query->addInnerJoin("gcUsers.id", "gcCOGInvitations.guest_id");
      $query->addWhereClause("event_id", $this->event_id, "=", "gcCOGInvitations");
      $query->addOrderByClause("gcUsers.lastName ASC, gcUsers.firstName ASC");
      $result = $this->db->runQuery($query);
      $this->invitedUsers = $this->db->getHash($result);
   }
   
   
   function getInvitedUsersIDs()
   {
      if (!$this->invitedUsers)
      {
         $this->getInvitedUsers();
      }
      
      $ids = array();
      foreach ($this->invitedUsers as $user)
      {
         $ids[] = $user['id'];
      }
      return $ids;
   }
   
   
   function getAllEvents()
   {
      if (!$this->eventsList || !IsSet($this->eventsList[0]['id']) || !$this->eventsList[0]['eventName'])
      {
         $query = $this->db->getSelectQuery("gcCOGSocialEvents", array("id", "eventName"));
         $query->addOrderByClause("id DESC");
         $result = $this->db->runQuery($query);
         $this->eventsList = $this->db->getHash($result);
         $data = array();
         foreach ($this->eventsList as $row)
         {
            $data[$row['id']] = $row['eventName'];
         }
         $this->eventsList = $data;
      }
      
      return $this->eventsList;
   }
      
   
   function run()
   {
      // technically, you should never be doing anything except "viewing" and
      // "updating" because those are the only action that pertain to this
      // application. Invitations are created for an existing event only, and
      // invitations aren't really "updated" so much as they're deleted and
      // then re-created. 
      switch ($this->determineAction('event_id'))
      {
         case "entering":
            $this->setFormTitle("Invite Guests");
         break;
      
         case "viewing":
            $this->setIDValidation("The event you are trying to invite people to is not valid.", "event_id");
            $this->setFormTitle("Invite Guests");
            $this->event_id = $_GET['event_id'];
         break;
      
         case "creating":
            $this->setFormTitle("Invitations Created");
            $this->setIDValidation("The event you are trying to invite people to is not valid.", "event_id");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setFormTitle("Invitations Recinded");
         break;
      
         case "updating":
            $this->setFormTitle("Invitations Updated");
            $this->setIDValidation("The event you are trying to invite people to is not valid.", "event_id");
            $this->importFromPost();
            $this->save();
         break;
      }
      
      // these calls are placed here and not in the constructor function because
      // they depend on event_id being assigned, either directly or via 
      // importFromPost().
      $this->setInputParams("usersList", "options", $this->getAllUsers());
      $this->setInputParams("event_id", "options", $this->getAllEvents());
      $this->renderer->createJavascriptTag("var invitedGuestsList = " . json_encode($this->invitedGuestsIDs) . ";", false, $this->page->head);
   }
   
   
   function save()
   {
      $this->event_id = $_POST['event_id']; 
      
      if (!$this->validateID("event_id"))
      {
         return false;
      }
      
      // delete the previous invitations.
      $query = $this->db->getDeleteQuery("gcCOGInvitations", array("event_id"=>$this->event_id));
      $deleteResult = $this->db->runQuery($query);
      
      // insert the new invitations. Use multi-insert statement.
      $data = array();      
      foreach ($_POST['usersList'] as $user_id)
      {
         if ($this->validator->validateProperty('user_id', $user_id))
         {
            $data[] = array('event_id'=>$this->event_id, 'guest_id'=>$user_id);
         }
      }
      
      $query = $this->db->getMultipleInsertQuery("gcCOGInvitations", $data);
      $result = $this->db->runQuery($query);
      if ($result)
      {
         $this->msgCenter->addAlert("Users were invited successfully.");
         return true;
      }
      else
      {
         // error messages should already be recorded from ->db
         return false;
      }
   }
   
   
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
      }
      
      $this->htmlObjects[] =& $html;
      return $this->htmlObjects;
   }
   
}
?>
