/* Author: Synpase-studio */
(function($){
  $( document ).ready(function() {
    /** ready */
    console.log('jstree');
    $('#jstree').jstree({
          "core" : {
              "themes" : {
                  "responsive": false
              }
          },
          "types" : {
              "default" : {
                  "icon" : "fa fa-folder icon-state-warning icon-lg"
              },
              "file" : {
                  "icon" : "fa fa-file icon-state-warning icon-lg"
              },
              "test" : {
                  "icon" : "fa fa-check icon-state-warning icon-lg"
              },
              "kurs" : {
                  "icon" : "fa fa-graduation-cap icon-state-warning icon-lg"
              }
          },
          "plugins": ["types"]
    });
    $("#jstree li").on("click", "a",
        function() {
            document.location.href = this;
        }
    );
  });
  Drupal.behaviors.cmlparser = {
    attach: function(context) {
      //* load */
    }
  };
})(this.jQuery);
