/**
 * @file
 * JS View handler implementation for the test_view plugin.
 */

(function (ad_entity) {

  ad_entity.viewHandlers = ad_entity.viewHandlers || {};

  ad_entity.viewHandlers.test_view = {
    initialize: function (containers, context, settings) {
      var helpers = ad_entity.helpers;
      var container;
      var container_id;
      for (container_id in containers) {
        if (containers.hasOwnProperty(container_id)) {
          container = containers[container_id];
          helpers.trigger(container.el, 'adEntity:initialized', true, true, {container: container});
        }
      }
    },
    detach: function (containers, context, settings) {}
  };

}(window.adEntity));
