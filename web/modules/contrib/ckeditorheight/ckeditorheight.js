/**
 * @file
 * CKEditor height.
 */

Drupal.editors.ckeditor.attach = function(method) {
  return function (element, format)
  {
    var toPx = function(value) {
      var element = jQuery('<div style="display: none; font-size: ' + value + '; margin: 0; padding:0; height: auto; line-height: 1; border:0;">&nbsp;</div>').appendTo('body');
      var height = element.height();
      element.remove();
      return height;
    }

    var rows = element.getAttribute("rows");
    if (rows) {
      const offset = drupalSettings.ckeditorheight.offset || 1;
      const line_height = drupalSettings.ckeditorheight.line_height || 1.5;
      const unit = drupalSettings.ckeditorheight.unit || 'em';

      var height = (rows * line_height + offset);
      var heightWithUnit = height + unit;
      var heightInPx = toPx(heightWithUnit);

      format.editorSettings.height = heightInPx;
      /* Care that every editor has its own config */
      CKEDITOR.config = jQuery.extend({}, CKEDITOR.config);
      CKEDITOR.config.autoGrow_minHeight = heightInPx;
    }
    return method.apply(this, [element, format]);
  }
}(Drupal.editors.ckeditor.attach);

