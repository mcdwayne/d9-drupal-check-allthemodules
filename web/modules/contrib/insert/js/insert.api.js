/**
 * @file
 * This is an example on how to implement the JavaScript part of Insert handling
 * for custom insert types.
 */

(function($, Drupal) {
  'use strict';

  // The custom insert type id matching the one defined in the PHP module code:
  var INSERT_TYPE_TEXT = 'text';

  // CSS selector definitions that will be used to retrieve values.
  var SELECTORS = {
    description: 'input[name$="[description]"]'
  };

  // "insert_text" would be the module's machine name.
  Drupal.behaviors.insert_text = {};

  Drupal.behaviors.insert_text.attach = function(context) {
    $('.insert', context).each(function() {
      var $insert = $(this);

      // Prevent processing a different insert type:
      if ($insert.data('insert-type') !== INSERT_TYPE_TEXT) {
        return;
      }

      // $insert.data('insert') is the Drupal.insert.Inserter instance attached
      // to a field's value.
      var $inserter = $($insert.data('insert'));

      // Be sure to have the event listener attached only once.
      $inserter.off('.insert_text').on('insert.insert_text', function(e) {
        var inserter = e.target;

        // Inserter manages retrieving the correct template for the currently
        // selected style:
        var template = inserter.getTemplate();

        // The "root" node of the field that replacement value selectors shall
        // be triggered on:
        var $field = inserter.$container.closest('td');

        // Loop through the selectors:
        $.each(SELECTORS, function(key, selector) {
          // Process replacements.
          var value = $field.find(selector).val();
          // This demonstrates a simple placeholder replacement. For more
          // sophisticated functionality, like synchronisation, it might make
          // sense to implement a prototype inheriting from
          // Drupal.insert.Handler, attach an instance to the field and call:
          // $field.data('myHandlerInstance').buildContent();
          var fieldRegExp = new RegExp('__' + key + '__', 'g');
          template = template.replace(fieldRegExp, value);
        });

        // Return the processed template.
        return template;
      });

    });
  }

})(jQuery, Drupal);
