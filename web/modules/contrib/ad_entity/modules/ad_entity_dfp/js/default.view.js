/**
 * @file
 * JS View handler implementation for ads which are using the 'dfp_default' view plugin.
 */

(function (ad_entity, drupalSettings, window) {

  ad_entity.viewHandlers = ad_entity.viewHandlers || {};

  ad_entity.viewHandlers.dfp_default = {
    initialize: function (containers, context, settings) {
      window.googletag.cmd.push(function () {
        var ad_tags = [];
        var onPageLoad = 'true';
        var container;
        var container_id;
        var ad_tag;
        var ad_el;
        if (this.numberOfAds > 0) {
          onPageLoad = 'false';
        }
        for (container_id in containers) {
          if (containers.hasOwnProperty(container_id)) {
            container = containers[container_id];
            if (typeof container.ad_tag === 'object') {
              continue;
            }

            ad_el = container.el.querySelector('.google-dfp-ad');
            if (ad_el === null) {
              continue;
            }

            this.numberOfAds++;
            ad_tag = {
              el: ad_el,
              data: function (key, value) {
                return ad_entity.helpers.metadata(this.el, this, key, value);
              }
            };
            container.ad_tag = ad_tag;
            this.define(ad_tag, this.numberOfAds.toString(), onPageLoad, container);
            this.addEventsFor(container);
            ad_tags.push(ad_tag);
          }
        }
        this.display(ad_tags);
      }.bind(this));
    },
    detach: function (containers, context, settings) {},
    define: function (ad_tag, slotNumber, onPageLoad, container) {
      var ad_id = ad_tag.el.id;
      var network_id = ad_tag.data('data-dfp-network');
      var unit_id = ad_tag.data('data-dfp-unit');
      var out_of_page = ad_tag.data('data-dfp-out-of-page');
      var slot;
      var sizes;
      var targeting;
      var key;
      var event_detail;

      if (out_of_page === true) {
        slot = window.googletag.defineOutOfPageSlot('/' + network_id + '/' + unit_id, ad_id);
      }
      else {
        sizes = ad_tag.data('data-dfp-sizes');
        if (typeof sizes !== 'object') {
          sizes = [];
        }
        slot = window.googletag.defineSlot('/' + network_id + '/' + unit_id, sizes, ad_id);
      }

      ad_tag.data('slot', slot);

      targeting = container.data('data-ad-entity-targeting');
      if (typeof targeting !== 'object') {
        targeting = {};
      }

      event_detail = {
        slot: slot,
        targeting: targeting,
        slotNumber: slotNumber,
        onPageLoad: onPageLoad
      };
      ad_entity.helpers.trigger(window, 'dfp:BeforeDisplay', false, true, event_detail);

      for (key in targeting) {
        if (targeting.hasOwnProperty(key)) {
          slot.setTargeting(key, targeting[key]);
        }
      }
      if (this.withSlotOrder) {
        slot.setTargeting('slotNumber', slotNumber);
        slot.setTargeting('onPageLoad', onPageLoad);
      }

      slot.addService(window.googletag.pubads());
    },
    display: function (ad_tags) {
      // When possible, load multiple slots at once to support roadblocks.
      // Slots with the same ad unit path wouldn't be refreshed
      // more than one time though, thus they're being split up.
      var slots = [[]];
      var slots_length;
      var slots_list;
      var slot;
      var i;
      var j;
      var k;
      var ad_tag;
      var unit_path;
      var exists;
      var slots_list_length;
      var ad_tags_length = ad_tags.length;

      for (i = 0; i < ad_tags_length; i++) {
        ad_tag = ad_tags[i];
        slot = ad_tag.data('slot');

        if (typeof slot === 'object') {
          window.googletag.display(ad_tag.el.id);

          unit_path = slot.getAdUnitPath();
          slots_length = slots.length;
          for (j = 0; j < slots_length; j++) {
            exists = false;
            slots_list = slots[j];
            slots_list_length = slots_list.length;
            for (k = 0; k < slots_list_length; k++) {
              if (unit_path === slots_list[k].getAdUnitPath()) {
                exists = true;
                break;
              }
            }
            if (exists === false) {
              slots_list.push(slot);
              break;
            }
            if ((j + 1) === slots.length) {
              slots.push([slot]);
            }
          }
        }
      }

      slots_length = slots.length;
      for (i = 0; i < slots_length; i++) {
        slots_list = slots[i];
        if (slots_list.length > 0) {
          if (typeof this.changeCorrelator === 'boolean') {
            window.googletag.pubads().refresh(slots_list, {changeCorrelator: this.changeCorrelator});
          }
          else {
            window.googletag.pubads().refresh(slots_list);
          }
        }
      }
    },
    addEventsFor: function (container) {
      // Mark container as initialized once advertisement has been loaded.
      var initHandler = function (event) {
        var helpers = ad_entity.helpers;
        var el = container.el;
        if (event.slot.getSlotElementId() === container.ad_tag.el.id) {
          helpers.removeClass(el, 'not-initialized');
          helpers.addClass(el, 'initialized');
          container.data('initialized', true);
          if (event.isEmpty === true) {
            helpers.addClass(el, 'empty');
            helpers.removeClass(el, 'not-empty');
          }
          else {
            helpers.addClass(el, 'not-empty');
            helpers.removeClass(el, 'empty');
          }
          helpers.trigger(el, 'adEntity:initialized', true, true, {container: container});
        }
      };

      window.googletag.pubads().addEventListener('slotRenderEnded', initHandler, false);
    },
    numberOfAds: 0,
    withSlotOrder: true,
    changeCorrelator: null
  };

  // Do not include slot order targeting when this feature is explicitly not enabled.
  if (drupalSettings.hasOwnProperty('dfp_order_info')) {
    if (!drupalSettings.dfp_order_info) {
      ad_entity.viewHandlers.dfp_default.withSlotOrder = false;
    }
  }

  // Include the changeCorrelator setting when it's being set.
  if (drupalSettings.hasOwnProperty('dfp_change_correlator')) {
    ad_entity.viewHandlers.dfp_default.changeCorrelator = drupalSettings.dfp_change_correlator;
  }

}(window.adEntity, drupalSettings, window));
