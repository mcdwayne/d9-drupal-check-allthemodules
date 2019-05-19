(function($, Drupal, window) {
  
  $.ready(function() {
    
    var e = $('form.wisski-ckeditor-entity-link-dialog-form').find('[name="attributes[href]"]');
    if (e.value) e.trigger('change');

  });


})(jQuery, Drupal, window);
