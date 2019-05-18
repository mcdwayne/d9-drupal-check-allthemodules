/**
 * @file
 * Initial JS for viewing Advertising entities.
 */

(function (ad_entity, Drupal, window) {

  // At this point, the global adEntity object is fully
  // initialized and available as Drupal component.
  Drupal.ad_entity = ad_entity;

  ad_entity.adContainers = ad_entity.adContainers || {};
  ad_entity.context = ad_entity.context || {};
  ad_entity.viewHandlers = ad_entity.viewHandlers || {};

  /**
   * Collects all not yet initialized Advertising containers from the given context.
   *
   * @param {object} context
   *   The part of the DOM being processed.
   * @param {object} settings
   *   The Drupal settings.
   *
   * @return {object}
   *   The newly added containers (newcomers).
   */
  ad_entity.collectAdContainers = function (context, settings) {
    var newcomers = {};
    var collected = ad_entity.adContainers;
    var queues = [ad_entity.queue];
    var queue;
    var length;
    var el;
    var i;
    var container;
    var event_detail;
    ad_entity.queue = [];
    if (!ad_entity.settings.inline) {
      queues.push(context.querySelectorAll('.ad-entity-container'));
    }
    while (queues.length > 0) {
      queue = queues.shift();
      length = queue.length;
      for (i = 0; i < length; i++) {
        el = queue[i];
        if (typeof el.id !== 'string' || !(el.id.length > 0)) {
          continue;
        }
        if (collected.hasOwnProperty(el.id)) {
          continue;
        }
        container = {
          el: el,
          data: function (key, value) {
            return ad_entity.helpers.metadata(this.el, this, key, value);
          }
        };
        collected[el.id] = container;
        newcomers[el.id] = container;
      }
    }
    event_detail = {
      collected: collected,
      newcomers: newcomers,
      context: context,
      settings: settings
    };
    ad_entity.helpers.trigger(window, 'adEntity:collected', false, true, event_detail);
    return newcomers;
  };

  /**
   * Restricts the given list of Advertising containers
   * to the scope of the current breakpoint.
   *
   * @param {object} containers
   *   The list of Advertising containers to restrict.
   *
   * @return {object}
   *   The containers which are in the scope of the current breakpoint.
   */
  ad_entity.restrictAdsToScope = function (containers) {
    var helpers = ad_entity.helpers;
    var scope = ['any'];
    var in_scope;
    var breakpoint;
    var container;
    var container_id;
    var variant;
    var variant_length;
    var el;
    var i;

    if (typeof window.themeBreakpoints === 'object') {
      if (typeof window.themeBreakpoints.getCurrentBreakpoint === 'function') {
        breakpoint = window.themeBreakpoints.getCurrentBreakpoint();
        if (breakpoint) {
          scope.push(breakpoint.name);
        }
      }
    }

    in_scope = {};
    for (container_id in containers) {
      if (containers.hasOwnProperty(container_id)) {
        container = containers[container_id];
        el = container.el;
        variant = container.data('data-ad-entity-variant');
        variant_length = variant.length;
        for (i = 0; i < variant_length; i++) {
          if (!(scope.indexOf(variant[i]) < 0)) {
            in_scope[container_id] = container;
            if (container.data('inScope') !== true) {
              helpers.addClass(el, 'in-scope');
              helpers.removeClass(el, 'out-of-scope');
              el.style.display = null;
              container.data('inScope', true);
            }
            break;
          }
        }
        if (!in_scope.hasOwnProperty(container_id) && (container.data('inScope') !== false)) {
          helpers.removeClass(el, 'in-scope');
          helpers.addClass(el, 'out-of-scope');
          el.style.display = 'none';
          container.data('inScope', false);
        }
      }
    }

    return in_scope;
  };

  /**
   * Correlates the Advertising containers with their view handlers.
   *
   * @param {object} containers
   *   The list of Advertising containers to correlate.
   *
   * @return {object}
   *   The correlation.
   */
  ad_entity.correlate = function (containers) {
    var view_handlers = ad_entity.viewHandlers;
    var view_handler;
    var correlation = {};
    var handler_id = '';
    var container;
    var container_id;

    for (container_id in containers) {
      if (containers.hasOwnProperty(container_id)) {
        container = containers[container_id];
        handler_id = container.data('data-ad-entity-view');

        if (view_handlers.hasOwnProperty(handler_id)) {
          view_handler = view_handlers[handler_id];
          correlation[handler_id] = correlation[handler_id] || {handler: view_handler, containers: {}};
          correlation[handler_id].containers[container_id] = container;
        }
      }
    }
    return correlation;
  };

  /**
   * Applies scope restriction and proper initialization
   * on given Advertisement containers.
   *
   * @param {object} containers
   *   The list of Advertising containers to restrict and initialize.
   * @param {object} context
   *   The DOM context.
   * @param {object} settings
   *   The Drupal settings.
   */
  ad_entity.restrictAndInitialize = function (containers, context, settings) {
    var view_handlers = ad_entity.viewHandlers;
    var helpers = ad_entity.helpers;
    var to_initialize = ad_entity.restrictAdsToScope(containers);
    var container;
    var container_id;
    var initialized;
    var disabled;
    var correlation;
    var handler_id;

    for (container_id in to_initialize) {
      if (to_initialize.hasOwnProperty(container_id)) {
        container = to_initialize[container_id];
        initialized = container.data('initialized');
        if (typeof initialized !== 'boolean') {
          initialized = !helpers.hasClass(container.el, 'not-initialized');
          container.data('initialized', initialized);
        }
        // Prevent re-initialization of already initialized Advertisement.
        if (initialized === true) {
          delete to_initialize[container_id];
        }
        else {
          // Do not initialize disabled containers.
          // As per documentation since beta status,
          // the primary flag for disabling initialization
          // is the class name.
          disabled = helpers.hasClass(container.el, 'initialization-disabled');
          container.data('disabled', disabled);
          if (disabled) {
            delete to_initialize[container_id];
          }
        }
      }
    }

    // Let the view handlers initialize their ads.
    correlation = ad_entity.correlate(to_initialize);
    for (handler_id in view_handlers) {
      if (view_handlers.hasOwnProperty(handler_id)) {
        if (correlation.hasOwnProperty(handler_id)) {
          correlation[handler_id].handler.initialize(correlation[handler_id].containers, context, settings);
        }
      }
    }
  };

  /**
   * Drupal behavior for viewing Advertising entities.
   */
  Drupal.behaviors.adEntityView = {
    attach: function (context, settings) {
      var containers = ad_entity.collectAdContainers(context, settings);
      var isEmptyObject = ad_entity.helpers.isEmptyObject;

      // No need to proceed in case no new containers have been found.
      if (isEmptyObject(containers)) {
        return;
      }

      // Apply Advertising contexts, if available.
      if (!(isEmptyObject(ad_entity.context))) {
        ad_entity.context.addFrom(context);
        ad_entity.context.applyOn(containers);
      }

      // Apply initial scope restriction and initialization on given Advertisement.
      ad_entity.restrictAndInitialize(containers, context, settings);

      // When responsive behavior is enabled,
      // re-apply scope restriction with initialization on breakpoint changes.
      if (ad_entity.hasOwnProperty('settings') && ad_entity.settings.hasOwnProperty('responsive')) {
        if (ad_entity.settings.responsive === true) {
          window.addEventListener('themeBreakpoint:changed', function () {
            ad_entity.restrictAndInitialize(containers, context, settings);
          });
        }
      }
    },
    detach: function (context, settings) {
      var containers = {};
      var collected = ad_entity.adContainers;
      var correlation;
      var handler_id;

      // Remove the detached container from the collection,
      // but keep them in mind for other view handlers to act on.
      var container_items = context.querySelectorAll('.ad-entity-container');
      var length = container_items.length;
      var i;
      var el;

      for (i = 0; i < length; i++) {
        el = container_items[i];
        if (typeof el.id !== 'string' || !(el.id.length > 0)) {
          continue;
        }
        if (!collected.hasOwnProperty(el.id)) {
          continue;
        }

        containers[el.id] = collected[el.id];
        delete collected[el.id];
      }

      // Let the view handlers act on detachment of their ads.
      correlation = ad_entity.correlate(containers);
      for (handler_id in ad_entity.viewHandlers) {
        if (ad_entity.viewHandlers.hasOwnProperty(handler_id)) {
          if (correlation.hasOwnProperty(handler_id)) {
            correlation[handler_id].handler.detach(correlation[handler_id].containers, context, settings);
          }
        }
      }
    }
  };

}(window.adEntity, Drupal, window));
