(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.init_inpost_geowidget = {
    attach: function (context, settings) {
      easyPack.init({
        closeTooltip: false,
      });
      if(!$('#inpost_geowidget').hasClass('easypack-widget')) {
        $('#inpost_geowidget').height('80vh');
        var map = easyPack.mapWidget('inpost_geowidget', function(point) {
          $("input[name='shipping_information[shipments][0][paczkomat][0][value]']").val(point.name);
          alert(Drupal.t('Wybrano paczkomat')+': '+point.name);
        });
      }
      $("input[name='shipping_information[shipments][0][paczkomat][0][value]']").attr('readonly',1);
      var gw = $("[data-drupal-selector='edit-commerce-shipping-paczkomaty-selection'],[data-drupal-selector='edit-shipping-information-shipments-0-paczkomat-wrapper']");
      var shipment_id = gw.find('#inpost_geowidget').data('shipment');
      var shipment_field = $("input[name='shipping_information[shipments][0][shipping_method][0]']");
      function hide_show(val) {
        var x = val.split('--');
        if(x[0]==shipment_id) gw.show();
        else gw.hide();
      }
      hide_show(shipment_field.filter(':checked').val());
      shipment_field.change(function(){hide_show($(this).val());});
    }
  };

})(jQuery, Drupal, drupalSettings);
