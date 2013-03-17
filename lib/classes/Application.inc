<?php
/**

Application.inc (class for web applications)

An application is any class designed to do a specific task. Applications should
conduct all of their business logic and then collect all of their properties
that need to be displayed to the user as various tag object via the renderer.

**/
//require_once("../dblib/MySQLi.inc");
require_once("lib/classes/MySQLi.inc");
require_once("lib/classes/Authenticator.inc");
abstract class gcApplication
{
   protected $page = null;
   protected $renderer = null;
   protected $msgCenter = null;
   protected $htmlObjects = false;
   public $parent = false;


	/**
	:Function: init()
	
	:Description:
   Sets all of the nested objects this application will rely on for rendering
   itself on the page.
	
	:Parameters:
	None.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function init()
   {
      $this->page =& gcPage::Singleton();
      $this->renderer =& gcHTMLRenderer::Singleton();
      $this->msgCenter =& gcMessageCenter::Singleton();
      $this->db =& gcMySQLiDB::Singleton();
      $this->auth =& gcAuthenticator::Singleton();
   }
   
   
   function set($propName, $propValue)
   {
      $this->$propName = $propValue;
   }
   
   
   function get($propName)
   {
      return $this->$propName;
   }
   
   
   function determineRequiredPermission()
   {
      $this->auth->setReqPermWeight(GC_AUTH_ALL);
   }
}



interface Application
{
   function init();
   function render();
   function run();
   function determineRequiredPermission();
}




	/**
	:Function: 
	
	:Description:
	
	:Parameters:
	None.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
?>
