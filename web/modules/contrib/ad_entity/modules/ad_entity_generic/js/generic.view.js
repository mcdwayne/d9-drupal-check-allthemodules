/**
 * @file
 * Advertising Entity: Js view handler for generic ads.
 */

(function (ad_entity) {

  ad_entity.viewHandlers = ad_entity.viewHandlers || {};

  ad_entity.viewHandlers.generic = ad_entity.viewHandlers.generic || {
    initialize: function (containers, context, settings) {
      var container;
      var container_id;
      var ad_tag;
      var ad_tags = [];
      var targeting;
      var helpers = ad_entity.helpers;
      var p13n = ad_entity.usePersonalization();
      var onPageLoad = true;
      if (this.slotNumber !== 0) {
        onPageLoad = false;
      }

      for (container_id in containers) {
        if (!containers.hasOwnProperty(container_id)) {
          continue;
        }
        container = containers[container_id];
        if (typeof container.ad_tag === 'object') {
          continue;
        }
        this.slotNumber++;

        targeting = container.data('data-ad-entity-targeting');
        if (typeof targeting !== 'object') {
          targeting = {};
        }
        targeting.slotNumber = this.slotNumber;
        targeting.onPageLoad = onPageLoad;
        targeting.personalized = targeting.personalized || p13n;

        ad_tag = {
          el: container.el.querySelector('.adtag'),
          data: function (key, value) {
            return ad_entity.helpers.metadata(this.el, this, key, value);
          }
        };
        ad_tag.id = ad_tag.el.id;
        ad_tag.format = ad_tag.data('data-ad-format');
        ad_tag.name = container.data('data-ad-entity');
        ad_tag.targeting = targeting;
        ad_tag.done = function (success, isEmpty) {
          var el = this.el;
          if ((success === false) || (this.ad_tag.isLoaded === true)) {
            return;
          }
          this.ad_tag.isLoaded = true;
          helpers.removeClass(el, 'not-initialized');
          helpers.addClass(el, 'initialized');
          this.data('initialized', true);
          if (isEmpty === true) {
            this.ad_tag.isEmpty = true;
            helpers.addClass(el, 'empty');
            helpers.removeClass(el, 'not-empty');
          }
          else {
            helpers.addClass(el, 'not-empty');
            helpers.removeClass(el, 'empty');
          }
          helpers.trigger(el, 'adEntity:initialized', true, true, {container: this});
        }.bind(container);
        container.ad_tag = ad_tag;

        ad_tags.push(ad_tag);
      }
      ad_entity.generic.load(ad_tags);
    },
    detach: function (containers, context, settings) {
      var ad_tags = [];
      var buffer = {cid: null, container: null};
      for (buffer.cid in containers) {
        if (!containers.hasOwnProperty(buffer.cid)) {
          continue;
        }
        buffer.container = containers[buffer.cid];
        if (typeof buffer.container.ad_tag === 'object') {
          ad_tags.push(buffer.container.ad_tag);
        }
      }
      ad_entity.generic.remove(ad_tags);
    },
    slotNumber: 0
  };

}(window.adEntity));
