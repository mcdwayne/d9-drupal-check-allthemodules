/**
 * @file
 * Initiatlize TimelineJS on all timeline js elements on the page.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Handles `aria-expanded` and `aria-pressed` attributes on details elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.timelinejs = {
    attach: function (context, settings) {
      // Check if timeline settings have been set.
      if (!settings.hasOwnProperty('timelinejs')) {
        return;
      }
      var $context = $(context);
      var timelineSettings = settings.timelinejs;
      $context.find('[data-timeline]').once('timelinejs-init').each(function () {
        var timeline;
        var timelineIdentifier = this.id;
        var settings = timelineSettings[timelineIdentifier];
        var options = {
          hash_bookmark: settings.hash_bookmark,
          start_at_end: settings.start_at_end,
          use_bc: settings.use_bc,
          dragging: settings.dragging,
          track_resize: settings.track_resize,
          default_bg_color: settings.default_bg_color,
          scale_factor: settings.scale_factor,
          initial_zoom: settings.initial_zoom,
          zoom_sequence: settings.zoom_sequence,
          timenav_position: settings.timenav_position,
          optimal_tick_width: settings.optimal_tick_width,
          base_class: settings.base_class,
          timenav_height: settings.timenav_height,
          timenav_height_percentage: settings.timenav_height_percentage,
          timenav_mobile_height_percentage: settings.timenav_mobile_height_percentage,
          timenav_height_min: settings.timenav_height_min,
          marker_height_min: settings.marker_height_min,
          marker_width_min: settings.marker_width_min,
          marker_padding: settings.marker_padding,
          start_at_slide: settings.start_at_slide,
          menubar_height: settings.menubar_height,
          duration: settings.duration,
          ease: TL.Ease[settings.ease],
          slide_padding_lr: settings.slide_padding_lr,
          slide_default_fade: settings.slide_default_fade,
          ga_property_id: settings.ga_property_id

        };

        timeline = new TL.Timeline(this.id, settings.url, options);
      });

    }
  };

})(jQuery, Drupal);
