/**
 * Drupalcamp components js
 */

(function ($) {
  "use strict";

  var $body = $('body');
  var $paragraphsToggle = $('<a href="#" class="ph-toggle">Show Paragraphs</a>').on('click', function(e) {
    e.preventDefault();
    if ($body.hasClass('show-paragraphs')) {
      $body.removeClass('show-paragraphs');
      $(this).text('Show Paragraphs');
    } else {
      $body.addClass('show-paragraphs');
      $(this).text('Hide Paragraphs');
    }
  });
  var $paragraphsLabelsToggle = $('<a href="#" class="ph-labels-toggle">Show Labels</a>').on('click', function(e) {
    e.preventDefault();
    $body.toggleClass('show-paragraphs-labels');
    if ($(this).text() === 'Show Labels') {
      $(this).text('Hide Labels');
    } else {
      $(this).text('Show Labels');
    }
  });
  var $paragraphsKey = $('<div class="ph-key"></div>');
  var $paragraphsTogglesContainer = $('<div class="ph-toggles">').append($paragraphsKey).append($paragraphsToggle).append($paragraphsLabelsToggle);

  $body.append($paragraphsTogglesContainer);

  var $paragraphs = $('.paragraph');
  var check = 'paragraph--type--';
  $paragraphs.each(function(i, el) {
    // Get the paragraph type
    var cls = $.map(this.className.split(' '), function (val, i) {
      if (val.indexOf(check) > -1) {
        return val.slice(check.length, val.length)
      }
    });

    var paragraphType = cls.join(' ');

    // Add helper elements for the border and label
    var $paragraphBorder = $('<div>').addClass('ph-paragraph-border');
    var $paragraphLabel = $('<div>').addClass('ph-paragraph-label').text(paragraphType);

    $(this).attr('data-paragraph', paragraphType);
    $(this).append($paragraphBorder.append($paragraphLabel));
  });

})(jQuery);