(function ($) {
  Drupal.behaviors.screenshot_widget = {
    attach: function (context, settings) {

      var explicitOriginalTarget;
      var trigger;

      $('form.screenshot-form').once('screenshot-widget').each(function() {

        var processedCount = 0;
        var progress_indicator = $(this).data('screenshot-progress-indicator');

        $(':submit', this).once('screenshot-submit').each(function() {
          this.addEventListener('click', function(event) {
            explicitOriginalTarget = event.target;
          });
        });

        this.addEventListener('submit', function(event) {

          var $form = $(event.target);
          var $elements = $("input[data-screenshot-controls='0']", $form);

          if ($elements.length !== processedCount) {
            event.preventDefault();

            $(explicitOriginalTarget).prop('disabled', true);

            var progressIndicatorMethod = 'setProgressIndicator' + progress_indicator.slice(0, 1).toUpperCase() + progress_indicator.slice(1).toLowerCase();
            if (progressIndicatorMethod in Drupal.Ajax.prototype && typeof Drupal.Ajax.prototype[progressIndicatorMethod] === 'function') {

              trigger = {
                element: explicitOriginalTarget,
                progress: {
                  message: '',
                }
              };
              Drupal.Ajax.prototype[progressIndicatorMethod].call(trigger);
            }

            $elements.each(function() {
              var $element = $(this);
              var selector = $element.data('screenshot-selector');
              var screenshot_element = document.body;

              if (selector) {
                var $selected_element = $(selector);
                if ($selected_element.length > 0) {
                  screenshot_element = $selected_element.get(0);
                }
              }

              html2canvas(screenshot_element, {useCORS: true, async: false}).then(function(canvas) {

                $element.val(canvas.toDataURL('image/jpeg', 0.9));
                processedCount++;
                if ($elements.length === processedCount) {
                  $(explicitOriginalTarget).prop('disabled', false);
                  if (trigger.progress.element) {
                    $(trigger.progress.element).remove();
                  }

                  $(explicitOriginalTarget).trigger('click');
                }
              });

              if ($element.data('time-limit') != 0) {

                setTimeout(function() {
                  processedCount = $elements.length;
                  $(explicitOriginalTarget).prop('disabled', false);
                  if (trigger.progress.element) {
                    $(trigger.progress.element).remove();
                  }
                  $(explicitOriginalTarget).trigger('click');
                }, $element.data('time-limit') * 1000);
              }
            });
          }
        });

      });

      $('.make-screenshot-button').once('manual-screenshot-widget').each(function() {

        this.addEventListener('mousedown', function(event) {
          event.stopImmediatePropagation();

          var $element = $('.screenshot-element', $(event.target).parent());
          var selector = $element.data('screenshot-selector');
          var screenshot_element = document.body;

          if (selector) {
            var $selected_element = $(selector);
            if ($selected_element.length > 0) {
              screenshot_element = $selected_element.get(0);
            }
          }

          html2canvas(screenshot_element, {useCORS: true, async: false}).then(function(canvas) {
            $element.val(canvas.toDataURL('image/jpeg', 0.9)).change();
            $(event.target).trigger(event.type);
          });

        });
      });

    }
  };
})(jQuery);
