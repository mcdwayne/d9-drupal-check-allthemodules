/**
 * @file
 * Provide better dragging capabilities to admin uis.
 */

/**
 * Triggers when weights columns are toggled.
 *
 * @event columnschange
 */

(function ($, Drupal, drupalSettings) {

  'use strict';
  
  Drupal.collapsibleDnd = Drupal.collapsibleDnd || {};
  
  Drupal.collapsibleDnd.makeDraggablesBetter = function(context, settings) {
    if ($('body').hasClass('collapsible-dnd--processes')) {
      return;
    }
    $('body').addClass('collapsible-dnd--processes');
    
    // TODO If the structure of the menu changes (new parents... parents no longer parents... etc), we need to update our stuff.
    
    var draggablesCollapserIndex = 0;
    $('a.tabledrag-handle', context).each(function() {
      var collapser = $('<div class="draggables-collapser state-expanded" rel-index="' + draggablesCollapserIndex + '"></div>');
      $(collapser).insertBefore(this);
      
      var childRows = Drupal.collapsibleDnd.findChildren($(this).parents('tr.draggable'));
      if (childRows.length == 0) {
        $(collapser).addClass('state-has-no-children');
      }
      draggablesCollapserIndex++;
    });
    
    $('.draggables-collapser', context).click(function() {
      // Get the ID of this collapser.
      var collapserId = $(this).attr('rel-index');
        
      // Get the children.
      var childRows = Drupal.collapsibleDnd.findChildren($(this).parents('tr.draggable'));
      
      if ($(this).hasClass('state-expanded')) {
        // Update the status of this collapser.
        $(this).removeClass('state-expanded').addClass('state-collapsed');
        
        // Loop through <td>'s of child rows.
        $('td', childRows).each(function() {
          // Hide and add the ID of the collapser.
          $(this).addClass('collapsible-dnd--hidden-td');
          $(this).attr('rel-collapsers', $(this).attr('rel-collapsers') + ' ' + collapserId);
        });
      }
      else {
        // Update the status of this collapser.
        $(this).removeClass('state-collapsed').addClass('state-expanded');
        
        // Loop through <td>'s of child rows.
        $('td', childRows).each(function() {
          // TODO Check if this child has another collpaser.
          // Show and remove the ID of the collapser.
          $(this).removeClass('collapsible-dnd--hidden-td');
          $(this).attr('rel-collapsers', $(this).attr('rel-collapsers').replace(collapserId, ''));
        });
      }
    });
  };
  
  Drupal.collapsibleDnd.findChildren = function (rowElement) {
    var rows = [];
    var indentation = $(rowElement).find('.js-indentation').length;
    var currentRow = $(rowElement).next('tr.draggable');

    while (currentRow.length) {
      // A greater indentation indicates this is a child.
      if (currentRow.find('.js-indentation').length > indentation) {
        rows.push(currentRow[0]);
      }
      else {
        break;
      }
      currentRow = currentRow.next('tr.draggable');
    }

    return rows;
  };

  /**
   * Collapsible drag'n'drop draggables.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.collapsibleDnd = {
    attach: function (context, settings) {
      Drupal.collapsibleDnd.makeDraggablesBetter(context, settings);
    }
  };

})(jQuery, Drupal, drupalSettings);
