/**
 * @file
 * SimpleMDE implementation of {@link Drupal.editors} API.
 */

(function ($, Drupal, editormd) {
  'use strict';

  var $document = $(document);

  // Regular expression used to determine existing heading.
  var headingRegExp = /^(#+)\s/;

  // Editor.md only prepends one or more # for each header handler. It doesn't
  // actually implement any logic to replace existing headers (if one decides
  // to change their minds). This is an attempt to "fix" this.
  // @todo Remove if/when this is fixed upstream.
  // @see https://github.com/pandao/editor.md/issues/551
  var heading = function (size) {
    return function () {
      var cm = this.cm;
      var cursor = cm.getCursor();
      var line = cm.getLine(cursor.line);

      // Determine the previous heading.
      var previousHeading = [].concat(line.match(headingRegExp)).filter(Boolean)[1] || '';
      var lineValue = line.replace(headingRegExp, '');

      // Retrieve the current selection value.
      var selection = cm.getSelection();

      // Determine if entire line should be replaced.
      var replaceLine = !selection || selection === lineValue;

      var heading = '#';
      for (var i = 1; i < size; i++) {
        heading += '#';
      }

      // Join the new header and value.
      var value = heading + ' ' + (replaceLine ? lineValue : selection);

      // Replace entire line.
      if (replaceLine) {
        cm.replaceRange(value, {line: cursor.line, ch: 0}, {line: cursor.line, ch: line.length});

        // Adjust cursor, taking into account any previous heading.
        if (previousHeading.length) {
          cursor.ch += heading.length - previousHeading.length;
        }

        cm.setCursor(cursor.line, cursor.ch);
      }
      // Replace selection.
      else {
        // Add newlines before and after selection.
        cm.replaceSelection('\n\n' + value + '\n\n');
        cm.setCursor(cursor.line + 2, 0);
      }
    };
  };

  // Fix existing H1...H6 toolbarHandlers.
  editormd.toolbarHandlers.h1 = heading(1);
  editormd.toolbarHandlers.h2 = heading(2);
  editormd.toolbarHandlers.h3 = heading(3);
  editormd.toolbarHandlers.h4 = heading(4);
  editormd.toolbarHandlers.h5 = heading(5);
  editormd.toolbarHandlers.h6 = heading(6);

  // Override italic to use underscores instead of asterisks.
  editormd.toolbarHandlers.italic = function () {
    var cm = this.cm;
    var cursor = cm.getCursor();
    var selection = cm.getSelection();
    cm.replaceSelection('_' + selection + '_');
    if (selection === '') {
      cm.setCursor(cursor.line, cursor.ch + 1);
    }
  };

  // Override HR to use only 3 hyphens.
  editormd.toolbarHandlers.hr = function () {
    var cm = this.cm;
    var cursor = cm.getCursor();
    cm.replaceSelection(((cursor.ch !== 0) ? '\n\n' : '\n') + '---\n\n');
  };

  editormd.toolbarModes = {
    full: [
      'bold', 'italic', 'quote', '|',
      'h2', 'h3', 'h4', '|',
      'list-ul', 'list-ol', 'hr', '|',
      'link', 'reference-link', 'image', '|',
      'code', 'preformatted-text', 'code-block', '|',
      'table', 'datetime', 'emoji', 'html-entities', '|',
      'goto-line', 'watch', 'preview', 'fullscreen', 'clear', 'search'
    ],
    simple: [
      'bold', 'italic', 'quote', '|',
      'h2', 'h3', 'h4', '|',
      'list-ul', 'list-ol', 'hr', '|',
      'watch', 'preview', 'fullscreen'
    ],
    mini: [
      'bold', 'italic', 'quote', 'link', 'image', 'list-ul', 'list-ol', 'hr'
    ]
  };

  /**
   * Editor.md.
   */
  Drupal.editors.editor_md = {

    defaults: {
      appendMarkdown: '',
      atLink: true,
      autoCloseBrackets: true,
      autoCloseTags: true,
      autoFocus: true,
      autoHeight: false,
      autoLoadModules: true,
      codeFold: true,
      crossDomainUpload: false,
      delay: 300,            // Delay parse markdown to html, Unit : ms
      dialogDraggable: true,
      dialogLockScreen: true,
      dialogMaskBgColor: '#000',
      dialogMaskOpacity: 0.1,
      dialogShowMask: true,
      disabledKeyMaps: [],
      editorTheme: 'default',
      emailLink: true,
      // @todo Add emoji support back in once Markdown module supports it.
      emoji: false,
      flowChart: false,
      fontSize: 'inherit',
      gotoLine: false,
      height: 440,
      htmlDecode: true,
      imageFormats: ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp'],
      imageUpload: false,
      imageUploadURL: '',
      indentUnit: 4,
      indentWithTabs: false,
      lineNumbers: true,
      lineWrapping: true,
      markdown: '',
      matchBrackets: true,
      matchWordHighlight: false,
      mode: 'gfm',
      name: '',
      pageBreak: false,
      path: '/libraries/editor.md/lib/',
      placeholder: '',
      pluginPath: '',
      previewCodeHighlight: true,
      previewTheme: '',
      readOnly: false,
      saveHTMLToTextarea: false,
      searchReplace: true,
      sequenceDiagram: false,
      showTrailingSpace: true,
      styleActiveLine: true,
      styleSelectedText: true,
      syncScrolling: true,
      tabSize: 4,
      taskList: false,
      tex: false,
      theme: '',
      toc: false,
      tocContainer: '',
      tocDropdown: false,
      tocm: true,
      tocStartLevel: 2,
      tocTitle: 'TOC',
      toolbar: true,
      toolbarAutoFixed: true,
      toolbarCustomIcons: {},
      toolbarHandlers: {},
      toolbarIcons: 'full',
      toolbarIconsClass: {
        bold: 'fa-bold',
        clear: 'fa-eraser',
        code: 'fa-code',
        'code-block': 'fa-file-code-o',
        datetime: 'fa-clock-o',
        del: 'fa-strikethrough',
        emoji: 'fa-smile-o',
        fullscreen: 'fa-arrows-alt',
        'goto-line': 'fa-terminal',
        h1: editormd.classPrefix + 'bold',
        h2: editormd.classPrefix + 'bold',
        h3: editormd.classPrefix + 'bold',
        h4: editormd.classPrefix + 'bold',
        h5: editormd.classPrefix + 'bold',
        h6: editormd.classPrefix + 'bold',
        help: 'fa-question-circle',
        hr: 'fa-minus',
        'html-entities': 'fa-copyright',
        image: 'fa-picture-o',
        info: 'fa-info-circle',
        italic: 'fa-italic',
        link: 'fa-link',
        'list-ol': 'fa-list-ol',
        'list-ul': 'fa-list-ul',
        pagebreak: 'fa-newspaper-o',
        'preformatted-text': 'fa-file-code-o',
        preview: 'fa-desktop',
        quote: 'fa-quote-left',
        redo: 'fa-repeat',
        'reference-link': 'fa-anchor',
        search: 'fa-search',
        table: 'fa-table',
        undo: 'fa-undo',
        unwatch: 'fa-eye',
        uppercase: 'fa-font',
        watch: 'fa-eye-slash'
      },
      toolbarIconTexts: {},
      toolbarTitles: {},
      uploadCallbackURL: '',
      value: '',
      watch: false,
      width: '100%'
    },

    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {string} format
     *   The text format for the editor.
     *
     * @return {boolean}
     *   Whether the editor was successfully attached.
     */
    attach: function (element, format) {
      var wrapper = element.parentNode;
      if (!wrapper.id) {
        wrapper.id = element.id + '-wrapper';
      }

      if (wrapper.classList.contains('editor-md-processed')) {
        return true;
      }

      wrapper.classList.add('editor-md-processed');

      return !!editormd(wrapper.id, this.getSettings(format, wrapper));
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     *   The element to detach the editor from.
     * @param {string} format
     *   The text format used for the editor.
     * @param {string} trigger
     *   The event trigger for the detach.
     *
     * @return {bool}
     *   Whether the editor was successfully detached.
     */
    detach: function (element, format, trigger) {

    },

    getSettings: function (format, wrapper) {
      var _this = this;

      var settings = $.extend(true, {}, this.defaults, format.editorSettings, {
        element: wrapper,
        forceSync: true
      });

      var onLoad = settings.onload;
      settings.onload = function () {
        var editormd = this;
        _this.onLoad(editormd);
        if (typeof onLoad === 'function') {
          $.proxy(onLoad, editormd)();
        }
      };

      // Append key bindings to toolbar icons.
      // @todo Move this upstream.
      if (typeof settings.lang === 'object' && typeof settings.lang.toolbar === 'object') {
        for (var keyBinding in editormd.keyMaps) {
          if (!editormd.keyMaps.hasOwnProperty(keyBinding)) {
            continue;
          }
          var icon = editormd.keyMaps[keyBinding];
          if (settings.lang.toolbar[icon] !== void 0) {
            settings.lang.toolbar[icon] += ' (' + keyBinding.replace('Cmd', '⌘') + ')';
          }
        }
      }

      var toolbarIcons = settings.toolbarIcons;
      settings.toolbarIcons = function () {
        return _this.getToolbarIcons(toolbarIcons, settings);
      };

      return settings;
    },

    getToolbarIcons: function (toolbarIcons, settings) {
      var icons = [];

      if (typeof toolbarIcons === 'string') {
        icons = editormd.toolbarModes[toolbarIcons];
      }
      else if (typeof toolbarIcons === 'object' && Object.prototype.toString.call(toolbarIcons) === '[object Array]') {
        icons = toolbarIcons;
      }

      if (!settings.emoji) {
        this.removeToolbarIcon(icons, 'emoji');
      }

      if (!settings.watch) {
        this.removeToolbarIcon(icons, ['watch', 'preview']);
      }

      return icons;
    },

    removeToolbarIcon: function (icons, remove) {
      remove = [].concat(remove).filter(Boolean);
      for (var i = 0, l = remove.length; i < l; i++) {
        var index = icons.indexOf(remove[i]);
        if (index !== -1) {
          icons.splice(index, 1);
        }
      }
    },

    /**
     * Reacts on a change in the editor element.
     *
     * @param {HTMLElement} element
     *   The element where the change occurred.
     * @param {function} callback
     *   Callback called with the value of the editor.
     */
    onChange: function (element, callback) {
      callback();
    },

    onLoad: function (editormd) {
      $document.trigger('editormd:loaded', editormd);
    }

  };

})(jQuery, Drupal, editormd);
