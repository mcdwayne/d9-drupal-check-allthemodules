/**
 * @file
 * Behaviors of fontawesome_menu_icons module.
 */

(function ($) {
  "use strict";

  /**
   * Behavior to initialize FontAwesome Icon Picker.
   *
   * @type {{attach: Drupal.behaviors.initFontAwesomeIconPicker.attach}}
   */
  Drupal.behaviors.initFontAwesomeIconPicker = {
    attach: function (context) {
      $(context)
        .find('.fa-menu-iconpicker')
        .once('init-font-awesome-icon-picker')
        .each(function () {
          $(this).iconpicker({
            placement: 'topRight',
            hideOnSelect: true,
            templates: {
              popover: '<div class="iconpicker-popover popover"><div class="arrow"></div>' +
              '<div class="popover-title"></div><div class="popover-content"></div></div>',
              footer: '<div class="popover-footer"></div>',
              buttons: '<button class="iconpicker-btn iconpicker-btn-cancel btn btn-default btn-sm">' + Drupal.t('Cancel') + '</button>' +
              '<button class="iconpicker-btn iconpicker-btn-accept btn btn-primary btn-sm">' + Drupal.t('Accept') + '</button>',
              search: '<input type="search" class="form-control iconpicker-search" placeholder="' + Drupal.t('Type to filter') + '" />',
              iconpicker: '<div class="iconpicker"><div class="iconpicker-items"></div></div>',
              iconpickerItem: '<a role="button" href="#" class="iconpicker-item"><i></i></a>'
            }
          });
        });
    }
  };

})(jQuery);
