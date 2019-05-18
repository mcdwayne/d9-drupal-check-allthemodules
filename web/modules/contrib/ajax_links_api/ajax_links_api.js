/**
 * @file
 * Handles ajax functionalities for Ajax Links API module.
 */

(function ($) {

  'use strict';

  var selectorNew = '';
  var title = '';
  var ajaxLoading = false;
  Drupal.behaviors.ajaxLinksApi = {
    attach: function (context) {
      var trigger = drupalSettings.data.ajax_links_api.trigger;
      var negativeTrigger = drupalSettings.data.ajax_links_api.negative_triggers;

      // Match the elements from the positive selector.
      var $elements = $(trigger);

      // Remove elements if the negative trigger is specified.
      if (negativeTrigger) {
        $elements = $elements.not(negativeTrigger);
      }

      // Add the click handler.
      $elements.click(function (e) {
        e.preventDefault();
        var selector;
        if (!ajaxLoading) {
          ajaxLoading = true;
          var url = $(this).attr('href');
          var id = $(this).attr('rel');
          if (id) {
            selector = $(this).attr('rel');
          }
          else {
            selector = drupalSettings.data.ajax_links_api.selector;
          }
          ajaxBefore(selector);
          ajaxLink(selector, url, context, true);
        }
      });

      window.onpopstate = function (e) {
        // When click this will get the 'current url' (since onpopstat is called.
        // AFTER the url change) and telling him not to add it to the state list.
        var html5 = drupalSettings.data.ajax_links_api.html5;
        if (html5 === 1 && window.history.state !== null) {
          // window.history.state is to tell the system if it was a 'soft' link or a
          // 'hard link' ie if there was a page in between that was not managed by
          // this script.
          var selector;
          if (!ajaxLoading) {
            ajaxLoading = true;
            selector = drupalSettings.data.ajax_links_api.selector;
            ajaxBefore(selector);
            ajaxLink(selector, document.URL, context, false);
          }
        }
      };
    }
  };
  function ajaxLink(selector, url, context, dochangestate) {
    var scriptsIncluded = drupalSettings.data.ajax_links_api.scripts_included;
    // If $scripts not included, attach existing scripts.
    if (scriptsIncluded === 0) {
      selectorNew = context;
    }
    else {
      selectorNew = selector;
    }

    $.ajax({
      url: url,
      type: 'GET',
      data: 'ajax=1',
      success: function (data) {
        ajaxAfter(selector, url, data, window, document, scriptsIncluded, dochangestate);
        Drupal.attachBehaviors(selectorNew);
      },
      error: function (xhr) {
        var data = xhr.response.replace('?ajax=1', '');
        ajaxAfter(selector, url, data, window, document, scriptsIncluded, dochangestate);
      }
    });
  }
  function ajaxBefore(selector) {
    // Preserve the height of the current content to avoid the entire page
    // collapsing.
    $(selector).css('height', $(selector).height() + 'px');

    // Replace the content with a throbber.
    $(selector).html('<div class="ajax-links-api-loading"></div>');
  }
  function ajaxAfter(selector, url, data, window, document, scriptsIncluded, dochangestate) {
    // Uncomment this if you are ussing html--ajax.tpl.php without $scripts.
    // For more details , check https://drupal.org/node/1923320
    if (scriptsIncluded === 0) {
      ajaxLoading = false;
    }

    // Reset the height of the container.
    $(selector).css('height', '');

    // Replace the contents of the container with the data.
    $(selector).html(data);

    // Update active class.
    $('a.active').removeClass('active').parents('li').removeClass('active-trail');
    // To check if the url ends with the link.
    $('a').filter(function () {
      return url.endsWith($(this).attr('href'));
    }).addClass('active').parents('li').addClass('active-trail');

    // Change Url if option is selected and for html5 compatible browsers.
    var html5 = drupalSettings.data.ajax_links_api.html5;
    if (html5 === 1 && window.history.replaceState) {
      // Get title of loaded content.
      var matches = data.match('<title>(.*?)</title>');
      if (matches) {
        // Decode any HTML entities.
        title = $('<div/>').html(matches[1]).text();
        // Since title is not changing with window.history.pushState(),
        // manually change title. Possible bug with browsers.
        document.title = title;
      }
      if (dochangestate) {
        // Store current url.
        window.history.replaceState({page: 0}, document.title, window.location.href);
        // Change url.
        window.history.pushState({page: 1}, title, url);
      }
    }

    // Views Pager.
    // Please check http://drupal.org/node/1907376 for details.
    var viewsPager = drupalSettings.data.ajax_links_api.vpager;
    if (viewsPager === 1) {
      $(selector + ' .view .pager a').each(function () {
        var href = $(this).attr('href');
        href = href.replace('?ajax=1', '');
        href = href.replace('&ajax=1', '');
        $(this).attr('href', href);
      });
    }

    // Form Validation.
    // Plese check http://drupal.org/node/1760414 for details.
    var formAction = $(selector + ' form').attr('action');
    if (formAction) {
      formAction = formAction.replace('?ajax=1', '');
      $('form').attr('action', formAction);
    }
  }
})(jQuery);
