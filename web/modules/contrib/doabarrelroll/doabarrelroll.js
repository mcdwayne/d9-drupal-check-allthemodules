(function ($, Drupal, settings) {

  Drupal.behaviors.doaBarrelRoll =  {
    attach: function(context, settings) {
      $('#search-form, #search-block-form', context).keyup(function(event){
        if ($(this).find('input.form-search').val().toLowerCase() == 'do a barrel roll') {
          if (settings.doabarrelroll.style === 'barrel') {
            $('body').addClass('doabarrelroll');
          }
          else {
            $('body').addClass('doanaileronroll');
          }
        }
        if ($(this).find('input.form-search').val().toLowerCase() == 'do an aileron roll') {
          $('body').addClass('doanaileronroll');
        }
      });
    },
  };

 })(jQuery, Drupal, drupalSettings);
