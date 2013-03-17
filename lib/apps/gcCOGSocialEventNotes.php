<?php
/**
gcCOGSocialEventNotes class - a class for creating an managing comments and/or
notes that invitees leave about the events they are invited to.

This class can be used to:
   Create or edit one note for one event by one user (guest).
    - or -
   Display all the notes for one event left by any/all guests.
   
**/

require_once("lib/classes/Form.inc");
class gcCOGSocialEventNotes extends gcForm implements Application
{
   public $id = 0;
   public $event_id = 0;
   public $guest_id = 0;
   public $note = "";
   public $notes = array();
   public $mode = "form";
   public $dateEntered = "";
   public $timestamp = 0;
   public $speaker = "";
   
   function gcCOGSocialEventNotes($event_id = 0)
   {
      $this->init();
      $this->event_id = $event_id;
      
      $this->setFormTitle("Leave a Witty Remark");
      $this->setFormProps("title", "id", "event_id", "guest_id", "note", "dateEntered");
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("guest_id", "type", "hidden");
      $this->setInputAttr("event_id", "type", "hidden");
      $this->setInputAttr("note", "type", "textarea");
      $this->setInputAttr("note", "rows", "6");
      $this->setInputAttr("note", "cols", "60");
      $this->setInputAttr("dateEntered", "type", "hidden");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setControlButtons("submitButton", "cancelButton");
      
      $this->guest_id = $this->auth->getUserID();
      
      $this->setValidationRule("note", "notEmpty");
      //$this->setIDValidation("This is not a valid note.", "id");
      $this->setIDValidation("The event you are trying to leave a note for is not valid.", "event_id");
      $this->setIDValidation("The guest trying to leave a note is not valid.", "guest_id");
   }
   
   
   function determineRequiredPermission()
   {
      $this->auth->setReqPermWeight(GC_AUTH_USER);
   }
   
   
   function &getNoteQuery()
   {
      $tables = array("gcCOGSocialEventNotes", "gcUsers");
      $query = $this->db->getSelectQuery($tables);
      $query->addColumn("CONCAT(gcUsers.firstName, ' ', gcUsers.lastName) as speaker", " ");
      $query->addColumn("unix_timestamp(dateEntered) as timestamp", " ");
      $query->addInnerJoin("gcCOGSocialEventNotes.guest_id", "gcUsers.id");
      
      return $query;
   }
   
