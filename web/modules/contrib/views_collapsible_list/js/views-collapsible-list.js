/**
 * @file
 * Expand/collapse functionality for list views.
 *
 * Items are expanded and collapsed in two ways:
 * - All by section: All items are expanded/collapsed via buttons.
 * - Individually: Items are expanded/collapsed via toggle icon.
 */

(function collapsibleListBehavior($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.collapsibleList = {
    attach: function collapsibleListState(context) {
      for (var i = 0; i < drupalSettings.viewsCollapsibleList.fields.length; i++) {
        var field = drupalSettings.viewsCollapsibleList.fields[i];
        $('.views-collapsible-list ' + field, context).addClass('js-collapsible').hide();
      }
      $('button.btn--collapse-all', context).attr('disabled', true);

      $('button.btn--list-collapse-action', context).click(function collapsibleListClick(event) {
        // Here expand/collapse buttons have 3 classes:
        // 1. A generic class identifying it as a collapsing action button.
        // 2. A class defines that defines the specific action.
        // 3. A class that defines the section where the action applies.
        var button = {
          action: {
            attribute: $(this).attr('class').split(' ')[1]
          },
          section: {
            expand: 'button.btn--expand-all.' + $(this).attr('class').split(' ')[2],
            collapse: 'button.btn--collapse-all.' + $(this).attr('class').split(' ')[2]
          }
        };
        event.preventDefault();

        function expandSection() {
          var section = '.views-collapsible-list ' + button.section.expand + ' ~ ul .js-collapsible';
          $(section).show('slow');
          // Disable 'expand' button.
          $(button.section.expand).attr('disabled', true);
          // Enable 'collapse' button.
          $(button.section.collapse).attr('disabled', false);
          // Add 'expanded' class to all list items.
          $(button.section.expand + ' ~ ul > li').addClass('js-expanded');
        }

        function collapseSection() {
          var section = '.views-collapsible-list ' + button.section.collapse + ' ~ ul .js-collapsible';
          $(section).hide('slow');
          // Enable 'expand' button.
          $(button.section.expand).attr('disabled', false);
          // Disable 'collapse' button.
          $(button.section.collapse).attr('disabled', true);
          // Remove 'expanded' class from all list items.
          $(button.section.collapse + ' ~ ul > li').removeClass('js-expanded');
        }

        if (button.action.attribute === 'btn--expand-all') {
          expandSection();
        }
        else {
          collapseSection();
        }
      });
    }
  };

  Drupal.behaviors.collapsibleListItem = {
    attach: function collapsibleListItemState(context) {
      $('span.collapse-expand-toggle', context).click(function expandCollapseClickHandler() {
        var $parent = $(this).parent();
        $parent.find('.js-collapsible').toggle('slow');
        $parent.toggleClass('js-expanded');

        var button = {
          section: {
            expand: 'button.btn--expand-all.' + $(this).parentsUntil('.item-list').siblings('button').attr('class').split(' ')[2],
            collapse: 'button.btn--collapse-all.' + $(this).parentsUntil('.item-list').siblings('button').attr('class').split(' ')[2]
          }
        };

        // If all items are collapsed, the expand button should be enabled,
        // and the collapse button should be disabled.
        if (($(button.section.expand + ' ~ ul > li.js-expanded').length) === 0) {
          $(button.section.expand).attr('disabled', false);
          $(button.section.collapse).attr('disabled', true);
        }
        // If all items are expanded the expand button should be disabled,
        // and the collapse button should be enabled.
        else if (($(button.section.expand + ' ~ ul > li').length) === (($(button.section.expand + ' ~ ul > li.js-expanded').length))) {
          $(button.section.expand).attr('disabled', true);
          $(button.section.collapse).attr('disabled', false);
        }
        else if ($(button.section.expand + ' ~ ul > li.js-expanded').length >= 1) {
          // If at least one item is expanded the expand button should be enabled,
          // and the collapse button should also be enabled.
          $(button.section.expand).attr('disabled', false);
          $(button.section.collapse).attr('disabled', false);
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
