(function ($, Drupal) {


  $(document).ready(function (e) {
    if ($('[data-gjs-type=container]').length == 0) {
      $('body').append('<a class="pd-edit-icon" href="?pd=1"><i class="fas fa-edit"></i></a>');
    }
  });

})(jQuery, Drupal);