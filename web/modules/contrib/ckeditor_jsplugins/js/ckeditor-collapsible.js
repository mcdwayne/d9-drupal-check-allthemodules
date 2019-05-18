/**
 * @file
 * CKEditor Collapsible functionality.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.CKEditorCollapsible = {
    attach: function (context, settings) {

      // Create collapsible functionality if the required elements exist is available.
      var $ckeditorCollapsible = $('.ckeditor-collapsible');
      if ($ckeditorCollapsible.length > 0) {
        // Create simple collapsible mechanism for each section.
        $ckeditorCollapsible.each(function () {
          var $collapsible = $(this);

          // Turn the collapsible sections to links so that the content is accessible & can be traversed using keyboard.
          $collapsible.children('dt').each(function () {
            var $section = $(this);
            var sectionText = $section.text().trim();
            var sectionContent = $section.next('dd').html();

            var ckeCollapsible = $('<div class="ckeCollapsible">')
              .append('<a href="javascript:void(0);" class="ckeCollapsible__title ckeditor-collapsible-toggle">'+
                        '<div class="icon"><svg width="9" height="14" viewBox="0 0 9 14" xmlns="http://www.w3.org/2000/svg"><path d="M2.314.878L.812 2.38l4.935 4.934-4.934 4.934 1.501 1.502L8.75 7.314z" fill="#2682A0" fill-rule="nonzero"></path></svg></div>'+
                        '<span>'+ sectionText +'</span>'+
                      '</a>'+
                      '<div class="ckeCollapsible__content" style="display: none;">'+ sectionContent +'</div>');

            $collapsible.append(ckeCollapsible);
            $section.next('dd').remove();
            $section.remove();
          });

          // Wrap the collapsible in a div element so that quick edit function shows the source correctly.
          $collapsible.removeClass('ckeditor-collapsible').wrap('<div class="ckeditor-collapsible-container"></div>');
        });

        // Add click event to body once because quick edits & ajax calls might reset the HTML.
        $('body').once('ckeditorCollapsibleToggleEvent').on('click', '.ckeditor-collapsible-toggle', function (e) {
          var $item = $(this).parents('.ckeCollapsible');
          //var $parent = $t.parent();

          $item.toggleClass('ckeCollapsible__active');
          $item.find('.ckeCollapsible__content').slideToggle(300);

          // Clicking on open element, close it.
          //if ($t.hasClass('active')) {
          //  $t.removeClass('active');
          //  $t.next().slideUp();
          //}
          //else {
            // Remove active classes.
            //$parent.children('dt.active').removeClass('active').children('a').removeClass('active');
            //$parent.children('dd.active').slideUp(function () {
            //  $(this).removeClass('active');
            //});

            // Show the selected section.
            //$t.addClass('active');
            //$t.next().slideDown(300).addClass('active');
          //}

          // Don't add hash to url.
          e.preventDefault();
        });
      }
    }
  };
})(jQuery);
