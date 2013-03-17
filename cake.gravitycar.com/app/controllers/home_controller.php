<?php
class HomeController extends AppController
{
   var $helpers = array ('Html', 'Session');
   var $name = "Home";
   var $skins = array();
   var $skinsPath = "../images/skins/";
   var $skinsCount = 0;
   
   function index()
   {
      $this->findSkins();
      $fields = array("*");
      $conditions = array('id in (10, 14, 15, 16)');
      list($blogEntry, $whoami, $resume, $exhortation) = $this->Home->find('all', array('fields' => $fields, 'conditions' => $conditions));
      $this->set('blogEntry', html_entity_decode($this->trimText($blogEntry['Home']['articleText'], 46)));
      $this->set('whoami', html_entity_decode($whoami['Home']['articleText']));
      $this->set('resume', html_entity_decode($resume['Home']['articleText']));
      $this->set('exhortation', html_entity_decode($exhortation['Home']['articleText']));
      $this->set('skins', $this->getSkinsLinks());
   }
   
   
   function trimText($text, $wordCount)
   {
      $newTextArray = array_slice(explode(" ", $text), 0, $wordCount);
      $newTextArray[] = " (click for more ...) ";
      return implode(" ", $newTextArray);
   }
   
   
   function getSkinThumbnail($skinName)
   {
     $skinID = basename($skinName, ".png");
     $html = "\n\t\t<div class=\"skinDiv\" id=\"$skinID\">" . 
     "<a href=\"/index.php?cssPath=" . basename($skinID, ".png") . "\" class=\"skinLink\">" . 
     "<img border=\"0\" src=\"http://www.gravitycar.com/images/default/skinFrame.gif\" height=\"103\" width=\"150\" style=\"background-image: url(http://www.gravitycar.com/images/skins/$skinName);\" /></a></div>";
     return $html;
   }
   
   
   function getSkinsLinks()
   {
     $skinCount = count($this->skins);
     if ($skinCount  < 1)
     {
       $this->findSkins();
     }
     
     $html = "";
     foreach ($this->skins as $skin)
     {
       $html .= $this->getSkinThumbnail($skin);
     }
     return $html;
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
         $this->skins[] = $file;
      }
      sort($this->skins);
      $this->skinsCount = count($this->skins);
      $this->set('skinsCount', $this->skinsCount);
   }
}
?>
