(function ($, Drupal, drupalSettings) {
    Drupal.shareSelection = Drupal.shareSelection || {};
    Drupal.shareSelection.selectedText = null;
    Drupal.shareSelection.dialogOpen = false;

    Drupal.shareSelection.getSelection = function () {
        if (window.getSelection) {
            var sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                return sel.getRangeAt(0);
            }
        }
        else if (document.selection && document.selection.createRange) {
            return document.selection.createRange();
        }
        return null;
    };

    Drupal.shareSelection.restoreSelection = function (range) {
        if (range) {
            if (window.getSelection) {
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
            else if (document.selection && range.select) {
                range.select();
            }
        }
    };

    var isDown = false;
    Drupal.behaviors.shareSelection = {
        attach: function (context, settings) {
            $('.note-selection-button').mousedown(function (e) {
                isDown = true;
            });
            $(document).mousedown(function (e) {
                if (isDown) {
                    Drupal.shareSelection.restoreSelection(Drupal.shareSelection.selectedText);
                    // Hiding share buttons.
                    setTimeout(function () {
                        $('.note-selection-wrapper').css('top', -9999).css('left', -9999);
                        isDown = false;
                    }, 500);
                }
                else {
                    $('.note-selection-wrapper').css('top', -9999).css('left', -9999);
                }
            });

            $('.field--name-' + drupalSettings.selection_note.field).find('*').mouseup(function (e) {
                if (!Drupal.shareSelection.dialogOpen) {
                    // Save selection on mouse-up.
                    Drupal.shareSelection.selectedText = Drupal.shareSelection.getSelection();
                    // Check selection text length.
                    var isEmpty = Drupal.shareSelection.selectedText.toString().length === 0;
                    // Set sharing wrapper position.
                    if (isEmpty) {
                        $('.note-selection-wrapper').css('top', -9999).css('left', -9999);
                    }
                    else {

                        $('.note-selection-wrapper').position({
                            of: e,
                            my: 'left top',
                            at: 'center',
                            collision: 'fit'
                        });
                    }
                }
            });

        }
    };
    Drupal.behaviors.appendText = {
        attach: function (context, settings) {
            $(document).ajaxComplete(function (event, request, settings) {
                $("input[name='source_node_id']").val(drupalSettings.selection_note.source_node_id);
                $("input[name='source_node_text']").val(Drupal.shareSelection.selectedText);
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
