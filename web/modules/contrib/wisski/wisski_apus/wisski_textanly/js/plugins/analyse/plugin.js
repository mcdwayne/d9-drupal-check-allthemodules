/**
 * @file
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {

  'use strict';


  CKEDITOR.plugins.add('wisski_analyse', {

    init: function (editor) {

      var myself = this;
      
      editor.addCommand('wisskiAnalyse', {
        
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, text) {
          
          if (!text) {
            text = editor.document.getBody().getHtml();
          }
          
          
          var processAnalysis = function(data, textStatus, jqXHR) {
            
            if (!data) {
              console.log("errors", "- unsuccessful analysis -");
            } else if (!!data.errors) {
              console.log("errors", data.errors);
            } else if (!data.data) {
              console.log("errors", "- no data returned -");
            } else {
              
              var ticket = data.ticket || null;
              data = data.data;

              // Support Undo
              editor.fire('saveSnapshot');
              
              console.log(data);

              if (!!data && !!data.annos) {
                for (var i in data.annos) {
                  
                  var anno = {},
                      a = data.annos[i];
                  anno.target = {};
                  anno.target.cat = a.class;
                  anno.target.ref = a.uri;
                  anno.body = {};
                  anno.body.textRange = a.range;
                  anno.rank = a.rank;

console.log("ba", anno);

                  editor.execCommand('wisskiSaveAnnotation', anno);

                }
              }
              
              
              // Support Undo
              editor.fire('saveSnapshot');

            }

          };

          $.ajax({
            url : Drupal.url("wisski/apus/pipe/analyse"),
            data : {
              query : {
                pipe : editor.config.wisski_analyse.pipe,  // TODO: read pipe from editor.config
                data : { text : text }
              }
            },
            dataType : 'json',
            success : processAnalysis
          });

        }

      });


      if (editor.ui.addButton) {
        editor.ui.addButton('wisskiAnalyse', {
          label: Drupal.t('Annotate'),
          command: 'wisskiAnalyse',
          icon: this.path + '/annotation.png'
        });
      }
      

    },

    

  });

})(jQuery, Drupal, CKEDITOR);
