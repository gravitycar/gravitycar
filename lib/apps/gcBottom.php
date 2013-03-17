<?php
class gcBottom extends gcApplication implements Application
{
   var $copyrightNotice;
   var $phone;
   var $email;
   
   function gcBottom()
   {
      $this->init();
      $this->copyrightNotice = "Copyright Mike Andersen " . date("Y");
      $this->phone = "408-264-4044";
      $this->email = "mike@gravitycar.com";
   }
   
   
   function run()
   {
      // no business logic to execute.
   }
   
   
   function &buildFooter()
   {
      /*
      $table = $this->renderer->createTableTag();
      $table->setAttribute("width", "100%");
      $table->setAttribute("class", "footer");
      $row = $table->addRow();
      
      $emailLink = $this->renderer->createATag($this->email, array('href'=>"mailto:" . $this->email));
      
      $emailCell = $row->addCell($emailLink);
      $copyrightCell = $row->addCell($this->copyrightNotice);
      $copyrightCell->setAttribute("align", "center");
      $phoneCell = $row->addCell($this->phone);
      $phoneCell->setAttribute("align", "right");
      return $table;
      */
      
      $footerDiv = $this->renderer->createDivTag($emailLink, array("class"=>"footer"));
      $emailLink = $this->renderer->createATag($this->email, array('class'=>'left', 'href'=>"mailto:"), $footerDiv);
      $phoneDiv = $this->renderer->createDivTag($this->phone, array("class"=>"right"), $footerDiv);
      $copyDiv = $this->renderer->createDivTag($this->copyrightNotice, array("class"=>"middle"), $footerDiv);
      return $footerDiv;
   }
   
   
   function render()
   {
      $this->htmlObjects[] = $this->buildFooter();
      return $this->htmlObjects;
   }
}
?>
