<?php
/**
gcCOGCommitments - a class for modeling the commitments that users have made to
attend an event for a given proposed date.
**/

require_once("lib/classes/Form.inc");
class gcCOGCommitments extends gcForm implements Application
{
   public $event_id = 0;
   public $guestsIDs = "";
   public $guest_id = 0;
   public $proposedDates = array();
   public $commitments = array();
   public $indexedCommitments = array();
   public $mode = "display";
   
   function gcCOGCommitments($event_id=0, $guestsIDs="")
   {
      $this->init();
      $this->setFormTitle("");
      $this->event_id = $event_id;
      $this->guestsIDs = $guestsIDs;
      $this->setIDValidation("The event you are trying to view commitments for is not valid.", "event_id");
      
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
   
   
   function getAllCommitments()
   {
      $guests = " in (" . join(",", $this->guestsIDs) . ")";
      $query = $this->db->getSelectQuery(array("gcCOGCommitments", "gcCOGProposedDates"));
      $query->addColumn("DATE_FORMAT(gcCOGProposedDates.proposedDate, '%M %d %Y %H:%i %p') as date", " ");
      $query->addWhereClause("event_id", $this->event_id);
      $query->addInnerJoin("guest_id", $guests, "");
      $query->addInnerJoin("gcCOGCommitments.proposedDate_id", "gcCOGProposedDates.id");
      $query->setOrderByClauses("gcCOGProposedDates.proposedDate ASC");
      
      $result = $this->db->runQuery($query);
      $this->commitments = $this->db->getHash($result);
      foreach ($this->commitments as $row)
      {
         $this->indexedCommitments[$row['guest_id']][] = $row['proposedDate_id'];
      }
      return $this->commitments;
   }
   
   
   function determineMostPopularDateID()
   {
      if (!$this->indexedCommitments)
      {
         $this->getAllCommitments();
      }
      $dates = array();
      foreach ($this->indexedCommitments as $guestID => $commitmentDates)
      {
         foreach ($commitmentDates as $dateID)
         {
            $dates[$dateID][] = $guestID;
         }
      }
      
      $maxCommitments = 0;
      $mostPopulareDateID = "";
      foreach ($dates as $dateID => $guestIDList)
      {
         $numCommittedToThisDate = count($guestIDList);
         if ($numCommittedToThisDate > $maxCommitments)
         {
            $maxCommitments = $numCommittedToThisDate;
            $mostPopulareDateID = $dateID;
         }
      }
      return $mostPopulareDateID;
   }
   
   
   function validateEventAndGuestID()
   {
      $eventOK = $this->validateID("event_id");
      
      $this->setIDValidation("There is a guest with an invalid ID in the invitations list.", "event_id");
      $guestOK = true;
      
      foreach ($this->$guestsIDs as $guest_id)
      {
         $this->guest_id = $guest_id;
         if (!$this->validateID("guest_id"))
         {
            $guestOK = false;
            break;
         }
      }
      $this->guest_id = 0;
      
      if ($guestOK && $eventOK)
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   function run()
   {
      if (!$this->validateEventAndGuestID())
      {
         return false;
      }
      
      
      switch ($this->determineAction("event_id"))
      {
         case "entering":
            
         break;
      
         case "updating":
            
         break;
      
         case "creating":
            
         break;
      
         case "deleting":
            
         break;
      
         case "viewing":
            
         break;
      }
   }
   
   
   function clearPreviousCommitments()
   {
      // delete all previous commitments for this guest at this event.
      $where = array();
      $where['event_id'] = $this->event_id;
      $where['guest_id'] = $this->guest_id;
      $delQuery = $this->db->getDeleteQuery("gcCOGCommitments", $where);
      $delOK = $this->db->runQuery($delQuery);
      return $delOK;
   }
   
   
   function saveCommitments()
   {
      // the id's of the event and the user making the commitment (guest) are
      // passed in when this object is instantiated.
      
      // delete all previous commitments for this user and event.
      $delOK = $this->clearPreviousCommitments();
      
      if (!$delOK)
      {
         // error msgs will already be recorded if the delete fails.
         $this->msgCenter->addError("Could not update your event commitment data. Please try again later.");
         return false;
      }
      
      // set up data for multiple insert.
      $data = array();
      
      // loop through POST
      foreach ($_POST as $inputName => $value)
      {
         // if post var name starts with "guest_date_" it's a commitment
         if (strpos($inputName, "guest_date_") !== 0)
         {
            continue;
         }
         
         // add commitment data to a multiple insert query.
         $data[] = array('event_id'=>$this->event_id, 'guest_id'=>$this->guest_id, 'proposedDate_id'=>$_POST[$inputName]);
       
      }  
      
      if ($data)
      {
         // get a multiple insert query.
         $insQuery = $this->db->getMultipleInsertQuery("gcCOGCommitments", $data);
         
        // run the multiple insert query
        $result = $this->db->runQuery($insQuery);
      }
      else
      {
         // no insert query run, and no error condition. This block is only 
         // executed if the user updates the chart with NO dates checked.
         $result = true;
      }
      
     
     if ($result)
     {
        $this->msgCenter->addAlert("Thanks! The dates you can make it on have been recorded. Can't wait to see you.");
        return true;
     }
     else
     {
        $this->msgCenter->addError("Sorry, but your dates could not be recorded at this time. Please try again later.");
        return false;
     }
   }
   
   
   function render()
   {
      if (!$this->validateID("event_id"))
      {
         return false;
      }
      $this->getAllCommitments();
   }
}
?>
