/**
 * @file
 * A Backbone view for a video player to observer event when displayed
 * in a swiper context.
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.nexxPLAY.SwiperObserverView = Backbone.View.extend(/** @lends Drupal.nexxPLAY.SwiperObserverView# */{

    /**
     * Bind swiper events.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      var _this = this;

      this.$el.on('videoIsInSwiper.nexxPLAYSwiperObserverView', function () {
        _this.onVideoIsInSwiper();
      });

      this.$el.on('videoIsVisibleInSwiper.nexxPLAYSwiperObserverView', function () {
        _this.onVideoIsVisibleInSwiper();
      });

      this.$el.on('videoIsInvisibleInSwiper.nexxPLAYSwiperObserverView', function () {
        _this.onVideoIsInvisibleInSwiper();
      });
    },

    /**
     * React on video player is in swiper.
     */
    onVideoIsInSwiper: function () {
      // Always autoplay videos in visible swiper slides.
      this.model.set('autoPlay', true);
      this.model.set('isVisible', false);
    },

    /**
     * React on video player is visible in swiper.
     */
    onVideoIsInvisibleInSwiper: function () {
      this.model.set('isVisible', false);
    },

    /**
     * React on video player is invisible in swiper.
     */
    onVideoIsVisibleInSwiper: function () {
      this.model.set('isVisible', true);
    }
  });

}(jQuery, Drupal, Backbone));
