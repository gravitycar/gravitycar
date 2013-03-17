function datePicker(timestamp, containerDiv, id, dateClickFunc)
{
   this.setDateClick(dateClickFunc);
	this.init(timestamp, containerDiv, id);
}

datePicker.prototype.startDate = null;
datePicker.prototype.thisMonth = null;
datePicker.prototype.lastMonth = null;
datePicker.prototype.nextMonth = null;
datePicker.prototype.calendarMonthStartsOn = null;
datePicker.prototype.months = new Array("JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC");
datePicker.prototype.days = new Array("S", "M", "T", "W", "T", "F", "S");
datePicker.prototype.daysInMonth = -1;
datePicker.prototype.monthIndex = -1;
datePicker.prototype.firstOfMonthIndex = -1;
datePicker.prototype.numberOfWeeks = -1;
datePicker.prototype.container = null;
datePicker.prototype.table = null;
datePicker.prototype.navRow = null;
datePicker.prototype.headerRow = null;
datePicker.prototype.allRows = new Array();
datePicker.prototype.onClick = null;
datePicker.prototype.id = null;
datePicker.prototype.now = new Date();


datePicker.prototype.init = function(timestamp, containerDiv, id)
{
	this.setDate(timestamp);
	this.setContainer(containerDiv);
	this.setID(id);
	this.buildTable();
}

datePicker.prototype.setDate = function(timestamp)
{
	this.startDate = new Date(timestamp);
	this.firstOfMonth = new Date(timestamp);
	this.lastMonth = new Date(timestamp);
	this.nextMonth = new Date(timestamp);
	this.calendarMonthStartsOn = new Date(timestamp);
	this.monthIndex = this.startDate.getMonth();
   
   this.lastMonth.setDate(1);
   this.nextMonth.setDate(1);
	this.lastMonth.setMonth(this.startDate.getMonth() - 1);
	this.nextMonth.setMonth(this.startDate.getMonth() + 1);
	this.firstOfMonth.setDate(1);
	this.firstOfMonthIndex = this.firstOfMonth.getDay();
	this.calendarMonthStartsOn.setDate(this.firstOfMonth.getDate() - this.firstOfMonthIndex);

	var tempMonth = new Date(timestamp);
	tempMonth.setMonth(tempMonth.getMonth() + 1);
	tempMonth.setDate(1);
	tempMonth.setDate(tempMonth.getDate() - 1);
	this.daysInMonth = tempMonth.getDate();

	this.numberOfWeeks = Math.ceil((1 + this.firstOfMonthIndex + this.daysInMonth) / 7);
}

datePicker.prototype.setDateClick = function(funcRef)
{
   this.dateClick = funcRef;
}


datePicker.prototype.buildWeekRows = function()
{
	var monthStartsTimestamp = this.calendarMonthStartsOn.getTime();
	var calendarDayDateObj = new Date(monthStartsTimestamp);
	for (w = 0; w < this.numberOfWeeks; w++)
	{
		var weekRow = this.table.insertRow(this.table.rows.length);
		for (d = 0; d < this.days.length; d++)
		{
			calendarDayDateObj.setTime(monthStartsTimestamp);
			var startDay = this.calendarMonthStartsOn.getDate();
			var dayCell = weekRow.insertCell(d);
         
			dayCell.className = "datePickerCell";
			var calendarDayIndex = (w * 7) + d;
			calendarDayDateObj.setDate(startDay + calendarDayIndex);
         
         if (this.now.getDate() === calendarDayDateObj.getDate() && this.now.getMonth() == calendarDayDateObj.getMonth() && this.now.getFullYear() == calendarDayDateObj.getFullYear())
         {
            dayCell.className += " currentDate";
         }
         
			if (calendarDayDateObj.getMonth() == this.monthIndex)
			{
				var dayNode = document.createTextNode(calendarDayDateObj.getDate());
				var dayLink = document.createElement("A");
				dayLink.href = "javascript: void test();";
				dayLink.onclick = this.dateClick;
				dayLink.setAttribute("timestamp", calendarDayDateObj.getTime());
				dayLink.appendChild(dayNode);
			}
			else
			{
            var dayNode = document.createTextNode(".");
				var dayLink = document.createElement("SPAN");
            dayLink.appendChild(dayNode);
            dayLink.style.visibility = "hidden";
			}
			dayCell.align = "center";
			dayCell.appendChild(dayLink);
		}
	}
}

datePicker.prototype.buildHeaderRow = function()
{
	this.headerRow = this.table.insertRow(1);
	for (i = 0; i < this.days.length; i++)
	{
		var headerCell = this.headerRow.insertCell(i);
		headerCell.align = "center";
		headerCell.className = "datePickerCell datePickerHeader";
		var headerNode = document.createTextNode(this.days[i]);
		headerCell.appendChild(headerNode);
	}
	this.allRows[this.allRows.length] = this.headerRow;
}

