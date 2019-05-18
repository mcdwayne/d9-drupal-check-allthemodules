/**
 * @file
 * JavaScript file for the Coffee module.
 */

 (function(win) {
   'use strict';
   var requestMap = {};
   if (typeof win.drupalSettings === 'undefined' && typeof win.Drupal.settings === 'object') {
     // How about just adding drupalSettings here then?
     win.drupalSettings = win.Drupal.settings;
   }

   // Make a couple of functions available globally.
   win.jsonpSparql = {
     templateSettings: {
       interpolate: /\{\{(.+?)\}\}/g
     },
     dataToString: function(settings, items) {
       var template = _.template(settings.data_template, this.templateSettings);
       var results = items.map(function(n) {
         var data = {};
         Object.keys(n).forEach(function(j) {
           data[j] = Drupal.checkPlain(n[j].value)
         });
         return template(data);
       });
       return results.join('');
     }
   };

  (function ($, Drupal, drupalSettings) {
    var PROCESSED_CLASS = 'jsonp-sparql-processed';

    var renderError = function(settings) {
      var $el = settings.element;
      var template = _.template(Drupal.checkPlain(settings.error_message), win.jsonpSparql.templateSettings);
      var d = {
        value: Drupal.checkPlain(settings.value)
      };
      return $el.html(template(d));
    }

    var processData = function(settings, id, data) {
      var $el = settings.element.find('.wrapper');
      // See if the dataset is empty.
      if (!data || !data.results || !data.results.bindings) {
        // Treat as an error.
        return renderError(settings);
      }
      // See if the dataset is empty.
      if (!data.results.bindings.length) {
        var emptyTemplate = _.template(Drupal.checkPlain(settings.empty_message), win.jsonpSparql.templateSettings);
        var ed = {
          value: Drupal.checkPlain(settings.value)
        };
        return $el.html(emptyTemplate(ed));
      }
      // So, we have results. Trust that user to provide a good template, yeah?
      var items = data.results.bindings;
      var output = [
        win.jsonpSparql.dataToString(settings, items)
      ];
      // See if we have something for the intro.
      if (settings.intro_text) {
        var template = _.template(settings.intro_text, win.jsonpSparql.templateSettings);
        var d = {
          value: settings.value
        };
        //
        Object.keys(items[0]).forEach(function(j) {
          d[j] = Drupal.checkPlain(items[0][j].value)
        });
        output.unshift(template(d));
      }
      $el.html(output.join(''));
    };

    Drupal.behaviors.jsonpSparql = {
      attach: function(context) {
        $(context).find('[data-is-sparql-block]').each(function(i, n) {
          var $el = $(n);
          if ($el.hasClass(PROCESSED_CLASS)) {
            return;
          }
          // Mark as processed.
          $el.addClass(PROCESSED_CLASS);
          // Append element for ajax.
          $el.find('.wrapper')
            .append('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
          // Get the id.
          var id = $el.attr('data-id');
          // Find the settings for it.
          var settings = drupalSettings.jsonp_sparql[id];
          settings.element = $el;
          // See if we already have the data from proxying.
          if (drupalSettings.jsonpSparqlData && drupalSettings.jsonpSparqlData[id]) {
            // This one is proxied and has data.
            var data = drupalSettings.jsonpSparqlData[id];
            try {
              data = JSON.parse(data)
            }
            catch (e) {
              // Something bad happened. I guess we need to use the error message.
              return renderError(settings);
            }
            return processData(settings, id, data);
          }
          // Replace the query with the value.
          var query = _.template(settings.sparql_query, win.jsonpSparql.templateSettings)({
            value: settings.value
          });
          // Do an AJAX request, and try to massage it in some way.
          $.ajax({
            dataType: 'jsonp',
            type: 'GET',
            url: settings.endpoint,
            data: {
              query: query,
              format: 'json',
              output: 'json',
              id: id
            },
            success: processData.bind(null, settings, id),
            error: renderError.bind(null, settings)
          });
          var $scriptEl = $('head script').filter(function(i, n) {
            return n.src.match(new RegExp(settings.endpoint)) && n.src.match(new RegExp(id));
          })[0];
          $scriptEl.onerror = function(e) {
            renderError(settings);
          }
        });
      },
      detach: function(context) {
      }
    }
  })(win.jQuery, win.Drupal, win.drupalSettings);
})(window);
