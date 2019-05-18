/**
* @file
* Placeholder file for custom sub-theme behaviors.
*
*/
(function ($, Drupal) {
  Drupal.behaviors.drowl_media_library = {
    attach: function (context, settings) {
      // Add .selected class to checked media bulk operation items
      $('.view-media-library .grid__content, .entities-list .item-container', context).each(function(){
        var $container = $(this);
        var $checkbox = $container.find('.form-item .form-checkbox');
        if ($checkbox.is(':checked')) {
          $container.addClass('selected');
        }
        $checkbox.on('change', function(){
          if ($checkbox.is(':checked')) {
            $container.addClass('selected');
          }else{
            $container.removeClass('selected');
          }
        });
      });
    },
  };
})(jQuery, Drupal);
