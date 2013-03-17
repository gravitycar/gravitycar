ieMenuFix = function() 
{
   if (document.all&&document.getElementById) 
   {
      navRoot = document.getElementById("navList");
      for (var i = 0; i < navRoot.childNodes.length; i++) 
      {
         node = navRoot.childNodes[i];
         
         if (node.nodeName=="LI")
         {
            node.onmouseover=function() 
            {
               this.className+="Over";
            }
            node.onmouseout=function() 
            {
               this.className=this.className.replace("Over", "");
            }
         }
      }
   }
}
window.addOnLoad(ieMenuFix);
