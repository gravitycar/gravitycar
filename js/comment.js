$(document).ready(function (){$("#submitButton").click(submitComment)});


function submitComment(evt)
{
   var data = $("#commentForm").serializeArray();
   var url = "recordComment.php";
   var callBack = successfullySavedComment;
   var dataType = "html";
   $("#submitButton").ajaxError(commentError);
   jQuery.post(url, data, callBack, dataType);
}


function commentError(jEvent, http, ajaxOptions, error)
{
   try
   {
      http.status;
      http.statusText;
      alert("An error has occurred. Error " + http.status + ": " + http.statusText);
   }
   catch(e)
   {
      //alert(e);
   }   
}


function successfullySavedComment(dataFromServer)
{
   try
   {
      $("#allCommentsContainer").prepend(dataFromServer);
   }
   catch(e)
   {
      //alert(e);
   }
}
