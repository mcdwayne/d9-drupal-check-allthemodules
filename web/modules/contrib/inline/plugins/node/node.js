(function($) {

Drupal.wysiwyg.plugins.node = {
  /**
   * Return whether the passed node belongs to this plugin.
   */
  isNode: function (node) {
    return ($(node).is('img.node'));
  },

  /**
   * Execute the button.
   */
  invoke: function (data, settings, instanceId) {
    if (data.format == 'html') {
      var options = { nid: '' };
      if (this.isNode(data.node)) {
        options.action = 'update';
      }
    }
    else {
      // @todo Plain text support.
    }
    if (typeof options != 'undefined') {
      Drupal.wysiwyg.instances[instanceId].openDialog(settings.dialog, options);
    }
  },

  /**
   * Replace inline tags in data.content with images.
   */
  attach: function (content, settings, instanceId) {
    return content;
  },

  /**
   * Replace images with inline tags in editor contents upon data.save.
   */
  detach: function (content, settings, instanceId) {
    return content;
  },

  /**
   * Insert new inline tag into editor instance.
   */
  insert: function (settings) {
    return '[node|nid=' + settings.nid + ']';
  }
};

})(jQuery);
