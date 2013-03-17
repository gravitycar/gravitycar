<?php
/**

movieQuoteTrivia!


player - optional. Warn non-logged in players to log in to record their scores.
score - number of questions asked
      - number anwered correctly
      - percent correct

question - quotation from a movie
         - array of possible answers
         - correct answer
         - number of times asked
         - number of times answered correctly.
         - asked already or not asked already?
         
questions order - the game needs to establish a random order of all questions
         to ask, and ask them in that order.

player history - which players have answered which questions correctly.


Game can either maintain state via $_SESSION variables to track which questions
have been asked or it can use javascript and ajax to query for a new question
after one has been asked. 

The $_SESSION method has the virtue of not needing ajax and DHTML libraries that
I haven't written yet.

The JS method is much, much cooler!

**/

require_once("lib/apps/gcMQTQuestion.php");
class gcMovieQuoteTrivia extends gcForm implements Application
{
   private $player;
   private $score = 0;
   private $quotes = array();
   private $questions = array();
   private $movies = array();
   private $questionOder = array();
   private $history = array();
   private $answeredQuestionID = false;
   private $mode = "game";
   
   function gcMovieQuoteTrivia()
   {
      $this->init();
      $this->setInputAttr("submitButton", "type", "submit");
      $this->setInputAttr("submitButton", "value", "Save");
      $this->setInputAttr("cancelButton", "type", "back");
      $this->setInputAttr("cancelButton", "value", "Back");
      $this->setControlButtons("submitButton", "cancelButton");
      $this->page->addJavascriptFile("js/jquery"); // points to symlink.
      $this->page->addJavascriptFile("js/mqtGame.js"); // points to symlink.
   }
   
   
   function run()
   {
      switch ($this->getMode())
      {
         case "game":
            $this->setupGame();
         break;
      
         case "list":
            $this->setupList();
         break;
      }
   }
   
   
   function setMode($mode)
   {
      $this->mode = $mode;
   }
   
   
   function getMode()
   {
      return $this->mode;
   }
   
   
   function setupList()
   {
      $this->getAllQuotes();
   }
   
   
   function setupGame()
   {
      // get a list of all quotes.
      $this->getSomeQuotes(13);
      
      // get a list of all unique movies.
      $this->getAllMovies();
      
      // loop through all the questions.
      foreach ($this->quotes as $quoteHash)
      {
         $question =& new gcMQTQuestion(GC_AUTH_ALL, "JSON");
         $question->setMovieList($this->movies);
         
         // populate question data
         $question->importFromHash($quoteHash);
         
         // set the correct answer for each quote.
         $question->setMovieName($this->movies[$question->movie_id]);
         
         // set one correct and two wrong answers for each quote.
         $question->setPossibleAnswers($this->generatePossibleAnswers($question));
         
         $this->questions[] =& $question;
      }
   }
   
   
   function generatePossibleAnswers($question)
   {
      $possibleAnswers[$question->movie_id] = $question->movieName;
      while (count($possibleAnswers) < 3)
      {
         $randKey = array_rand($this->movies);
         $possibleAnswers[$randKey] = $this->movies[$randKey];
      }
      
      $shuffledAnswers = array();
      $shuffledKeys = array_keys($possibleAnswers);
      shuffle($shuffledKeys);
      
      foreach ($shuffledKeys as $key)
      {
         $shuffledAnswers[$key] = $possibleAnswers[$key];
      }
      return (array)$shuffledAnswers;
   }
   
   
   function getAllMovies()
   {
      if ($this->movies)
      {
         return $this->movies;
      }
      
      $result = $this->db->runSelectQuery("gcMQTMovies", "*");
      $data = $this->db->getHash($result);
      
      foreach ($data as $movie)
      {
         $this->movies[$movie['id']] = $movie['movieName'];
      }
      
      return $this->movies;
   }
   
   
   function getSomeQuotes($number)
   {
      if ($this->quotes)
      {
         return $this->quotes;
      }
      
      $query = $this->db->getSelectQuery("gcMQTQuotes", "*");
      $query->addLimitClause($number);
      $query->addOrderByClause('RAND()');
      $result = $this->db->runQuery($query);
      $this->quotes = $this->db->getHash($result);
      return $this->quotes;
   }
   
   
   function getAllQuotes()
   {
      if ($this->quotes)
      {
         return $this->quotes;
      }
      
      $result = $this->db->runSelectQuery("gcMQTQuotes", "*");
      $this->quotes = $this->db->getHash($result);
      return $this->quotes;
   }
   
   
   function render()
   {
      switch($this->getMode())
      {
         case "game":
            return $this->renderGame();
         break;
         
         case "list":
            return $this->renderList();
         break;
      }
   }
   
   
   function &renderList()
   {
      $result = $this->db->runSelectQuery("gcMQTQuotes", array('id', 'quote'));
      $list = $this->buildEditList(&$result, "addMovieQuote.php", "id", "quote");
      return $list;
   }
   
   
   function renderGame()
   {
      $this->htmlObjects[] =& $this->buildIntroDiv();
      $form = $this->renderer->createFormTag("", array("id"=>"mqtForm"));
      $this->htmlObjects[] = &$form;
      
      $simpleQuestions = array();
      foreach ($this->questions as $question)
      {
         $question->setRenderMode("JSON");
         $simpleQuestions[] = $question->render();
      }
      shuffle($simpleQuestions);
      $js .= "var questions = [" . join(",", $simpleQuestions) . "]";
      $js .= "\n\$(document).ready(startGame);";
      $this->renderer->createJavascriptTag($js, array("type"=>"text/javascript"), $this->renderer->head);
      
      return $this->htmlObjects;
   }
   
   
   function &buildIntroDiv()
   {
      $welcome = "Welcome to my Movie Quote Trivia game.";
      $welcomeSpan = $this->renderer->createH3Tag($welcome);
      $intro = "Just read the quotation below, and the click on the radio button
               next to the name of the film you think the quote is from. Your
               percentage score will be shown at right. Good luck! <br><br><br>
               ";
      $introDiv = $this->renderer->createDivTag(false, array("class"=>"instructions"));
      $scoreDiv = $this->renderer->createDivTag("0%", array("id"=>"mqtScore"));
      $introDiv->addContents($scoreDiv);
      $introDiv->addContents($welcomeSpan);
      $introDiv->addContents($intro);
      return $introDiv;
   }
   
   
   function renderControlButtons()
   {
      $this->table = $this->renderer->createTableTag("", array("width"=>"100%"));
      $this->buildControlButtons();
      return $this->table;
   }
   
   
   function renderQuestion(&$question)
   {
      $attrs = array("class"=>"mqtQuestionContainer", "id"=>"mqtQuestion_" . $question->id);
      $container = $this->renderer->createDivTag(false, $attrs);
      
      $attrs = array("class"=>"mqtQuote", "id"=>"mqtQuote_" . $question->id);
      $quote = $this->renderer->createDivTag($question->quote, $attrs, &$container);
      
      $attrs = array("class"=>"mqtSpeaker", "id"=>"mqtSpeaker_" . $question->id);
      $speaker = $this->renderer->createDivTag("--" . $question->characterName, $attrs, &$quote);
      
      $attrs = array("class"=>"mqtAnswersContainer", "id"=>"mqtAnswers_" . $question->id);
      $answersContainer = $this->renderer->createDivTag(false, $attrs, &$container);
      
      foreach ($question->possibleAnswers as $movieID => $movieName)
      {
         $radioAttrs = array('name'=>"answer_" . $question->movie_id, 'value'=>$movieID);
         $radioButton = $this->renderer->createRadioButtonTag($radioAttrs);
         
         $radioButtonContainer = $this->renderer->createDivTag($radioButton, array("class"=>"mqtRadioButtonContainer"));
         $textContainer = $this->renderer->createDivTag($question->possibleAnswers[$movieID], array("class"=>"mqtMovieNameContainer"));
         
         $answerContainer = $this->renderer->createDivTag(false, array("class"=>"mqtAnswerContainer"));
         $answerContainer->addContents(&$radioButtonContainer);
         $answerContainer->addContents(&$textContainer);
         
         $answersContainer->addContents($answerContainer);
      }
      
      return $container;
   }
}

?>
