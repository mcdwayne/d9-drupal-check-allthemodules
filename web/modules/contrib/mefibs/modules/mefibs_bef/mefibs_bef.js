/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */
(function ($) {

// This makes sure, that the selected class is properly set on the links when
// using the options select-as-links.
Drupal.behaviors.MefibsBEFForm = {
  attach: function(context, settings) {
    $(context).each(function(index, el) {
      $('.bef-select-as-links', el).each(function() {
        var selected = $(this).find('select').val().toLowerCase().replace(/_/g, '-').replace(/ /g, '-');
        if (typeof selected == 'undefined') {
          return;
        }
        var select_id = $(this).find('select').attr('id').toLowerCase().replace(/_/g, '-').replace(/ /g, '-');
        // console.log(select_id);
        // console.log(selected);
        $(this).find('.form-item').removeClass('selected');
        $(this).find('#' + select_id + '-' + selected).addClass('selected');
      });
    });
  }
};

// Is there any way that we can be sure, that better_exposed_filters has
// already run? Anyway, their code is buggy (at least for multi form
// scenarios), so we override it here.
Drupal.behaviors.better_exposed_filters_select_as_links.attach = function(context) {

  $('.bef-select-as-links', context).once(function() {
    var $widgets = $(this).find('.views-exposed-widgets');
    // Hide the actual form elements from the user.
    $widgets.find('.bef-select-as-links select').hide();
    $(this).find('a').click(function(event) {
      // Get a shortcut to the div around links and select elements.
      var $wrapper = $(this).parents('.bef-select-as-links');
      // Get the option for the current form item.
      var $options = $wrapper.find('select option');
      // We have to prevent the page load triggered by the links.
      event.preventDefault();
      event.stopPropagation();
      var text = $(this).text();
      // Set the corresponding option inside the select element, and unset
      // all other options.
      $options.attr('selected', false);
      $options.filter(function() {
        return $(this).text() == text;
      }).attr('selected', true);
      $wrapper.find('.bef-new-value').val($options.filter(':selected').val());

      // Submit the form.
      $(this).parents('form').find('.views-submit-button input').click();
    });
  });
};

})(jQuery);
