function showUserGifts(userID)
{
   var container = document.getElementById("allGiftListsContainer");
   var IDOfListToShow = "giftList_" + userID;
   for (index in container.children)
   {
      var child = container.children[index];
      if (child.tagName != "DIV")
      {
         continue;
      }
      
      if (child.style)
      {
         if (child.id == IDOfListToShow)
         {
            child.style.display = "block";
         }
         else
         {
            child.style.display = "none";
         }
      }
   }
   
   var userList = document.getElementById("usersList");
   for (index in userList.children)
   {
      var listItem = userList.children[index];
      if (listItem.id == "userListItem_" + userID)
      {
         listItem.className = "selectedList";
      }
      else
      {
         listItem.className = "";
      }
   }
}

function showFirstList()
{
   var container = document.getElementById("allGiftListsContainer");
   for (index in container.children)
   {
      var child = container.children[index];
      if (child.className == "giftListContainer")
      {
         var id = child.getAttribute("userID");
         showUserGifts(id);
         break;
      }
   }
}


function updatePurchasedState(checkbox)
{
   var xmlhttp=new XMLHttpRequest();
   var id = checkbox.getAttribute('id');
   var purchased = checkbox.checked ? 1 : 0;
   var params = "id=" + id + "&purchased=" + purchased + "&task=setGiftPurchasedState";
   
   xmlhttp.open("POST","ajaxUpdate.php",true);
   xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   xmlhttp.setRequestHeader("Content-length", params.length);
   xmlhttp.setRequestHeader("Connection", "close");
   
   xmlhttp.send(params);
  
   xmlhttp.onreadystatechange=function()
   {
      switch (xmlhttp.readyState)
      {
        case 0:
           
        break;
      
        case 1:
           
        break;
      
        case 2:
           
        break;
      
        case 3:
           
        break;
      
        case 4:
           if (checkbox.checked)
           {
              alert("This gift is now marked as purchased.");
           }
           else
           {
              alert("This gift is no longer marked as purchased.");
           }
        break;
      }
   }
}


window.addOnLoad(showFirstList);
