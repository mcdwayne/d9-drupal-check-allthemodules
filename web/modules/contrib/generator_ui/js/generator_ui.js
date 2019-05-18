/**
 * Created by Aymen on 22/02/2016.
 */


function CopyContent(element) {
    selectTextElement(element);
    document.execCommand('Copy');
   // document.getSelection().removeAllRanges();
    console.log("Code copyed");
    return false;
}
function selectTextElement(element) {
    if (window.getSelection) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        var range = document.createRange();
        range.selectNodeContents(element);
        sel.addRange(range);
    } else if (document.selection) {
        var textRange = document.body.createTextRange();
        textRange.moveToElementText(element);
        textRange.select();
    }
}
(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.form_highlight = {
        attach: function(context, settings) {
            // Highlight result
            jQuery('pre code').each(function(i, block) {
                hljs.highlightBlock(block);
            });
            // Select Result
            jQuery(".hljs").dblclick(function(){
                selectTextElement(jQuery(this)[0]);
            });
            jQuery(".select-text").click(function(){
                var text = jQuery(this).next("code.hljs");
                CopyContent(text[0]);
            });

            return false;
        }
    };
})(jQuery, Drupal, drupalSettings);

