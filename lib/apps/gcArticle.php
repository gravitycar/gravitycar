<?php
/**
gcArticle - a class for creating, editing and display articles of any length.
**/

require_once("lib/classes/Form.inc");
require_once("lib/apps/gcComment.php");
class gcArticle extends gcForm implements Application
{
   public $id = false;
   public $dateCreated = "";
   public $user_id = false;
   public $articleTitle = "";
   public $articleText = "";
   protected $mode = "text";
   private $commentsAllowed = false;
   private $isBlogEntry = 0;
   
   function gcArticle()
   {
      $this->init();
      //$this->db->setTestMode(true);
      //$this->db->setDebug(true);
      $this->page->addJavascriptFile("js/jquery.js");
      $this->page->addJavascriptFile("js/comment.js");
      $this->allowTags("<a><p><div><img><b><br><i><em><table><tr><td><th><strong><span><ul><ol><li>");
      $this->setFormProps("title", "id", "user_id", "dateCreated", "articleTitle", "isBlogEntry", "articleText");
      $this->setFormTitle("Create a new Article");
      
      $this->setInputAttr("title", "type", "heading");
      
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("dateCreated", "type", "hidden");
      $this->setInputAttr("user_id", "type", "hidden");
      $this->setInputAttr("articleTitle", "type", "text");
      $this->setInputAttr("articleTitle", "size", "50");
      $this->setInputParams("articleTitle", "label", "Title");
      $this->setInputAttr("isBlogEntry", "type", "radio");
      $this->setInputParams("isBlogEntry", "label", "Blog");
      $this->setInputParams("isBlogEntry", "options", array("1"=>"Yes", "0"=>"No"));
      
      $this->setInputParams("commentsAllowed", "label", "Comments");
      $this->setInputAttr("commentsAllowed", "type", "radio");
      $this->setInputParams("commentsAllowed", "options", array("1"=>"Yes", "0"=>"No"));
      
      $this->setInputAttr("articleText", "type", "textarea");
      $this->setInputAttr("articleText", "cols", "90");
      $this->setInputAttr("articleText", "rows", "30");
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setControlButtons("submitButton", "cancelButton");
      $this->setValidationRule("articleTitle", "notEmpty");
      $this->setValidationRule("articleText", "notEmpty");
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
            $this->auth->setReqPermWeight(GC_AUTH_WRITER);
         break;
      }
   }
   
   
   /**
   :Function: getAllArticles()
   
   :Description:
   Returns a mysqli result object of all the articles, ordered by dateCreated.
   
   :Parameters:
   
   :Return Value:
   mysqli_result.
   
   :Notes:
   **/
   function getAllArticles()
   {
      $result = $this->db->runSelectQuery("gcArticles", array("id", "articleTitle"));
      
      if (!$result)
      {
         $this->msgCenter->addError("Could not query articles. Please try again later.");
         return false;
      }
      return $result;
   }
   
   /**
   :Function: getArticleData()
   
   :Description:
   Checks for a reasonable ID being passed in, and then queries the db for the
   article with that passed in id. Imports the resulting data into this 
   instance. Returns a hash of article data.
   
   :Parameters:
   int $id - the row ID for this article.
   
   :Return Value:
   hash - the data for the passed in article.
   
   :Notes:
   **/
   function getArticleData($articleID=false)
   {
      $articleID = $this->id ? $this->id : $articleID;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $articleID);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $query = $this->db->getSelectQuery("gcArticles", "*");
      $query->addWhereClause("id", $articleID);
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
            $this->msgCenter->addError("The article you are looking for does not exist.");
         }
      }
      else
      {
         $this->msgCenter->addError("The article's data could not be retrieved.");
         $this->msgCenter->addDebug($query->queryToString());
      }
      
      $this->importFromHash($data);
      return $data;
   }
   
   
   function getAllComments()
   {
      $tables = array("gcUsers", "gcComments");
      $query = $this->db->getSelectQuery($tables); 
      $query->setColumns(array());
      $query->addColumns(array("concat(firstName, ' ', lastName) as userName"), " ");
      $query->addColumns(array("commentTitle", "commentText", "dateCreated"), "gcComments");
      $query->addWhereClause("article_id", $this->id, "=", "gcComments");
      $query->addInnerJoin("gcUsers.id", "gcComments.user_id");
      $query->addOrderByClause("gcComments.dateCreated ASC");
      $result = $this->db->runQuery($query);
      return $result;
   }
   
   /**
   :Function: allowComments()
   
   :Description:
   Sets the commentsAllowed property to the passed in boolean state. Comments
   should be allowed for blog posts, and not allowed for other kinds of 
   articles.
   
   :Parameters:
   boolean $state - true to allow and show comments, false otherwise.
   
   :Return Value:
   none.
   
   :Notes:
   **/
   function allowComments($state=true)
   {
      $this->commentsAllowed = (bool)$state;
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
      switch ($this->determineAction())
      {
         case "entering":
            $this->setFormTitle("Create a new Article");
            $this->setInputAttr("user_id", "value", $this->auth->getUserID());
            $this->setInputAttr("dateCreated", "value", date("Y-m-d H:i:s"));
         break;
      
         case "viewing":
            $this->setFormTitle("Update Article");
            $this->setIDValidation("The ID of the article you are trying to view is not valid.");
            $this->getArticleData($_GET['id']);
            $this->addControlButton("deleteButton");
         break;
      
         case "creating":
            $this->setFormTitle("Update Article");
            $this->importFromPost();
            $this->save();
         break;
      
         case "deleting":
            $this->setFormTitle("Article has been deleted");
            $this->setIDValidation("The ID of the article you are trying to delete is not valid.");
            $this->delete();
         break;
      
         case "updating":
            $this->setFormTitle("Article was Updated");
            $this->setIDValidation("The ID of the article you are trying to modify is not valid.");
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
   saves an article by either creating it or updating it, depending on the
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
         $this->msgCenter->addError("Could not save: validation failed.");
         return false;
      }
      
      $this->addSkipProp("id"); // NEVER update row ID!
      $data = $this->exportToHash();
      if (empty($_POST['id']))
      {
         // creating a new article.
         $query = $this->db->getQuery("insert", "gcArticles", $data);
         $confirmMsg = "New Article Created Successfully";
         $needRowID = true;
      }
      else
      {
         // updating an existing article.
         $where = array("id" => $this->id);
         $query = $this->db->getQuery("update", "gcArticles", $data, $where);
         $confirmMsg = "Article Updated Successfully";
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
   
   
   /**
   :Function: delete()
   
   :Description:
   Deletes an article from the database.
   
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
      $query = $this->db->getQuery("delete", "gcArticles", "", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Article Deleted Successfully");
      }
      return $deleteOK;
   }
   
   
   function getIsBlogEntry()
   {
      return $this->isBlogEntry;
   }
   
   
   function setIsBlogEntry($state)
   {
      if ($state === "0")
      {
         $state = false;
      }
      $this->isBlogEntry = (bool)$state;
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
   
   
   /**
   :Function: render()
   
   :Description:
   Creates the object heirarchy necessary to render this object as HTML. 
   Articles can be rendered as forms or as plain
   text. The mode of this object instance will dictate how to render this
   instance.
   
   :Parameters:
   string $mode - the render mode. Optional, default is "text".
   
   :Return Value:
   array - an array of HTML objects to render this object instance.
   
   :Notes:
   **/
   function render($mode="")
   {
      if ($mode !== "")
      {
         $this->setMode($mode);
      }
      
      switch ($this->getMode())
      {
         case "form":
            $this->htmlObjects[] = $this->renderAsForm();
         break;
      
         case "text":
            $this->htmlObjects[] = $this->renderAsText();
         break;
         
         case "list":
            $this->htmlObjects[] = $this->renderAsList();
         break;
      }
      
      return $this->htmlObjects;
   }
   
   
   function &renderAsList()
   {
      $result = $this->getAllArticles();
      if ($result)
      {
         $this->htmlObjects[] = $this->buildEditList(&$result, "editArticle.php", "id", "articleTitle");
      }
      return $this->htmlObjects;
   }
 
   
   function &renderAsForm()
   {
      return $this->buildForm();
   }
   
   
   function &renderAsText()
   {
      $this->page->setTitle($this->articleTitle);
      $this->page->setPageTitle($this->articleTitle);
      $attr = array("class"=>"articleText");
      $this->articleText = html_entity_decode($this->articleText);
      $textDiv = $this->renderer->createDivTag($this->articleText, $attr);
      if ($this->commentsAllowed)
      {
         $textDiv = $this->renderComments(&$textDiv);
      }
      return $textDiv;
   }
   
   
   
   function trimArticleLength()
   {
      $words = explode(" ", $this->articleText);
      $newText = array();
      $wc = count($words);
      $hardLimit = 46;
      $limit = $wc < $hardLimit ? $wc : $hardLimit;
      for ($w = 0; $w < $limit; $w++)
      {
         $newText[] = $words[$w];
      }
      
      if ($w >= $hardLimit)
      {
         $newText[] = " (click for more ...)";
      }
      
      $this->articleText = implode(" ", $newText);
   }
   
   function &renderAsBlog()
   {
      $attr = array("class"=>"blogEntry");
      $this->articleText = html_entity_decode($this->articleText);
      $this->articleTitle = html_entity_decode($this->articleTitle);
      $titleTag = $this->renderer->createH3Tag($this->articleTitle);
      $dateTag = $this->renderer->createDivTag($this->formatDate($this->dateCreated), array("class"=>"blogEntryDate"));
      $textTag = $this->renderer->createDivTag($this->articleText, array("class"=>"blogEntryText"));
      $linkAttr = array("href"=>"blog.php?id=" . $this->id);
      if (!$this->commentsAllowed)
      {
         $commentLink = $this->renderer->createATag("Comments", $linkAttr);
         $commentDiv = $this->renderer->createDivTag($commentLink, array("class"=>"commentLinkContainer"));
      }
      else
      {
         $commentDiv = $this->renderer->createDivTag();
      }
      $blogContainer = $this->renderer->createDivTag($titleTag, $attr);
      $blogContainer->addContents($dateTag);
      $blogContainer->addContents($textTag);
      $blogContainer->addContents($commentDiv);
      if ($this->commentsAllowed)
      {
         $blogContainer =& $this->renderComments(&$blogContainer);
      }
      return $blogContainer;
   }
   
   
   function &renderComments(&$textDiv)
   {
      $comment = new gcComment();
      $comment->setMode("form");
      if ($this->auth->getAuthOK())
      {
         $comment->set("dateCreated", date("Y-m-d H:i:s"));
         $comment->set("user_id", $this->auth->getUserID());
         $comment->set("article_id", $this->id);
         $textDiv->addContents($comment->render());
         $comment->form->setAttribute("action", "recordComment.php");
         $comment->form->setAttribute("id", "commentForm");
         $comment->form->setAttribute("onSubmit", "function() {alert('Help!'); return false;}");
      }
      else
      {
         $comment->setSkipProps($comment->getFormProps());
         $comment->removeSkipProp("title", "cancelButton");
         $comment->addFormProp("login");
         $comment->setInputAttr("login", "type", "button");
         $comment->setInputAttr("login", "value", "Log In");
         $comment->setInputAttr("login", "onClick", "window.location='/login.php'");
         $comment->controlButtons = array();
         $comment->setControlButtons("login", "cancelButton");
         $comment->setFormTitle("You must log in to leave your own comment");
         $textDiv->addContents($comment->render());
      }
      
      $commentsResult = $this->getAllComments();
   
      while($commentData = $this->db->getRow($commentsResult))
      {
         $thisComment = new gcComment();
         $thisComment->addFormProp("userName");
         $thisComment->importFromHash($commentData);
         $div =& $thisComment->renderAsText();
         array_unshift($comment->form->contents, $div);
      }
      
      return $textDiv;
   }
}
?>
