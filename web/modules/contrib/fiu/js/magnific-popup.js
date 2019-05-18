(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.magnific_popup = {
    attach: function (context, settings) {
      var items = [];

      $('.fiu-image-info').each(function () {
        var fiuId = $(this).attr('id');
        items[fiuId] = [];
        items[fiuId].push({
          src: $(this).html(),
          type: 'inline'
        });
      });

      $('.fiu-image-details').each(function () {
        var fiuId = $(this).data('fiu-id');

        $(this).magnificPopup({
          items: items[fiuId],
          gallery: {
            enabled: true
          },
          callbacks: {
            change: function () {
              this.content.on('change', function (event) {
                var val = event.target.value;
                var id = event.target.id;
                var itemNumber = event.target.getAttribute('data-item-number');
                var identifier = '#' + fiuId + ' .attr #' + id;
                $(identifier).attr('value', val);

                /* Change item */
                var changedItem = $(identifier).parents('.fiu-image-info').html();
                mfp.items[itemNumber].src = changedItem;
              });
            }
          }
        });
      });

      var mfp = $.magnificPopup.instance;

    }
  };

})(jQuery, Drupal);
