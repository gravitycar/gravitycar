<?php

class gcTop extends gcApplication implements Application
{
   var $navDivAttrs;
   var $navLinksData;
   var $navLinks;
   var $navListAttr;
   var $navDiv;
   var $navList;
   var $errorDiv; // defined dynamically in buildMessageBoxes()
   var $alertDiv; // defined dynamically in buildMessageBoxes()
   var $errorDivAttrs; // defined dynamically in buildMessageBoxes()
   var $alertDivAttrs; // defined dynamically in buildMessageBoxes()
   var $errorList; // defined dynamically in buildMessageBoxes()
   var $alertList; // defined dynamically in buildMessageBoxes()
   var $styleMap = array();
   
   
   function gcTop()
   {
      $this->init();
      $this->page->addStylesheet("main.css");
      $this->page->addStylesheet("nav.css");
      $this->page->addJavascriptFile("js/addOnLoad.js");
      $this->page->addJavascriptFile("js/ieMenuFix.js");
      $favicon = new gcLinkTag("favicon.ico");
      $favicon->setAttribute("type", "");
      $favicon->setAttribute("rel", "Shortcut Icon");
      $this->page->head->addContents($favicon);
      $this->navDivAttrs = array('id'=>'navContainer');
      
      $this->styleMap[1] = "topMenu";
      $this->styleMap[2] = "subMenu";
      $this->styleMap[3] = "flyMenu";
      
      $this->navLinks = array();
      
      $this->navListAttr = array('id'=>'navList', 'class'=>'navList');
      
      $this->errorDivAttrs = array('id'=>'errorMsgContainer', 'align'=>'center', 'class'=>'errorMsgsDiv');
      $this->alertDivAttrs = array('id'=>'alertMsgContainer', 'align'=>'center', 'class'=>'alertMsgsDiv');
      $this->debugDivAttrs = array('id'=>'debugMsgContainer', 'align'=>'center', 'class'=>'debugMsgsDiv');
   }
   
   
   function buildNavList()
   {
      $this->navDiv = $this->renderer->createDivTag("", $this->navDivAttrs);
      $menuRoot = $this->renderer->createULTag(false, $this->navListAttr, $this->navDiv);
      foreach ($this->navLinks as $item)
      {
         if ($item)
         {
            $menuRoot->addContents($item->build());
         }
      }
      return $this->navDiv;
   }
   
   
   function &buildLoginLink()
   {
      $authDivAttrs = array("class"=>"loginDiv", "id"=>"loginDiv");
      $authDiv = $this->renderer->createDivTag("", $authDivAttrs);
      //$authDiv->setAttribute("class", "loginDiv");
      
      if ($this->auth->getAuthOK())
      {
         $span = $this->renderer->createSpanTag("Welcome " . $this->auth->getUser() . "<br>", "", $authDiv);
         $link = $this->renderer->createATag("Log Out");
         $link->setAttribute("href", "login.php?logout=yes");
      }
      else
      {
         $link = $this->renderer->createATag("Log In");
         $link->setAttribute("href", "login.php");
      }
      
      $authDiv->addContents($link);
      return $authDiv;
   }
   
   
   function buildMessageBox($type)
   {
      $msgs = $this->msgCenter->getMsgs($type);
      if (!$msgs)
      {
         return false;
      }
      
      $attrName = $type . "DivAttrs";
      $divName = $type . "Div";
      $listName = $type . "List";
      $divTitle = $this->msgCenter->messageCategoryTitles[$type];
      $attrs = $this->$attrName;
      $this->$divName = $this->renderer->createDivTag("", $attrs);
      $titleDiv = $this->renderer->createDivTag($divTitle, array('class'=>'title'), $this->$divName);
      $this->$listName = $this->renderer->createULTag($msgs, false, $this->$divName);
      return $this->$divName;
   }
   
   
   /*
   :Notes:
   This function must be called *after* authentication has been run. That means
   it should be called during the render process, not during instantiation
   (when auth exists but has not been run) or during the run() call of this
   object (because it's possible to load this application before the login
   app is loaded, and apps are run in the order they're loaded in).
   */
   function addSpecificUserLinks()
   {
      if ($this->auth->isLoggedIn())
      {
         $this->navLinks['home']->addItem("Log Out", "login.php?logout=yes");
         $this->navLinks['home']->addItem("My Profile", "editUser.php?id=" . $_SESSION['userID']);
      }
      else
      {
         $this->navLinks['home']->addItem("Log In", "login.php");
      }
   }
   
   
   /*
   :Notes:
   This function must be called *after* authentication has been run. That means
   it should be called during the render process, not during instantiation
   (when auth exists but has not been run) or during the run() call of this
   object (because it's possible to load this application before the login
   app is loaded, as apps are run in the order they're loaded in).
   */
   function setMenuLinks()
   {
      $this->navLinks['home'] = $this->getMenuObj("Home", "index.php");
      $this->navLinks['about'] = $this->getMenuObj("About Me", "article.php?id=1");
      $this->navLinks['mqt'] = $this->getMenuObj("Movie Quotes!", "movieQuoteGame.php");
      $this->navLinks['cog'] = $this->getMenuObj("Event Organizer", "eventLogin.php");
      $this->navLinks['gifts'] = $this->getMenuObj("Gifts", "giftList.php");
      $this->navLinks['users'] = $this->getMenuObj("Skins", "", GC_AUTH_ALL);
      
      $this->navLinks['about']->setID("about");
      $this->navLinks['about']->addItem("Resume (pdf)", "resources/resume_2011.pdf");
      $this->navLinks['about']->addItem("About the Site", "article.php?id=2");
      $this->navLinks['about']->addItem("Blog", "blog.php");
      $this->navLinks['about']->addItem("Gravity Car?", "article.php?id=11");
      $this->navLinks['about']->addItem("Create Article", "editArticle.php", GC_AUTH_WRITER);
      $this->navLinks['about']->addItem("Edit an Article", "listArticles.php", GC_AUTH_WRITER);
      
      $this->navLinks['gifts']->setID('gifts');
      $this->navLinks['gifts']->addItem("Add Gift", "editGifts.php", GC_AUTH_USER);
      
      $this->navLinks['mqt']->setID("mqt");
      $this->navLinks['mqt']->addItem("Play Movie Quote Trivia", "movieQuoteGame.php");
      $this->navLinks['mqt']->addItem("Add Movie Quote", "addMovieQuote.php", GC_AUTH_USER);
      $this->navLinks['mqt']->addItem("Edit Quotes", "listMovieQuotes.php", GC_AUTH_ADMIN);
      //$this->navLinks['demos']->addItem("Photos", "photos.php");
      
      $this->navLinks['cog']->setID("cog");
      $this->navLinks['cog']->addItem("New Event", "editEvent.php", GC_AUTH_ADMIN);
      $this->navLinks['cog']->addItem("Edit Event", "listEvents.php?task=event", GC_AUTH_ADMIN);
      $this->navLinks['cog']->addItem("Invite People", "listEvents.php?task=invitations", GC_AUTH_ADMIN);
      $this->navLinks['cog']->addItem("Propose Dates", "listEvents.php?task=proposeDates", GC_AUTH_ADMIN);
      
      $this->navLinks['users']->setID("users");
      
      /*
      $qs = $_SERVER['QUERY_STRING'];
      $qs = empty($qs) ? "?cssPath=" : "?$qs" . "&cssPath=";
      
      $url = $_SERVER['PHP_SELF'] . $qs;
      */
      if ($_GET)
      {
         $pairs = array();
         foreach ($_GET as $name => $value)
         {
            if ($name == "cssPath")
            {
               continue;
            }
            $pairs[] = "$name=$value";
         }
         $pairs[] = "cssPath=";
         $qs = "?" . implode("&", $pairs);
      }
      else
      {
         $qs = "?cssPath=";
      }
      $url = $_SERVER['PHP_SELF'] . $qs;
      
      $this->navLinks['users']->addItem("Get It in Gear!", $url . "gears");
      $this->navLinks['users']->addItem("Super Tokyo", $url . "tokyo");
      $this->navLinks['users']->addItem("The Tap", $url . "tap");
      
      $this->navLinks['home']->addItem("Add User", "editUser.php", GC_AUTH_ADMIN);
      $this->navLinks['home']->addItem("Edit User", "listUsers.php", GC_AUTH_ADMIN);
   }
   
   
   function addSubMenuLink($mainMenuName, $label, $url, $permReq=GC_AUTH_ALL)
   {
      $this->navLinks[$mainMenuName]->addItem($label, $url, $permReq);
   }
   
   
   function &getMenuObj($label, $url, $authReq=0)
   {
      return new gcMenu($label, $url, &$this->styleMap, $authReq);
   }
   
   
   function &buildTitle()
   {
      $title = $this->renderer->title->getContents();
      $attr = array("id"=>"pageTitle");
      $parent =& $this->renderer->body;
      $h1Tag =& $this->renderer->createH1Tag($title, $attr, false);
      return $h1Tag;
   }
   
   
   function render()
   {
      $this->setMenuLinks();
      $this->addSpecificUserLinks();
      $this->htmlObjects[] = $this->buildNavList();
      $this->htmlObjects[] = $this->buildLoginLink();
      $this->htmlObjects[] = $this->buildTitle();
      $this->htmlObjects[] = $this->buildMessageBox("error");
      $this->htmlObjects[] = $this->buildMessageBox("debug");
      $this->htmlObjects[] = $this->buildMessageBox("alert");
      return $this->htmlObjects;
   }
   
   
   function run()
   {
      // no business logic performed here.
   }
}


