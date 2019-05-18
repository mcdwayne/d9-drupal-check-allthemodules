(function ($) {
  $(document).ready(function() {
    $('a.help-modal').click(function(){
      var url = $(this).attr('href');
      var elem = $('<div></div>')
        .load(url, function () {
          elem.dialog("option", "title", elem.find("title").text());
        })
        .dialog({  //create dialog, but keep it closed
          title: "",
          autoOpen: false,
          width: 'auto',
          modal: true,
          closeOnEscape: true
        })
        .dialog("open");
      $('.ui-icon-closethick').removeClass('ui-icon-closethick').addClass('ui-icon-circle-close');
      $('.ui-widget-overlay').on("click", function() {
        //Close the dialog
        elem.dialog("close");
      });
      return false;
    });
  });
})(jQuery);