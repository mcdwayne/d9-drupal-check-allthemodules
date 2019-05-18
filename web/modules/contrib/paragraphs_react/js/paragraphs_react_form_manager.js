/**
 * @file
 * Defines Javascript behaviors for paragraphs_react_form_manager.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';
  Drupal.behaviors.paragraphsReactFormManager = {
    attach: function (context) {

      if($(context).hasClass('draggable')){
        Drupal.behaviors.paragraphsReactFormManager.manageDragAndDropChanges(context);
      }

      $('.field--type-entity-reference-revisions').each(function () {
        if ($(this).find('.react-jsx-save').length > 0) {
          $(this).addClass('preact-form-manager-enabled');
        }
      });

      var startIndex = 0;
      $('.preact-form-manager-enabled .paragraphs-subform').each(function () {
        if($(this).find('.preact-single-jsx').length <= 0) {
          var markup = '<div class="preact-single-jsx">';
          markup += '<label>' + Drupal.t('JSX Markup') + '</label>';
          markup += '<textarea class="form-textarea" rows="5" cols="60"></textarea>';
          markup += '<span style="display:none;" class="index-preact" id="index-preact-'+startIndex+'">'+startIndex+'</span>';
          markup += '</div>';
          $(this).prepend(markup);
          startIndex++;
        }
      });
      if(!$(context).hasClass('draggable')) {
        Drupal.behaviors.paragraphsReactFormManager.loadDefaultData();
      }
      $('.preact-single-jsx .form-textarea').bind('change keyup',function () {
        Drupal.behaviors.paragraphsReactFormManager.updateSavedData();
      });
    },
    updateSavedData : function(){
      var dataArray = [];
      $('.preact-single-jsx .form-textarea').each(function(){
        dataArray.push($(this).val());
      });
      $('.react-jsx-save').val(JSON.stringify(dataArray));
    },
    loadDefaultData : function() {
      if($('.react-jsx-save').val().length > 0){
        var jsonDefault = JSON.parse($('.react-jsx-save').val());
        var index = 0;
        $('.preact-single-jsx .form-textarea').each(function(){
          $(this).val(jsonDefault[index]);
          index++;
        });
      }
    },
    manageDragAndDropChanges: function(context) {
      var changedId = $(context).find('.index-preact').text();
      var neworderarray = [];
      var sindex = 0;
      $('.preact-form-manager-enabled .paragraphs-subform').each(function(){
        var curtxt = $(this).find('.index-preact').text();
        if(curtxt !== sindex && curtxt === changedId){
          //get data from sindex and put into changeId div
          var sindexdata = $('#index-preact-'+sindex).siblings('textarea').val();
          var changedIdData = $('#index-preact-'+changedId).siblings('textarea').val();
          $('#index-preact-'+changedId).siblings('textarea').val(changedIdData);
          $('#index-preact-'+sindex).siblings('textarea').val(sindexdata);
          //change index-preact text of sindex into changedId index-preact text
        }
        sindex++;
        neworderarray.push($(this).find('.index-preact').text());
      });
      Drupal.behaviors.paragraphsReactFormManager.updateSavedData();
    }

  }

})(jQuery, Drupal, drupalSettings);