class gcMenu
{
   var $label = "";
   var $href = "";
   var $id = "";
   var $className = "";
   var $items = array();
   var $tier = 1;
   var $styleMap = "";
   var $renderer = false;
   var $userAuthWeight = 0;
   var $authReq = 0;
   
   function gcMenu($label, $href, &$styleMap, $authReq=0)
   {
      $this->label = $label;
      $this->href = $href;
      $this->id = "nav" . strtolower(preg_replace('/\W/', "", $label));
      $this->styleMap =& $styleMap;
      $this->setClassName($this->styleMap[$this->tier]);
      $this->renderer =& gcHTMLRenderer::Singleton();
      $this->auth =& gcAuthenticator::Singleton();
      $this->authReq = $authReq;
      $this->userAuthWeight = $this->auth->getUserPermWeight();
   }
   
   
   function &addItem($label, $href, $authReq=0)
   {
      if ($this->userAuthWeight < $authReq)
      {
         return false;
      }
      
      $menuItem =& new gcMenu($label, $href, &$this->styleMap);
      $menuItem->incrementTier($this->tier);
      $menuItem->setClassName($this->styleMap[$menuItem->tier]);
      $menuItem->setAuthReq($authReq);
      $this->items[] = $menuItem;
      return $menuItem;
   }
   
   
   function setID($id)
   {
      $this->id = $id;
   }
   
   
   function setAuthReq($authReq)
   {
      $this->authReq = $authReq;
   }
   
   
   function setClassName($className)
   {
      $this->className = $className;
   }
   
   
   function isMenu()
   {
      return count($this->items) > 0;
   }
   
   
   function incrementTier($tier)
   {
      $this->tier = $tier + 1;
   }
   
   
   function &build()
   {
      if ($this->userAuthWeight < $this->authReq)
      {
         return "";
      }
      
      $aTagClass = $this->className . "Link";
      $liTagClass = $this->className . "Item";
      $ulTagClass = $this->className;
      
      $aID = $this->id . "Link";
      $liID = $this->id . "Item";
      $ulID = $this->id;
      
      $li = $this->renderer->createLITag(false, array("id"=>$liID, "class"=>$liTagClass));
      $a = $this->renderer->createATag($this->label, array("id"=>$aID, "href"=>$this->href, "class"=>$aTagClass), $li);
      if ($this->items)
      {
         $ulTagClass = $this->items[0]->styleMap[$this->items[0]->tier];
         $ul = $this->renderer->createULTag(false, array("id"=>$ulID, "class"=>$ulTagClass), $li);
         foreach ($this->items as $item)
         {
            $ul->addContents($item->build());
         }
      }
      
      return $li;
   }
}

?>
