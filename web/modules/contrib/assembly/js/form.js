(function ($, Drupal) {

Drupal.behaviors.assemblyForm = {
  attach: function(context, settings) {
    $('[data-assembly-revision-checkbox]').once('assemblyForm').each(function() {
      var $checkbox = $(this), $log = $checkbox.closest('.ief-form').find('[data-assembly-revision-log]');
      // Only do this if there's a log message to show/hide
      if (!$log.length) {
        return;
      }
      // Create a toggle link
      var $toggle = $('<a class="assembly-toggle-revision-log" href="#">Add a log message</a>').on('click', function(e) {
        e.preventDefault();
        $toggle.text($log.is(':visible') ? 'Add a log message' : 'Clear log message' );
        $log.find('textarea').val('');
        $log.toggle();
      });

      // add it after the relevant label and add a helper class. Maybe i should use [for=""] here
      $checkbox
        .closest('.form-type-checkbox')
        .addClass('assembly-revision-checkbox')
        .find('label')
          .first()
          .after($toggle)
      ;

      // Don't show the toggle if revision is unchecked
      $checkbox.on('change', function() {
        $toggle.toggle($checkbox.is(':checked'));
      });

      // Init the stuff
      $log.toggle();
      $toggle.toggle($checkbox.is(':checked'));

    });
  }
}

})(jQuery, Drupal);