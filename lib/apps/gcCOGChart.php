<?php
/**
gcCOGChart - the chart of goodness class. This chart allows users to see what
dates have been proposed for an event and who has committed to come to the 
event on which particular dates.
**/
require_once("lib/classes/Form.inc");
require_once("lib/apps/gcCOGSocialEvent.php");
require_once("lib/apps/gcCOGInvitation.php");
require_once("lib/apps/gcCOGProposeDate.php");
require_once("lib/apps/gcCOGCommitments.php");
require_once("lib/apps/gcCOGSocialEventNotes.php");
class gcCOGChart extends gcForm implements Application
{
   public $event_id = 0;
   public $guest_id = 0;
   public $dates = array();
   public $guests = array();
   public $commitments = array();
   public $mode = "chart";
   public $event = false;
   public $inviteObj = false;
   public $datesObj = false;
   public $commitmentObj = false;
   
   function gcCOGChart()
   {
      $this->init();
      $this->page->addStylesheet("cog.css");
      $this->setIDValidation("The event you are trying to attend is not valid.", "event_id");
      $this->locateEventID_OR_Redirect();
      $this->guest_id = $this->auth->getUserID();
   }
   
   
   function determineRequiredPermission()
   {
      switch ($this->getMode())
      {
         case "chart":
            $this->auth->setReqPermWeight(GC_AUTH_USER);
         break;
      
         case "form":
            $this->auth->setReqPermWeight(GC_AUTH_ADMIN);
         break;
      }
   }
   
      
   
