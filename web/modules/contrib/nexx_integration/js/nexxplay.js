/**
 * @file
 * Defines the nexx video player behavior.
 */

(function ($, Drupal, window) {

  'use strict';

  /**
   * nexx video methods of Backbone objects.
   *
   * @namespace
   */
  Drupal.nexxPLAY = Drupal.nexxPLAY || {};

  /**
   * Whether the API is ready?
   *
   * @type {boolean}
   */
  Drupal.nexxPLAY.apiIsReady = false;

  /**
   * A Backbone.Collection of {@link Drupal.nexxPLAY.PlayerModel} instances.
   *
   * @type {Backbone.Collection}
   */
  Drupal.nexxPLAY.collection = new Backbone.Collection([], {model: Drupal.nexxPLAY.PlayerModel});

  /**
   * The {@link Backbone.View} instances associated with each nexx element.
   *
   * @type {Array}
   */
  Drupal.nexxPLAY.views = [];

  window.onPlayReady = function () {

    /* global _play */
    // Bind play state listener.
    _play.config.addPlaystateListener(function (object) {
      var model;

      // Update player index in corresponding model.
      if (object.event === 'playeradded') {
        if ((model = Drupal.nexxPLAY.collection.findWhere({containerId: object.playerContainer}))) {
          model.set('playerIndex', Number(object.playerIndex));
        }
      }

      // Update play state in corresponding model.
      if ((model = Drupal.nexxPLAY.collection.findWhere({playerIndex: Number(object.playerIndex)}))) {
        model.set('state', object.event);
      }
    });

    // Inform all models that API is ready.
    Drupal.nexxPLAY.collection.forEach(function (model) {
      model.set('apiIsReady', true);
    });

    // Set global flag that API is ready.
    Drupal.nexxPLAY.apiIsReady = true;
  };

  /**
   * Initialize nexx video players.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for nexx video player elements.
   */
  Drupal.behaviors.nexx = {
    attach: function (context, settings) {
      $(context).find('[data-nexx-video-id]').each(function () {
        var config = {
          containerId: $(this).attr('id'),
          videoId: $(this).attr('data-nexx-video-id')
        };

        // Automatically start playback?
        var autoPlay = $(this).attr('data-nexx-video-autoplay');
        if (typeof autoPlay !== 'undefined') {
          if (autoPlay === 'false' || autoPlay === '0') {
            config.autoPlay = 0;
          }
          else if (autoPlay === 'true' || autoPlay === '1') {
            config.autoPlay = 1;
          }
        }

        // Exit Mode
        var exitMode = $(this).attr('data-nexx-video-exitmode');
        if (typeof exitMode !== 'undefined' && exitMode !== '') {
          config.exitMode = exitMode;
        }

        // Disable Ads
        var disableAds = $(this).attr('data-nexx-video-disableads');
        if (typeof disableAds !== 'undefined' && disableAds !== '') {
          config.disableAds = Number(disableAds);
        }

        // Stream Type
        var streamType = $(this).attr('data-nexx-video-streamtype');
        if (typeof streamType !== 'undefined' && streamType !== '') {
          config.streamType = streamType;
        }

        var model = new Drupal.nexxPLAY.PlayerModel(config);

        // Add model to collection.
        Drupal.nexxPLAY.collection.add(model);

        // Prepare view options.
        var viewOptions = {
          collection: Drupal.nexxPLAY.collection,
          el: this,
          model: model
        };

        // Initialize views.
        Drupal.nexxPLAY.views.push({
          swiperObserverView: new Drupal.nexxPLAY.SwiperObserverView(viewOptions),
          playerView: new Drupal.nexxPLAY.PlayerView(viewOptions)
        });

        // API is ready?
        if (Drupal.nexxPLAY.apiIsReady) {
          model.set('apiIsReady', true);
        }
      });
    },

    detach: function (context, settings) {
      Drupal.nexxPLAY.collection.map(function (model) {
        // Remove player model from collection if detached (e.g. when closing
        // media overlay).
        if ($(context).find('#' + model.get('containerId'))) {
          Drupal.nexxPLAY.collection.remove(model);
        }
      });
    }
  };

}(jQuery, Drupal, window));
