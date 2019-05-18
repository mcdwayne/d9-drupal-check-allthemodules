/**
 * @file
 * CKEditor Accordion functionality.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.CKEditorAccordion = {
    attach: function (context, settings) {

      // Create accordion functionality if the required elements exist is available.
      var $ckeditorAccordion = $('.ckeditor-accordion');
      if ($ckeditorAccordion.length > 0) {
        // Create simple accordion mechanism for each section.
        $ckeditorAccordion.each(function () {
          var $accordion = $(this);

          // Turn the accordion sections to links so that the content is accessible & can be traversed using keyboard.
          $accordion.children('dt').each(function () {
            var $section = $(this);
            var sectionText = $section.text().trim();
            var sectionContent = $section.next('dd').html();

            var ckeAccordion = $('<div class="ckeAccordion">')
              .append('<a href="javascript:void(0);" class="ckeAccordion__title ckeditor-accordion-toggle">'+
                        '<div class="icon"><svg width="9" height="14" viewBox="0 0 9 14" xmlns="http://www.w3.org/2000/svg"><path d="M2.314.878L.812 2.38l4.935 4.934-4.934 4.934 1.501 1.502L8.75 7.314z" fill="#2682A0" fill-rule="nonzero"></path></svg></div>'+
                        '<span>'+ sectionText +'</span>'+
                      '</a>'+
                      '<div class="ckeAccordion__content" style="display: none;">'+ sectionContent +'</div>');

            $accordion.append(ckeAccordion);
            $section.next('dd').remove();
            $section.remove();
          });

          // Wrap the accordion in a div element so that quick edit function shows the source correctly.
          $accordion.removeClass('ckeditor-accordion').wrap('<div class="ckeditor-accordion-container"></div>');
        });

        // Add click event to body once because quick edits & ajax calls might reset the HTML.
        $('body').once('ckeditorAccordionToggleEvent').on('click', '.ckeditor-accordion-toggle', function (e) {
          var $item = $(this).parents('.ckeAccordion');
          //var $parent = $t.parent();

          // Collapse All
          $(this).parents('.ckeditor-accordion-container').find('.ckeditor-accordion-toggle').removeClass('ckeAccordion__active');
          $(this).parents('.ckeditor-accordion-container').find('.ckeAccordion__content').slideUp(300);

          // Toggle Active one.
          $item.toggleClass('ckeAccordion__active');
          $item.find('.ckeAccordion__content').slideToggle(300);

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
