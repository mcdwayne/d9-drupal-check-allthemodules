/**
 * @file
 * @fileGlobal jQuery, Drupal */

(function ($, Drupal) {
  'use strict';
  
  $('div#dropzone').dropzone({
    url: '/admin/commerce-smart-upload',
    dictDefaultMessage: Drupal.t('Drag your images here or click on the box to upload')
  });

})(jQuery, Drupal);
