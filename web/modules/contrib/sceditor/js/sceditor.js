(function ($, Drupal) {
  Drupal.behaviors.SCEditor = {
    attach: function (context, settings) {
      var flask = new CodeFlask;
      var ext = settings.sceditor.ext;
      if (ext == 'module' || ext == 'install' || ext == 'inc') {
        ext = 'php';
      }
      if (ext == 'yml') {
        ext = 'yaml';
      }
      if (ext == 'html') {
        ext = 'markup';
      }
      flask.run('#sceditor', { language: ext });
      $('#sceditor').on("change keyup paste click", function () {
        var data = $('#sceditor code').text();
        $('form#sceditor-form > #file_content').val(data);
      });
    }
  };
})(jQuery, Drupal);
