function presetInvitations()
{
   if ($('select.csleft').length == 0)
   {
      return;
   }
   
   for (index = 0; index < $('select.csleft')[0].options.length; index++)
   {
      if (typeof $('select.csleft')[0].options[index].value == "undefined")
      {
         continue;
      }
         
      var userID = $('select.csleft')[0].options[index].value;
      if (invitedGuestsList[userID])
      {
         $('select.csleft')[0].options[index].selected = true;
      }
   }
   $('input.csadd').click();
}

function setUpComboSelect()
{
   $('#usersList').comboselect({
      lflbl: 'All Users', 
      rtlbl: 'Invited Users', 
      sort: 'both', 
      rembtn: '<< Rem', 
      addbtn: 'Add >>'});
}

$(document).ready(setUpComboSelect);
$(document).ready(presetInvitations);
