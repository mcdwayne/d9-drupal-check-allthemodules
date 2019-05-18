/**
 * @file
 * JS handler implementation for the 'turnoff' context.
 *
 * @deprecated See README.md why this feature is deprecated.
 */

(function (ad_entity) {

  ad_entity.adContainers = ad_entity.adContainers || {};

  ad_entity.context = ad_entity.context || {};

  ad_entity.context.turnoff = {
    apply: function (container, context_settings, newcomers) {
      var id = container.el.id;
      // Remove the container from the DOM.
      container.el.parentNode.removeChild(container.el);
      // Delete the container from the global collection.
      delete ad_entity.adContainers[id];
      // Delete the container from the current list.
      delete newcomers[id];
    }
  };

}(window.adEntity));
