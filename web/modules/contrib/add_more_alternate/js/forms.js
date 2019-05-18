(function($, Drupal, drupalSettings) {

  $(function() {
    $('table.field-multiple-table').each(function() {
      var first = true,
          $table = $(this),
          $trs = $table.find('tbody tr'),
          total = $trs.length,
          setRemoveAdd = function() {
            $table.parent().find('.add-item').off('click').remove();
            $table.parent().find('.remove-item').off('click').remove();

            // Add item button.
            if ($trs.filter('.visually-hidden').length !== 0) {
              $table.parent().append('<button class="add-item ' + drupalSettings.add_more_alternate.add_item_classes + '">' + drupalSettings.add_more_alternate.add_item_label + '</button>');

              // Add click handler to `add-item`.
              $table.parent().find('.add-item').click(function() {
                var numberHidden = $trs.filter('.visually-hidden').length;
                $trs.each(function() {
                  if ($(this).hasClass('visually-hidden')) {
                    // Just remove the first hidden one.
                    $(this).removeClass('visually-hidden');
                    return false;
                  }
                });

                setRemoveAdd();

                return false;
              });
            }

            if ($trs.filter('.visually-hidden').length < total - 1) {
              $table.parent().append('<button class="remove-item ' + drupalSettings.add_more_alternate.remove_item_classes + '">' + drupalSettings.add_more_alternate.remove_item_label + '</button>');

              // Add click handler to `remove-item`.
              $table.parent().find('.remove-item').click(function() {
                var $removeItem;
                if ($trs.filter('.visually-hidden').length === 0) {
                  $removeItem = $trs.last();
                }
                else {
                  $removeItem = $trs.filter(':not(.visually-hidden)').last();
                }

                // Clear out all values in item.
                $removeItem.find('input, textarea').each(function() {
                  $(this).val('');
                  if ($(this).attr('type') == 'radio') {
                    $(this).prop('checked', false);
                  }
                  if ($(this).attr('type') == 'checkbox') {
                      $(this).prop('checked', false);
                  }
                })
                $removeItem.find('select').each(function() {
                  $(this).val('_none');
                })

                $removeItem.addClass('visually-hidden');

                setRemoveAdd();

                return false;
              });
            }
          },
          trIsEmpty = function($tr) {
            // Determine whether a table row is empty or not.
            var isEmpty = true;
            $tr.find('input, select').each(function() {
              if ($(this).is('select')) {
                isEmpty = isEmpty && $(this).val() == '_none';
              }
              else if ($(this).attr('type') == 'radio') {
                isEmpty = isEmpty && !$(this).is(':checked');
              }
              else if ($(this).attr('type') == 'checkbox') {
                  isEmpty = isEmpty && !$(this).is(':checked');
              }
              else {
                isEmpty = isEmpty &&
                  ($(this).val() == '' ||
                   $(this).hasClass('optional') ||
                   $(this).attr('type') == 'hidden' ||
                   $(this).attr('type') == 'submit');
              }
            });
            isEmpty = isEmpty && ($tr.find('.field--type-file span.file a').length === 0);

            return isEmpty;
          };

      $trs.each(function() {
        var isEmpty;
        if (!first) {
          isEmpty = trIsEmpty($(this));
          if (isEmpty) {
            $(this).addClass('visually-hidden');
          }
        }
        else {
          first = false;
        }
      });

      setRemoveAdd();
    });
  });
})(jQuery, Drupal, drupalSettings);
