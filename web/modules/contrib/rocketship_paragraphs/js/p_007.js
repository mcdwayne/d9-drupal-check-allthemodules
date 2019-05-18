/**
 * Rocketship UI JS
 *
 * contains: triggers for functions
 * Functions themselves are split off and grouped below each behavior
 *
 * Drupal behaviors:
 *
 * Means the JS is loaded when page is first loaded
 * + during AJAX requests (for newly added content)
 * use jQuery's "once" to avoid processing the same element multiple times
 * http: *api.jquery.com/one/
 * use the "context" param to limit scope, by default this will return document
 * use the "settings" param to get stuff set via the theme hooks and such.
 *
 *
 * Avoid multiple triggers by using jQuery Once
 *
 * EXAMPLE 1:
 *
 * $('.some-link', context).once('js-once-my-behavior').click(function () {
 *   // Code here will only be applied once
 * });
 *
 * EXAMPLE 2:
 *
 * $('.some-element', context).once('js-once-my-behavior').each(function () {
 *   // The following click-binding will only be applied once
 * * });
 */

(function ($, Drupal, window, document) {

  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') { window.rocketshipUI = {}; }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_p007 = {
    attach: function (context, settings) {

      var uspItem = $('.paragraph--type-p-007-child', context);
      var linkField = uspItem.find('.field--name-image-url-field a', context);

      // If the image has a link, insert link in the title as well
      if (uspItem.length && linkField.length) self.linkTitle(uspItem, linkField);

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * If the USP item image has a link, also add it to the USP item title
   * by wrapping the content in an a-tag
   *
   */
  self.linkTitle = function(paragraph, linkField) {

    // find the link attributes and the title field

    paragraph.once('js-once-usp-item-titlelink').each(function() {

      var titleField = paragraph.find('.field--name-field-p-title'),
        linkUrl = linkField.attr('href'),
        linkTitle = linkField.attr('title'),
        linkTarget = linkField.attr('target'),
        hasHeadingTag = false;

      // check if the field contains a h2, h3, h4, h5 or h6 tag
      var titleHtml = titleField.html();

      if (titleHtml.indexOf('<h2>') !== -1 || titleHtml.indexOf('<h3>') !== -1 || titleHtml.indexOf('<h4>') !== -1 || titleHtml.indexOf('<h5>') !== -1 || titleHtml.indexOf('<h6>') !== -1) {
        hasHeadingTag = true;
      }

      // get the heading tag from the title field + the text string,
      // because we need to wrap that text in a link-tag
      var titleTag = titleField.children().first();

      // only proceed if the title field contains a heading tag

      if (typeof titleTag !== 'undefined' && titleTag.length && hasHeadingTag) {

        var titleText = titleTag.text();

        // wrap the title text in a link tag, along with copied link-attributes

        var newTitleText = '<a href="' + linkUrl + '"';

        if (typeof linkTarget !== 'undefined' && linkTarget.length) {
          newTitleText += ' target="' + linkTarget + '"';
        }

        if (typeof linkTitle !== 'undefined' && linkTitle.length) {
          newTitleText += ' title="' + linkTitle + '"';
        }

        newTitleText += '>' + titleText + '</a>';

        // replace the tag content (text & html),
        // with our wrapped text (link + original text)
        titleTag.html(newTitleText);
      }

    });

  };

})(jQuery, Drupal, window, document);
