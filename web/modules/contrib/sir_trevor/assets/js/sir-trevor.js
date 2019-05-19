(function ($, Drupal, drupalSettings) {

  'use strict';

  $(document).ready(function () {
    SirTrevor.setDefaults({
      iconUrl: "/sir-trevor/icons.svg",
      uploadUrl: "/sir-trevor/image"
    });

    // Prevent blocks from focusing on initialization.
    SirTrevor.Blocks.Text.prototype.onBlockRender =
    SirTrevor.Blocks.Heading.prototype.onBlockRender = function() {
      this.toggleEmptyClass();
    }

    for (let fieldName in drupalSettings.sirTrevor) {
      let configuration = {
        el: document.querySelector(`[data-sir-trevor-field-name="${fieldName}"]`),
        defaultType: drupalSettings.sirTrevor[fieldName].defaultType,
      };

      var blockTypes = drupalSettings.sirTrevor[fieldName].blockTypes;
      if (blockTypes.length > 0) {
        configuration.blockTypes = blockTypes;
      }

      new SirTrevor.Editor(configuration);
    }

  });


})(jQuery, Drupal, drupalSettings);
