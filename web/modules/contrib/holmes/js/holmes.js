(function(Drupal, drupalSettings, $){
  Drupal.behaviors.holmes = {
    attach: function (context, settings) {
      var _this = this;
      $('body').once('holmes').each(function() {
        _this.addHolmesToggle();
      });
    },
    addHolmesToggle: function() {
      var _this = this;
      var isEnabled = drupalSettings.holmesEnabled || false;

      var $holmesToggle = $('<a id="holmes-enable" href="?holmes-debug=true" title="Enable Holmes"></a>');

      var action = this.getActionLabel(isEnabled);
      var $label = $('<label for="holmes-enable-chk" ><span class="action">' + action + '</span> markup issues</label>');
      $holmesToggle.append($label);

      var checked = isEnabled ? ' checked="checked" ' : '';
      var $checkbox = $('<input id="holmes-enable-chk" name="holmes-enable" type="checkbox" value="enabled"' + checked + '/>');
      $holmesToggle.append($checkbox);

      var $body = $('body');
      $body.append($holmesToggle);

      $label.click(function(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        // Toggle the enabled state.
        isEnabled = !isEnabled;

        // Check/un-check checkbox based on enabled state.
        $checkbox.prop('checked', isEnabled);

        // Change the label of the button depending
        // on the enabled state.
        action = _this.getActionLabel(isEnabled);
        $('.action', $label).html(action);

        // Toggle the styles
        $body.toggleClass('holmes-debug');
      });
    },
    getActionLabel(isEnabled) {
      return isEnabled ? 'Hide' : 'Show';
    }
  };
})(Drupal, drupalSettings, jQuery);
