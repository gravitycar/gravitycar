if (typeof window.top.gc == "undefined")
{
   window.top.gc = new Object();
}

$(document).ready(
   function() 
   {
      var cog = window.top.gc.cog;
      cog.init();
      $("#cogTable tr td").hide();
      for (i = cog.firstExpandedCol; i <= cog.lastExpandedCol; i++)
      {
         fastExpand(i);
      }
      
      $("#cogContainer").show();
      if (cog.maxExpandedCols < $("#cogTable tr").first().children().length)
      {
         $("#nextPrevCont").width($("#cogTable").width());
      }
      else
      {
         $("#nextPrevCont").hide();
      }
   }
   );

window.top.gc.cog = {
   //numCells: $("#cogTable tr")[0].children("td"),
   minColIndex: 1,
   maxColIndex: -1,
   maxExpandedCols: 10,
   firstExpandedCol: 0,
   lastExpandedCol: 10,
   
   forward: function() 
   {
      var cog = window.top.gc.cog;
      if (cog.lastExpandedCol < cog.maxColIndex)
      {
         cog.showLink("prev");
         cog.firstExpandedCol++;
         cog.lastExpandedCol++;
         var expandIndex = cog.lastExpandedCol;
         var contractIndex = cog.firstExpandedCol;
         $("#cogTable tr").each(function() {$(this).children().eq(contractIndex).hide()});
         $("#cogTable tr").each(function() {$(this).children().eq(expandIndex).show()});
      }
      
      if (cog.lastExpandedCol == cog.maxColIndex)
      {
         cog.hideLink("next");
      }
   },
   
   backward: function()
   {
      var cog = window.top.gc.cog;
      
      if (cog.firstExpandedCol == cog.minColIndex)
      {
         cog.hideLink("prev");
      }
      
      if (cog.firstExpandedCol >= cog.minColIndex)
      {
         cog.showLink("next");
         var expandIndex = cog.firstExpandedCol;
         var contractIndex = cog.lastExpandedCol;
         $("#cogTable tr").each(function() {$(this).children().eq(contractIndex).hide()});
         $("#cogTable tr").each(function() {$(this).children().eq(expandIndex).show()});
         cog.firstExpandedCol--;
         cog.lastExpandedCol--;
      }
   },
   
   stopScrolling: function()
   {
      if (window.stopScrollingChart)
      {
         window.clearInterval(window.stopScrollingChart);
         window.stopScrollingChart = false;
      }
   },
   
   hideLink: function(which)
   {
      var cog = window.top.gc.cog;
      var link = cog[which + "Link"];
      link.hide();
      cog.stopScrolling();
   },
   
   
   showLink: function(which)
   {
      var cog = window.top.gc.cog;
      var link = cog[which + "Link"];
      link.show();
   },
   
   init: function()
   {
      var cog = window.top.gc.cog;
      cog.table = $("#cogTable");
      cog.rows = $("#cogTable tr");
      cog.cellCount = cog.rows.eq(0).children().length;
      cog.maxColIndex = cog.cellCount - 1;
      cog.nextLink = $("#nextLink");
      cog.prevLink = $("#prevLink");
      cog.hideLink("prev");
   },
   
   diag: function()
   {
      var cog = window.top.gc.cog;
      var str = "";
      for (prop in cog)
      {
         if (typeof cog[prop] == "function")
         {
            continue;
         }
         
         str += "\n" + prop + ": ";
         
         try 
         {
            str += cog[prop];
         }
         catch(e)
         {
            str += typeof cog[prop];
         }
      }
      alert(str);
   }
   
};



function fastContract(cellIndex)
{
   $("#cogTable tr").each(function() {$(this).children().eq(cellIndex).hide()});
}


function fastExpand(cellIndex)
{
   $("#cogTable tr").each(function() {$(this).children().eq(cellIndex).show()});
}

function expandColumn(cellIndex)
{
   $("#cogTable tr").each(function() {$(this).children().eq(cellIndex).show(500)});
}


function contractColumn(cellIndex)
{
   $("#cogTable tr").each(function() {$(this).children().eq(cellIndex).hide(500)});
}

function showNext()
{
   window.stopScrollingChart = window.setInterval(window.top.gc.cog.forward, 100);
   return false;
}


function showPrev()
{
   window.stopScrollingChart = window.setInterval(window.top.gc.cog.backward, 100);
   return false;
}


function stopScrolling()
{
   window.top.gc.cog.stopScrolling();
   return false;
}


function doNothing()
{
   return false;
}
