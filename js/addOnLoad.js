window.onLoadFunctions = new Array();
window.addOnLoad = function(funcRef)
{
   if (window.addOnLoad.arguments.length == 2)
   {
      var args = window.addOnLoad.arguments[1];
   }
   else
   {
      var args = new Array();
   }
   
   var index = window.onLoadFunctions.length;
   window.onLoadFunctions[index] = new Object();
   window.onLoadFunctions[index]['func'] = funcRef;
   
   if (!args instanceof Array)
   {
      var args = new Array(args);
   }
   
   window.onLoadFunctions[index]['args'] = args;
}


window.runOnLoadFunctions = function()
{
   for (funcIndex in window.onLoadFunctions)
   {
      var func = window.onLoadFunctions[funcIndex]['func'];
      var args = window.onLoadFunctions[funcIndex]['args'];
      func.apply(window, args); 
   }
}

window.onload = runOnLoadFunctions;
