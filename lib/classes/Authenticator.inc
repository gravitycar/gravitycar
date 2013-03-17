<?php
/**

Authenticator.inc (class for web pages to allow users to log in)

This object checks to see if the user has the necessary permissions to use a
given application. Every application can optionally set a required permission
"weight" necessary to use it. Higher weights mean a higher level of permission.
The default required weight is 0.

App Weight: means:
0           Anyone can use this application.
10          A logged in user can use this application to edit his own records.
20          Only an admin may use this application.

This component does NOT handle loggin in the user. It only checks to see if the
user is logged in and if the user has the required permission weight.
**/

define("GC_AUTH_ALL", 0);
define("GC_AUTH_USER", 10);
define("GC_AUTH_WRITER", 15);
define("GC_AUTH_ADMIN", 20);

require_once("lib/classes/Validator.inc");


class gcAuthenticator
{
   public $userPermWeight = 0;
   public $reqPermWeight = 0;
   public $authOK = null;
   public $user = "";
   public $userID = "";
   private static $instance;
      
   
   /**
   :Function: Singleton
   
   :Description:
   Returns a reference to the same instance of this class whenever it's called.
   If no instance of this class has been created, an instance is created and
   stored in a static variable.
   
   :Parameters:
   None.
   
   :Return Value:
   gcAuthenticator - the instance of the authenticator object.
   
   :Notes:
   The authenticator class also contains a reference to the msg center, so error
   messages will be recorded there.
   **/
   public static function &Singleton()
   {
      if (!IsSet(self::$instance))
      {
         $c = __CLASS__;
         self::$instance = new $c;
         self::$instance->msgCenter =& gcMessageCenter::Singleton();
         self::$instance->page =& gcPage::Singleton();
         
         if (IsSet($_SESSION['userEmail']))
         {
            self::$instance->setUserPermWeight($_SESSION['userPermWeight']);
            self::$instance->setUser($_SESSION['userEmail']);
            self::$instance->setUserID($_SESSION['userID']);
            self::$instance->setUserName($_SESSION['userName']);
            self::$instance->setAuthOK(true);
         }
         else
         {
            self::$instance->setUserPermWeight(0);
            self::$instance->setAuthOK(false);
         }
      }
      
      return self::$instance;
   }
   
   
   /**
   :Function: setUserPermWeight()
   
   :Description:
   Sets the permission weight for the current user. The weight determines if the
   user's permission allows the user to view the current application. The 
   application will have to set its required permission weight. If the user's
   permission weight is equal to or greater than the application's required 
   weight the user may use the application.
   
   :Parameters:
   int $weight - the user's permissions weight.
   
   :Return Value:
   None.
   
   :Notes:
   Weight      Means
   0           Annonymous user - anybody not logged in.
   10          Logged in user - can edit their own records.
   20          Admin user - can edit any records.
   **/
   function setUserPermWeight($weight)
   {
      if (is_numeric($weight))
      {
         $this->userPermWeight = $weight;
      }
      else
      {
         $msg = "Tried to set Permission weight to a ";
         $msg .= gettype($weight);
         $msg .= ": value is '$weight'";
         $this->msgCenter->addDebug($msg);
         $this->userPermWeight = 0;
      } 
   }
   
   
   /**
   :Function: getUserPermWeight()
   
   :Description:
   returns the permission weight for the logged in user.
   
   :Parameters:
   None.
   
   :Return Value:
   int - the permission weight for the logged in user.
   
   :Notes:
   Weight      Means
   0           Annonymous user - anybody not logged in.
   10          Logged in user - can edit their own records.
   20          Admin user - can edit any records.
   **/
   function getUserPermWeight()
   {
      return $this->userPermWeight;
   }
   
   
   /**
   :Function: setReqPermWeight()
   
   :Description:
   Sets the permission weight required by this application for a user to use it.
   If the current user's permission weight is lower than the application's 
   required weight, the user cannot use the application.
   
   This method will only accept a higher value than the current required weight.
   For example, if several applications call this method like this:
   
   $app1->auth->setAppPermWeight(10);
   $app2->auth->setAppPermWeight(20);
   $app3->auth->setAppPermWeight(10);
   
   Then the value of reqPermWeight is 20. The second call with 10 as the arg
   will be discarded.
   
   :Parameters:
   int $weight - the user's permissions weight.
   
   :Return Value:
   None.
   
   :Notes:
   Weight      Means
   0           Annonymous user - anybody not logged in.
   10          Logged in user - can edit their own records.
   20          Admin user - can edit any records.
   **/
   function setReqPermWeight($weight)
   {
      if (is_numeric($weight))
      {
         if ($weight > $this->reqPermWeight)
         {
            $this->reqPermWeight = $weight;
         }
      }
      else
      {
         $msg = "Tried to set Permission weight to a ";
         $msg .= gettype($weight);
         $msg .= ": value is '$weight'";
         $this->msgCenter->addDebug($msg);
         $this->reqPermWeight = 0;
      } 
   }
   
