(function ($) {

  'use strict';

  Drupal.behaviors.entityForm = {
    attach: function (context, settings) {
      $('.dcs-edit-override',context).click( function(e) {
        var checkbox  = $(this);
        var id        = checkbox.attr('data-dcs-edit-override-id');
        var override  = checkbox.is(':checked');
        var elements  = $('.dcs-edit-override-id-'+id);
        if(override) {
          elements.removeClass('dcs-edit-override-hide');
        }
        else {
          elements.addClass('dcs-edit-override-hide');
        }
      } );
    }
  };

})(jQuery, drupalSettings);
