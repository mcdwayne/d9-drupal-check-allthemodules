"use strict";

var develAceStorage = function() {
  this.save = function(val) {
    localStorage.setItem('devel_ace', val);
  };

  this.get = function() {
    return localStorage.getItem('devel_ace');
  };
};

var develAce = function(storage) {
  this.originalTextarea = document.querySelector('#edit-code');
  this.storage = storage;

  /**
   * Get the code from the original textarea.
   */
  this.getCode = function() {
    var code = this.editor.getValue();
    return code.substring(5, code.length);
  };

  /**
   * Get the ace editor object.
   */
  this.getEditor = function() {
    return this.editor;
  };

  /**
   * Initialise the editor with the autocomplete functions we support.
   *
   * @param autocomplete_functions
   *   An array of auto complete functions.
   */
  this.init = function(autocomplete_functions) {
    this.editor = ace.edit("devel-ace");
    this.editor.setTheme("ace/theme/textmate");
    this.editor.getSession().setMode("ace/mode/php");

    // Enable auto-completion.
    var langTools = ace.require("ace/ext/language_tools");
    this.editor.setOptions({
      enableBasicAutocompletion: true,
      enableSnippets: true,
      enableLiveAutocompletion: false
    });
    langTools.addCompleter({
      getCompletions: function(editor, session, pos, prefix, callback) {
        callback(null, autocomplete_functions.map(function(function_name) {
          return { name: '', value: function_name, score: 1, meta: "" };
        }));
      }
    });

    this.bindEvents();
    this.restoreContent();
    this.editor.focus();
  };

  /**
   * Bind any events we need.
   */
  this.bindEvents = function() {
    var self = this;

    // On blur we need to copy the code into the textarea for form submission.
    this.editor.on('blur', function() {
      self.originalTextarea.value = self.getCode();
    });

    // Store the code into local storage before we navigate away.
    window.onunload = function() {
      self.storage.save(self.getCode());
    };
  };

  /**
   * Restore the content into the editor. First we check the original devel
   * textarea, second we check local storage and finally just init with php
   * brackets.
   */
  this.restoreContent = function() {
    // If we have text in the original devel text area then we need to copy that
    // into our editor. Our editor requires PHP tags so prepend them.
    var text = this.originalTextarea.value;
    if (text.length) {
      this.populateEditor(text);
      return;
    }

    // Otherwise, maybe we can restore code from the users local storage.
    var saved = this.storage.get();
    if (saved) {
      this.populateEditor(saved);
      return;
    }

    // Finally, it's just a new session. Add some PHP tags.
    this.populateEditor("");
  };

  /**
   * Populate the editor contents and set the cursor position.
   *
   * @param content
   *   The string to put into the editor.
   */
  this.populateEditor = function(content) {

    this.editor.setValue("<?php\n\n" + content.trim(), 1);
  };
};

(function (Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.devel_ace = {
    attach: function(context) {
      var ace = new develAce(new develAceStorage()),
          drupal_functions = window.drupalSettings.devel_ace.autocomplete_functions;

      ace.init(drupal_functions);
    }
  };

})(Drupal, drupalSettings);
