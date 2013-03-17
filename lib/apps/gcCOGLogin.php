<?php
/**

gcCOGLogin - a class for users to log in directly to the event they want to
see in the chart of goodness (COG).


This class combine the standard login form with a list of current events
that users may choose from to determine which event they want to attend.
**/
require_once("lib/apps/gcLogin.php");
require_once("lib/apps/gcCOGSocialEvent.php");

class gcCOGLogin extends gcForm implements Application
{
   public $loginObj = false;
   public $eventObj = false;
   public $event_id = 0;
   
   
   function gcCOGLogin()
   {
      $this->init();
      $this->page->addStylesheet("cog.css");
      $this->setIDValidation("The event you are trying to attend is not valid.", "event_id");
   }
   
   
   function run()
   {
      $this->loginObj = new gcLogin();
      $this->eventObj = new gcCOGSocialEvent();
      
      switch ($this->determineAction("user"))
      {
         case "entering":
            if (!$this->auth->getAuthOK())
            {
               $this->loginObj->run();
            }
            $this->eventObj->getAllEvents();
         break;
      
         case "viewing":
            
         break;
      
         case "creating":
            
         break;
      
         case "updating":
            if (!$this->auth->getAuthOK())
            {
               $this->loginObj->run();
            }
            $this->event_id = $_POST['event_id'];
            $this->redirect();
         break;
      
         case "deleting":
            
         break;
      }
   }
   
   
   function redirect()
   {
      if ($this->auth->getAuthOK())
      {
         if ($this->validateID("event_id"))
         {
            header("location: events.php?event_id=" . $this->event_id);
         }
         else
         {
            $this->msgCenter->deleteError(0);
            $this->msgCenter->deleteError(1);
            $this->msgCenter->deleteError(2);
            $this->msgCenter->addError("You must select an event to attend.");
         }
      }
      else
      {
         $this->msgCenter->addError("You must log in before you can select an event.");
      }
   }
   
   
   function render()
   {
      $container = $this->renderer->createDivTag(false, array('id'=>'eventOptionsContainer'));
      
      $this->eventObj->getAllEvents();
      $events = array();
      
      foreach ($this->eventObj->events as $eventID=>$event)
      {
         $events[$eventID] = $event['eventName'];
      }
      
      
      $radioButtons = $this->renderer->createRadioButtonCollection($events, "", array('name'=>'event_id'));
      
      foreach ($radioButtons as $button)
      {
         $container->addContents($button);
      }
      
      $this->loginObj->setFormTitle("Log into your event.");
      
      $loggedIn = $this->auth->getAuthOK();
      if ($loggedIn)
      {
         $this->loginObj->setInputAttr("user", "type", "plainText");
         $this->loginObj->setInputAttr("user", "value", $this->auth->getUser());
         $this->loginObj->setInputAttr("pass", "type", "plainText");
         $this->loginObj->setInputAttr("pass", "value", "********");
      }
      
      $loginForm = $this->loginObj->buildForm();
      $loginForm->contents[0]->rows[0]->cells[0]->setAttribute("colSpan", "3");
      if (!$loggedIn)
      {
         $loginForm->contents[0]->rows[3]->contents[0]->setAttribute("colSpan", "3");
      }
      else
      {
         $loginForm->contents[0]->rows[1]->setAttribute("vAlign", "middle");
      }
      $cell = $loginForm->contents[0]->rows[1]->addCell();
      $cell->setAttribute("rowspan", "2");
      $cell->addContents($container);
      $this->htmlObjects[] = $loginForm;
      return $this->htmlObjects;
   }
}
?>
