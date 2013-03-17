<?php

require_once("lib/classes/Form.inc");
class gcSaySomething extends gcForm implements Application
{
   public $something = "";
   private $privateString = "";
   
   // constructor
   function gcSaySomething()
   {
      // init() should be called in every constructor function immediately.
      $this->init();
      // Now that init() has been called, you have access to methods in the
      // properties init() provides your objects. So you can, for example, set
      // the page title or add js and css files.
      $this->page->setTitle("say something");
      $this->page->addJavascriptFile("/js/giftLists.js");
      $this->page->addStylesheet("giftList.css");
      
      // or you can turn test mode on in your database connection.
      $this->db->setTestMode(true);
      
      // because this class extends gcForm, you can set input attributes:
      $this->setFormProps("title", "something");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("title", "value", "Say Something!");
      $this->setInputAttr("something", "type", "text");
      $this->setInputAttr("say_it", "type", "submit");
      $this->setInputAttr("say_it", "value", "Say It!");
      $this->setControlButtons("say_it");
      $this->setValidationRule("something", "notEmpty");
   }

   // the run() method is where your business logic is carried out.
   function run()
   {
      // you can use gcForm::determineAction() to detect whether a form
      // submission has been made or not, and proceed accordingly;
      switch ($this->determineAction())
      {
         case "entering":
            $this->setFormTitle("Say Something");
         break;
         
         case "creating":
            $this->setFormTitle("What You Said");
            // importFromPost() will automatically validate the "something"
            // field in post, and if it passes validation will assign the value
            // from post to the something property of this object.
            $this->importFromPost();
         break;
      }
   }
   
   // the sayIt() method will return a div with the text the user submitted.
   function sayIt()
   {
      $divAttributes = array('class'=>'somethingSaid', 'align'=>'center');
      return $this->renderer->createDivTag($this->something, $divAttributes);
   }
   
   // render() returns an array of HTML objects (which are produced by the 
   // renderer) so that the page object can add them to the body tag. You can
   // specify a different parent than body by setting $this->parent to a 
   // reference to a gc*Tag object.
   function render()
   {
      if ($this->something != "")
      {
         $this->htmlObjects[] = $this->sayIt();
      }
      $this->htmlObjects[] = $this->buildForm();
      return $this->htmlObjects;
   }
}
?>
