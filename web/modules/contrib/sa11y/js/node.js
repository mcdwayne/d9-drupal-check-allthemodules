/**
 * @file node.js
 *
 * Sa11y tooltips for node pages.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  // Bootstrap fix.
  $.widget.bridge('uitooltip', $.ui.tooltip);

  // Tooltip globals.
  var tipSettings = {
    classes: {'ui-tooltip':'sa11y-tip'},
    content: function () {
      return $(this).attr('data-sa11y-tip');
    },
    position: {
      my: "center bottom-20",
      at: "center top",
      using: function (position, feedback) {
        $(this).css(position);
        $(this)
          .addClass(feedback.vertical);
      }
    },
    open: function (event, ui) {
      ui.tooltip.mouseleave(
        function () {
          $(this).fadeOut('400', function () {
            $(this).remove();
          });
        }
      );
    },
    close: function (event, ui) {
      ui.tooltip.hover(
        function () {
          $(this).stop(true).fadeTo(400, 1);
        },
        function () {
          $(this).fadeOut('400', function () {
            $(this).remove();
          });
        }
      );
    }
  };

  /**
   * Main functionality.
   * @type {{attach: Drupal.behaviors.sa11yNode.attach}}
   */
  Drupal.behaviors.sa11yNode = {
    attach: function (context, settings) {

      var $context = $(context);

      // Need to find in self as well from AJAX replace.
      var $sa11yTab = $context.find('#sa11y-node-tab').add($context.filter('#sa11y-node-tab'));
      if ($sa11yTab.length && settings.sa11y) {
        var sa11y = settings.sa11y;

        var message = '';
        switch (sa11y.status) {
          case 'error':
          case 'outdated':
            message = Drupal.t("Report Error");
            break;
          case 'complete':
            message = Drupal.t("Toggle Report");
            break;
          case 'clean':
            message = Drupal.t("No Issues Found!");
            break;
        }

        // ERROR.
        if (sa11y.status === 'error' || sa11y.status === 'outdated') {
          $sa11yTab.append(
            '<span class="sa11y-status ' + sa11y.status + '" title="' + message + '">' +
            '<span class="visually-hidden">' + message + '</span>' +
            '</span>'
          );
        }
        // Already Loaded.
        else if (sa11y.violations) {
          // No issues!
          if ($.isEmptyObject(sa11y.violations)) {
            $sa11yTab.append(
              '<span class="sa11y-status ' + sa11y.status + '" title="' + message + '">' +
              '<span class="visually-hidden">' + message + '</span>' +
              '</span>'
            );
          }
          // Process Results.
          else {
            $sa11yTab.addClass('active').parent().css('position', 'relative');
            $sa11yTab.after(
              '<div class="sa11y-result-container">' +
              '<label for="sa11y-result" class="visually-hidden">' + Drupal.t("Toggle Report") + '</label> ' +
              '<input id="sa11y-result" type="checkbox" title="' + Drupal.t("Toggle Report") + '" />' +
              '</div>'
            );

            // Show the report in tooltips.
            var $sallyToggle = $('#sa11y-result');
            $sallyToggle.click(function (e) {
              var $this = $(this);
              var $violations = $('.sa11y-violation');

              // Disable.
              if ($this.hasClass('active')) {
                $this.removeClass('active').addClass('inactive').attr('title', Drupal.t('Toggle Report'));

                // Tooltip may not exist here.
                if ($this.is(':ui-tooltip')) {
                  $this.uitooltip('close').uitooltip('disable');
                }

                $violations.removeClass('active').uitooltip('close').uitooltip('disable');
              }
              // Enable.
              else if ($this.hasClass('inactive')) {
                $this.removeClass('inactive').addClass('active');

                // Tooltip may not exist here.
                if ($this.is(':ui-tooltip')) {
                  $this.uitooltip('enable').uitooltip('open');
                }

                $violations.addClass('active').uitooltip('enable').uitooltip('open');
              }
              // Init.
              else {
                $this.addClass('active');

                var misses = 0;
                $.each(sa11y.violations, function (index, violation) {
                  var $el = $(violation.dom);
                  if ($el.length) {
                    $el
                      .attr('title', 'violation')
                      .addClass('sa11y-violation active ' + violation.impact)
                      .attr('data-sa11y-tip', violation.message);
                  }
                  else {
                    misses++;
                  }
                });

                // Alert that some can't be shown.
                if (misses && $this.is(':visible')) {
                  $this
                    .attr('data-sa11y-tip', Drupal.t('@missing items could not be displayed, !report to see them', {
                      '@missing': misses,
                      '!report': '<a class="sa11y-link" href="' + Drupal.url(sa11y.reportLink) + '">' + Drupal.t('View Report') + '</a>'
                    }))
                    .uitooltip(tipSettings).uitooltip('open');
                }

                // Init Tooltips.
                $('.sa11y-violation').uitooltip(tipSettings).uitooltip('open');
              }
            });

            // Display results as soon as ready for new reports.
            if (sa11y.show) {
              $sallyToggle.click();
            }

          }
        }
        // Load/Pending, make an ajax call to update.
        else {
          $sa11yTab.append(
            '<span class="sa11y-status pending" title="' + message + '">' +
            '<span class="visually-hidden">' + message + '</span>' +
            '</span>'
          );

          var ajax_call = Drupal.ajax({
            url: Drupal.url('sa11y/js/' + sa11y.reportId + '/' + sa11y.nid)
          });

          // Wait 3s if this is not the first call.
          if (sa11y.timer) {
            setTimeout(function () {
              ajax_call.execute();
            }, 3000);
          }
          else {
            ajax_call.execute();
          }
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
