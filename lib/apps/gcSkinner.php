<?php
/**
gcSkinner - a class for displaying links to the "other" skins for the gravitycar
web site.

**/

class gcSkinner extends gcApplication implements Application
{
   public $skins = array();
   public $skinsPath = "images/skins/";
   
   function gcSkinner()
   {
      $this->init();
      $this->findSkins();
      $this->page->addStylesheet("skinner.css");
   }
   
   
   function findSkins()
   {
      $dir = opendir($this->skinsPath);
      
      while (false !== ($file = readdir($dir)))
      {
         if ($file == "." || $file == "..")
         {
            continue;
         }
         $this->skins[] = $this->skinsPath . $file;
      }
      sort($this->skins);
   }
   
   function run()
   {
      
   }
   
   
   function render()
   {
      $skinBackground = $this->renderer->createDivTag();
      $skinBackground->setAttribute("id", "skinBackground");
      
      $skinsContainer = $this->renderer->createDivTag();
      $skinsContainer->setAttribute("id", "skinsContainer");
      
      $countDiv = $this->renderer->createDivTag(count($this->skins), false, $skinsContainer);
      $countDiv->setAttribute("class", "skinDiv");
      $countDiv->setAttribute("id", "skinCount");
            
      foreach ($this->skins as $filePath)
      {
         $cssPath = basename($filePath, ".png");
         
         $skinDiv = $this->renderer->createDivTag();
         $skinDiv->setAttribute("class", "skinDiv");
         $skinDiv->setAttribute("id", $cssPath);
         
         $link = $this->renderer->createATag();
         $link->setAttribute("href", $_SERVER['PHP_SELF'] . "?cssPath=" . $cssPath);
         $link->setAttribute("class", "skinLink");
         
         $img = $this->renderer->createImageTag();
         
         $framePath = str_replace("css/", "", $this->page->cssPath) . "skinFrame.gif";
         $img->setAttribute('src', "images/$framePath");
         $img->setAttribute('height', '103');
         $img->setAttribute('width', '150');
         $img->setAttribute('alt', 'Sight Skin');
         $img->setAttribute("style", "background-image: url(images/skins/$cssPath.png);");
         
         $link->addContents($img);
         $skinDiv->addContents($link);
         $skinsContainer->addContents($skinDiv);
      }
      $skinBackground->addContents($skinsContainer);
      
      $this->htmlObjects[] = $skinBackground;
      return $this->htmlObjects;
   }
}
?>
