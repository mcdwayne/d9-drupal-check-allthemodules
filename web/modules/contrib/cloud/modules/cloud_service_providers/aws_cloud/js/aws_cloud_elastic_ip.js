(function ($, Drupal) {

  Drupal.ElasticIp = {
    showHide: function (type) {
      if (type == 'instance') {
        // hide the network container
        $('#network-interface-ip-container').hide();
        $('#instance-ip-container').show();
      }
      else {
        $('#network-interface-ip-container').show();
        $('#instance-ip-container').hide();
      }
    },
  }

  Drupal.behaviors.elasticIpFormSwitcher = {
    attach: function (context, settings) {
      // Show/hide the different containers.
      $('#edit-resource-type', context).change(function () {
        var selected = $(this).children("option:selected").val();
        Drupal.ElasticIp.showHide(selected);
      });

      // Initialize and reinitialize when Ajax form refreshes.
      var selected = $('#edit-resource-type').children("option:selected").val();
      Drupal.ElasticIp.showHide(selected);
    }
  };
})(jQuery, Drupal);