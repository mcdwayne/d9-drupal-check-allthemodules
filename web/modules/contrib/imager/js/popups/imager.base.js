/**
 * @file
 * Declare Imager module base class - Drupal.imager.popups.baseC.
 *
 * The dialog base class is the basis for all popups in the Imager module.
 *
 * When a dialog is opened for the first time an AJAX call is made which loads
 * the render array for the dialog and renders it.  The resulting HTML is then
 * inserted into the content area of the dialog.
 *
 * The easy way to open a dialog is to call dialogToggle.  The base class
 * loads the dialog if necessary, it then opens the dialog and calls
 * dialogOnOpen().  This results in the following logic:
 *
 * First column is the implementing class - either baseC or impC.
 * Substitute impC with the name of a real class implementing a baseC dialog.
 *
 * baseC  dialogToggle()
 * baseC    if dialogHave()
 * baseC      if dialogIsOpen()
 * baseC        dialogClose()
 * impC           dialogOnClose()
 *            else
 * baseC        dialogOpen()
 * impC           dialogOnOpen()
 * impC             dialogUpdate()
 *          else
 * baseC       dialogLoad()
 * baseC          dialogCreate()
 * impC              dialogOnCreate()
 * baseC                dialogOpen()
 * impC                    dialogOnOpen()
 * impC                       dialogUpdate()
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.js
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.js.
 */

/**
 * Wrap file in JQuery();.
 *
 * @param $
 */
(function ($) {
  'use strict';

  /**
   * Initialize a dialog.
   *
   * Convenience function to initialize a dialog and set up buttons
   * to open and close it.
   *
   * @param {string} name
   *   Name of the dialog.
   * @param {string} buttonId [optional]
   *   CSS ID of the button which opens and closes this dialog.
   * @param {Object} processFunc [optional]
   *   Function to execute when button is clicked.
   *   If not specified it defaults to dialogToggle().
   *
   * @return {baseC} popup
   */
  Drupal.imager.popups.initDialog = function initDialog(name, buttonId, processFunc) {
    var Popups = Drupal.imager.popups;
    var popup;
    if (buttonId) {
      var $button = $(buttonId);
      if ($button) {
        // Execute dialogs constructor.
        popup = Popups[name + 'C']({$selectButton: $button});
        if (processFunc) {
          $button.click(processFunc);
        }
        else {
          $button.click(popup.dialogToggle);
        }
      }
    }
    else {
      // Execute dialogs constructor.
      popup = Popups[name + 'C']({$selectButton: null});
    }
    Popups[name] = popup;
    return popup;
  };

  Drupal.imager.popups.baseC = function baseC(spec) {
    var popup = {
      settings: {},
      spec: spec || {}
    };

    // Return if popup is loaded.
    popup.dialogHave = function dialogHave() {
      return (popup.$elem) ? true : false;
    };

    // Load the dialog using AJAX.
    popup.dialogLoad = function dialogLoad() {
      Drupal.imager.core.ajaxProcess(popup,
        Drupal.imager.settings.actions.renderDialog.url,
        {
          action: 'render-dialog',
          popupName: popup.spec.name
        },
        popup.dialogCreate);
    };

    // Create popup from AJAX response.
    popup.dialogCreate = function dialogCreate(response, $callingElement) {
      // Create the popup wrapper.
      popup.$wrapper = $(document.createElement('DIV'))
        .attr('id', popup.spec.cssId)
        .addClass('imager-popup');
      Drupal.imager.$wrapper.append(popup.$wrapper);

      // Create the popup title
      if (popup.spec.title) {
        popup.$title = $(document.createElement('DIV'))
                         .addClass('imager-title')
                         .html(popup.spec.title);
        popup.$wrapper.append(popup.$title);
      }

      // Create the popup content
      popup.$content = $(document.createElement('DIV'))
        .addClass('imager-content')
        .html(response.content);
      popup.$wrapper.append(popup.$content);


      if (response.buttonpane) {
        // Create the popup buttonpane
        popup.$buttonpane = $(document.createElement('DIV'))
          .addClass('imager-buttonpane')
          .html(response.buttonpane);
        popup.$wrapper.append(popup.$buttonpane);
        popup.$buttonpane.find('input').click(function (event) {
          popup.onButtonClick(event.target.id);
        });
      }

      popup.$elem = $('#' + popup.spec.cssId);

      // Make the popup resizable.
      if (popup.spec.resizable) {
        popup.$elem.resizable({
          resize: function (event, ui) {
            if (popup.dialogOnResize) {
              popup.dialogOnResize(event, ui);
            }
          }
        });
      }

      // Make the popup draggable.
      if (popup.spec.draggable) {
        popup.$wrapper.draggable();
      }

      // Set the zIndex.
      if (popup.spec.zIndex) {
        popup.$wrapper.css('zindex', popup.spec.zIndex);
      }

      // Position the popup.
      if (popup.spec.position) {
        popup.$wrapper.css(popup.spec.position);
      }
      else {
        popup.$wrapper.css({left: '75px', bottom: '100px'});
      }

      // Let inheriting class make any final changes.
      popup.dialogOnCreate();
    };

    /**
     * Actions to take when the dialog is opened.
     *
     * @return {boolean}
     *   True if open, False if closed.
     */
    popup.dialogIsOpen = function dialogIsOpen() {
      return (popup.$elem && popup.isOpen) ? true : false;
    };

    /**
     * If the dialog is open then close it.
     */
    popup.dialogClose = function dialogClose() {
      if (popup.$elem) {
        if (popup.spec.$selectButton) {
          popup.spec.$selectButton.removeClass('checked');
        }
        if (popup.isOpen) {
          popup.isOpen = false;
          popup.$elem.hide();
        }
        popup.dialogOnClose();
        popup.settings = {};
      }
    };
    // Open the popup if it exists, otherwise create it.
    popup.dialogOpen = function dialogOpen(settings) {
      $.extend(popup.settings, settings);
      if (popup.$elem) {
        if (popup.spec.$selectButton) {
          popup.spec.$selectButton.addClass('checked');
        }
        if (!popup.isOpen) {
          popup.isOpen = true;
          popup.$elem.show();
          popup.dialogOnOpen();
        }
      }
      else {
        popup.dialogLoad();
      }
    };
    // Toggle the dialog if it exists, otherwise create it.
    popup.dialogToggle = function dialogToggle(settings) {
      $.extend(popup.settings, settings);
      if (popup.dialogHave()) {
        if (popup.dialogIsOpen()) {
          popup.dialogClose();
        }
        else {
          popup.dialogOpen(settings);
        }
      }
      else {
        popup.dialogLoad();
      }
    };

    popup.setSelectButton = function setSelectButton($elem) {
      popup.spec.$selectButton = $elem;
      if (popup.dialogHave()) {
//      popup.$elem.dialog({
//        position: {
//          my: 'left',
//          at: 'right',
//          of: $elem
//        }
//      });
      }
    };

    popup.dialogUpdate = function dialogUpdate() {
    };
    popup.dialogOnCreate = function dialogOnCreate() {
    };
    popup.dialogOnOpen = function dialogOnOpen() {
    };
    popup.dialogOnClose = function dialogOnClose() {
    };

    return popup;
  };
})(jQuery);
