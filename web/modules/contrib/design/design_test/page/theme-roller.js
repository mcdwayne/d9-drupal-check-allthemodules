(function ($) {

/**
 * Themeroller Demo.
 */
Drupal.behaviors.markupTestThemeRoller = {
  attach: function (context, settings) {
    // Accordion.
    $('#accordion', context).accordion({ header: 'h3' });
    // Autocomplete.
    $('#auto-complete', context).autocomplete({
      source: ["c++", "java", "php", "coldfusion", "javascript", "asp", "ruby", "python", "c", "scala", "groovy", "haskell", "perl"]
    });
    // Buttons.
    $("#button").button();
    $("#radioset").buttonset();
    // Tabs.
    $('#tabs', context).tabs();
    // Dialog.
    $('#dialog', context).dialog({
      autoOpen: false,
      width: 600,
      buttons: {
        'Ok': function () {
          $(this).dialog('close');
        },
        'Cancel': function() {
          $(this).dialog('close');
        }
      }
    });
    // Dialog link.
    $('#dialog_link', context).click(function () {
      $('#dialog', context).dialog('open');
      return false;
    });
    // Datepicker.
    $('#datepicker', context).datepicker({
      inline: true
    });
    // Slider.
    $('#slider', context).slider({
      range: true,
      values: [17, 67]
    });
    // Progressbar.
    $('#progressbar', context).progressbar({
      value: 20
    });
    // Hover states on static widgets.
    $('#dialog_link, ul#icons li', context).hover(
      function () {
        $(this).addClass('ui-state-hover');
      },
      function () {
        $(this).removeClass('ui-state-hover');
      }
    );
  }
};

})(jQuery);
