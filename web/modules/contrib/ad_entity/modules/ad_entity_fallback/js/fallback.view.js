/**
 * @file
 * JS fallback view handler implementation.
 */

(function (ad_entity, drupalSettings, window) {

  ad_entity.fallbacks = ad_entity.fallbacks || {};

  var fallbacks = ad_entity.fallbacks;

  /**
   * Correlates all known ad containers with their fallback containers.
   *
   * @param {object} containers
   *   The list of containers with both original and fallback containers.
   *
   * @return {object}
   *   The correlation.
   */
  fallbacks.correlateContainers = function (containers) {
    var correlationId;
    var container;
    var container_id;
    var correlated = {};
    var item;
    for (container_id in containers) {
      if (containers.hasOwnProperty(container_id)) {
        container = containers[container_id];
        // Fetch the original container.
        correlationId = container.data('data-fallback-container');
        if (typeof correlationId !== 'undefined') {
          if (!correlated.hasOwnProperty(correlationId)) {
            correlated[correlationId] = {originalContainer: null, fallbackContainer: null};
          }
          correlated[correlationId].originalContainer = container;
        }
        else {
          // Fetch the fallback container.
          correlationId = container.data('data-fallback-container-for');
          if (typeof correlationId !== 'undefined') {
            if (!correlated.hasOwnProperty(correlationId)) {
              correlated[correlationId] = {originalContainer: null, fallbackContainer: null};
            }
            correlated[correlationId].fallbackContainer = container;
          }
        }
        if (typeof correlationId !== 'undefined') {
          item = correlated[correlationId];
          // Create a reference to the instance of the fallback container.
          if (item.originalContainer !== null && item.fallbackContainer !== null) {
            item.originalContainer.data('fallbackObject', item.fallbackContainer);
          }
        }
      }
    }
    return correlated;
  };

  /**
   * Loads fallback containers in case the original ones are empty.
   *
   * @param {object} containers
   *   The list of containers with both original and fallback containers.
   * @param {object} context
   *   The DOM context.
   * @param {object} settings
   *   The Drupal settings.
   */
  fallbacks.processFallbacks = function (containers, context, settings) {
    var helpers = ad_entity.helpers;
    var correlated = this.correlateContainers(containers);
    var to_load = {};
    var id;
    for (var correlationId in correlated) {
      if (correlated.hasOwnProperty(correlationId)) {
        var item = correlated[correlationId];
        var original = item.originalContainer;
        var fallback = item.fallbackContainer;
        if (original === null || fallback === null) {
          continue;
        }
        if (original.data('initialized') === true || original.data('inScope') !== true || original.data('fallbackProcessed') === true) {
          continue;
        }
        if (fallback.data('initialized') === true || fallback.data('inScope') !== true) {
          continue;
        }
        helpers.removeClass(fallback.el, 'initialization-disabled');
        fallback.data('disabled', false);
        id = fallback.el.id;
        to_load[id] = fallback;

        // Make sure that others won't accidentally try to
        // initialize the original container again.
        helpers.addClass(original.el, 'initialization-disabled');
        original.data('disabled', true);
        original.data('fallbackProcessed', true);
      }
    }
    if (!(helpers.isEmptyObject(to_load))) {
      ad_entity.restrictAndInitialize(to_load, context, settings);
    }
  };

  /**
   * Helper function to get the fallback container for the given Advertising container.
   *
   * @param {object} container
   *   The Advertising container.
   *
   * @return {object}
   *   The fallback when given, or undefined when not.
   */
  fallbacks.getFallbackContainerFor = function (container) {
    var fallback = container.data('fallbackObject');
    if (typeof fallback === 'undefined') {
      var correlationId = container.data('data-fallback-container');
      if (correlationId !== 'undefined') {
        // Perform a complete lookup on all containers
        // to fetch the corresponding fallback container.
        var all_containers = ad_entity.adContainers;
        for (var id in all_containers) {
          if (all_containers.hasOwnProperty(id)) {
            var suspect = all_containers[id];
            if (correlationId === suspect.data('data-fallback-container-for')) {
              fallback = suspect;
              container.data('fallbackObject', fallback);
              break;
            }
          }
        }
      }
    }
    return fallback;
  };

  /**
   * Event listener callback when Advertising containers have been collected.
   *
   * @param {object} event
   *   The corresponding event object.
   */
  fallbacks.onCollect = function (event) {
    var detail = event.detail;
    var processCallback = this.processFallbacks.bind(this, detail.newcomers, detail.context, detail.settings);
    var timeout = 1000;
    if (drupalSettings.hasOwnProperty('ad_entity') && drupalSettings.ad_entity.hasOwnProperty('fallback_timeout')) {
      timeout = drupalSettings.ad_entity.fallback_timeout;
    }
    window.setTimeout(processCallback, timeout);
  };

  window.addEventListener('adEntity:collected', fallbacks.onCollect.bind(fallbacks));

}(window.adEntity, drupalSettings, window));
