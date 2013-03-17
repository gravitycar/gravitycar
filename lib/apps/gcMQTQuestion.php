<?php

require_once("lib/classes/Form.inc");
require_once("lib/classes/emailer.inc");
class gcMQTQuestion extends gcForm implements Application
{
   public $quote = "";
   public $id;
   public $asked = false;
   public $percentageCorrect = 0;
   public $possibleAnswers = array();
   public $movie_id = false;
   public $movieName = "";
   public $movieList = array();
   public $characterName = "";
   public $renderMode = "Form";
   public $user_id = 0;
   public $requiredPermWeight = GC_AUTH_USER;
   
   
   /**
   :Function: gcMQTQuestion
   
   :Description:
   The constructor for the class. Takes two optional arguments, one for the
   required permission weight to access the class, another for the render
   mode.
   
   The question always amounts to "what movie is this quotation from?" Every
   question object should come with a correct answer, which is the movie_id
   property of this object. When questions are rendered in the game, they
   will have 3 possible answers, and the game is responsible for providing the
   additional two answers. The question only provides its own, correct, answer.
   
   :Parameters:
   int $requiredPermWeight - the permission level necessary to access the class.
      Defaults to GC_AUTH_USER but for the game, anyone can play so it's passed
      GC_AUTH_ALL in that case.
   string $renderMode - in what mode should the question be rendered? Currently
      only "form" works, which is the default.
   
   :Return Value:
   gcMQTQuestion - the instance of the question object.
   
   :Notes:
   **/
   function gcMQTQuestion($requiredPermWeight=GC_AUTH_USER, $renderMode="Form")
   {
      $this->init();
      $this->requiredPermWeight = $requiredPermWeight;
      $this->setRenderMode($renderMode);
      $this->determineRequiredPermission($this->requiredPermWeight);
      $this->setFormProps("title", "id", "quote", "movie_id", "movieName", 
                          "characterName", "user_id");
      $this->setInputAttr("title", "type", "heading");
      $this->setInputAttr("title", "value", $this->getFormTitle());
      $this->setInputAttr("id", "type", "hidden");
      $this->setInputAttr("quote", "type", "textarea");
      $this->setInputAttr("quote", "cols", "80");
      $this->setInputAttr("quote", "rows", "3");
      $this->setInputAttr("movie_id", "type", "select");
      $this->setInputAttr("characterName", "type", "text");
      $this->setInputAttr("characterName", "size", "30");
      $this->setInputAttr("movieName", "type", "text");
      $this->setInputAttr("movieName", "size", "60");
      $this->setInputAttr("user_id", "type", "hidden");
      $this->setInputAttr("user_id", "value", $this->auth->getUserID());
      
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("deleteButton", "type", "delete");
      $this->setInputAttr("deleteButton", "value", "Delete");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setInputAttr("newButton", "type", "new");
      $this->setInputAttr("newButton", "value", "New Quote");
      $this->setControlButtons("submitButton", "cancelButton");
      
      $this->setInputParams("characterName", "label", "Spoken By");
      //$this->setInputParams("movie_id", "options", $this->getAllMovieIDs());
      
      $this->setValidationRule("quote", "notEmpty");
      $this->setValidationRule("characterName", "notEmpty");
      $this->setValidationRule("movie_id", "notEmpty");
   }
   
   
   function setRenderMode($mode)
   {
      $mode = ucfirst($mode);
      switch ($mode)
      {
         case "Form":
         case "Game":
         case "JSON":
            $this->renderMode = $mode;
         break;
         
         default:
            $this->msgCenter->addDebug();
         break;
      }
   }
   
   
   function getRenderMode()
   {
      return $this->renderMode;
   }
   
   
   function setMovieList($movieList)
   {
      $this->movieList = $movieList;
   }
   
   
   function render()
   {
      $methodName = "render" . $this->getRenderMode();
      if (method_exists($this, $methodName))
      {
         $this->htmlObjects = call_user_func(array(__CLASS__, $methodName));
      }
      
      return $this->htmlObjects;
   }
   
   
   function renderJSON()
   {
      $simpleObj = (object) array();
      $simpleObj->id = $this->id;
      $simpleObj->quote = html_entity_decode($this->quote, ENT_QUOTES);
      $simpleObj->speaker = html_entity_decode($this->characterName);
      foreach ($this->possibleAnswers as $id => $filmTitle)
      {
         $simpleObj->answers[$id] = html_entity_decode($filmTitle);
      }
      $simpleObj->mid = $this->movie_id;
      
      return json_encode($simpleObj);
   }
   
   
   function renderForm()
   {
      $this->setInputParams("movie_id", "options", $this->getAllMovieIDs());
      return $this->buildForm();
   }
   
   
   function renderGame()
   {
      $attrs = array("class"=>"mqtQuestionContainer", "id"=>"mqtQuestion_" . $this->id);
      $container = $this->renderer->createDivTag(false, $attrs);
      
      $attrs = array("class"=>"mqtQuote", "id"=>"mqtQuote_" . $this->id);
      $quote = $this->renderer->createDivTag($this->quote, $attrs, &$container);
      
      $attrs = array("class"=>"mqtSpeaker", "id"=>"mqtSpeaker_" . $this->id);
      $speaker = $this->renderer->createDivTag($this->characterName, $attrs, &$quote);
      
      $attrs = array("class"=>"mqtAnswersContainer", "id"=>"mqtAnswers_" . $this->id);
      $answersContainer = $this->renderer(false, $attrs, &$container);
      
      
   }
   
   
   function getAllMovies()
   {
      $query = $this->db->getSelectQuery("gcMQTMovies", "*");
      //$query->addWhereClause("id", "!=", $this->movie_id, "gcMQTMovies");
      $result = $this->db->runQuery($query);
      $data = $this->db->getHash(&$result);
      list($wrongAnswer1, $wrongAnswer2) = array_rand($data, 2);
      
      if ($data[$wrongAnswer1]['id'] == $this->movie_id)
      {
         $wrongAnswer1--;
      }
      
      if ($data[$wrongAnswer2]['id'] == $this->movie_id)
      {
         $wrongAnswer2++;
      }
   }
   
   
   function run()
   {
      if ($this->getRenderMode() == "Game")
      {
         $this->getQuoteData();
         return true;
      }
      
      switch ($this->determineAction())
      {
         case "updating":
            // editing existing quote. Validate ID.
            $this->setIDValidation("This isn't a valid movie quote.");
            $this->setFormTitle("Quote Updated");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
            $this->addControlButton("newButton");
         break;
         
         case "creating":
            $this->setFormTitle("New Quote Added");
            $this->importFromPost();
            $this->save();
            $this->addControlButton("deleteButton");
            $this->addControlButton("newButton");
         break;
         
         case "deleting":
            $this->delete();
            $this->setFormTitle("Quote Deleted");
            $this->addControlButton("newButton");
         break;
         
         case "entering":
            $this->setFormTitle("Enter New Movie Quote");
         break;
         
         case "viewing":
         default:
            $this->setIDValidation("Sorry, we could not locate the requested quote.");
            $this->setFormTitle("Update Movie Quote");
            $this->getQuoteData($_GET['id']);
            $this->addControlButton("deleteButton");
         break;
      }
   }
   
   
   function getQuoteData($id)
   {
      
      $id = $this->id ? $this->id : $id;
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $where = array("id" => $id);
      $result = $this->db->runSelectQuery("gcMQTQuotes", "*", $where);
      
      if ($result)
      {
         if ($this->db->getNumRecords($result) == 1)
         {
            $data = $this->db->getRow($result);
         }
         else
         {
            $this->msgCenter->addError("There is no quote with the ID you have requested.");
         }
      }
      else
      {
         $this->msgCenter->addError("Requested quote cannot be displayed.");
         $data = array();
      }
      
      $this->importFromHash($data);
      $this->db->close_result($result);
      return $data;
   }
   
   
   function save()
   {
      if (!$this->validationPassed)
      {
         $this->msgCenter->addError("Could not save changes: validation failed.");
         return false;
      }
      
      if (empty($_POST['id']))
      {
         return $this->createQuote();
      }
      else
      {
         return $this->updateQuote();
      }
   }
   
   
   function getMovieIDFromTitle($title)
   {
      $title = strtolower($title);
      $where = array("lower(movieName)" => "$title");
      $query = $this->db->getSelectQuery("gcMQTMovies", "*", $where);
      $query->whereClauses[0]->tableName = "";
      
      $result = $this->db->runQuery($query);
      
      if ($this->db->getNumRecords($result) > 0)
      {
         $row = $this->db->getRow($result);
         $id = $row['id'];
         return $id;
      }
      else
      {
         return 0;
      }
   }
   
   
   function createNewMovie($movieName)
   {
      $query = $this->db->getInsertQuery("gcMQTMovies", array("movieName" => $movieName));
      $movieAddedOK = $this->db->runQuery($query);
      if ($movieAddedOK)
      {
         $this->setInputParams("movie_id", "options", $this->getAllMovieIDs());
         return $this->db->getNewRowID();
      }
      else
      {
         $this->msgCenter->addError("Could not add $movieName to movie db.");
         return false;
      }
   }
   
   
   