   function formatProposedDate($timestamp)
   {
      // remember: this is a php timestamp, in seconds, not a javascript
      // timestamp (which would be in milliseconds).
      $formattedDate = date("D\nM j\ng:i A", $timestamp);
      $formattedDate = str_replace("\n", "<br>", $formattedDate);
      return $formattedDate;
   }
   
   
   function locateEventID_OR_Redirect()
   {
      if (IsSet($_GET['event_id']))
      {
         $this->event_id = $_GET['event_id'];
      }
      else if (IsSet($_POST['event_id']))
      {
         $this->event_id = $_POST['event_id'];
      }
      else
      {     
         if ($this->validateID("event_id"))
         {
            return true;
         }
         header("location: /eventLogin.php");
         exit();
         return false;
      }
   }
   
   
   function checkCommitment($guestID, $dateID)
   {
      if (IsSet($this->commitmentObj->indexedCommitments[$guestID]))
      {
         return in_array($dateID, $this->commitmentObj->indexedCommitments[$guestID]);
      }
      else
      {
         return false;
      }
   }
   
   
   function addMyEventsToMenu()
   {
      return false;
      $events = $this->event->getAllMyEvents($this->guest_id);
      foreach ($events as $id => $event)
      {
         $menu = "cog";
         $label = $event['eventName'];
         $url = "events.php?event_id=$id";
         
         $this->page->applications['gcTop']->addSubMenuLink($menu, $label, $url);
      }
   }
   
   
   function run()
   {
      $this->locateEventID_OR_Redirect();
      $eventOK = $this->validateID("event_id");
      
      if (!$eventOK)
      {
         $this->validationPassed = false;
         return false;
      }
      
      $this->event = new gcCOGSocialEvent($this->event_id);
      $this->event->getEventData();
      $this->page->setTitle("Event Organizer for " . $this->event->eventName);
      
      $this->inviteObj = new gcCOGInvitation($this->event_id);
      $this->inviteObj->getInvitedUsers();
      
      $this->datesObj = new gcCOGProposeDate($this->event_id);
      $this->datesObj->getAllDates();
      
      $this->notesObj = new gcCOGSocialEventNotes($this->event_id);
      
      $invitedGuestsIDs = $this->inviteObj->getInvitedUsersIDs();
      
      if (empty($this->guest_id))
      {
         $this->guest_id = $this->auth->getUserID();
      }
      
      $jsFiles = array("js/addOnLoad.js", "js/ieMenuFix.js", "js/jquery.js", "js/cog.js");
      $cssFiles = array("main.css", "nav.css", "cog.css");
      $this->page->setJavascriptFiles($jsFiles);
      $this->page->setStyleSheets($cssFiles);
      
      if (!in_array($this->guest_id, $invitedGuestsIDs))
      {
         $this->msgCenter->addError("Sorry, you must be invited to this event to attend it.");
         $this->validationPassed = false;
         return false;
      }
      
      $this->commitmentObj = new gcCOGCommitments($this->event_id, $invitedGuestsIDs);
      
      
      switch($this->determineAction("event_id"))
      {
         case "entering":
            $this->commitmentObj->getAllCommitments();
         break;
      
         case "viewing":
            $this->commitmentObj->getAllCommitments();
         break;
      
         case "creating":
            
         break;
      
         case "updating":
            $this->commitmentObj->set("guest_id", $this->guest_id);
            $this->save();
            $this->commitmentObj->getAllCommitments();
         break;
      
         case "deleting":
            
         break;
      }
      // this call must be done after save().
      $this->notesObj->getAllNotes();
   }
   
   
   function save()
   {
      // get and validate the the event id and guest id.
      $this->event_id = $_POST['event_id'];
      $this->guest_id = $_POST['guest_id'];
      
      $this->setIDValidation("The event you are trying to make commitments to attend is not valid.", "event_id");
      $this->setIDValidation("The guest trying to commit to these dates is not valid", "guest_id");
      
      $eventOK = $this->validateID("event_id");
      $guestOK = $this->validateID("guest_id");
      
      if (!$eventOK || !$guestOK)
      {
         return false;
      }
      
      $this->commitmentObj->saveCommitments();
      
      if (!empty($_POST['note']))
      {
         $this->notesObj->importFromPost();
         $this->notesObj->save();
      }
   }
   
   
   function render()
   {
      if (!$this->validationPassed)
      {
         return false;
      }
      
      // create form to contain table and checkboxes.
      $this->form =& $this->renderer->createFormTag();
      $this->form->setAttribute("id", "cogForm");
      $this->form->setAttribute("action", $this->form->getAttribute("action") . "?event_id=" . $this->event_id);
      $guestIDInput = $this->renderer->createHiddenInputTag($this->auth->getUserID(), array('id'=>'guest_id'), $this->form);
      $eventIDInput = $this->renderer->createHiddenInputTag($this->event_id, array('id'=>'event_id'), $this->form);
      
      // create wrapping div
      $this->container =& $this->renderer->createDivTag(false, array('id'=>'cogContainer', 'class'=>'tableDiv'), $this->form);
      
      $mostPopularID = $this->commitmentObj->determineMostPopularDateID();
      if ($mostPopularID)
      {
         $mostPopularTimestamp = $this->datesObj->dates[$mostPopularID];
         $formattedPopularDate = date("l F jS g:i A", $mostPopularTimestamp);
         $mostPopularDate = "Most Popular Meeting Time: $formattedPopularDate";
      }
      else
      {
         $mostPopularDate = "No Date is the most popular yet.";
      }
      $this->renderer->createH3Tag($mostPopularDate, array("class"=>"subTitle"), $this->container);
      
      // create dates table.
      $tableAttrs = array();
      $tableAttrs['id'] = "cogTable";
      $this->table =& $this->renderer->createTableTag(false, $tableAttrs, $this->container);
      
      // add header row - loop through each date formatted as mm/dd<br>hh:ii
      $thead =& $this->renderer->createTHeadTag(false, false, &$this->table);
      $headerRow =& $thead->addRow(array('id'=>'datesRow'));
      $emptyCell = $headerRow->addCell();
      $emptyCell->setAttribute("class", "empty");
      
      $dateCount = 1;
      foreach ($this->datesObj->dates as $dateID=>$timestamp)
      {
         $formattedDate = $this->formatProposedDate($timestamp);
         $dateCell =& $headerRow->addCell();
         $dateCell->addContents($formattedDate);
         $dateCell->setAttribute("class", "dateHeader");
         $dateCount++;
      }
      
      // loop through each invited user
      foreach ($this->inviteObj->invitedUsers as $user)
      {
         $row =& $this->table->addRow();
         $row->setAttribute("class", "inviteeRow");
         $nameCell =& $row->addCell();
         $nameCell->addContents($user['name']);
         $nameCell->setAttribute("class", "nameCell");
         
         // loop through each date
         reset($this->datesObj->dates);
         foreach ($this->datesObj->dates as $dateID=>$timestamp)
         {
            $dateCount = 1;
            
            // create a table cell for this date.
            $dateCell =& $row->addCell();
            $dateCell->setAttribute("class", "cannotMakeIt");
            if ($this->checkCommitment($user['id'], $dateID))
            {
               $dateCell->setAttribute("class", "canMakeIt");
            }
            
            // check to see if the current user is the logged in user.
            if ($this->auth->userOwnsRecord($user['id']))
            {
               // current user is logged in user: show checkbox.
               $attrs = array();
               $attrs['id'] = "guest_date_" . $user['id'] . "_" . $dateID;
               $attrs['value'] = $dateID;
               $checkbox = $this->renderer->createCheckboxTag($attrs, &$dateCell);
               if ($dateCell->getAttribute("class") == "canMakeIt")
               {
                  $checkbox->setAttribute("checked", true);
               }
            }
            else
            {
               // current user is not logged in user: show empty box.
               $space = "&nbsp;";// . $user['id'] . "_" . $dateID;
               $dateCell->addContents($space);
            }
            $dateCount++;
         }
      }
      
      $this->buildNextPrev();
      $this->buildNotes();
      $this->buildButton();
      
      
      return $this->form;
   }
   
   
   function buildNotes()
   {
      $notesDiv = $this->renderer->createDivTag("", array("id"=>"noteInputContainer"), $this->container);
      $this->renderer->createSpanTag("Enter your witty remarks here.", array("id"=>"notesLabel"), $notesDiv);
      $notesDiv->addContents($this->notesObj->buildInput("note", ""));
      $notesDiv->addContents($this->notesObj->renderAllNotes());
   }
   
   function buildButton()
   {
      $btnDiv = $this->renderer->createDivTag("", array("id"=>"submitContainer"), $this->container);
      $this->renderer->createSubmitButtonTag("Update", false, $btnDiv);
   }
   
   
   function buildNextPrev()
   {
      $cont = $this->renderer->createDivTag("", false, $this->container);
      $cont->setAttribute("id", "nextPrevCont");
      
      $prevLink = $this->renderer->createATag("<-Prev", false);
      $prevLink->setAttribute("href", "#");
      $prevLink->setAttribute("id", "prevLink");
      $prevLink->setAttribute("onMouseDown", "return showPrev();");
      $prevLink->setAttribute("onMouseUp", "return stopScrolling();");
      
      $nextLink = $this->renderer->createATag("Next->", false);
      $nextLink->setAttribute("href", "#");
      $nextLink->setAttribute("id", "nextLink");
      $nextLink->setAttribute("onMouseDown", "return showNext();");
      $nextLink->setAttribute("onMouseUp", "return stopScrolling();");
      
      $prevDiv = $this->renderer->createDivTag($prevLink, false, $cont);
      $prevDiv->setAttribute("id", "prevDiv");
      
      $nextDiv = $this->renderer->createDivTag($nextLink, false, $cont);
      $nextDiv->setAttribute("id", "nextDiv");
   }
   
}

?>
