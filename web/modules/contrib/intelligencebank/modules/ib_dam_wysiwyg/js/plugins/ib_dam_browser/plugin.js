/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($, _, Drupal, CKEDITOR, _ib) {
  
  "use strict";
  
  const WIDGET_ID = 'ib_dam_browser';
  const COMMAND_ID = 'ib_dam_browser';
  
  /**
   * A CKEditor plugin for IntelligenceBank Asset Browser.
   */
  CKEDITOR.plugins.add(WIDGET_ID, {
    requires: 'widget',
    canUndo: false,
  
    onLoad: function() {
      // @todo: move this css into file.
      CKEDITOR.addCss('.id-dam-widget {\n' +
        '  display: block;\n' +
        '  padding: 8px;\n' +
        '  margin: 10px;\n' +
        '  background: #eee;\n' +
        '  border-radius: 4px;\n' +
        '  border: 1px solid #ddd;\n' +
        '  box-shadow: 0 1px 1px #fff inset, 0 -1px 0px #ccc inset;\n' +
        '}\n' +
        '.id-dam-widget__summary {\n' +
        '  text-align: center;\n' +
        '  display: block;\n' +
        '}');
    },
    
    init: function (editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
      this.registerContextMenu(editor);
      this.registerDbClick(editor);
    },
  
    ibNotices: {},
  
    /**
     * Show asset display settings on dbclick event.
     *
     * @param editor CKEDITOR.editor
     */
    registerDbClick: function (editor) {
      editor.on('doubleclick', function (evt) {
        var currentWidget = getCurrentWidgetData(editor);
        if (_ib.isEmpty(currentWidget)) {
          return null;
        }
        var info = currentWidget.element.findOne('.id-dam-widget__summary');
        var text = info['$'].textContent + '<br><br>' + info.getAttribute('title');
        var msg  = createIbWysiwygMessage(editor, 'success', text, '' , '');
  
        msg.show();
      });
    },
    
    // @todo: add context menu with "align" commands.
    registerContextMenu: function () {},
    
    addCommand: function (editor) {
      var self = this;
      
      var dialogSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        
        if (self.dialogSave(editor, values)) {
          editor.fire('saveSnapshot');
        }
      };
      
      editor.addCommand(COMMAND_ID, {
        exec: function (editor, data) {
          self.ibNotices.badElement = createIbWysiwygMessage(editor, 'info', '', 'DrupalIbDamBrowser_messageUseEmptyOrIbElement', '');
          
          var existingValues = {},
            currentWidget = editor.widgets.focused,
            url = Drupal.url('ib-dam-wysiwyg/dialog/' + editor.config.drupal.format),
            
            dialogSettings = {
              title: editor.config.DrupalIbDamBrowser_dialogTitleAdd,
              dialogClass: 'ib-dam-wysiwyg-dialog'
            };
          
          if (currentWidget) {
            existingValues = currentWidget.data.json;
          }
  
          if (currentWidget && currentWidget.name !== WIDGET_ID) {
            self.ibNotices.badElement.show();
            return;
          }
          
          if (_ib.isEmpty(existingValues)) {
            // @todo: PRE handle better focus positioning.
            //self.initFocus(editor);
          }
  
          Drupal.ckeditor.openDialog(editor, url, existingValues, dialogSaveWrapper, dialogSettings);
        }
      });
    },
    
    initFocus: function(editor) {
      editor.focus();
      var selection       = editor.getSelection();
      var selected_ranges = selection.getRanges();
      if (typeof selected_ranges[0] === 'undefined') {
        selected_ranges[0] = editor.createRange();
        selected_ranges[0].endContainer = selected_ranges[0].root;
      }
      var node    = selected_ranges[0].endContainer;
      var parents = node.getParents(true);
      var empty   = editor.document.createElement('p');

      node = parents[parents.length - 2].getFirst();
      while (true) {
        var x = node.getNext();
        if (x == null) break;
        node = x;
      }
      selection.selectElement(node);
      if (node.hasOwnProperty('scrollIntoView')) {
        node.scrollIntoView(true);
        selected_ranges = selection.getRanges();
        selected_ranges[0].collapse(false);
        selection.selectRanges(selected_ranges);
        editor.insertHtml(empty.getOuterHtml());
      }
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    dialogSave: function (editor, values) {
      if (values.hasOwnProperty('errors')) {
        var error = createIbWysiwygMessage(editor, 'warning', values.errors, '', 0);
        error.show();
        return false;
      }
      else {
        this.initFocus(editor);
        var widget = editor.document.createElement('p');
        widget.setHtml(JSON.stringify(values));
        editor.insertHtml(widget.getOuterHtml());
      }
      return true;
    },
    
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add(WIDGET_ID, {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: false,
        init: self.initElement,
        data: self.data,
      });
    },

    /**
     * Get widget preview.
     */
    upcast: function (element, data) {
      if (!element.getHtml().match(/^({(?=.*source_type\b)(?=.*type)(?=.*name)(?=.*display_settings)(?=.*remote_url\b|.*file_id\b)(?=.*preview_uri\b)(.*)})$/)) {
        return;
      }
      data.json = decodeHtmlEntities(JSON.parse(element.getHtml()));

      element.setHtml(Drupal.theme('ibDamWysiwygWidget', data.json));
      return element;
    },

    /**
     * Serialize widget settings.
     */
    downcast: function (element) {
      this.data.json = decodeHtmlEntities(this.data.json);
      element.setHtml(JSON.stringify(this.data.json));
    },
    
    /**
     * Set size of the element based on thumbnail size.
     *
     * Workaround for local assets that have image_styles,
     * because we can't get dimensions from image style directly on backend,
     * we wait on frontend until images will be rendered,
     * and then apply element sizes based on thumbnail sizes.
     */
    initElement: function() {
      // this -> widget.
      var img = this.element.findOne('img');
      var me  = this;
  
      setTimeout(function() {
        var display = me.data.json.display_settings;
        
        if (img === null
          || _ib.isEmpty(img['$'])
          || _ib.isEmpty(display.width)
        ) {
          return false;
        }
        
        var imgW    = $(img['$']).width();
        var widgetW = me.element['$']['clientWidth'];
  
        if (imgW < 100) {
          me.element.setStyle('width', parseInt(display.width) + 'px');
        }
        else if ((widgetW - imgW) > 50) {
          me.element.setStyle('width', imgW + 'px');
        }
      }, 100);
    },
  
    data: function() {
      var settings = this.data.json.display_settings;
      if (!_ib.isEmpty(settings.width)) {
        this.element.setStyle('width', settings.width + 'px');
      }
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton(WIDGET_ID, {
        label: editor.config.DrupalIbDamBrowser_editorButtonLabel,
        command: COMMAND_ID,
        icon: this.path + '/icon.png'
      });
    }
  });
  
  // Make dialog width relative, not a fixed.
  $(window).on('dialog:beforecreate', function (e, dialog, $element, settings) {
    var classes = settings.dialogClass ? settings.dialogClass.split(' ') : [];
    
    if (classes.indexOf('ib-dam-wysiwyg-dialog') !== -1) {
      settings.width = '90%';

      $element.parent('.ui-dialog')
        .removeClass('.ui-dialog--narrow')
        .animate({ width: '800px' });
      
      settings.dialogClass = _(classes)
        .filter(function(c){ return c !== 'ui-dialog--narrow'; })
        .join(' ');
    }
  });
  
  /**
   * The widget template viewable in the WYSIWYG.
   */
  Drupal.theme.ibDamWysiwygWidget = function (settings) {
    // @todo: PRE rewrite this using underscore _.template,
    // to get better html structure overview.
    var markup  = $('<span class="id-dam-widget"></span>');
    var display = settings.display_settings;
    var text    = _ib.flattenProperties(display);
    
    if (['image', 'video'].indexOf(settings.type) !== -1) {
      var preview = $('<img class="id-dam-widget__image">')
        .attr('src', settings.preview_uri);
        
      if (!_ib.isEmpty(display.width)) {
        preview.width(display.width);
      }
      markup.append(preview);
    }
    
    $('<span class="id-dam-widget__summary"></span>')
      .attr('title', text)
      .text('Asset name: ' + Drupal.checkPlain(settings.name))
      .appendTo(markup);
    
    return markup.get(0).outerHTML;
  };

  /**
   * Decode html entities within the remote and/or preview URLs.
   *
   * @param {Object} settings
   *   Settings object.
   *
   * @return {Object}
   *   Updated version of settings object.
   */
  function decodeHtmlEntities(settings) {
    if (settings.hasOwnProperty('display_settings')) {
      var display = settings.display_settings;
      if (display.hasOwnProperty('preview_uri')) {
        display.preview_uri = decodeString(display.preview_uri);
      }
      if (display.hasOwnProperty('remote_url')) {
        display.remote_url = decodeString(display.remote_url);
      }
    }
    if (settings.hasOwnProperty('remote_url')) {
      settings.remote_url = decodeString(settings.remote_url);
    }
    if (settings.hasOwnProperty('preview_uri')) {
      settings.preview_uri = decodeString(settings.preview_uri);
    }
    return settings;
  }

  /**
   * Small callback function for the decodeHtmlEntities.
   */
  function decodeString(str) {
    return _.unescape(Drupal.checkPlain(str));
  }
  
  function getCurrentWidgetData(editor) {
    var currentWidget = editor.widgets.focused;
    return currentWidget && currentWidget.name === WIDGET_ID
      ? currentWidget
      : false;
  }
  
  function createIbWysiwygMessage(editor, type, message, messageKey, duration) {
    var text = editor.config.hasOwnProperty(messageKey) && messageKey !== ''
      ? editor.config[messageKey]
      : message;
    
    return new CKEDITOR.plugins.notification(editor, {
      message: text,
      type: type,
      duration: duration !== '' ? duration : 7000
    });
  }

})(jQuery, _, Drupal, CKEDITOR, ibDam);
