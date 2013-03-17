<?php
/**
gcComment - a class for creating and managing user comments on articles.
**/

require_once("lib/classes/Form.inc");
require_once("lib/classes/emailer.inc");
class gcComment extends gcForm implements Application
{
   public $id = false;
   public $dateCreated = "";
   public $user_id = false;
   public $article_id = false;
   public $moderated = false;
   public $commentTitle = "";
   public $commentText = "";
   private $mode = "text";
   public $userName = "noname";
   
   function gcComment()
   {
      $this->init();
      //$this->db->setTestMode(true);
      //$this->db->setDebug(true);
      $this->allowTags("<b><i><em><strong><p><br>");
      $this->setFormProps("title", "id", "user_id", "article_id", "dateCreated", "commentTitle", "commentText");
      $this->setFormTitle("Leave a comment");
      $this->setInputAttr("title", "type", "heading");
      
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("user_id", "type", "hidden");
      $this->setInputAttr("article_id", "type", "hidden");
      $this->setInputAttr("dateCreated", "type", "hidden");
      $this->setInputAttr("moderated", "type", "hidden");
      $this->setInputAttr("commentTitle", "type", "text");
      $this->setInputAttr("commentTitle", "size", "50");
      $this->setInputParams("commentTitle", "label", "Subject");
      $this->setInputAttr("commentText", "type", "textarea");
      $this->setInputAttr("commentText", "cols", "60");
      $this->setInputAttr("commentText", "rows", "6");
      $this->setInputAttr("submitButton", "type", "button");
      $this->setInputAttr("submitButton", "value", "Send");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setControlButtons("submitButton", "cancelButton");
      $this->setValidationRule("commentTitle", "notEmpty");
      $this->setValidationRule("commentText", "notEmpty");
      $this->setValidationRule("article_id", "notEmpty");
      $this->setValidationRule("article_id", "numeric");
      $this->setValidationRule("user_id", "numeric");
   }
   
   
   function determineRequiredPermission()
   {
      switch ($this->getMode())
      {
         case "text":
            $this->auth->setReqPermWeight(GC_AUTH_ALL);
         break;
      
         case "form":
         case "list":
         case "moderate":
            $this->auth->setReqPermWeight(GC_AUTH_WRITER);
         break;
      }
   }
   
   
   function getAllComments()
   {
      $result = $this->db->runSelectQuery("gcComments", array("id", "commentTitle"));
      
      if (!$result)
      {
         $this->msgCenter->addError("Could not query comments. Please try again later.");
      }
      return $result;
   }
   
   
   function getComment($commentID=false)
   {
      $commentID = $this->id ? $this->id : $commentID;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $commentID);
      
      if (!$validID)
      {
         // fail silently
         return false;
      }
      
      $query = $this->db->getSelectQuery("gcComments", "*");
      $query->addWhereClause("id", $commentID);
      $result = $this->db->runQuery($query);
      $data = array();
      
      if ($result)
      {
         if ($this->db->getNumRecords($result) == 1)
         {
            $data = $this->db->getRow($result);
         }
         else
         {
            $this->msgCenter->addError("The comment you are looking for does not exist.");
         }
      }
      else
      {
         $this->msgCenter->addError("The comment could not be retrieved.");
         $this->msgCenter->addDebug($query->queryToString());
      }
      
