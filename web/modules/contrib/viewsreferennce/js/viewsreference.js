
(function ($) {

  "use strict";

  /**
   * Handles an autocompleteselect event.
   *
   * Override the autocomplete method to add a custom event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {bool}
   *   Returns false to indicate the event status.
   */
  Drupal.autocomplete.options.select = function selectHandler(event, ui) {
    var terms = Drupal.autocomplete.splitValues(event.target.value);
    // Remove the current input.
    terms.pop();
    // Add the selected item.
    if (ui.item.value.search(',') > 0) {
      terms.push('"' + ui.item.value + '"');
    }
    else {
      terms.push(ui.item.value);
    }
    event.target.value = terms.join(', ');
    // Fire custom event that other controllers can listen to.
    jQuery(event.target).trigger('viewsreference-select');
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  }

//  Drupal.behaviors.viewsReference = {
//    attach: function (context, settings) {
//      // Show display id field after autocomplete
//
//      var autocomplete_field = jQuery('.js-form-type-entity-autocomplete input');
//      var select_field = jQuery('.js-form-type-entity-autocomplete select');
//
//      autocomplete_field.on('autocomplete-select', function() {
//        var target = $(this).val();
//        //var target_id = target.replace('#\((.*?)\)#');
//        var start_pos = target.indexOf('(') + 1;
//        var end_pos = target.indexOf(')',start_pos);
//        var target_id = target.substring(start_pos,end_pos)
//        console.log(target_id);
//        var selectOptions = Drupal.ajax({
//          base: select_field.attr('id'),
//          element: select_field.$,
//          url: Drupal.url('viewsreference/ajax/' + target_id ),
//          progress: {type: 'throbber'},
//          // Use a custom event to trigger the call.
//          event: 'views_reference_event'
//        });
////        selectOptions.options.complete = function () {
//////          self.$('.ipe-category-picker-top .ipe-icon-loading').remove();
////
////          self.setFormMaxHeight();
////
//////          self.$('.ipe-category-picker-top *').hide().fadeIn();
////        };
//        selectOptions.execute();
//
//      })
//
//    }
//  };

})(jQuery, drupalSettings);
