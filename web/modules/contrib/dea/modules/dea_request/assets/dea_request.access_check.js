(function($, Drupal) {
  var attributeSelector = function (attributes) {
    return _.map(attributes, function (value, key) {
      return '[' + key + '="' + value + '"]'
    }).join('');
  };

  Drupal.behaviors.dea_request_access_checks = {
    attach: function (context, settings) {
      var operations = [];
      $('[data-entity-operation][data-entity-type][data-entity-id]', context).once('dea-access-check').each(function () {
        operations.push([
            $(this).data('entity-type'),
            $(this).data('entity-id'),
            $(this).data('entity-operation')
        ].join(':'))
      });
      if (operations.length === 0) {
        return;
      }
      $.ajax({
        type: 'POST',
        cache: true,
        url: Drupal.url('dea/check-access'),
        data: {'operations': _.uniq(operations).sort()},
        success: function(response) {
          _.each(response, function(status, operation) {
            var op = operation.split(':');
            var entity_type = op[0];
            var entity_id = op[1];
            var entity_operation = op[2];
            var selector = attributeSelector({
              'data-entity-type': entity_type,
              'data-entity-id': entity_id,
              'data-entity-operation': entity_operation
            });
            $(selector, context).addClass('is-' + status);
            var $element = $(selector, context);
            if (status === 'requestable') {
              // Reset the elements url to the request form and attach the
              // ajax dialog.
              $element.attr('href', Drupal.url([
                'request-access',
                entity_type,
                entity_id,
                entity_operation
              ].join('/')));
              $element.addClass('use-ajax');
              $element.attr('data-dialog-type', 'modal');
            }
          });
          // Re-run ajax attach behaviors.
          Drupal.behaviors.AJAX.attach(context, settings);
        }
      });
    }
  };
}(jQuery, Drupal));