   /**
   :Function: resetReqPermWeight()
   
   :Description:
   Resets the required permission weight for this page to 0. This method is
   called during the runLogin() method in this class, because after the user
   is logged in the required permissions may change.
   
   :Parameters:
   None.
   
   :Return Value:
   None
   
   :Notes:
   **/
   function resetReqPermWeight()
   {
      $this->reqPermWeight = 0;
   }
   
   
   /**
   :Function: getReqPermWeight()
   
   :Description:
   returns the permission weight for this web page.
   
   :Parameters:
   None.
   
   :Return Value:
   int - the permission weight for this web page.
   
   :Notes:
   Weight      Means
   0           Annonymous user - anybody not logged in.
   10          Logged in user - can edit their own records.
   20          Admin user - can edit any records.
   **/
   function getReqPermWeight()
   {
      return $this->reqPermWeight;
   }
   
   
   /**
   :Function: setAuthOK()
   
   :Description:
   Sets this object's authOK property to the passed in value. If authOK is
   set to any value other than NULL, the check for the password matching the
   db is NOT run.
   
   :Parameters:
   boolean $state - the state (true or false) that you want to set the authOK
      property to.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setAuthOK($state)
   {
      $this->authOK = $state;
   }
   
   
   /**
   :Function: getAuthOK()
   
   :Description:
   Returns this object's authOK property, indicating whether or not the session
   variable indicating the user has logged in was found or not.
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if the log in session var was found, false otherwise.
   
   :Notes:
   **/
   function getAuthOK()
   {
      return $this->authOK;
   }
   
   
   
