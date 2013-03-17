$(document).ready(
   function() 
   {
      $("#uploadedFiles").change(function()
         {
            var clone = $("#uploadedFiles").parent().parent().clone(true);
            console.log();
            var input = clone[0].children[1].children[0];
            input.id = "uploadedFiles_" + $(".formTable")[0].rows.length;
            input.name = input.id;
            input.value = "";
            $("#controlButtons").before(clone);
         })
   }
);
