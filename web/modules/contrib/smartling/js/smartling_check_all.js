(function ($) {

  Drupal.behaviors.smartlingCheckAll = {
    attach: function (context, settings) {
      if (settings.smartling != undefined && settings.smartling.checkAllId != undefined) {
        $.each(settings.smartling.checkAllId, function (index, id) {
          var $checkboxWrapper = $(id, context),
            $checkboxesSelector = '#smartling-check-all-' + index,
            $checkboxesLink = $($checkboxesSelector, context);

          if ($checkboxesLink.length || $checkboxWrapper.length < 1) {
            return;
          }
          if ($checkboxWrapper.children().length > 5) {
            $checkboxWrapper.addClass('big-select-languages-widget');
          }

          $checkboxesLink = $('<a href="#" id="' + $checkboxesSelector + '">' + Drupal.t('Check/uncheck all') + '</a>');
          $checkboxesLink.click({checkboxWrapper: $checkboxWrapper}, function (e) {
            var $_this = $(this);
            $_this.toggleClass('checked');
            $checkboxWrapper.find(':checkbox').each(function () {
              if (!$_this.hasClass('checked')) {
                $(this).filter(':checked').prop('checked', false);
              }
              else {
                $(this).filter(':not(:checked)').prop('checked', true);
              }
            });
            e.preventDefault();
          });
          $checkboxWrapper.prepend($checkboxesLink);
        });
      }
    }
  };

})(jQuery);