   function establishMovieID($movieName)
   {
      $id = $this->getMovieIDFromTitle($movieName);
      if ($id == 0)
      {
         $id = $this->createNewMovie($movieName);
      }
      
      return $id;
   }
   
   
   function createQuote()
   {
      if (!$this->movie_id)
      {
         $this->movie_id = $this->establishMovieID($this->movieName);
      }
      
      if (!$this->movie_id)
      {
         $this->msgCenter->addError("Could not add quote. No movie selected or entered.");
         return false;
      }
      
      $this->addSkipProp("id");
      $this->addSkipProp("movieName");
      $data = $this->exportToHash();
      $query = $this->db->getInsertQuery("gcMQTQuotes", $data);
      $createOK = $this->db->runQuery($query);
      
      if ($createOK && $this->movie_id)
      {
         $sendMsg = new gcEMailer("New Movie Quote", $this->auth->getUserName() . "\n" .  implode("\n", $_POST));
         $this->msgCenter->addAlert("New Quote Added");
         $this->id = $this->db->getNewRowID();
         $this->removeSkipProp("id");
         $this->removeSkipProp("movieName");
         $this->db->close_result($createOK);
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   
   function updateQuote()
   {
      if (!$this->movie_id)
      {
         $this->movie_id = $this->establishMovieID($this->movieName);
      }
      
      if (!$this->movie_id)
      {
         $this->msgCenter->addError("Could not add quote. No movie selected or entered.");
         return false;
      }
      
      $this->addSkipProp("id");
      $this->addSkipProp("movieName"); // you want to skip this, it's not in the table
      $data = $this->exportToHash();
      $where = array('id' => $this->id);
      $query = $this->db->getUpdateQuery("gcMQTQuotes", $data, $where);
      $updateOK = $this->db->runQuery($query);
      
      $this->removeSkipProp("id");
      
      if ($updateOK)
      {
         $this->msgCenter->addAlert("Quote Updated!");
         $this->db->close_result($updateOK);
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   function delete()
   {
      $this->setIDValidation("The ID of the quote you are trying to delete is not valid.");
      $id = $_POST['id'];
      
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $where = array('id'=>$id);
      $query = $this->db->getDeleteQuery("gcMQTQuotes", $where);
      $deleteOK = $this->db->runQuery($query);
      
      if ($deleteOK)
      {
         $this->msgCenter->addAlert("Movie Quote was deleted successfully.");
      }
      $this->db->close_result($deleteOK);
   }
   
   
   function getAllMovieIDs()
   {
      if (!$this->movieList)
      {
         $results = $this->db->runSelectQuery("gcMQTMovies", "*", "", "movieName ASC");
         $data = $this->db->getHash(&$results);
         
         $hash = array();
         $hash[0] = "New Movie";
         foreach ($data as $row)
         {
            $hash[$row['id']] = $row['movieName'];
         }
         $this->movieList = $hash;
      }
      
      return $this->movieList;
   }
   
   function setMovieName($name)
   {
      $this->movieName = $name;
   }
   
   
   function getMovieName()
   {
      return $this->movieName;
   }
   
   
   function setPossibleAnswers($answersHash)
   {
      $this->possibleAnswers = $answersHash;
   }
   
   
   function determineRequiredPermission()
   {
      $this->auth->setReqPermWeight($this->requiredPermWeight);
   }
   
   
   
}
?>