   /**
   :Function: isLoggedIn()
   
   :Description:
   Wrapper for getAuthOK().
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if the log in session var was found, false otherwise.
   
   :Notes:
   **/
   function isLoggedIn()
   {
      return $this->getAuthOK();
   }
   
   
   /**
   :Function: checkAuth()
   
   :Description:
   Determines if authentication is necessary. If authentication isn't required,
   the user may proceed. If auth is required, checks the user's auth state.
   If the user is auth'd in, is their permission weight greater than or equal
   to the highest required permission of all applications loaded in this page.
   If the user isn't auth'd in OR their weight is too low, give 'em an error
   message and show them the log in form. 
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if the user can use this application, false otherwise.
   
   :Notes:
   **/
   function checkAuth()
   {
      // if the required permission weight is 0, no further checks are 
      // necessary. Anybody can use this app.
      if ($this->getReqPermWeight() === 0)
      {
         $auth = true;
         return $auth;
      }
      
      if ($this->getAuthOK())
      {
         // the user is logged in. Do they have permission to use this app?
         if ($this->getUserPermWeight() >= $this->getReqPermWeight())
         {
            // yes they do.
            $auth = true;
         }
         else
         {
            // no they don't.
            $this->msgCenter->addError("You do not have permission to use this page.");
            $auth = false;
         }
      }
      else
      {
         // the user is not logged in. Return false.
         $auth = false;
      }
      return $auth;
   }
   
   
   /**
   :Function: runLogin()
   
   :Description:
   This method loads and runs the gcLogin application. This function is called
   whenever checkAuth() returns false before an application is run on a page
   that requires a user permission weight of more than 0. After the gcLogin
   app runs, the user's auth state is checked. The user is either logged in
   (authOK = true) or not logged in (authOK = false).
   
   Not Logged In:
   A message is stored telling the user they must log in.  
   A login form is displayed.
   Any other apps are removed from the applications list in the page so they
      are not run or rendered.
   The original form action is stored in a session variable so it can be
      retrieved and applied to the form after the user logs in.
   The method returns false.
   
   Logged In:
   The gcLogin application is removed from the list of apps.
   The required perm weight for this page is reset to 0.
   All the remaining applications re-determine their required permission weight.
   The original action session var is deleted.
   The method returns checkAuth()'s result, which is true if the user is logged
      in with a high enough permission weight and false otherwise.
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if the user is logged in with sufficient permission weight.
      false otherwise.
      
   :Notes:
   **/
   function runLogin()
   {
      $this->page->loadApplication("gcLogin");
      $this->page->applications["gcLogin"]->run();
      if ($this->getAuthOK())
      {
         $this->page->removeApplications("gcLogin");
         $this->resetReqPermWeight();
         foreach ($this->page->applications as $appName => $appObj)
         {
            // re-determine required permissions, which may change based on 
            // login state.
            $appObj->determineRequiredPermission();
         }
         //unset($_SESSION['original action']);
         return $this->checkAuth();
      }
      else
      {
         $this->msgCenter->addError("You must log in before using this page");
         $okApps = array("gcTop", "gcLogin");
         $removeApps = array_diff(array_keys($this->page->applications), $okApps);
         foreach ($removeApps as $appName)
         {
            $this->page->removeApplications($appName);
         }
         $this->setReqPermWeight(0);
         $this->page->loadApplication("gcBottom");
         reset($this->page->applications);
         $_SESSION['original action'] = IsSet($_GET['id']) ? "viewing" : "entering";
         return false;
      }
   }
   
   
   /**
   :Function: userOwnsRecord()
   
   :Description:
   This utility function compares the passed in record owner ID to the user_id
   of the logged in user ('user_id' should be a key in $_SESSION). If the 
   logged in user's ID matches the record owner id, then the user owns this
   record and can (presumably) edit it.
   
   If the user isn't logged in, this function returns false.
   
   :Parameters:
   int $recordOwnerID - the ID of the user who owns whatever record is being
      considered for viewing or updating.
   
   :Return Value:
   boolean - true if the user owns this record, false otherwise. Also false if
      the user is not logged in.
   
   :Notes:
   **/
   function userOwnsRecord($recordOwnerID)
   {
      if ($this->getAuthOK())
      {
         return $recordOwnerID == $_SESSION['userID'];
      }
      return false;
   }
   
   
   /**
   :Function: setUser()
   
   :Description:
   Sets the user name for this object. This should be an email address.
   
   :Parameters:
   string $user - the user name to log in with (email address)
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setUser($userName)
   {
      //debug_print_backtrace();
      $this->user = $userName;
   }
   
   
   /**
   :Function: getUser()
   
   :Description:
   returns the user name to log in with (email address). If user is not already
   set, this function will check in the $_SESSION global var to see if the
   value exists there and use that value if it's set. If it's not set there,
   the user isn't logged in and this method will return "".
   
   :Parameters:
   None.
   
   :Return Value:
   string - the login name for this object.
   
   :Notes:
   **/
   function getUser()
   {
      if ($this->user == "")
      {
         if (IsSet($_SESSION['userEmail']))
         {
            $this->setUser($_SESSION['userEmail']);
         }
      }
      return $this->user;
   }
   
   
   function setUserName($name)
   {
      $this->userName = $name;
   }
   
   
   function getUserName()
   {
      if ($this->userName == "")
      {
         if (IsSet($_SESSION['userName']))
         {
            $this->setUserName($_SESSION['userName']);
         }
      }
      return $this->userName;
   }
   
   
   /**
   :Function: setUserID()
   
   :Description:
   Sets the user ID for this user. This is the DB row ID number.
   
   :Parameters:
   int - the row ID for this user.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setUserID($id)
   {
      $this->userID = $id;
   }
   
   /**
   :Function: getUserID()
   
   :Description:
   Returns the row id for this user. If this value isn't already set, this
   method will look in the $_SESSION global var for it and return that value.
   If the value isn't set in $_SESSION, the user isn't logged in and this
   method will return 0.
   
   :Parameters:
   None.
   
   :Return Value:
   int - the row ID for this user. 0 if the user is not logged in.
   
   :Notes:
   **/
   function getUserID()
   {
      if ($this->userID == "")
      {
         if (IsSet($_SESSION['userID']))
         {
            $this->setUserID($_SESSION['userID']);
         }
      }
      return $this->userID;
   }
   
   
   /**
   :Function: isAdmin()
   
   :Description:
   Returns true if the user is auth'd in and is an admin (their permission
   weight is equal to the admin permission weight).
   
   :Parameters:
   None.
   
   :Return Value:
   boolean - true if auth'd in user is an admin, false if not.
   
   :Notes:
   **/
   function isAdmin()
   {
      if ($this->getAuthOK())
      {
         if ($this->getUserPermWeight() == GC_AUTH_ADMIN)
         {
            return true;
         }
      }
      return false;
   }
   
   
   /**
   :Function: clearAuth()
   
   :Description:
   Clears out all auth variables in session, server and this class.
   
   :Parameters:
   None.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function clearAuth()
   {
      unset($_SESSION['userEmail']);
      unset($_SESSION['userID']);
      unset($_SESSION['userName']);
      unset($_SESSION['userPermName']);
      unset($_SESSION['userPermWeight']);
      unset($_SERVER['PHP_AUTH_USER']);
      unset($_SERVER['PHP_AUTH_PW']);
      unset($this->user);
      unset($this->pass);
      $this->setUserPermWeight(0);
      $this->setAuthOK(false);
   }
}
?>
