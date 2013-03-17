<?php
/**
gcHome - the class for rendering the home page. The home page will need to 
instantiate and call some article objects and render them as well as other 
home page elements.

All of the home page elements should be rendered as just divs, to improve
the flexibility of the layout.
**/

require_once("lib/classes/Form.inc");
require_once("lib/apps/gcArticle.php");
require_once("lib/apps/gcBlog.php");
class gcHome extends gcApplication implements Application
{
   public $welcomeArticleID = 14;
   public $resumeArticleID = 15;
   public $latestBlogID = 10;
   public $exhortationID = 16;
   public $welcomeArticle = false;
   public $resumeArticle = false;
   public $blog = false;
   public $exhortation = false;
   
   function gcHome()
   {
      $this->init();
      $this->page->addStylesheet("home.css");
      $this->welcomeArticle = new gcArticle();
      $this->welcomeArticle->id = $this->welcomeArticleID;
      
      $this->resumeArticle = new gcArticle();
      $this->resumeArticle->id = $this->resumeArticleID;
      
      $this->blog = new gcArticle();
      $this->blog->id = $this->latestBlogID;
      
      $this->exhortation = new gcArticle();
      $this->exhortation->id = $this->exhortationID;
   }
   
   
   function run()
   {
      $this->welcomeArticle->setMode("text");
      $this->welcomeArticle->getArticleData();
      
      $this->resumeArticle->setMode("text");
      $this->resumeArticle->getArticleData();
      
      $this->blog->setMode("text");
      $this->blog->getArticleData();
      $this->blog->trimArticleLength();
      
      $this->exhortation->setMode("text");
      $this->exhortation->getArticleData();
   }
   
   
   function render()
   {
      $container =& $this->renderer->createDivTag(false, array('id'=>'homePageContents'));
      
      $welcomeArticleTags = $this->welcomeArticle->render();
      $welcomeArticleTags[0]->setAttribute("id", "welcome");
      $welcomeArticleTags[0]->setAttribute("onClick", "window.location='article.php?id=1';");
      $container->addContents($welcomeArticleTags[0]);
      
      $resumeArticleTags = $this->resumeArticle->render();
      $resumeArticleTags[0]->setAttribute("id", "resume");
      $resumeArticleTags[0]->setAttribute("onClick", "window.location='resources/resume_2011.pdf';");
      $container->addContents($resumeArticleTags[0]);
      
      $blogTags = $this->blog->render();
      $blogTags[0]->setAttribute("id", "blog");
      $blogTags[0]->setAttribute("onClick", "window.location='blog.php';");
      $container->addContents($blogTags[0]);
      
      $exhortationTags = $this->exhortation->render();
      $exhortationTags[0]->setAttribute("id", "exhortation");
      $container->addContents($exhortationTags[0]);
      
      $this->htmlObjects[] = $container;
      
      $title = "Gravitycar.com - Mike Andersen's Web Site";
      $this->page->setTitle($title);
      $this->page->setPageTitle("");
      return $this->htmlObjects;
   }
}
?>
