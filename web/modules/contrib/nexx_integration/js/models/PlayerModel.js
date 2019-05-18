/**
 * @file
 * A Backbone Model for a nexx video player.
 */

(function (Drupal, Backbone) {

  'use strict';

  /**
   * Backbone model for a nexx video player.
   *
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.nexxPLAY.PlayerModel = Backbone.Model.extend(/** @lends Drupal.nexxPLAY.PlayerModel# */{

    /**
     * @type {object}
     *
     * @prop {boolean} apiIsReady
     * @prop {boolean} autoPlay
     * @prop {string} containerId
     * @prop {boolean} disableAds
     * @prop {string} exitMode
     * @prop {boolean} isPaused
     * @prop {boolean} isVisible
     * @prop {number} playerIndex
     * @prop {boolean} playerIsReady
     * @prop {string|null} state
     * @prop {string} streamType
     * @prop {string|null} videoId
     */
    defaults: /** @lends Drupal.nexxPLAY.PlayerModel# */{

      /**
       * API is ready?
       *
       * @param {boolean}
       */
      apiIsReady: false,

      /**
       * Whether to start playback automatically.
       */
      autoPlay: false,

      /**
       * The HTML ID of the video container.
       */
      containerId: '',

      /**
       * Disable all Ad Types in this Player.
       */
      disableAds: 0,

      /**
       * The exitMode (loop, replay, load, navigate). Empty string equals to Omnia default.
       */
      exitMode: '',

      /**
       * Player is paused?
       *
       * @param {boolean}
       */
      isPaused: true,

      /**
       * Whether the player is currently visible.
       */
      isVisible: true,

      /**
       * The player index.
       */
      playerIndex: -1,

      /**
       * Whether the player is ready?
       */
      playerIsReady: false,

      /**
       * The player state.
       *
       * @param {string|null}
       */
      state: null,

      /**
       * @prop {string} streamType
       */
      streamType: 'video',

      /**
       * The video ID.
       *
       * @param {string|null}
       */
      videoId: null
    },

    /**
     * Return whether the player is initialized (player index >= 0).
     *
     * @return {boolean}
     *   Whether the player is initialized.
     */
    playerIsInitialized: function () {
      return this.get('playerIndex') >= 0;
    }
  });

})(Drupal, Backbone);
