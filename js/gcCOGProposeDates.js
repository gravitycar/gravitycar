$().ready(createDatePicker);
$().ready(populateProposedDates);



function createDatePicker()
{
   var containerID = "calDiv";
   var calendarID = "proposedDateCalendar";
   var now = new Date();
   var container = document.getElementById(containerID);
   picker = new datePicker(now.getTime(), container, calendarID, saveDate);
}

function populateProposedDates()
{
   for (rowID in proposedDates)
   {
      var timestamp = new Number(proposedDates[rowID]) * 1000;
      buildSavedDate(timestamp, rowID);
   }
}
