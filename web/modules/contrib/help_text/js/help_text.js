/**
 * @file
 * Toggles display of help text.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.fda_form_help_text = {
    attach: function (context, settings) {
      settings.help_text_toggle = settings.help_text_toggle || drupalSettings.help_text_toggle;
  
      // Set Title and Alt text
      var title_text = settings.help_text_toggle.title;
      var alt_text = settings.help_text_toggle.alt;
      // Set Font Awesome icon
      var icon = settings.help_text_toggle.icon;
      // Set icon size
      var icon_size = settings.help_text_toggle.icon_size;


      // Add help text icon next to label above description
      $('form .description, form .details-description').not('.better-descriptions').once().each(function( index ) {

        var icon_btn = $('<a>')
          .addClass('help_text_icon')
          .attr('href', 'javaScript:void(0);')
          .html(" <i class='fa "+icon+" "+icon_size+"' title='"+title_text+"' alt='"+alt_text+"'></i> ");

     
        // Ignore descriptions that have no text
        if(!$.trim($(this).html())){
          return;
        }

        var inputField  = $(this).prev();
        // We have different ways to target different field types
        if(inputField.find("#ajax-wrapper").length >0) {
          // Embedded upload fields
          icon_btn.addClass('ajax_wrapper_help_text_toggle');
          inputField.find("#ajax-wrapper").prev().append(icon_btn.prop('outerHTML'));
        }
        else if(inputField.hasClass("option")) {
          // Checkbox fields
          icon_btn.addClass('checkbox_help_text_toggle');
          inputField.append(icon_btn.prop('outerHTML'));
        }
        else if(inputField.hasClass("container-inline")) {
          // Date fields
          icon_btn.addClass('inline_help_text_toggle');
          inputField.prev().append(icon_btn.prop('outerHTML'));
        }
        else if (inputField.hasClass("form-managed-file")) {
          // upload fields
          return;
        }
        else if(inputField.hasClass("filter-wrapper") ) {
				// WYSIWIGfields	
  			icon_btn.addClass('wysiwig_help_text_toggle');
  			inputField.parent().find('.form-type-textarea > label').append(icon_btn.prop('outerHTML'));
  		}
        else if(inputField.hasClass("form-select")) {
          // Select fields
          icon_btn.addClass('select_help_text_toggle');
          inputField.before(icon_btn.prop('outerHTML'));
        }
        else if (inputField.hasClass("field-multiple-table")) {
          // multi-select entity reference
          icon_btn.addClass('entity_reference_help_text_toggle');
          inputField.find('.field-label').append(icon_btn.prop('outerHTML'));
        }
        else {
          // Most fields
          icon_btn.addClass('help_text_toggle');
          inputField.prev().append(icon_btn.prop('outerHTML'));
        }
        $(this).hide();

      });

      // On help icon click toggle disply of description
      $('.help_text_toggle').once().click(function() {
        // Most fields
        toggleHelpTextDisplay($(this).parent());
      });      
      $('.checkbox_help_text_toggle').once().click(function() {
        // Checkbox fields
        toggleHelpTextDisplay($(this).parent().prev());
      });      
      $('.image_help_text_toggle').once().click(function() {
        // Image fields
        toggleImageHelpTextDisplay($(this).parent());
      });
      $('.select_help_text_toggle').once().click(function() {
        // Select fields
        toggleChosenHelpTextDisplay($(this));
      });
      $('.wysiwig_help_text_toggle').once().click(function() {
        // WYSIWIG fields
        toggleHelpTextDisplay($(this).parent().parent());
      });
      $('.inline_help_text_toggle').once().click(function() {
        // Date/upload fields
        toggleHelpTextDisplay($(this).parent());
      });
      $('.ajax_wrapper_help_text_toggle').once().click(function() {
        // Embedded upload fields
        toggleHelpTextDisplay($(this).parent().parent());
      });
      $('.entity_reference_help_text_toggle').once().click(function() {
        // multi-select entity reference
        toggleEntityReferenceDispaly($(this).closest('table'));
      });
    }
  };
})(jQuery, Drupal);


/*
 * Hides help text if it is visible and shows it if it is not.
 */
function toggleHelpTextDisplay(jQueryElem) {
  if(jQueryElem.next().next().is(":visible")) {
    jQueryElem.siblings('.description').hide();
  }
  else {
    jQueryElem.siblings('.description').show();
  }
}
function toggleChosenHelpTextDisplay(jQueryElem) {
  if(jQueryElem.siblings('.description').is(":visible")) {
    jQueryElem.siblings('.description').hide();
  }
  else {
    jQueryElem.siblings('.description').show();
  }
}
function toggleImageHelpTextDisplay(jQueryElem) {
  if(jQueryElem.next().find('.details-description').is(":visible")) {
    jQueryElem.next().find('.details-description').hide();
  }
  else {
    jQueryElem.next().find('.details-description').show();
  }
}
function toggleEntityReferenceDispaly(jQueryElem) {
  if(jQueryElem.siblings('.description').is(":visible")) {
    jQueryElem.siblings('.description').hide();
  }
  else {
    jQueryElem.siblings('.description').show();
  }
}
