
JSONEditor.defaults.themes.drupal_seven = JSONEditor.AbstractTheme.extend({

  getFormInputField: function(type) {
    var el = this._super(type);
    var knownTypes = ['text','date','time']
    el.className += ' form-' + (knownTypes.indexOf(type) >= 0 ? type : 'text');
    return el;
  },
  getIndentedPanel: function() {
    var el = document.createElement('div');
    el.style = el.style || {};
    //el.style.paddingLeft = '10px';
    //el.style.marginLeft = '10px';
    //el.style.borderLeft = '1px solid #ccc';
    return el;
  },
  getButtonHolder: function() {
    var el = document.createElement('div');
    el.style.paddingLeft = '10px';
    return el;
  },
  getButton: function(text, icon, title) {
    var el =  this._super(text, icon, title);
    el.className += ' button';
    return el;
  },
  getHeader: function(text) {
    return document.createElement('div');
  }

});

(function($) {
  function initJsonFormEditor(container) {
    $('.field--widget-json-form-editor .form-textarea-wrapper', container).each(function(i,el) {
      var taEl = $('textarea.form-textarea', el);
      var json = {};
      try {
        if ($(el).data('editor')) {
          console.warn('already has json editor', el);
          return;
        }
        if(taEl.text()) json = JSON.parse(taEl.text());

        // could be cleaner, do attached libraries have access to field_names?
        // normally "field_performances[value]"
        // in field settings "default_value_input[field_performances][value]"
        // in ief "field_ship_events[form][inline_entity_form][field_performances][value]"
        var name_parts = $(taEl)
          .attr('name').split(/[\[\]]/)
          .filter(function(e){ return !!e; });
        var value_pos = name_parts.lastIndexOf('value');
        var field_name = name_parts[value_pos-1];

        if(!drupalSettings.json_form_editor[field_name].schema) {
          var formUrl = '/admin/structure/types/manage/'+drupalSettings.json_form_editor.bundle+'/form-display';
          $(el).append('<span class="form-item--error-message"> Schema is empty! Go to <a href="'+formUrl+'">field settings</a> to change it</span>');
          return;
        }
        // check if options can be overriden in schema?
        var editor = new JSONEditor(el, {
          schema: JSON.parse(drupalSettings.json_form_editor[field_name].schema),
          startval: json,
          disable_collapse: true,
          disable_edit_json: true,
          disable_properties: true,
          theme: 'drupal_seven',
          ajax: true
        });
        $(el).data('editor', editor);

        editor.on('change',function() {
          // with IEF if textinput was changed and Update clicked without moving cursor away, jsoneditor called
          // eventhandlers after $(el).data('editor') had become undefined, this relies on patched jsoneditor
          if(!(this instanceof JSONEditor)) {
            console.warn('unsupported version of jsoneditor detected');
            return;
          }
          var val = this.getValue();
          $('textarea.form-textarea', el).text( JSON.stringify(val) );
        }); 

        taEl.hide();
      } catch(ex) {
        $(el).append('<span class="form-item--error-message"> Error while creating editor: '+ (ex ? ex.message : '') +'</span>');
        console.error(ex);
      }
    });
  }

  $(function() {
    // if IEF installed hook the event, this is true even if current page is not using IEF
    // so init might be called multiple times
    if(Drupal.behaviors.inlineEntityForm) {
      // IEF calls this first with the embedded form and then with the containing form, we could
      // skip the containing form, that has already been initialized during page load
      Drupal.behaviors.inlineEntityForm.attach = initJsonFormEditor;
    }

    // wait until CKEditor is done with the page, otherwise it goes crazy over missing styles.js and config.js
    // Uncaught Error: [CKEDITOR.resourceManager.load] Resource name "default" was not found at
    if(typeof CKEDITOR !== 'undefined') {
      CKEDITOR.on("instanceReady", initJsonFormEditor);
    } else {
      initJsonFormEditor();
    }
  });
})(jQuery);
