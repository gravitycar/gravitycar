<?php
/**
gcMusicList - a list of cd's by a particular artist, powered by phpBrainz.

NOTE: Very Experimental.
**/
require_once("lib/classes/Form.inc");
require_once("lib/classes/Validator.inc");
require_once("lib/phpbrainz/phpBrainz.class.php");

class gcMusicList extends gcForm implements Application
{
   public $brain = false;
   
   function gcMusicList()
   {
      $this->init();
      $this->brain = new phpBrainz();
   }
   
   function run()
   {
      $args = array("title"=>"Learn", "artist"=>"Foo Fighters");
      $releaseFilter = new phpBrainz_ReleaseFilter($args);
      $releaseResults = $this->brain->findRelease($releaseFilter);
      print_r($releaseResults);
   }
   
   function render()
   {
      $this->htmlObjects = "Hello World";
      return $this->htmlObjects;
   }
}
?>
