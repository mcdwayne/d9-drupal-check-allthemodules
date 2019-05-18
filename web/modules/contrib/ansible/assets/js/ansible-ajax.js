(function($) {
  /**
   * Add new command for reading a event.
   */
  setTimeout(function() {
		$.ajax({
			type: "GET",
			url: "/ansible/ajax/"+drupalSettings.js.ansibleajax.id,
			data: {"ajaxCall":true},
			async: true,
      success: function(response) {
      	$(".se-pre-con").fadeOut("slow");

         $('#ansibleContent').append("<pre>"+response+"</pre>");
      },
      error: function(response) {
        $(".se-pre-con").fadeOut("slow");
        console.log(response.responseText);
      }
    }).responseText;

  },1000);
})(jQuery);