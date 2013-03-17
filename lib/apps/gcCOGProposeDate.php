<?php
/**
gcCOGProposeDate - a class for proposing one or more dates for an event and 
modifying which dates are proposed for an event.

This class needs to manage potentially many dates for a single event. When
new dates are prosed for an event, any previously proposed dates are dropped.
Then the new dates are saved.
**/

require_once("lib/classes/Form.inc");
class gcCOGProposeDate extends gcForm implements Application
{
   public $event_id = 0;
   public $dates = array();
   public $mode = "form";
   
   
   function gcCOGProposeDate($event_id=0)
   {
      $this->init();
      $this->page->addJavascriptFile("js/jquery.js");
      $this->page->addStylesheet("gcCOGProposeDates.css");
      $this->event_id = $event_id;
      $this->page->addJavascriptFile("js/datePicker.js");
      $this->page->addJavascriptFile("js/gcCOGProposeDates.js");
      $this->setFormTitle("Propose Dates");
      $this->setFormProps("title", "hours", "calDiv", "datesDiv", "event_id");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("event_id", "type", "hidden");
      $this->setInputAttr("calDiv", "type", "emptyContainer");
      $this->setInputAttr("datesDiv", "type", "emptyContainer");
      $this->setInputParams("hours", "options", $this->getHourOptions());
      $this->setInputAttr("hours", "type", "select");
      $this->setInputAttr("hours", "value", date("H"));
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setControlButtons("submitButton", "cancelButton");
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
   
   
   function getAllDatesAsJSON()
   {
      if (!$this->dates)
      {
         $this->getAllDates();
      }
      else
      {
      }
      
      return "var proposedDates = " . json_encode($this->dates) . ";";
   }
   
   
   function getAllDates()
   {
      $query = $this->db->getSelectQuery("gcCOGProposedDates");
      $query->addColumn("id", "gcCOGProposedDates");
      $query->addColumn("unix_timestamp(proposedDate) as timestamp", " ");
      $query->addWhereClause("event_id", $this->event_id);
      $query->addWhereClause("proposedDate", date("Y-m-d H:i:s"), ">");
      $query->addOrderByClause("proposedDate", "ASC");
      $result = $this->db->runQuery($query);
      //$this->dates = $this->db->getHash($result);
      $rows = $this->db->getHash($result);
      foreach ($rows as $row)
      {
         $this->dates[$row['id']] = $row['timestamp'];
      }
      
      return $result;
   }
   
   
   function getHourOptions()
   {
      $hours = array();
      $ampm = "AM";
      for ($h = 0; $h < 24; $h++)
      {
         $formattedHour = $h;
         if ($h == 0)
         {
            $formattedHour = 12;
         }
         
         if ($h > 11)
         {
            $ampm = "PM";
         }
         
         if ($h > 12)
         {
            $formattedHour = $h - 12;
         }
         $hours["$h"] = "$formattedHour:00 $ampm";
      }
      return $hours;
   }
   
   
   function validateEventID()
   {
      $this->validator = new gcValidator($this->validationRules);
      $this->validator->validateProperty('event_id', $this->event_id);
      
      if (!$this->validationPassed)
      {
         return false;
      }
      else
      {
         return true;
      }
   }
   
   
   function save()
   {
      $data = array();
      
      if (IsSet($_POST['deleteDates']))
      {
         $idsToDelete = join(", ", $_POST['deleteDates']);
         $deleteQuery = $this->db->getDeleteQuery("gcCOGProposedDates", true);
         $where = $deleteQuery->addWhereClause("id", "($idsToDelete)", " in ", "gcCOGProposedDates");
         $where->noQuoteValues();
         $deleteResult = $this->db->runQuery($deleteQuery);
         if ($deleteResult)
         {
            $this->msgCenter->addAlert(count($_POST['deleteDates']) . " proposed date(s) were deleted.");
         }
         else
         {
            $this->msgCenter->addError("Could not delete any proposed dates.");
         }
      }
      
      foreach ($_POST as $fieldName => $fieldValue)
      {
         if (strpos($fieldName, "timestamp_") === 0)
         {
            $timestamp = $fieldValue;
            $mysqlDate = date("Y-m-d H:i:s", $timestamp);
            $data[] = array('event_id'=>$this->event_id, 'proposedDate'=>$mysqlDate);
         }
      }
      
      if ($data)
      {
         $query = $this->db->getMultipleInsertQuery("gcCOGProposedDates", $data);
         $result = $this->db->runQuery($query);
      }
      else
      {
         $result = 0;
      }
      
      if ($result)
      {
         $this->msgCenter->addAlert("Proposed dates added successfully.");
         return true;
      }
      else if ($result === 0)
      {
         // no updates so no errors.
         return true;
      }
      else
      {
         // error in updates. Already registered in msgCenter by db.
         return false;
      }
   }
   
   
   function run()
   {
      /*
      Technically you should only ever be "viewing" an existing event or 
      updating an existing event. 
      */
      switch ($this->determineAction('event_id'))
      {
         case "entering":
            $this->setFormTitle("Propose Dates for This Event");
         break;
      
         case "viewing":
            $this->setIDValidation("The event you are trying to propose dates for is not valid.", "event_id");
            $this->setFormTitle("Propose Dates for This Event");
            $this->event_id = $_GET['event_id'];
         break;
      
         case "creating":
            $this->setFormTitle("Proposed Dates Have Been Saved");
         break;
      
         case "deleting":
            $this->setFormTitle("Dates have been deleted");
         break;
      
         case "updating":
            $this->setIDValidation("The event you are trying to propose dates for is not valid.", "event_id");
            $this->setFormTitle("Proposed Dates Have Been Saved");
            $this->importFromPost();
            $this->save();
         break;
      }
      
      // these calls are placed here and not in the constructor function because
      // they depend on event_id being assigned, either directly or via 
      // importFromPost().
      $this->renderer->createJavascriptTag($this->getAllDatesAsJSON(), false, $this->page->head);
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
            //$html =& $this->renderList();
            $html =& $this->renderForm();
         break;
         
         case "list":
            $html =& $this->renderList();
         break;
      }
      
      $this->htmlObjects[] =& $html;
      return $this->htmlObjects;
   }
   
   
   function &renderList()
   {
      if (!$this->dates)
      {
         $this->getAllDates();
      }
      
      $count = 0;
      $this->formProps = array();
      $this->setFormTitle("Delete Dates");
      $this->addFormProp("title");
      $this->setInputAttr("title", "type", "heading");
      foreach ($this->dates as $date)
      {
         $timestamp = $date['timestamp'];
         $id = $date['id'];
         
         $propName = "date_$count";
         $propValue = $id;
         $formattedDate = date("M j Y g:i A", $timestamp);
         
         $this->addFormProp($propName);
         $this->setInputAttr($propName, "type", "checkbox");
         $this->setInputAttr($propName, "desc", $formattedDate);
         
         $count++;
      }
      
      return $this->buildForm();
   }
   
   
   function &renderForm()
   {
      $this->addSkipProp("calDiv");
      $this->addSkipProp("datesDiv");
      $html =& $this->buildForm();
      
      $newContents = array();
      $table = $html->contents[1];
      for ($i = 0; $i < count($table->contents); $i++)
      {
         $newContents[] = $table->contents[$i];
         if ($i == 1)
         {
            $newRow = $this->renderer->createTRTag(false, false);
            //$newRow = $table->addRow();
            $newCell = $this->renderer->createTDTag(false, array("colspan"=>"2"), &$newRow);
            $newCell->addContents($this->buildInput("calDiv", ""));
            $newCell->addContents($this->buildInput("datesDiv", ""));
            $newContents[] =& $newRow;
         }
      }
      $table->contents = $newContents;
      return $html;
   }
}
?>
