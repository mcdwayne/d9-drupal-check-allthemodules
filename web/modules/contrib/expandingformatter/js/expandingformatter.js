/*
*
*global Drupal:true
*
**/
(function (jQuery) {
  'use strict';

  /**
   * Logic for expanding/collapsing fields that configured with a toggle.
   */
  Drupal.behaviors.expandingFormatter = {
    attach: function (context, settings) {
      var jQueryformatters = jQuery(context).find('div.expanding-formatter');
      jQueryformatters.once('expanding-formatter').each(function () {
        var jQueryformatter = jQuery(this).removeClass('expanded collapsed');
        var jQuerycontent = jQueryformatter.find('.expanding-formatter-content');
        var jQuerytrigger = jQueryformatter.find('.expanding-formatter-trigger a');
        var data = jQueryformatter.data();
        if (!data.effect) {
          data.effect = 'normal';
          jQuerycontent.hide();
        }
        else {

          // Get normal expanded height.
          data.expandedHeight = jQueryformatter.outerHeight(false);
          jQuerycontent.hide();
          data.collapsedHeight = jQueryformatter.outerHeight(false);
          jQueryformatter.addClass('collapsed').height(data.collapsedHeight);
          jQuerycontent.removeAttr('style');
          if (!data.css3) {
            jQuerycontent.hide();
          }
        }
        data = jQuery.extend({}, data, {
          jQueryformatter: jQueryformatter,
          jQuerycontent: jQuerycontent,
          jQuerytrigger: jQuerytrigger
        });
        jQuerytrigger.bind('click', function () {
          data.expanded = jQueryformatter.hasClass('expanded');

          // Non-CSS and CSS effects.
          if (typeof Drupal.expandingFormatterEffects[data.effect] !== 'undefined') {
            // Use CSS3 if applicable.
            if (data.css3 && typeof Drupal.expandingFormatterEffects[data.effect + 'Css'] !== 'undefined') {
              Drupal.expandingFormatterEffects[data.effect + 'Css'](data);
            }
            else {

              // Otherwise use non-CSS effect.
              Drupal.expandingFormatterEffects[data.effect](data);  // Otherwise use non-CSS effect.
            }
          }
          else if (data.css3 && typeof Drupal.expandingFormatterEffects[data.effect + 'Css'] !== 'undefined') {

            // CSS3 effects.
            Drupal.expandingFormatterEffects[data.effect + 'Css'](data);
          }
          // Error.
          else {
            window.alert('Unknown effect: ' + data.effect);
          }
        });
      });
    }
  };

  /**
   * Object used for animation of expanding formatters.
   *
   * If the effect supports CSS3, it should create an additional method like
   * 'effectNameCss3'.
   */
  Drupal.expandingFormatterEffects = Drupal.expandingFormatter || {
    normal: function (data) {
      if (data.expanded) {
        data.jQueryformatter
          .removeClass('expanded')
          .addClass('collapsed');
        data.jQuerycontent.hide();
      }
      else {
        data.jQueryformatter
          .removeClass('collapsed')
          .addClass('expanded');
        data.jQuerycontent.show();
      }
    },
    collapseCss: function (data) {
      data.jQueryformatter
        .removeClass('expanded')
        .addClass('collapsed')
        .height(data.collapsedHeight)
        .trigger('collapsed', [data]);
      data.jQuerytrigger.text(data.expandedLabel);
    },
    expandCss: function (data) {
      data.jQueryformatter
        .removeClass('collapsed')
        .addClass('expanded')
        .height(data.expandedHeight)
        .trigger('expanded', [data]);
      if (data.collapsedLabel) {
        data.jQuerytrigger.text(data.collapsedLabel);
      }
      else {
        data.jQuerytrigger.hide();
      }
    },
    fade: function (data) {
      if (data.jQueryformatter.hasClass('expanded')) {
        data.jQuerytrigger.fadeOut(data.jsDuration);
        data.jQuerycontent
          .css({
            display: 'inline',
            opacity: 1
          })
          .animate({
            opacity: 0
          }, data.jsDuration, function () {
            data.jQuerycontent.css({
              display: data.inline ? 'inline-block' : 'block',
              height: 0,
              overflow: 'hidden',
              width: 0
            });
            data.jQueryformatter
              .removeClass('expanded')
              .addClass('collapsed')
              .height(data.collapsedHeight)
              .trigger('collapsed', [data])
              .find('.expanding-formatter-ellipsis').fadeIn(data.jsDuration);
            data.jQuerytrigger.text(data.expandedLabel).fadeIn(data.jsDuration);
          });
      }
      else {
        data.jQueryformatter
          .removeClass('collapsed')
          .addClass('expanded')
          .height(data.expandedHeight)
          .find('.expanding-formatter-ellipsis').hide();
        data.jQuerytrigger.hide();
        if (data.collapsedLabel) {
          data.jQuerytrigger
            .text(data.collapsedLabel)
            .fadeIn(data.jsDuration);
        }
        data.jQuerycontent
          .removeAttr('style')
          .css({
            display: data.inline ? 'inline' : 'block',
            opacity: 0
          })
          .animate({
            opacity: 1
          }, data.jsDuration, function () {
            data.jQueryformatter.trigger('expanded', [data]);
          });
      }
    },
    fadeCss: function (data) {
      data.jQueryformatter.addClass('fading');
      // Collapse.
      if (data.expanded) {
        setTimeout(function () {
          data.jQueryformatter.removeClass('fading');
          Drupal.expandingFormatterEffects.collapseCss(data);
        }, 500);
      }
      // Expand.
      else {
        setTimeout(function () {
          data.jQueryformatter.removeClass('fading');
        }, 500);
        Drupal.expandingFormatterEffects.expandCss(data);
      }
    },
    slide: function (data) {
      if (data.expanded) {
        data.jQueryformatter
          .removeClass('expanded')
          .addClass('collapsed');
        data.jQuerytrigger.text(data.expandedLabel);
        data.jQueryformatter.animate({
          height: data.collapsedHeight
        }, data.jsDuration, function () {
          data.jQuerycontent.hide();
          data.jQueryformatter
            .trigger('collapsed', [data])
            .find('.expanding-formatter-ellipsis').show();
        });
      }
      else {
        data.jQueryformatter
          .removeClass('collapsed')
          .addClass('expanded')
          .find('.expanding-formatter-ellipsis').hide();
        data.jQuerycontent.show();
        if (data.collapsedLabel) {
          data.jQuerytrigger.text(data.collapsedLabel);
        }
        else {
          data.jQuerytrigger.hide();
        }
        data.jQueryformatter.animate({
          height: data.expandedHeight
        }, data.jsDuration, function () {
          data.jQueryformatter.trigger('expanded', [data]);
        });
      }
    },
    slideCss: function (data) {

      // Add/remove animation classes to assist with styles.
      data.jQueryformatter.addClass('sliding');
      setTimeout(function () {
        data.jQueryformatter.removeClass('sliding');
      }, 500);

      // Collapse.
      if (data.expanded) {
        Drupal.expandingFormatterEffects.collapseCss(data);
      }
      else {

        // Expand.
        Drupal.expandingFormatterEffects.expandCss(data);
      }
    }
  };

})(jQuery);
