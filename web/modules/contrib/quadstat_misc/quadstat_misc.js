/**
 * @file
 * Quadstat Miscellaneous JavaScript behaviors
 */

/* global Quadstat_Misc, Drupal, jQuery, document */
(function ($, Drupal, document, Quadstat_Misc) {

  'use strict';

  /**
   * Attaches behaviors for Custom.
   */
  Drupal.behaviors.quadstat_misc = {
    attach: function (context, settings) {

       if ($('table#quadstat-submission-table-view').length) {
         $('table#quadstat-submission-table-view').before('<div class="go-back"><p><a href="javascript:history.go(-1)">[ Go Back ]</a></p></div>');
       }

       if ($('table#quadstat-submission-table-view').length) {
         window.addEventListener("pageshow", function(evt){
          if(evt.persisted){
            setTimeout(function(){
              window.location.reload();
            },10);
          }
         }, false);
       }

       $('.tabs__tab a').each(function() {
         if ($(this).attr('title') == 'Tour') {
           $(this).css('color', 'green');
         }
       });

       $('.tabs__tab a').click(function(e) {
         if ($(this).attr('title') == 'Tour') {
           e.stopPropagation();
           e.preventDefault();
           $('.toolbar-tab button').click();
           return false;
         }
       });
       if ($('#edit-r').length) {
         var cm = $('#edit-r').get(0)
         var text = $('#edit-r').text();
         var editor = CodeMirror(function(node) { cm.parentNode.replaceChild(node, cm); }, { mode: 'r', value: text, readOnly: false, lineWrapping:true, lineNumbers: true });
      }
      if ($('#edit-field-operation-help-0-value').length) {
        var editor = CodeMirror.fromTextArea($('#edit-field-operation-help-0-value')[0], { mode: 'htmlmixed', lineNumbers: true});
      }
      if ($('.form-item-field-operation-help-0-value textarea.form-textarea').length && $('#edit-field-operation-help-0-value').length === 0) {
        var cm = $('.form-item-field-operation-help-0-value textarea.form-textarea').get(0)
        var text = $('.form-item-field-operation-help-0-value textarea.form-textarea').text();
        var editor = CodeMirror(function(node) { cm.parentNode.replaceChild(node, cm); }, { mode: 'htmlmixed', value: text, readOnly: false, lineWrapping:true, lineNumbers: true });
      }
      // Add CSS for selector currently not possible with CSS3 (:not) chained. Webform does not add classes for every element individually
      if($('body.path-webform').length && !$('body.path-admin').length) { 
        $('details').css('display', 'none');
      }
      if($('form#node-operation-form').length) {
        $('.view-filters').css('display', 'none');
      }
     
      $('#views-exposed-form-applications-block-1').on('change', 'select', function (e) {
        var val = $(e.target).val();
        $('#views-exposed-form-applications-block-1 .ui-autocomplete-input').val(val);
      });

      $('#views-exposed-form-my-datasets-block-1').on('change', 'select', function (e) {
        var val = $(e.target).val();
        $('#views-exposed-form-my-datasets-block-1 .ui-autocomplete-input').val(val);
      });

      $('#views-exposed-form-dataset-block-1').on('change', 'select', function (e) {
        var val = $(e.target).val();
        $('#views-exposed-form-dataset-block-1 .ui-autocomplete-input').val(val);
      });

    }
  };
}(jQuery, Drupal, document));
