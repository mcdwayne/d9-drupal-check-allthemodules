/**
 * @file
 * A Backbone view for a nexx video player.
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.nexxPLAY.PlayerView = Backbone.View.extend(/** @lends Drupal.nexxPLAY.PlayerView# */{

    /**
     * Initialize video player.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      this.listenTo(this.model, 'change:isVisible', this.onIsVisibleChange);
      this.listenTo(this.model, 'change:apiIsReady', this.onApiIsReadyChange);
      this.listenTo(this.model, 'change:isPaused', this.onIsPausedChange);
      this.listenTo(this.model, 'change:playerIsReady', this.onPlayerIsReadyChange);
      this.listenTo(this.model, 'change:state', this.onStateChange);
    },

    /**
     * React on API is ready changes.
     */
    onApiIsReadyChange: function () {

      /* global _play */
      if (this.model.get('apiIsReady') && !this.model.playerIsInitialized()) {
        var id = this.model.get('containerId');
        var videoId = this.model.get('videoId');
        var streamType = this.model.get('streamType');
        var config = new _play.PlayerConfiguration({
          // certain stream types do not work with the 'static' mode
          dataMode: ['video', 'audio', 'live', 'panorama'].indexOf(streamType) !== -1 ? 'static' : 'api',
          autoPlay: this.model.get('autoPlay'),
          exitMode: this.model.get('exitMode'),
          disableAds: this.model.get('disableAds')
        });

        _play.control.addPlayer(id, videoId, streamType, config);

      }
    },

    /**
     * React on is paused changes.
     */
    onIsPausedChange: function () {

      /* global _play */
      // Player is initialized?
      if (this.model.get('playerIsReady')) {
        var id = this.model.get('containerId');
        var isPaused = this.model.get('isPaused');

        // Pause player.
        if (isPaused) {
          _play.control.interact.pause(id);
        }

        // Start player.
        else {
          _play.control.interact.play(id);
        }
      }
    },

    /**
     * React on is visible changes.
     */
    onIsVisibleChange: function () {
      if (this.model.get('playerIsReady')) {
        if (this.model.get('isVisible') && this.model.get('autoPlay')) {
          this.model.set('isPaused', false);
        }
        else {
          this.model.set('isPaused', true);
        }
      }
    },

    /**
     * React on player is ready changes.
     */
    onPlayerIsReadyChange: function () {
      // Autoplay?
      if (this.model.get('autoPlay')) {
        this.model.set('isPaused', false);
      }
    },

    /**
     * React on state changes.
     */
    onStateChange: function () {
      var state = this.model.get('state');

      switch (state) {
        case 'playerready':
          if (this.model.playerIsInitialized()) {
            this.model.set('playerIsReady', true);
          }
          break;
        case 'pause':
          if (this.model.get('playerIsReady')) {
            this.model.set('isPaused', true);
          }
          break;
        case 'play':
          if (this.model.get('playerIsReady')) {
            this.model.set('isPaused', false);
          }
          break;
      }
    }
  });

}(jQuery, Drupal, Backbone));
