/**
 * @file
 * Handles the AJAX for the view_show_more plugin.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.AjaxCommands.prototype.viewsShowMore = function (ajax, response) {
    var selector = response.selector || ajax.wrapper;
    var $wrapper = $(selector);
    var method = response.method || ajax.method;
    var appendAt = response.append_at || '';
    var effect = ajax.getEffect(response);
    var settings = response.settings || ajax.settings || drupalSettings;
    var currentViewId = selector.replace('.js-view-dom-id-', 'views_dom_id:');

    var $newContent = $($.parseHTML(response.data, document, true));
    $newContent = Drupal.theme('ajaxWrapperNewContent', $newContent, ajax, response);

    // Get the existing ajaxViews object.
    var view = Drupal.views.instances[currentViewId];

    Drupal.detachBehaviors($wrapper.get(0), settings);
    view.$view.removeOnce('ajax-pager');
    view.$exposed_form.removeOnce('exposed-form');

    // Set up our default query options. This is for advance users that might
    // change there views layout classes. This allows them to write there own
    // jquery selector to replace the content with.
    // Provide sensible defaults for unordered list, ordered list and table
    // view styles.
    var contentSelector = appendAt && !response.options.content_selector ? '.view-content ' + appendAt : response.options.content_selector || '.view-content';
    var pagerSelector = response.options.pager_selector || '.pager-show-more';

    // Immediately hide the new content if we're using any effects.
    if (effect.showEffect !== 'show' && effect.showEffect !== 'scrollToggle') {
      $newContent.find(contentSelector).children().hide();
    }

    var $contentArea = $wrapper.find(contentSelector);

    // Scrolling effect.
    if (effect.showEffect === 'scroll_fadeToggle' || effect.showEffect === 'scrollToggle') {
      // Get old contenter height.
      var oldHeight = $contentArea.addClass('clearfix').height();

      // Get content count.
      var oldItems = $contentArea.children().length;
      var newItems = $newContent.find(contentSelector).children().length;

      // Calculate initial new height.
      var newHeight = oldHeight + Math.ceil(oldHeight / oldItems * newItems);

      // Set initial new height for scrolling.
      if (effect.showEffect === 'scroll_fadeToggle') {
        $contentArea.height(newHeight);
      }

      // Get offset top for scroll.
      var positionTop = $contentArea.offset().top + oldHeight - 50;

      // Finally Scroll.
      $('html, body').animate({scrollTop: positionTop}, effect.showSpeed);
    }

    // Update the pager
    $wrapper.find(pagerSelector).replaceWith($newContent.find(pagerSelector));

    // Add the new content to the page.
    $contentArea[method]($newContent.find(contentSelector).children());

    if (effect.showEffect !== 'show' && effect.showEffect !== 'scrollToggle') {
      if (effect.showEffect === 'scroll_fadeToggle') {
        effect.showEffect = 'fadeIn';
      }
      $contentArea.children(':not(:visible)')[effect.showEffect](effect.showSpeed);
      $contentArea.queue(function (next) {
        $(this).css('height', 'auto');
        next();
      });
    }

    // Additional processing over new content.
    $wrapper.trigger('viewsShowMore.newContent', $newContent.clone());

    Drupal.attachBehaviors($wrapper.get(0), settings);
  };

})(jQuery, Drupal, drupalSettings);
