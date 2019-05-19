/**
 * @file
 * Drupal behavior for Skillset Inview.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Attach  behavior and notify the overlay of the Skillset Inview block.
   */
  Drupal.behaviors.skillsetInview = {
    attach: function (context) {

      $('.skillset-inview-wrapper', context).
        once('skillsetInviewSetup').
        find('.skill-bar').
        css({width: '0'}).
        parent().
        find('.percent').
        hide();

      $('.skillset-inview-wrapper', context).once('skillsetInview').on('inview', function (event, visible) {
        var $thisBlock = $(this);
        if (visible) {
          Drupal.skillsetInview.showSkillsets($thisBlock);
        }
        else {
          Drupal.skillsetInview.hideSkillsets($thisBlock);
        }
      });

      $('.skillset-inview-wrapper', context).once('skillsetInviewClick').on('click', function () {
        var $thisBlock = $(this);
        Drupal.skillsetInview.showSkillsets($thisBlock);
      });

    }
  };

  /**
   * Init namespace.
   */
  Drupal.skillsetInview = Drupal.skillsetInview || {};

  /**
   * Random number within a range generator.
   *
   * @param {number} min
   * min number to use
   * @param {number} max
   * max number to use
   *
   * @return {number} timer
   * time to use for speed of progress bar
   */
  Drupal.skillsetInview.randomNum = function (min, max) {
    return Math.random() * (max - min) + min;
  };

  /**
   * Routine to close/reset bar graphs.
   *
   * @param {Object} $thisBlock
   * jQuery object to process
   */
  Drupal.skillsetInview.hideSkillsets = function ($thisBlock) {
    $thisBlock.find('.skill-line .percent').stop(true, true).fadeOut('0', function () {
      $thisBlock.find('.skill-bar').stop(true, true).css({width: '0%'});
    });
  };

  /**
   * Routine to animate open bar graphs.
   *
   * @param {Object} $thisBlock
   * jQuery object to process
   */
  Drupal.skillsetInview.showSkillsets = function ($thisBlock) {
    var rows = $thisBlock.find('.skill-row');
    $.each(rows, function () {
      var percent = $(this).find('.skill-bar').data('percent');
      var time = Drupal.skillsetInview.randomNum(300, 1200);
      var $thisRow = $(this);
      $thisRow.find('.skill-bar').stop(true, true).delay(time).animate({width: percent + '%'}, 1600, 'easeOutQuart', function () {
        $thisRow.find('.skill-line .percent').stop(true, true).fadeIn('800');
      });
    });
  };

}(jQuery, Drupal));
