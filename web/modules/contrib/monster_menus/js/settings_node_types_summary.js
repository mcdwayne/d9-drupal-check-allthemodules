(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-settings-node-types'] = function (context) {
    if ($('input[name="allowed_node_types_inherit"]:checked', context).length) {
      return Drupal.t('inherit') + '<br />';
    }
    var nodeTypes = [];
    $('select[name="allowed_node_types[]"]')
      .children('option:selected')
      .each(function() {
        nodeTypes.push(Drupal.checkPlain($(this).text()));
      });
    return nodeTypes.join(', ').toLowerCase();
  };
})(jQuery, Drupal, drupalSettings);