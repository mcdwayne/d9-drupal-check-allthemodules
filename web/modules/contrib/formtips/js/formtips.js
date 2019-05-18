/**
 * @file
 */

(function ($) {

  Drupal.behaviors.formtips = {
    attach: function (context, settings) {
      var formtip_settings = settings.formtips;
      var selectors = formtip_settings.selectors;
      if ($.isArray(selectors)) {
        selectors = selectors.join(', ');
      }

      var $descriptions = $('.form-item .description,.form-item .filter-guidelines')
        .not(selectors)
        .not('.formtips-processed');

      // Filter out empty descriptions. This helps avoid the password strength
      // description getting caught in a help.
      $descriptions = $descriptions.filter(function () {
        return $.trim($(this).text()) !== '';
      });

      $descriptions.addClass('formtips-processed');

      if (formtip_settings.max_width.length) {
        $descriptions.css('max-width', formtip_settings.max_width);
      }

      // Hide descriptions when escaped is hit.
      $(document).on('keyup', function (e) {
        if (e.which === 27) {
          $descriptions.removeClass('formtips-show');
        }
      });

      var $formtip = $('<a class="formtip"></a>');

      $descriptions.each(function () {
        var $description = $(this),
          $item = $(this).closest('.form-item,.filter-wrapper').first(),
          $label = $item.find('label,.fieldset-legend').first();

        // If there is no label, skip.
        if (!$label.length) {
          return;
        }
        $item.addClass('formtips-item');
        $description.toggleClass('formtips-show', false);
        $item.css('position', 'relative');
        $label.append($formtip);

        if (formtip_settings.trigger_action === 'click') {
          $formtip.on('click', function () {
            $description.toggleClass('formtips-show');
            return false;
          });
          // Hide description when clicking elsewhere.
          $item.on('click', function (e) {
            var $target = $(e.target);
            if (!$target.hasClass('formtip') && !$target.hasClass('formtips-processed')) {
              $description.toggleClass('formtips-show', false);
            }
          });
        }
        else {
          $formtip.hoverIntent({
            sensitivity: formtip_settings.sensitivity,
            interval: formtip_settings.interval,
            over: function () {
              $description.toggleClass('formtips-show', true);
            },
            timeout: formtip_settings.timeout,
            out: function () {
              $description.toggleClass('formtips-show', false);
            }
          });
        }
      });
    }
  };

})(jQuery);