      $this->importFromHash($data);
      return $data;
   }
   
   
   function sendEmail()
   {
      $subject = "New comment left by " . $this->auth->getUser();
      $message = "Comment follows:\n\n" . $this->commentTitle . "\n\n" . $this->commentText;
      $emailer = new gcEmailer($subject, $message);
      return $emailer->msgSentOK();
   }
   
   
   function run()
   {
      switch($this->determineAction())
      {
         case "entering":
            $this->setFormTitle("Create a new Comment");
            $this->setInputAttr("user_id", "value", $this->auth->getUserID());
            $this->setInputAttr("dateCreated", "value", date("Y-m-d H:i:s"));
         break;
      
         case "viewing":
            $this->setFormTitle("Update Article");
            $this->setIDValidation("The ID of the comment you are trying to view is not valid.");
            $this->getCommenttData($_GET['id']);
            $this->addControlButton("deleteButton");
         break;
      
         case "creating":
            $this->setFormTitle("Update Comment");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setFormTitle("Comment has been deleted");
            $this->setIDValidation("The ID of the comment you are trying to delete is not valid.");
            $this->delete();
         break;
      
         case "updating":
            $this->setFormTitle("Comment was Updated");
            $this->setIDValidation("The ID of the comment you are trying to modify is not valid.");
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
         $this->msgCenter->addError("Could not save: validation failed.");
         return false;
      }
      
      $this->addSkipProp("id"); // NEVER update row ID!
      $data = $this->exportToHash();
      if (empty($_POST['id']))
      {
         // creating a new article.
         $query = $this->db->getQuery("insert", "gcComments", $data);
         $confirmMsg = "New Comment Created Successfully";
         $needRowID = true;
         $this->sendEmail();
      }
      else
      {
         $where = array("id" => $this->id);
         $query = $this->db->getQuery("update", "gcComments", $data, $where);
         $confirmMsg = "Comment Updated Successfully";
         $needRowID = false;
      }
      
      $queryOK = $this->db->runQuery($query);
      // remove the skip prop for ID, we will want to include it in the form.
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
      
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $where = array('id'=>$id);
      $query = $this->db->getQuery("delete", "gcComments", "", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Article Deleted Successfully");
      }
      return $deleteOK;
   }
   
   
   /**
   :Function: setMode()
   
   :Description:
   Sets the mode for this object. The mode determines how the object should
   be rendered. Currently, the options are:
      "form" - render the object as a form for editing or creation.
      "text" - render the object as plain text for reading only.
   
   The default is "text".
   
   :Parameters:
   string $mode - the render mode. Optional, default is "text".
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setMode($mode)
   {
      $this->mode = $mode;
      $this->determineRequiredPermission();
   }
   
   
   /**
   :Function: getMode()
   
   :Description:
   Returns the mode of this object. The mode determines how the object should
   be rendered. Currently, the options are:
      "form" - render the object as a form for editing or creation.
      "text" - render the object as plain text for reading only.
   
   The default is "text".
   
   :Parameters:
   None.
   
   :Return Value:
   string - the render mode. Optional, default is "text".
   
   :Notes:
   **/
   function getMode()
   {
      return $this->mode;
   }
   
   
   function formatDate($dateTimeString)
   {
      list($date, $time) = split(" ", $dateTimeString);
      list($year, $month, $day) = split("-", $date);
      list($hour, $min, $sec) = split(":", $time);
      $timestamp = mktime((int)$hour, (int)$min, (int)$sec, (int)$month, (int)$day, (int)$year);
      $formattedDate = date("M jS Y g:i:sA", $timestamp);
      return $formattedDate;
   }
   
   
   function render($mode="")
   {
      if ($mode !== "")
      {
         $this->setMode($mode);
      }
      
      $this->commentsContainer = $this->renderer->createDivTag(false, array("id"=>"allCommentsContainer"));
      switch ($this->getMode())
      {
         case "form":
            $this->commentsContainer->addContents($this->renderAsForm());
         break;
      
         case "text":
            $this->commentsContainer->addContents($this->renderAsText());
         break;
         
         case "list":
            $this->commentsContainer->addContents($this->renderAsList());
         break;
         
         case "raw":
            //$this->commentsContainer->addContents($this->renderAsText());
            $comment = $this->renderAsText();
            $this->htmlObjects = array($comment);
            return $this->htmlObjects;
         break;
      }
      
      $this->htmlObjects[] =& $this->commentsContainer; 
      return $this->htmlObjects;
   }
   
   
   function &renderAsList()
   {
      $result = $this->getAllArticles();
      $this->htmlObjects[] = $this->buildEditList(&$result, "editArticle.php", "id", "articleTitle");
      return $this->htmlObjects;
   }
 
   
   function &renderAsForm()
   {
      return $this->buildForm();
   }
   
   
   function &renderAsText()
   {
      $attr = array("class"=>"commentText");
      $this->commentText = html_entity_decode($this->commentText);
      $this->commentTitle = html_entity_decode($this->commentTitle);
      $title = $this->renderer->createH3Tag($this->commentTitle);
      $userAndDate = "Written by " . $this->userName . " on " . $this->formatDate($this->dateCreated);
      $userDiv = $this->renderer->createDivTag($userAndDate, array("class"=>"commentWriter"));
      $commentDiv = $this->renderer->createDivTag($title, $attr);
      $commentDiv->addContents($userDiv);
      $commentDiv->addContents($this->commentText);
      return $commentDiv;
   }
}
?>
