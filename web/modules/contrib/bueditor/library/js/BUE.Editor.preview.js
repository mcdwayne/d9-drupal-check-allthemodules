(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor preview.
 */

/**
 * Editor preview builder.
 */
BUE.buildEditorPreview = function(E) {
  // Preview element is created on demand. Set a destroyer for it.
  E.bind('destroy', BUE.destroyEditorPreview);
};

/**
 * Editor preview destroyer.
 */
BUE.destroyEditorPreview = function(E) {
  E.hidePreview();
  delete E.previewEl;
};

/**
 * Toggles the preview.
 */
Editor.togglePreview = function(content) {
  return this.previewing ? this.hidePreview() : this.showPreview(content);
};

/**
 * Shows the preview with content.
 */
Editor.showPreview = function(content) {
  this.setState('previewing');
  return this.setPreviewContent(content);
};

/**
 * Hides the preview.
 */
Editor.hidePreview = function() {
  var xhr;
  this.unsetState('previewing');
  // Abort if a preview request is in progress.
  if (xhr = this.previewXHR) {
    delete this.previewXHR;
    if (xhr.abort) xhr.abort();
  }
};

/**
 * Sets the preview content.
 */
Editor.setPreviewContent = function(content) {
  var previewEl = this.getPreviewEl();
  $(previewEl).html(content || '');
  return previewEl;
};

/**
 * Creates and returns the preview element.
 */
Editor.getPreviewEl = function() {
  var previewEl = this.previewEl;
  if (!previewEl) {
    previewEl = this.previewEl = BUE.createEl('<div class="bue-preview"></div>');
    this.textareaWrapperEl.appendChild(previewEl);
  }
  return previewEl;
};

})(jQuery, BUE, BUE.Editor.prototype);