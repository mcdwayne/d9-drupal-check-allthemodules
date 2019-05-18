/**
 * @file
 * Javacript for the expand_collapse_formatter module.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.expandCollapseFormatter = {
    attach: function () {
      var field = $('.expand-collapse');

      field.once('expand-toggle').each(function (delta) {
        // Instantiate a new formatter class and collapse the content.
        var formatter = new Drupal.expandCollapseFormatter($(this), delta);

        // Attach click event to toggle link.
        if (typeof formatter.toggleLink !== 'undefined') {
          formatter.toggle();

          formatter.toggleLink.on('click', function (event) {
            event.preventDefault();
            formatter.toggle();
          });
        }
      });
    }
  };

  /**
   * Constructor for Drupal.expandCollapseFormatter.
   *
   * @param {object} field
   *   The field wrapper on which the expand/collapse to apply.
   * @param {string} delta
   *   The delta of field.
   */
  Drupal.expandCollapseFormatter = function (field, delta) {
    this.id = 'expand-collapse-' + delta;
    this.content = field.find('.ec-content');
    this.trimLength = field.attr('data-trim-length');
    this.state = field.attr('data-default-state');
    this.linkTextOpen = field.attr('data-link-text-open');
    this.linkTextClose = field.attr('data-link-text-close');
    this.linkClassOpen = field.attr('data-link-class-open');
    this.linkClassClose = field.attr('data-link-class-close');
    this.text = this.content.html();
    this.showMore = Drupal.t(this.linkTextOpen);
    this.showLess = Drupal.t(this.linkTextClose);

    // Set an id for the field element.
    field.attr('id', this.id);

    // Create a read more link and initiate the toggle.
    if (this.text.length > this.trimLength) {
      this.toggleLink = $('<a>', {
        text: this.showMore,
        href: '#' + this.id,
        class: 'ec-toggle-link ' + this.linkClassOpen
      });
      // Insert the read more link after the content.
      this.content.after(this.toggleLink);
      // Initiate expand/collapse.
      this.toggle();
    }
  };

  /**
   * Toggle function to expand or collapse the field.
   *
   * @type {{toggle: Drupal.expandCollapseFormatter.toggle}}
   */
  Drupal.expandCollapseFormatter.prototype = {
    toggle: function () {
      var content = '';
      var linkText = '';
      var linkClass = '';

      if (this.state === 'expanded') {
        // Trim the content content to a predefined number of characters.
        content = this.trimText(this.text);

        linkText = this.showMore;
        linkClass = 'ec-toggle-link ' + this.linkClassOpen;
        this.state = 'collapsed';
      }
      else {
        content = this.text;
        linkText = this.showLess;
        linkClass = 'ec-toggle-link ' + this.linkClassClose;
        this.state = 'expanded';
      }

      this.content.html(content);
      this.toggleLink.text(linkText);
      this.toggleLink.attr('class', linkClass);
    },
    trimText: function (text) {
      var trimmed = text.substr(0, this.trimLength);
      trimmed = trimmed.substr(
        0,
        Math.min(trimmed.length, trimmed.lastIndexOf(' '))
      );
      trimmed += ' ...';

      return trimmed;
    }
  };

})(jQuery);
