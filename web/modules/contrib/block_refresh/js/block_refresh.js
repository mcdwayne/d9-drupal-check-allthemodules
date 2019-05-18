(function ($) {
  Drupal.behaviors.block_refresh = {
    attach: function (context) {
      $.each(Drupal.settings.block_refresh.settings, function (key, settings) {
        var element = settings.element;
        setBlockRefresh('#' + element, '.content', settings['auto'], settings['manual'], settings['init'], settings['timer'], settings['arguments'], settings['block']['block'], settings['block']['delta']);

        if (settings['panels']) {
          element = element.replace('block-', 'pane-');
          setBlockRefresh('.' + element, '.pane-content', settings['auto'], settings['manual'], settings['init'], settings['timer'], settings['arguments'], settings['block']['block'], settings['block']['delta']);
        }
      });

      function setBlockRefresh(element, element_content, auto, manual, init, timer, arguments, block, delta) {
        // Do not bother if no element exists or has already been processed.
        if (!$(element).size() || $(element).hasClass('block-refresh-processed')) {
          return;
        }

        $(element).addClass('block-refresh-processed');

        //Get argument from referring page and append to end of load request
        args = '';
        query = '';
        if (arguments) {
          $.each(Drupal.settings.block_refresh.args, function (index, arg) {
            args += '/' + arg;
          });
          query = Drupal.settings.block_refresh.query;
        }

        if (auto) {
          setInterval(function () {
            $(element + ' ' + element_content).load(Drupal.settings.basePath + Drupal.settings.pathPrefix + 'block_refresh/' + block + '/' + delta + args + query, function () {
              Drupal.attachBehaviors(this);
            });
          }, timer * 1000); // We need to multiply by 1000 because the admin enters a number in seconds,  but the setInterval() function expects milliseconds
        }
        if (manual) {
          refresh_link = '<div class="block-refresh-button">' + Drupal.t('Refresh') + '</div>';
          // We'll attach the refresh link to the header if it exists...
          if ($(element + ' h2').length) {
            // note: for some reason I couldn't get $(this) to work, I don't know why
            $(element + ' h2').before(refresh_link);
          }
          // ...otherwise we will attach it to the content
          else {
            $(element + ' ' + element_content).before(refresh_link);
          }
        }
        if (init) {
          $(element + ' ' + element_content).load(Drupal.settings.basePath + Drupal.settings.pathPrefix + 'block_refresh/' + block + '/' + delta + args + query, '', function () {
            Drupal.attachBehaviors(this);
          });
        }

        $('.block-refresh-button').click(function () {
          $(this).addClass('block-refresh-button-throbbing');
          $(element + ' ' + element_content).load(Drupal.settings.basePath + Drupal.settings.pathPrefix + 'block_refresh/' + block + '/' + delta + args + query, '', function () {
            $('.block-refresh-button').removeClass('block-refresh-button-throbbing');
            Drupal.attachBehaviors(this);
          });
        });
      }
    }
  };
})(jQuery);
