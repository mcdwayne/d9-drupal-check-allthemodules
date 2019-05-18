(function($) {
  $(function() {
    var fg = {};
    fg.hideEmptyFieldGroups = function($form) {
      $form.find('.field-gate-field-group').each(function() {
        var isVisible = false;
        $(this).find('.field-gate, .n-field-gate').each(function() {
          if (!$(this).hasClass('field-gate-hide')) {
            isVisible = true;
          }
        });
        if (!isVisible) {
          $(this).addClass('field-gate-hide');
        }
        else {
          $(this).removeClass('field-gate-hide');
        }
      });
    }

    fg.setFieldGates = function($form) {
      var foundGate = false;

      $form.find('.field-gate, .n-field-gate').each(function() {
        if (foundGate !== false) {
          $(this).addClass('field-gate-hide');
        }
        else {
          if ($(this).hasClass('field-gate') && !$(this).hasClass('field-gate-passed')) {
            foundGate = $(this);
          }
          else {
            $(this).removeClass('field-gate-hide');
          }
        }
      });

      if (foundGate !== false) {
        if (foundGate.hasClass('field-gate-hide-actions')) {
          $form.find('.form-actions').addClass('field-gate-hide');
        }
      }
      else {
        $form.find('.form-actions').removeClass('field-gate-hide');
      }

      fg.hideEmptyFieldGroups($form);
    }

    $('.field-gate').change(function() {
      var matchValue = /field-gate-value-(.*?)\s/g.exec($(this).attr('class'))[1],
        selectValue = $(this).find('input').filter(':checked').val(),
        messageId = /(field-gate-message-.*?)\s/g.exec($(this).attr('class'))[1];

      if (matchValue === selectValue) {
        $('#' + messageId).hide();
        $(this).addClass('field-gate-passed');
        fg.setFieldGates($(this).parents('form'));
      }
      else if (selectValue !== undefined) {
        $(this).removeClass('field-gate-passed');
        $('#' + messageId).show();
        fg.setFieldGates($(this).parents('form'));
      }
    });

    $('form.field-gate-enabled').each(function() {
      fg.setFieldGates($(this));
    });

    $('.field-gate').trigger('change');
  });
})(jQuery);