datePicker.prototype.buildNavRow = function()
{
	this.navRow = this.table.insertRow(0);
	var backCell = this.navRow.insertCell(0);
	var monthCell = this.navRow.insertCell(1);
	var nextCell = this.navRow.insertCell(2);
	this.backCell = backCell;
	this.monthCell = monthCell;
	this.nextCell = nextCell;

	backCell.colSpan = 1;
	monthCell.colSpan = 5;
	nextCell.colSpan = 1;
	monthCell.align = "center";
	nextCell.align = "right";
	backCell.id = "backCell";
	monthCell.id = "monthCell";
	nextCell.id = "nextCell";
	backCell.className = "navCell";
	monthCell.className = "navCell";
	nextCell.className = "navCell";

	var backNode = document.createTextNode("<");
	var monthNode = document.createTextNode(this.months[this.monthIndex] + " " + this.startDate.getFullYear());
	var nextNode = document.createTextNode(">");

	var backLink = document.createElement("A");
	var nextLink = document.createElement("A");

	backLink.href = "javascript: void test;";
	backLink.onclick = goToDate;
	backLink.setAttribute("timestamp", this.lastMonth.getTime());

	nextLink.href = "javascript: void test();";
	nextLink.onclick = goToDate;
	nextLink.setAttribute("timestamp", this.nextMonth.getTime());

	backLink.appendChild(backNode);
	nextLink.appendChild(nextNode);

	backCell.appendChild(backLink);
	monthCell.appendChild(monthNode);
	nextCell.appendChild(nextLink);

	this.allRows[this.allRows.length] = this.navRow;
}

datePicker.prototype.buildTable = function()
{
	if (this.container == null)
	{
		alert("no container");
		return false;
	}

	if (this.id == null)
	{
		alert("no id");
		return false;
	}

	if (this.startDate == null)
	{
		alert("no start date");
		return false;
	}

	this.table = document.createElement("TABLE");
	this.table.id = this.id + "_table";
	this.table.cellPadding = 0;
	this.table.cellSpacing = 0;
	this.table.className = "datePickerTable";
	this.buildNavRow();
	this.buildHeaderRow();
	this.buildWeekRows();
	this.container.appendChild(this.table);


}

datePicker.prototype.getNumberOfWeeks = function()
{
}

datePicker.prototype.setContainer = function(containerDiv)
{
	this.container = containerDiv;
}

datePicker.prototype.getContainer = function()
{
	return this.container;
}

datePicker.prototype.setID = function(id)
{
	this.id = id;
}

datePicker.prototype.getID = function()
{
	return this.id;
}

function test()
{
	//alert("hi");
}

function getEvent(event)
{
	if (!event)
	{
		event = window.event;
	}

	if (!event.target)
	{
		event.target = event.srcElement;
	}
	return event;
}

function goToDate(event)
{
	event = getEvent(event);
	var timestamp = parseInt(event.target.getAttribute("timestamp"));
	picker.container.removeChild(picker.table);
	picker.init(timestamp, picker.container, picker.id);
}

function saveDate(event)
{
	event = getEvent(event);
	var timestamp = new Number(event.target.getAttribute("timestamp"));
	var savedDate = new Date(timestamp);
	var selectedTimeMenu = document.getElementById("hours");
	savedDate.setHours(selectedTimeMenu.options[selectedTimeMenu.selectedIndex].value);
	savedDate.setMinutes(0);
	savedDate.setSeconds(0);
   buildSavedDate(savedDate.getTime(), false);
}

function buildSavedDate(timestamp, dateID)
{
   var savedDate = new Date(timestamp);
	var container = document.getElementById("datesDiv");
	var dateDiv = document.createElement("DIV");
   dateDiv.setAttribute("dateID", dateID ? dateID : "");
   
   var year = savedDate.getFullYear();
   var dateParts = savedDate.toLocaleString().split(year);
   var br = document.createElement("BR");
	var dateNode = document.createTextNode(dateParts[0] + " " + year);
   var timeNode = document.createTextNode(dateParts[1]);
   
	var dateInput = document.createElement("INPUT");
	dateInput.type = "hidden";
   
	dateInput.id = dateID ? "proposedDate_" + dateID : "timestamp_" + container.childNodes.length;
	dateInput.name = dateInput.id;
	dateInput.value = Math.floor(timestamp / 1000);
   
   var deleteNode = document.createTextNode("X");
   var deleteLink = document.createElement("A");
   var deleteDiv = document.createElement("DIV");
   deleteLink.onclick = deleteDate; 
   deleteLink.appendChild(deleteNode);
   deleteDiv.appendChild(deleteLink);
   deleteDiv.className = "deleteDiv";
   
   dateDiv.appendChild(deleteDiv);
	dateDiv.appendChild(dateNode);
   dateDiv.appendChild(br);
   dateDiv.appendChild(timeNode);
	dateDiv.appendChild(dateInput);
	dateDiv.className = "savedDate";
   deleteLink.dateDiv = dateDiv;
	container.appendChild(dateDiv);
}


function deleteDate(event)
{
	event = getEvent(event);
   
   if (event.target.dateDiv.getAttribute("dateID") != "")
   {
      deleteInput = document.createElement("INPUT");
      deleteInput.type = "hidden";
      deleteInput.name = "deleteDates[]";
      deleteInput.value = event.target.dateDiv.getAttribute("dateID");
      var form = event.target.dateDiv.children[event.target.dateDiv.children.length-1].form;
      form.appendChild(deleteInput);
   } 
   
	var parentNode = event.target.dateDiv.parentNode;
	parentNode.removeChild(event.target.dateDiv);
}

function initPicker()
{
	var container = document.getElementById("datePickerContainer");
	var now = new Date();
	picker = new datePicker(now.getTime(), container, "dp");
}

Date.prototype.get2DigitYear = function()
{
	var year = new String(this.getYear());
	var shortYear = year.slice(2, 4);
	return shortYear;
}

