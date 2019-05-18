/**
 * Javascript file to customize autocomplete and handle related events.
 */

(function ($, Drupal) {
  var menu_autocomplete_selected_value = '';
  Drupal.behaviors.admin_menu_search_autocomplete = {
    attach: function attach(context) {

      /* On menu select redirect to menu page. */
      $('#admin-menu-search-toolbar-tab #toolbar-item-admin-menu-search-tray .ui-autocomplete-input').on('autocompleteclose', function(e) {
        menu_autocomplete_selected_value = $(this).val();
        if (typeof e.originalEvent != 'undefined') {
          auotcomplete_widget_id = e.originalEvent.currentTarget.id;
          $('#' + auotcomplete_widget_id).find('li').each(function(){
            if (menu_autocomplete_selected_value == $(this).children('a').html()) {
              window.location = $(this).children('a').data('href');
            }
          });
        }
      });

      /* Custom render callback for autocomplete item. */
      $('.form-item-admin-menu-search-keyword input', context).each(function() {
        $(this).autocomplete().data('ui-autocomplete')._renderItem = function(ul, item) {
          ul.addClass('admin-menu-search-autocomplete-list');
          max_height = $(window).height() / 2;
          ul.css('max-height', max_height + 'px');
          return $('<li>').addClass('admin-menu-search-autocomplete-list-item')
            .append($('<a data-href="' + item.href + '">')
            .addClass('admin-menu-search-autocomplete-link')
            .html(item.label))
            .appendTo(ul);
        };
      });

      /* Keyboard shortcut (ALT + M) to access menu search form. */
      $(document).keyup(function (e) {
        if (!$(e.target).is(':input')) {
          /* Alt + M. */
          if (e.which == 77 && e.altKey) {
            toolbar_item_admin_menu_search = $('#admin-menu-search-toolbar-tab a#toolbar-item-admin-menu-search');
            if (!toolbar_item_admin_menu_search.hasClass('is-active')) {
              toolbar_item_admin_menu_search.trigger('click');
            }
            $('form#admin-menu-search-form input#edit-admin-menu-search-keyword').focus();
          }
        }
      });
    }
  };
})(jQuery, Drupal);
