/**
 * @file
 * Mixitup views script.
 */

(function ($, Drupal, drupalSettings, storage) {

  "use strict";

  var selector = drupalSettings.mixitup_settings_array.selector_name;

  Drupal.behaviors.mixitup_views = {
    attach: function (context, settings) {

      var filterObject = {
        // Declare any variables we will need as properties of the object.
        $filters: null,
        $reset: null,
        groups: [],
        outputArray: [],
        checkboxes: null,
        outputString: '',
        widgetType : '',
        init: function (filters, reset, container,widget_type,agregation_type) {
          var self = this;
          self.widgetType = widget_type;
          self.agregationType = agregation_type;
          self.joinSeparator = (self.agregationType === 'or' ? ',' : '');
          self.$filters = filters;
          self.$reset = reset;
          self.$container = container;
          self.checkboxes =  $('.mixitup_views_filter.form-checkbox');

          switch(self.widgetType) {
            case 'checkboxes' :
              self.$filters.find('.form-type-checkbox').each(function () {
                self.groups.push({
                  $inputs: $(this).find('input'),
                  active: [],
                  tracker: false
                });
              });
              break;
            case 'select' :
              self.$filters.find('.form-type-select').each(function () {
                self.groups.push({
                  $selects: $(this).find('select'),
                  active: [],
                  tracker: false
                });
              });
              break;
          }

          self.bindHandlers();
        },
        // The "bindHandlers" method will listen for whenever a form value changes.
        bindHandlers: function () {
          var self = this;

          self.$filters.on('change', function () {
            self.parseFilters();
          });

          self.$reset.on('click', function (e) {
            e.preventDefault();
            self.$filters[0].reset();
            self.checkboxes.parent().siblings().show();
            self.parseFilters();
          });
        },
        // The parseFilters method checks which filters are active in each group.
        parseFilters: function () {
          var self = this;

          // Loop through each filter group and add active filters to arrays.
          var filters = false;
          for (var i = 0, group; group = self.groups[i]; i++) {
            // Reset arrays.
            group.active = [];

            switch(self.widgetType) {
              case 'checkboxes':
                group.$inputs.each(function () {
                  $(this).is(':checked') && group.active.push(this.value);
                });
                break;
              case 'select':
                group.$selects.each(function () {
                  group.active.push(this.value);
                });
                break;
            }

            group.active.length && (group.tracker = 0);
            if (group.active.length) {
              filters = true;
            }
          }
          if (filters && self.widgetType === 'checkboxes') {
            self.$reset.show();
          }
          else {
            self.$reset.hide();
          }

          self.concatenate();
        },
        // The "concatenate" method will crawl through each group, concatenating filters as desired.
        concatenate: function () {
          var self = this;
          var cache = '';
          var crawled = false;
          var checkTrackers = function () {
            var done = 0;

            for (var i = 0, group; group = self.groups[i]; i++) {
              (group.tracker === false) && done++;
            }

            return (done < self.groups.length);
          };

          var unique = function(value,index,self) {
            return self.indexOf(value) === index;
          }

          var crawl = function () {
            var cache = [];
            for (var i = 0, group; group = self.groups[i]; i++) {
              group.active[group.tracker] && (cache.push(group.active[group.tracker]));

              if (i === self.groups.length - 1) {
                cache = cache.filter(unique);
                self.outputArray = self.outputArray.concat(cache);
                cache = [];
                updateTrackers();
              }
            }
          };

          var updateTrackers = function () {
            for (var i = self.groups.length - 1; i > -1; i--) {
              var group = self.groups[i];

              if (group.active[group.tracker + 1]) {
                group.tracker++;
                break;
              } else if (i > 0) {
                group.tracker && (group.tracker = 0);
              } else {
                crawled = true;
              }
            }
          };
          // Reset output array.
          self.outputArray = [];

          do {
            crawl();
          }
          while (!crawled && checkTrackers());

          self.outputString = self.outputArray.join(self.joinSeparator);

          // If the output string is empty, show all rather than none.
          !self.outputString.length && (self.outputString = 'all');
          // Send the output string to MixItUp via the 'filter' method.

          if (typeof mixer !== 'undefined') {
            mixer.destroy();
          }
          mixitup(selector).destroy();
          var mixer = mixitup(selector, drupalSettings.mixitup_settings_array[selector]);
          mixer.filter(self.outputString);
        }
      };

      $.each(drupalSettings.mixitup_settings_array, function (container, settings) {
        var $container = $(container);
        var $filters = $(drupalSettings.filters_form_id);
        var $reset = $(drupalSettings.reset_id);
        var $widget_type = drupalSettings.filtering_type;
        var $agregation_type = drupalSettings.agregation_type;
        filterObject.init($filters, $reset, $container, $widget_type, $agregation_type);

        // Sorting functionality.
        $('.sort', $container).on('click', function () {
          var data_sort = $(this).attr('data-sort');
          if (!$(this).hasClass('desc')) {
            // Refresh all other sorts).
            $('.sort_item', $container).removeClass('desc').addClass('asc');
          }
          if ($(this).hasClass('asc')) {
            mixitup(selector).destroy();
            mixitup(selector).sort(data_sort + ':asc');
            $(this).removeClass('asc').addClass('desc');
          }
          else {
            if ($(this).hasClass('desc')) {
              mixitup(selector).destroy();
              mixitup(selector).sort(data_sort + ':desc');
              $(this).removeClass('desc').addClass('asc');
            }
          }
        });
      });

      if (drupalSettings.mixitup_views_filters.hide_unchecked_checkboxes === true) {
        $('.mixitup_views_filter.form-checkbox').once().on('change', function () {
           if ($(this).prop("checked")) {
             $(this).parent().siblings().animate({left:'0px', show: 'toggle'}, 150);
           }
           else {
             $(this).parent().siblings().animate({right:'0px', show: 'toggle'}, 150);
           }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings, window.localStorage);
