(function ($, Drupal) {

  Drupal.behaviors.bisnodeWebapiJs = {
    attach: function (context, settings) {

      var bisnode = this;
      var genaralSettings = settings.bisnode.general_settings;
      var webforms = settings.bisnode.webforms;

      // Get the mappings that were sent to JS and iterate through them.
      $.each(webforms, function (webformID) {

        var groups = webforms[webformID];

        // Iterate through all the groups.
        $.each(groups, function (i, group) {

          var searchField = group.search_field;

          // Iterate through the actual forms.
          $('.webform-submission-' + webformID.replace(/_/g, '-') + '-form', context).each(function () {

            var $form = $(this);

            var timeObject = null;
            var loading = $('<div class="loading-bisnode">' + genaralSettings.loading_text + '</div>');

            // Find the search field and bind the callback to its events.
            var $searchField = $('.bisnode-source---' + searchField, $form);
            if (!$searchField.is('input')) {
              $searchField = $searchField.find('input');
            }

            $searchField.on('change keyup', function (event) {

              loading.remove();
              var xhrAjax = null;

              // Set the proper timeout for each event.
              var timeOut = 1000;
              if (event.type === 'change') {
                timeOut = 100;
              }

              var $this = $(this);
              var search = $this.val();

              clearTimeout(timeObject);

              if (!search) {
                return;
              }

              timeObject = setTimeout(function () {
                // Abort a old ajax in process.
                if (xhrAjax) {
                  xhrAjax.abort();
                }

                // Set the "loading" message.
                $this.parent().after(loading);

                bisnode.debug("Starting AJAX request...");

                xhrAjax = $.ajax({
                  url: settings.path.baseUrl + 'bisnode/search-directory',
                  method: 'POST',
                  data: {
                    string: search
                  },
                  success: function (data) {
                    xhrAjax = null;

                    bisnode.debug(data, "AJAX response");

                    // Dispatch an event, to let other modules know about
                    // received data.
                    var e = document.createEvent('CustomEvent');
                    e.initCustomEvent('bisnode-response-received', true, false, data);
                    window.dispatchEvent(e);

                    // Fill up fields only if we have one result.
                    if (data.results.length == 1) {
                      var result = data.results[0];

                      // Filling starts here!
                      bisnode.applyResult(result, group.mapping_fields, $form);
                    }

                    // Remove the "loading" message.
                    loading.remove();
                  }
                });
              }, timeOut);

            });
          });

        });

      });
    },

    applyResult: function (bisnodeResult, mapping, $form) {

      this.debug(bisnodeResult, "bisnodeResult");
      this.debug(mapping, "mapping");
      this.debug($form, "$form");

      for (var drupal_field in mapping) {
        if (mapping[drupal_field] === 'none') {
          this.debug("Field '" + drupal_field + "' is not mapped, skipping.");
          continue;
        }
        var bisnode_field = mapping[drupal_field];

        var bisnode_value = this.getMappingFieldResult(bisnode_field, bisnodeResult);
        if (!bisnode_value) {
          this.debug("Field '" + drupal_field + "' has no bisnode return value, skipping.");
          continue;
        }

        var $targetElement = $('.bisnode-target---' + bisnode_field, $form);

        if (!$targetElement.is('input')) {
          $targetElement = $targetElement.find('input');
        }
        this.debug("Field '" + drupal_field + "' will get value '" + bisnode_value + "'");
        this.debug($targetElement, "$targetElement for " + drupal_field);
        $targetElement.val(bisnode_value);
      }
    },

    getMappingFieldResult: function (field, result) {
      if (result && typeof result[field] == 'string') {
        return result[field];
      }
      return '';
    },

    debug: function (value, key) {
      if (drupalSettings.bisnode.general_settings.debug_js) {
        if (key) {
          console.log(value, key);
        }
        else {
          console.log(value);
        }
      }
    }

  }

})(jQuery, Drupal);