   function getNoteData()
   {
      $this->setIDValidation("This is not a valid note.", "id");
      $eventOK = $this->validateID("event_id");
      $noteOK = $this->validateID("id");
      
      if (!$eventOK || !$noteOK)
      {
         return false;
      }
      $query = $this->getNoteQuery();
      $query->addWhereClause("id", $this->id, "=", "gcCOGSocialEventNotes");
      
      $this->db->setTestMode(true);
      $result = $this->db->runQuery($query);
      $this->db->setTestMode(false);
      $data = $this->db->getHash($result);
      $this->importFromHash($data[0]);
   }
   
   
   function getAllNotes()
   {
      if (!$this->validateID("event_id"))
      {
         return false;
      }
      
      $query = $this->getNoteQuery();
      $query->addWhereClause("event_id", $this->event_id);
      $query->addOrderByClause("dateEntered DESC");
      $result = $this->db->runQuery($query);
      $rows = $this->db->getHash($result);
      foreach ($rows as $row)
      {
         $this->notes[] = $row;
      }
      
      return $this->notes;
   }
   
   
   function run()
   {
      switch ($this->determineAction("event_id"))
      {
         case "entering":
            $this->setFormTitle("Enter your witty remark.");
         break;
      
         case "viewing":
            $this->setFormTitle("Update Note");
            $this->id = $_GET['id'];
            $this->setInputAttr("dateEntered", "type", "plainText");
            $this->getNoteData();
         break;
      
         case "creating":
            $this->setFormTitle("Update Note");
            $this->setInputAttr("dateEntered", "type", "plainText");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setFormTitle("Article has been deleted");
            $this->delete();
         break;
      
         case "updating":
            $this->setFormTitle("Event was Updated");
            $this->setInputAttr("dateEntered", "type", "plainText");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
         break;
      }
   }
   
   
   function save()
   {
      if (!$this->validationPassed)
      {
         $this->msgCenter->addError("Could not save note: validation failed.");
         return false;
      }
      
      $this->addSkipProp("id");
      $data = $this->exportToHash();
      if (empty($_POST['id']))
      {
         $data['dateEntered'] = "NOW()";
         $query = $this->db->getQuery("insert", "gcCOGSocialEventNotes", $data);
         $query->unquoteColumnValue("dateEntered");
         $confirmMsg = "Note entered successfully.";
         $needRowID = true;
      }
      else
      {
         $where = array("id"=>$this->id);
         $query = $this->db->getQuery("update", "gcCOGSocialEventNotes", $data, $where);
         $confirmMsg = "Note updated sucessfully";
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
   
   
   function delete()
   {
      $id = $_POST['id'];
      $this->setIDValidation("This is not a valid note.", "id");
      $validID = $this->validateID("id");
      
      if (!$validID)
      {
         return false;
      }
      
      $where = array('id', $id);
      $query = $this->db->getQuery("delete", "gcCOGSocialEventNotes", "", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Note deleted successfully.");
      }
      return $deleteOK;
   }
   
   
   function render()
   {
      switch ($this->mode)
      {
         case "form":
            $this->htmlObjects[] = $this->buildForm();
         break;
      
         case "display":
            $this->htmlObjects[] = $this->renderNote();
         break;
      
         case "displayAll":
            $this->htmlObjects[] = $this->renderAllNotes();
         break;
      
         case "list":
            $this->htmlObjects[] = $this->renderList();
         break;
      }
   }
   
   
   function renderList()
   {
      $query = $this->db->getSelectQuery("gcCOGSocialEventNotes", array("id", "note"));
      $query->addColumn("SUBSTRING(1, 50, note) as shortNote", " ");
      if (IsSet($this->event_id))
      {
            
         if (!$this->validateID("event_id"))
         {
            return false;
         }
         
         $query->addWhereClause("event_id", $this->event_id);
      }
      
      if (IsSet($this->guest_id))
      {
         if (!$this->validateID("guest_id"))
         {
            return false;
         }
         
         $query->addWhereClause("guest_id", $this->guest_id);
      }
      
      $result = $this->db->runQuery($query);
      $url = "editNote.php";
      return $this->buildEditList($result, $url, "id", "shortNote");
   }
   
   
   function &renderNote()
   {
      if ($this->id == 0)
      {
         $this->msgCenter->addDebug("Cannot display social event note. No ID set.");
         return false;
      }
      
      if ($this->guest_id == 0)
      {
         $this->getNoteData();
      }
      
      $formattedDate = " on " . date("D F jS g:i A", $this->timestamp);
      $noteContainer = $this->renderer->createDivTag("", array('class'=>'noteContainer'));
      $guestSpan = $this->renderer->createSpanTag($this->speaker, array('class'=>'noteSpeaker'));
      $noteDiv = $this->renderer->createDivTag($this->note, array('class'=>'noteDiv'));
      $dateSpan = $this->renderer->createSpanTag($formattedDate, array('class'=>'noteDate'));
      $noteContainer->addContents($guestSpan);
      $noteContainer->addContents($dateSpan);
      $noteContainer->addContents($noteDiv);
      return $noteContainer;
   }
   
   
   function renderAllNotes()
   {
      if ($this->event_id == 0)
      {
         $this->msgCenter->addDebug("Cannot get social event notes. No event ID is set.");
         return false;
      }
      
      if (!$this->notes)
      {
         $this->getAllNotes();
      }
      
      $html = array();
      $this->addFormProp("timestamp");
      $this->addFormProp("speaker");
      foreach ($this->notes as $note)
      {
         $this->importFromHash($note);
         $html[] = $this->renderNote();
      }
      
      return $html;
   }
}
?>
