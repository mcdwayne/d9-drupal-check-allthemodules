/**
 * @file
 * Entry point for Imager module.
 *
 * Attaches Imager to Drupal.behaviors.  Assigns paths and constants needed
 * for running Imager with Drupal.
 * Once page is loaded imager.init() is run once to initialize Imager
 * imager. imager.attach() is run every time content has changed either
 * initially or with AJAX.
 *
 * This file can be replaced with a similar file to make Imager run
 * as a standalone JavaScript library independent from Drupal.
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.inc
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.inc
 * Variables starting with $ are only used for jQuery 'wrapped sets' of objects.
 */

/**
 * Wrap file in JQuery().
 *
 * @param $
 */
(function ($) {
  'use strict';

  var imager;
  Drupal.behaviors.imager = {
    // Attach functions are executed by Drupal upon page load or ajax loads.
    attach: function (context, settings) {

      if (!imager) {
        var basepath = drupalSettings.path.baseUrl;
        imager = Drupal.imager.start();
        imager.init({
          modulePath: drupalSettings.imager.modulePath,
          cssContainer: drupalSettings.imager.cssContainer,
          cssImage: drupalSettings.imager.cssImage,
          basePath: basepath,
          filePath: drupalSettings.filePath,
          attachBehaviors: Drupal.attachBehaviors,
          actions: {
            displayEntity: {
              url: basepath + 'imager/ajax/display_entity'
            },
            displayMap: {
              url: basepath + 'imager/ajax/display_map'
            },
            editFormFieldLoad: {
              url: basepath + 'imager/ajax/edit_form_field_load'
            },
            saveFileEntityField: {
              url: basepath + 'imager/ajax/save_file_entity_field'
            },
            saveFile: {
              url: basepath + 'imager/ajax/save_image'
            },
            viewBrowser: {
              url: basepath + 'imager/ajax/view_browser'
            },
            emailFile: {
              url: basepath + 'imager/ajax/email_file'
            },
            deleteFile: {
              url: basepath + 'imager/ajax/delete_file'
            },
            printImage: {
              url: basepath + 'imager/ajax/print_image'
            },
            renderDialog: {
              url: basepath + 'imager/ajax/render_dialog'
            }
          }
        });
        imager.attach();
        Drupal.imager.$wrapper = $(document.createElement('div')).attr('id', 'imager-wrapper').attr('tabindex', 1);
        $('body').append(Drupal.imager.$wrapper);
      }

      // Build thumbnail list and initializes event handlers on thumbnails.
    }
  };
})(jQuery);
