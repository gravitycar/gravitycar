<?php
/**
gcBlog - a class for creating a blog-like interface.

This class is for displaying articles that are designated as blog entries,
and their comments, if any. It will have several modes:

"all" - display all blog entries.
"latest5" - display the five most recent entries, with an optional offset. 
   Default.
"single" - dislay one blog entry with its comments


**/
require_once("lib/classes/Form.inc");
require_once("lib/apps/gcArticle.php");
class gcBlog extends gcForm implements Application
{
   private $entries = array();
   private $comments = array();
   private $title = "Mike's Blog";
   protected $mode = "latest5";
   private $limit = 4;
   private $offset = 0;
   private $pageNum = 0;
   public $trimArticleLength = false;
   
   function gcBlog()
   {
      $this->init();
      //$this->db->setTestMode(true);
      
      $this->page->addJavascriptFile("js/jquery.js");
      $this->page->addJavascriptFile("js/comment.js");
      if (IsSet($_GET['pageNum']))
      {
         $pageNum = $_GET['pageNum'];
         $this->setIDValidation("Sorry, you have requested an invalid page.", "pageNum");
         $validator = new gcValidator($this->validationRules);
         $validPageNum = $validator->validateProperty('id', $pageNum);
         
         if ($validPageNum)
         {
            $this->pageNum = $pageNum;
         }
         else
         {
            $this->pageNum = 0;
         }
      }
      $this->offset = $this->limit * $this->pageNum;
      
      if (IsSet($_GET['id']))
      {
         $this->setMode("single");
         $this->setIDValidation("Sorry, you have requested an invalid blog entry.", "id");
         $validator = new gcValidator($this->validationRules);
         $validID = $validator->validateProperty('id', $_GET['id']);
         if ($validID)
         {
            $this->id = $_GET['id'];
         }
         else
         {
            $this->id = 0;
         }
      }
   }
   
   
   function getTotalEntries()
   {
      $query =& $this->db->getSelectQuery("gcArticles");
      unset($query->columns["gcArticles"]);
      $query->setColumns("count(id) as total", " ");
      $query->addWhereClause("isBlogEntry", "1");
      $result = $this->db->runQuery($query);
      $data = $this->db->getRow($result);
      return $data['total'];
   }
   
   
   function getLatest5Entries($offset=0)
   {
      $query =& $this->db->getSelectQuery("gcArticles");
      $query->addWhereClause("isBlogEntry", "1");
      $query->addLimitClause($this->limit);
      $query->addOffsetClause($this->offset);
      $query->addOrderByClause("dateCreated DESC");
      $result = $this->db->runQuery($query);
      $data = $this->db->getHash($result);
      return $data;
   }
   
   
   function getOneEntry($id)
   {
      $result = $this->db->runSelectQuery("gcArticles", "*", array("id"=>$id));
      return $this->db->getHash($result);
   }
   
   
   function buildPageLinks()
   {
      $totalEntries = $this->getTotalEntries();
      $baseURL = $_SERVER['PHP_SELF'];
      
      $nextLinkNum = $this->pageNum + 1;
      $prevLinkNum = $this->pageNum - 1;
      
      $prevLinkNum = $prevLinkNum > 0 ? $prevLinkNum : 0;
      
      $nextURL = $baseURL . "?pageNum=" . $nextLinkNum;
      $prevURL = $baseURL . "?pageNum=" . $prevLinkNum;
      
      $nextText = $totalEntries <= $this->offset + $this->limit ? "" : "NEXT >>";
      $prevText = $prevLinkNum == 0  && $nextLinkNum == 1 ? "" : "<< PREV";
      
      $nextLink = $this->renderer->createATag($nextText, array("href"=>$nextURL));
      $nextDiv = $this->renderer->createDivTag($nextLink, array("id"=>"nextLink"));
      
      $prevLink = $this->renderer->createATag($prevText, array("href"=>$prevURL));
      $prevDiv = $this->renderer->createDivTag($prevLink, array("id"=>"prevLink"));
      
      $navContainer = $this->renderer->createDivTag(false, array("id"=>"nextPrevLinks"));
      $navContainer->addContents($prevDiv);
      $navContainer->addContents($nextDiv);
      return $navContainer;
   }
   
   
   function run()
   {
      switch ($this->mode)
      {
         case "latest5":
            $this->entries = $this->getLatest5Entries($this->offset);
         break;
      
         case "all":
            
         break;
      
         case "single":
            $this->entries = $this->getOneEntry($this->id);
         break;
      }
   }
   
   
   function render()
   {
      switch ($this->mode)
      {
         case "latest5":
            $html = $this->renderLatest5();
         break;
      
         case "all":
            
         break;
      
         case "single":
            $html = $this->renderSingle();
         break;
      }
      
      $pageTitle = $this->title;
      $this->page->setTitle($pageTitle);
      $this->page->setPageTitle($pageTitle);
      return $html;
   }
   
   
   function renderSingle()
   {
      // if there were any errors, show nothing.
      if ($this->msgCenter->hasErrors())
      {
         return $this->htmlObjects;;
      }
      $entry = new gcArticle();
      $entry->setMode("text");
      $entry->allowComments(true);
      $entry->importFromHash($this->entries[0]);
      
      if ($this->trimArticleLength)
      {
         $entry->trimArticleLength();
      }
      
      $this->htmlObjects[] = $entry->renderAsBlog();
      return $this->htmlObjects;
   }
   
   
   function renderLatest5()
   {
      foreach ($this->entries as $entryData)
      {
         $thisEntry = new gcArticle();
         $thisEntry->setMode("text");
         $thisEntry->allowComments(false);
         $thisEntry->importFromHash($entryData);
         $this->htmlObjects[] = $thisEntry->renderAsBlog();
      }
      $this->htmlObjects[] = $this->buildPageLinks();
      return $this->htmlObjects;
   }
   
   
   function trim($state=true)
   {
      $this->trimArticleLength = $state;
   }
}
?>
