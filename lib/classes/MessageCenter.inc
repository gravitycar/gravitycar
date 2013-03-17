<?php
/**
MessageCenter.inc (class for message object).

The message center is the class used to aggregate and dispatch all messages
from code to either the user or to log files. Messages can be errors, warnings,
alerts and log messages.

The message center should always be accessed by a singleton method. This way
multiple applications can register messages in one place and all those messages
will be rendered appropriately during page rendering.

A new type of error messages has been added. debug messages are debugging error
messages or other errors that are import for a developer to see, but should be
hidden from view for the general public. The showDebug property determines
if debug errors are displayed to the user like other error messages or if they 
are logged silently. If they are to be logged, then adding debug messages
will just add them to the logEntries list.

**/

class gcMessageCenter
{
   var $logDir = "";
   var $errors;
   var $alerts;
   var $debug;
   var $logEntries;
   var $showDebug = false;
   private static $instance;
   
   function gcMessageCenter()
   {
      $this->logDir = "/var/log/gclogs/";
      $this->errors = array();
      $this->debug = array();
      $this->alerts = array();
      $this->logEntries = array();
      
      $this->messageCategoryTitles = array('error'=>"Error Report",
                                           'debug'=>'Debug Info',
                                           'alert'=>"Attention",
                                           'log'=>"Log Entries");
      /*
      $this->messageCategoryTitles = array('error'=>"Uh, Dude. . . ",
                                           'devError'=>'Debug Info',
                                           'alert'=>"Yo, Dude. . .",
                                           'log'=>"Log Entries");
      */
      if (strpos($_SERVER['SERVER_NAME'], "narya") !== false)
      {
         // in dev environment.
         $this->showDebug = true;
      }
      else
      {
         // in production environment.
         $this->logDir = "/logs/";
      }
   }
   
   
   /**
   :Function: Singleton
   
   :Description:
   Returns a reference to the same instance of this class whenever it's called.
   If no instance of this class has been created, an instance is created and
   stored in a global variable.
   
   :Parameters:
   None
   
   :Return Value:
   gcMessageCenter - the instance of the Message Center object.
   
   :Notes:
   **/
   public static function Singleton()
   {
      if (!IsSet(self::$instance))
      {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }
   
   
   /**
   :Function: addDebug()
   
   :Description:
   Adds a new error message to the debug list of error messages. These
   errors are not intended for the general public to see.
   
   :Parameters:
   string $errorMsg - the error message being added.
   
   :Return Value:
   int - the array index value of the error message, in case you need to 
   retrieve it or erase it.
   
   :Notes:
   **/
   function addDebug($errorMsg)
   {
      if ($this->showDebug)
      {
         $this->debug[] = $errorMsg;
         return count($this->debug) - 1;
      }
      else
      {
         return $this->addLog($errorMsg);
      }
   }
   
   
   /**
   :Function: addError()
   
   :Description:
   Adds a new error message to the list of the message center's errors. These
   errors are intended to be shown to the general public.
   
   :Parameters:
   string $errorMsg - the error message being added.
   
   :Return Value:
   int - the array index value of the error message, in case you need to 
   retrieve it or erase it.
   
   :Notes:
   **/
   function addError($errorMsg)
   {
      $this->errors[] = $errorMsg;
      return count($this->errors) - 1;
   }
   
   
   /**
   :Function: addAlert()
   
   :Description:
   Adds a new alert message to the list of alerts in the message center.
   
   :Parameters:
   string $alertMsg - the message to be added.
   
   :Return Value:
   int - the array index value of the alert message, in case you need to 
   retrieve it or erase it.
   
   :Notes:
   **/
   function addAlert($alertMsg)
   {
      $this->alerts[] = $alertMsg;
      return count($this->alerts) - 1;
   }
   
   
   /**
   :Function: addLog()
   
   :Description:
   Adds a new log entry to a list of log entries. NOTE: this function does
   not actually write the message to the log file, it just groups them together.
   Writing out log messages should be done AFTER the page rendering is complete.
   Also Note that this function takes two arguments, the message and the file
   where the message should be logged. When you record a log entry, it's added
   to a list of entries for that log file. So when you try to retrieve that
   entry later by index, you will need the index AND the file the message was
   to be logged in.
   
   :Parameters:
   string $logMsg - the message to be added to the log file.
   string $logFile - the file name to the log file you want the message to
      be recorded in. Applications should have their own log file name that 
      they send by default.
   
   :Return Value:
   int - the array index of th
   
   :Notes:
   **/
   function addLog($logMsg, $logFile="gclog")
   {
      $this->logEntries[$logFile][] = $logMsg;
      return count($this->logEntries[$logFile]) - 1;
   }
   
   
   /**
   :Function: getDebug()
   
   :Description:
   Retrieves a debug message string based on the passed in index.
   
   :Parameters:
   int $index - the numerical index of the message you want.
   
   :Return Value:
   string - the value of the message corresponding to the passed in index,
      empty string if the passed in index is not defined in the errors array.
   
   :Notes:
   **/
   function getDebug($index)
   {
      if (IsSet($this->debug[$index]))
      {
         return $this->debug[$index];
      }
      return "";
   }
   
   
   /**
   :Function: getError()
   
   :Description:
   Retrieves an error message string based on the passed in index.
   
   :Parameters:
   int $index - the numerical index of the message you want.
   
   :Return Value:
   string - the value of the message corresponding to the passed in index,
      empty string if the passed in index is not defined in the errors array.
   
   :Notes:
   **/
   function getError($index)
   {
      if (IsSet($this->errors[$index]))
      {
         return $this->errors[$index];
      }
      return "";
   }
   
   
   /**
   :Function: getAlert($index)
   
   :Description:
   Retrieves an alert message string based on the passed in index.
   
   :Parameters:
   int $index - the numerical index of the message you want.
   
   :Return Value:
   string - the value of the message corresponding to the passed in index,
      empty string if the passed in index is not defined in the alerts array.
   
   :Notes:
   **/
   function getAlert($index)
   {
      if (IsSet($this->alerts[$index]))
      {
         return $this->alert[$index];
      }
      return "";
   }
   
   
   /**
   :Function: getLogEntry
   
   :Description:
   Retrieves a log entry for a particular log file based on the passed in 
   message index and the passed in log file name.
   
   :Parameters:
   int $index - the index of the log entry.
   string $logFile - the name of the file the entry will be logged in.
   
   :Return Value:
   string - the value of the message
   
   :Notes:
   **/
   function getLogEntry($index, $logFile)
   {
      if (IsSet($this->logEntries[$logFile]))
      {
         return $this->logEntries[$logFile];
      }
      return "";
   }
   
   
   /**
   :Function: getMsgs()
   
   :Description:
   Retrieves a type of message, either error, alert or log.
   
   :Parameters:
   string $type - the type of message to be retrieved: [errors|alerts|logEntries]
   
   :Return Value:
   array - the named array of messages. False if the passed in type isn't
      defined.
   
   :Notes:
   **/
   function getMsgs($type)
   {
      switch ($type)
      {
         case "error":
            $type = "errors";
         break;
         
         case "alert":
            $type = "alerts";
         break;
            
         case "log":
            $type = "logEntries";
         break;
         
         case "debug":
            $type = "debug";
         break;
      }
      
      if (IsSet($this->$type))
      {
         $msgs = array();
         foreach ($this->$type as $msg)
         {
            if (!empty($msg))
            {
               $msgs[] = $msg;
            }
         }
         return $msgs;
      }
      return false;
   }
   
   
   /**
   :Function: deleteDebug()
   
   :Description:
   Sets a previously entered debug message to an empty string. If the index is
   not currently defined in the errors list, it won't be created.
   
   :Parameters:
   int $index - the array index of the debug message you want to delete.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function deleteDebug($index)
   {
      if (IsSet($this->debug[$index]))
      {
         $this->debug[$index] = "";
      }
   }
   
   
   /**
   :Function: deleteError()
   
   :Description:
   Sets a previously entered error message to an empty string. If the index is
   not currently defined in the errors list, it won't be created.
   
   :Parameters:
   int $index - the array index of the error message you want to delete.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function deleteError($index)
   {
      if (IsSet($this->errors[$index]))
      {
         $this->errors[$index] = "";
      }
   }
   
   
   /**
   :Function: deleteAlert()
   
   :Description:
   Sets a previously entered alert message to an empty string. If the index is
   not currently defined in the alerts list, it won't be created.
   
   :Parameters:
   int $index - the array index of the alert message you want to delete.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function deleteAlert($index)
   {
      if (IsSet($this->alerts[$index]))
      {
         $this->alerts[$index] = "";
      }
   }
   
   
   /**
   :Function: deletelogEntry()
   
   :Description:
   Sets a previously entered logEntry message to an empty string. If the index is
   not currently defined in the logEntrys list, it won't be created.
   
   :Parameters:
   int $index - the array index of the logEntry message you want to delete.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function deletelogEntry($index, $logFile)
   {
      if (IsSet($this->logEntries[$logFile][$index]))
      {
         $this->logEntries[$logFile][$index] = "";
      }
   }
   
   
   /**
   :Function: clearAllMessages()
   
   :Description:
   Sets all of the message arrays to be empty arrays.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function clearAllMessages()
   {
      $this->logEntries = array();
      $this->errors = array();
      $this->debug = array();
      $this->alerts = array();
   }
   
   
   /**
   :Function: hasErrors()
   
   :Description:
   Determines if any error messages have been registered with the message
   center. Returns true if there are error messages and false if there are none.
   
   :Parameters:
   None
   
   :Return Value:
   boolean - true if there are error messages, false otherwise.
   
   :Notes:
   **/
   function hasErrors()
   {
      return count($this->errors) > 0 ? true : false;
   }
   
   
   /**
   :Function: writeLogEntries()
   
   :Description:
   Appends the log entries to every log file.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function writeLogEntries()
   {
      foreach($this->logEntries as $fileName)
      {
         $filePath = $this->logDir . $fileName;
         $fileHandle = fopen($filePath, "a+");
         
         if (!$fileHandle)
         {
            print("<br>Could not write to $fileName");
         }
         
         foreach($this->logEntries[$fileName] as $msg)
         {
            fwrite($fileHandle, $msg . "\n");
         }
         fclose($fileHandle);
      }
   }
   
}

   
   
   /**
   :Function: 
   
   :Description:
   None.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
?>
