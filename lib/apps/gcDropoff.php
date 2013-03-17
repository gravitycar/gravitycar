<?php
/**
gcDropoff - a class for allowing folks to upload jpg and zip files to
my webserver.
**/

//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
require_once("lib/classes/Form.inc");
require_once("lib/classes/emailer.inc");

class gcDropoff extends gcForm implements Application
{
   protected $files;
   public $from;
   
   function gcDropoff()
   {
      $this->init();
      $this->validate = true;
      $this->page->addJavascriptFile("js/jquery.js");
      $this->page->addJavascriptFile("js/dropoff.js");
      $this->setFormProps("from", "uploadedFiles");
      $this->acceptUploads = true;
      $this->setFormTitle("Upload Files");
      
      $this->setInputAttr("from", "type", "text");
      $this->setInputAttr("from", "size", "30");
      $this->setInputAttr("from", "value", $this->auth->getUser());
      
      $this->setInputAttr("uploadedFiles", "type", "file");
      $this->setInputAttr("uploadedFiles", "type", "file");
      $this->setInputAttr("uploadedFiles", "size", "50");
      
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Upload");
      $this->setControlButtons("submitButton");
      //$this->setValidationRule("uploadedFiles", "notEmpty");
      $this->setValidationRule("from", "notEmpty");
   }
   
   function run()
   {
      if ($this->determineAction() == "creating" && $this->validationPassed)
      {
         $this->importFromPost();
         if (!$this->validationPassed)
         {
            return false;
         }
         
         $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/dropbox/";
         //$this->msgCenter->addAlert("Number of files received: " . count($_FILES));
         foreach ($_FILES as $file)
         {
            //$this->msgCenter->addAlert("File is: " . $file['name']);
            if ($file['size'] == 0)
            {
               //$this->msgCenter->addAlert("skipped " .  $file['name']);
               continue;
            }
            
            //$fileInfoHandle = finfo_open();
            //$info = finfo_file($fileInfoHandle, $file['tmp_name']);
            //finfo_close($fileInfoHandle);
            $info = "JPEG";
            $uploadFile = $uploadDir . $file['name'];
            $this->msgCenter->addAlert($file['name'] . " was received. It is a " . $info . ". Let's put it in $uploadFile");
            if (
                stripos($info, "JPEG") !== false || 
                stripos($info, "JPG") !== false || 
                stripos($info, "PNG") !== false || 
                stripos($info, "GIF") !== false ||
                stripos($info, "ZIP") !== false ||
                stripos($info, "TAR") !== false )
            {
               if (move_uploaded_file($file['tmp_name'], $uploadFile)) 
               {
                  $this->msgCenter->addAlert("Thanks " . $this->from . ", " . $file['name'] . " looks good, and was successfully uploaded.");
                  $this->sendGoodEmail($this->from, $uploadFile);
               }
               else 
               {
                  //$this->msgCenter->addError($file['name'] . " That's not neighborly of you! " . $file['tmp_name'] . " " . $file['error']);
                  $this->msgCenter->addError("Something went wrong with " . $file['name'] . ". <a href=\"mailto:mike@gravitycar.com\">Ask me</a> about it some time soon.");
                  $this->sendBadEmail($this->from, $file['name'], $file['error']);
               }
            }
            else
            {
               $this->msgCenter->addError($file['name'] . " is not a valid file type. I only want pictures, zips or tar balls.");
               continue;
            }
         }
      }     
      return true;
   }
   
   function sendGoodEmail($from, $path)
   {
      $subject = "New file uploaded by $from";
      $message = "$from uploaded a file to $path. Go git it!";
      $emailer = new gcEmailer($subject, $message);
      return $emailer->msgSentOK();
   }
   
   function sendBadEmail($from, $file, $errorCode)
   {
      $subject = "File upload from $from FAILED";
      $message = "$from uploaded $file but something went horribly wrong, which caused error code '$errorCode'! Check it out: http://www.php.net/manual/en/features.file-upload.errors.php";
      $emailer = new gcEmailer($subject, $message);
      return $emailer->msgSentOK();
   }
   
   function render()
   {
      $this->htmlObjects[] = $this->buildForm();
      return $this->htmlObjects;
   }
}
?>
