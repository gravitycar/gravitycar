<?php
/**
emailer.inc (class for sending emails from applications.)
   
This simple class is intended to send email to one person (me). It's 
mostly for alerting me to moderate comments.
**/

class gcEmailer
{
   private $to = "mike@gravitycar.com";
   private $from = "webmaster@gravitycar.com";
   private $subject = "No Subject Set";
   private $message = "";
   private $headers = ""; 
   private $params = "-f";
   private $okToSend = true;
   private $msgSent = false;
   
   function gcEMailer($subject, $msg)
   {
      $this->subject = $subject;
      $this->message = $msg;
      $this->headers = "From: $this->from\r\n" . "Reply-To: $this->from\r\n" . "X-Mailer: PHP/" . phpversion();
      $this->params .= $this->from;
      if ($this->to != "mike@gravitycar.com")
      {
         $this->okToSend = false;
      }
      
      if ($this->from != "webmaster@gravitycar.com")
      {
         $this->okToSend = false;
      }
      
      if ($this->okToSend)
      {
         if ($_SERVER['SERVER_NAME'] != "narya")
         {
            $this->msgSent = mail($this->to, $this->subject, $this->message, $this->headers, $this->params);
         }
         else
         {
            //$this->msgSent = mail($this->to, $this->subject, $this->message, $this->headers, $this->params);
            $this->msgSent = true;
         }
      }
   }
   
   
   function msgSentOK()
   {
      return $this->msgSent;
   }
}
?>
