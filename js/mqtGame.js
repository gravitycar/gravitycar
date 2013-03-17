if (typeof window.top.gc == "undefined")
{
   window.top.gc = new Object();
}

window.top.gc.mqtg = {
   name: "movie quote trivia game",
   currentQuestionIndex: 0,
   numberCorrectlyAnswered: 0,
   
   init: function()
   {
      this.form = $("#mqtForm");
      this.questions = questions;
      this.indexQuestions();
      var firstQuestion = this.getQuestionContainer(this.questions[0]);
      this.form.append(firstQuestion);
      $("#mqtQuestion_" + this.questions[this.currentQuestionIndex].id).show('slow');
      this.score = $("#mqtScore");
   },
   
   
   indexQuestions: function()
   {
      this.indexedQuestions = new Object();
      for (i = 0; i < this.questions.length; i++)
      {
         var question = this.questions[i];
         this.indexedQuestions[question.id] = question;
      }
   },
   
   
   checkAnswer: function(evt)
   {
      var game = window.top.gc.mqtg;
      var parts = evt.target.name.split("_");
      var questionID = parts[1];
      var question = game.indexedQuestions[questionID];
      var movieID = question.mid;
      var answerID = evt.target.value;
      var quoteDivID = "#mqtQuote_" + questionID;
      var quote = $(quoteDivID);
      
      if (movieID == answerID)
      {
         quote.css('background-color', "#0F0");
         var correct = true;
      }
      else
      {
         quote.css('background-color', "red");
         var correct = false;
      }
      $("input[name=answer_" + questionID + "]:radio").attr("disabled","disabled");
      $("input[name=answer_" + questionID + "]:radio").unbind("click");
      $("input[name=answer_" + questionID + "]:radio").blur();
      game.updateScore(correct);
      game.showNextQuestion();
   },
   
   
   updateScore: function(correct)
   {
      if (correct)
      {
         this.numberCorrectlyAnswered++;
      }
      var percentage = this.getPercentageCorrect();
      this.score.text(percentage + "%")
   },
   
   
   getPercentageCorrect: function()
   {
      var questionsAsked = this.currentQuestionIndex + 1;
      
      if (this.numberCorrectlyAnswered > 0 && questionsAsked > 0)
      {
         var percentage = (this.numberCorrectlyAnswered / questionsAsked) * 100;
         percentage = Math.round(percentage);
      }
      else
      {
         var percentage = 0;
      }
      return percentage;
   },
   
   
   showNextQuestion: function()
   {
      if (this.currentQuestionIndex < this.questions.length - 1)
      {
         this.currentQuestionIndex++;
         var nextQuestion = this.getQuestionContainer(this.questions[this.currentQuestionIndex]);
         this.form.prepend(nextQuestion);
         $("#" + nextQuestion.id).show("slow");
      }
      else
      {
         this.showFinalScore();
      }
   },
   
   
   showFinalScore: function()
   {
      var container = document.createElement("DIV");
      container.className = "mqtQuestionContainer";
      container.id = "finalScore";
      
      var title = document.createTextNode("Congratulations! You have answered all the questions.");
      var h2 = document.createElement("h2");
      h2.appendChild(title);
      
      var text = "Your final score was " + this.score.text();
      text += " That means you " + this.interpretScore(this.getPercentageCorrect());
      text = document.createTextNode(text);
      
      container.appendChild(h2);
      container.appendChild(text);
      this.form.prepend(container);
      $(container).show('slow');
   },
   
   
   interpretScore: function(score)
   {
      var index = Math.round(score / 10);
      var scoreMeanings = new Object();
      scoreMeanings[0] = "never, ever, ever watch movies. I weep for you.";
      scoreMeanings[1] = "should really get out more.";
      scoreMeanings[2] = "own a television and watched once, to see \"Nick at Nite\".";
      scoreMeanings[3] = "are too well-adjusted to play this game. Go back to your spreadsheet.";
      scoreMeanings[4] = "are not a geek, but you're not well-adjusted either. It's the worst of both worlds! Aiiigh!";
      scoreMeanings[5] = "like movies, but you don't live for them.";
      scoreMeanings[6] = "are trying to date the hottie at blockbuster. It's not gonna happen.";
      scoreMeanings[7] = "are a lot of fun playing D&D even when you're sober.";
      scoreMeanings[8] = "are a stock-holder with NetFlix.";
      scoreMeanings[9] = "are a tan-less geek god.";
      scoreMeanings[10] = "are cheating, probably via imdb.com.";
      
      return scoreMeanings[index];
   },
   
   
   getQuestionContainer: function(question)
   {
      var container = document.createElement("DIV");
      container.className = "mqtQuestionContainer";
      container.id = "mqtQuestion_" + question.id;
      
      var quoteText = document.createTextNode(question.quote);
      var quote = document.createElement("DIV");
      quote.className = "mqtQuote";
      quote.id = "mqtQuote_" + question.id;      
      quote.appendChild(quoteText);
      container.appendChild(quote);
      
      var speakerText = document.createTextNode("--" + question.speaker);
      var speakerDiv  = document.createElement("DIV");
      speakerDiv.className = "mqtSpeaker";
      speakerDiv.id = "mqtSpeaker_" + question.id;
      speakerDiv.appendChild(speakerText);
      container.appendChild.speakerDiv;
      
      var answersContainer = document.createElement("DIV");
      answersContainer.className = "mqtAnswersContainer";
      answersContainer.id = "mqtAnswersContainer_" + question.id;
      container.appendChild(answersContainer);
      
      for (movieID in question.answers)
      {
         var movieName = question.answers[movieID];
         var radioButtonContainer = document.createElement("DIV");
         radioButtonContainer.className = "mqtRadioButtonContainer";
         
         var radio = document.createElement("INPUT");
         radio.type = "radio";
         radio.name = "answer_" + question.id;
         radio.value = movieID;
         //radio.onclick = this.checkAnswer;
         $(radio).click(this.checkAnswer)
         radioButtonContainer.appendChild(radio);
         
         var radioTextContainer = document.createElement("DIV");
         radioTextContainer.className = "mqtMovieNameContainer";
         var radioText = document.createTextNode(movieName);
         radioTextContainer.appendChild(radioText);
         
         var answerContainer = document.createElement("DIV");
         answerContainer.className = "mqtAnswerContainer";
         answerContainer.appendChild(radioButtonContainer);
         answerContainer.appendChild(radioTextContainer);
         
         answersContainer.appendChild(answerContainer);
      }
      return container;
   }
}


function startGame()
{
   var game = window.top.gc.mqtg;
   game.init();
   
}
