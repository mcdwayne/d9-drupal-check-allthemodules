/**
 * @file
 * Drupal-specific implementation of isotope package.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.isotope = {
    isotopeActiveFilters: {},
    isotopeHash: function isotopeHash() {
      var hash = window.location.href.split('#')[1];
      var params = Drupal.behaviors.isotope.paramsUnserialize(hash);
      $.each(params, function(key, value) {
        var filterGroup = key.split('.')[0];
        var instanceID = key.split('.')[1];
        if (filterGroup == 'sort') {
          var $selector = $('.js-isotope-options.sorts');
          if (instanceID) {
            $selector = $selector.filter("[data-instance-id='" + instanceID + "']");
          }
          if ($selector.length) {
            // This sort option exists.
            Drupal.behaviors.isotope.isotopeApply('sort', value, instanceID);
          }
        }
        else {
          var $selector = $(".js-isotope-options[data-filter-group='" + filterGroup + "']");
          if (instanceID) {
            $selector = $selector.filter("[data-instance-id='" + instanceID + "']");
          }
          if ($selector.length) {
            // This filter option exists.
            Drupal.behaviors.isotope.isotopeApply('filter', value, instanceID, filterGroup);
          }
        }
      });

    },
    isotopeAddHash: function isotopeAddHash(group, value, instanceID) {
      var hash = window.location.href.split('#')[1];
      var params = Drupal.behaviors.isotope.paramsUnserialize(hash);
      if (typeof instanceID != 'undefined') {
        params[group + '.' + instanceID] = value;
      }
      else {
        params[group] = value;
      }
      location.hash = $.param(params);

    },
    paramsUnserialize: function paramsUnserialize(p) {
      if (typeof p == 'undefined') {
        return {};
      }
      var ret = {},
        seg = p.replace(/^\?/,'').split('&'),
        len = seg.length, i = 0, s;
      for (; i < len; i++) {
        if (!seg[i]) {
          continue;
        }
        s = seg[i].split('=');
        ret[s[0]] = s[1];
      }
      return ret;
    },
    isotopeApply: function isotopeApply(type, value, instanceID, filterGroup) {
      // Find all identical optionSets.
      var $optionSets = $(".js-isotope-options");
      if (type == 'filter') {
        $optionSets = $optionSets.filter("[data-filter-group='" + filterGroup + "']");
      }
      if (type == 'sort') {
        $optionSets = $optionSets.filter(".js-sorts");
      }
      var $container = $('.js-isotope-grid');
      if (typeof instanceID != 'undefined') {
        var $optionSets = $optionSets.filter("[data-instance-id='" + instanceID + "']");
        var $container = $('#' + instanceID);
      }

      // Apply class change to all identical optionsets.
      $optionSets.find('.selected').removeClass('selected');
      if (type == 'filter') {
        $optionSets.find("[data-filter='" + value + "']").addClass('selected');
        // Combine filters.
        var applyValue = '';
        Drupal.behaviors.isotope.isotopeActiveFilters[filterGroup] = value;
        for (var prop in Drupal.behaviors.isotope.isotopeActiveFilters) {
          applyValue += Drupal.behaviors.isotope.isotopeActiveFilters[ prop ];
        }
        $container.isotope({ filter: applyValue });
      }
      if (type == 'sort') {
        $optionSets.find("[data-sort-by='" + value + "']").addClass('selected');
        applyValue = value.split(',');
        $container.isotope({ sortBy: applyValue });
      }
    },
    isotopeRefresh: function isotopeRefresh() {
      $('.js-isotope-grid').isotope('layout');
    },
    filterClick: function filterClick(e) {
      var $this = $(this);
      var presentation;

      // Identify what has been clicked.
      var $optionSet = $this.parents('.js-isotope-options');
      var filterGroup = $optionSet.attr('data-filter-group');
      var instanceID = $optionSet.attr('data-instance-id');
      var filterValue = $this.attr('data-filter');

      if (typeof instanceID !== 'undefined') {
        presentation = $('#' + instanceID).data('isotope');
      }
      else {
        presentation = $('.js-isotope-grid').data('isotope');
      }

      if (presentation.urlFilters == 1) {
        Drupal.behaviors.isotope.isotopeAddHash(filterGroup, filterValue, instanceID);
      }
      else {
        Drupal.behaviors.isotope.isotopeApply('filter', filterValue, instanceID, filterGroup);
      }
    },
    sorterClick: function sorterClick(e) {
      var $this = $(this);
      var presentation;

      // Identify what has been clicked.
      var $optionSet = $this.parents('.js-isotope-options');
      var instanceID = $optionSet.attr('data-instance-id');
      var sortValue = $this.attr('data-sort-by');

      if (typeof instanceID !== 'undefined') {
        presentation = $('#' + instanceID).data('isotope');
      }
      else {
        presentation = $('.js-isotope-grid').data('isotope');
      }

      if (presentation.urlFilters == 1) {
        Drupal.behaviors.isotope.isotopeAddHash('sort', sortValue, instanceID);
      }
      else {
        Drupal.behaviors.isotope.isotopeApply('sort', sortValue, instanceID);
      }
    },
    attach:function(context, settings) {
      var $container = $('.js-isotope-grid', context);

      // Check for an AJAX call; context includes no body element.
      if ($('body', context).length == 0) {
        var $new_items = $(context).filter('.js-isotope-element');
        // Check for new items; e.g. from views_infinite_scroll.
        if ($new_items.length) {
          // Append new items.
          $('.js-isotope-grid').isotope('appended', $new_items);
        }
        // Check DOM for uninitialised container via AJAX views request.
        // @TODO: settings.views.ajax_path probs won't work in D8.
        // else if($container.length
        //   && !$container.data('isotope')
        //   && settings.views.ajax_path === '/views/ajax') {
        //   // Get the isotope module JSON options data.
        //   var $data = $container.data('isotope');
        //   // Init new container with options data.
        //   $container.isotope($data);
        // }
      }

      if ($container.length) {
        // Pre-select first option in option sets.
        $('.js-isotope-options', context).each(function() {
          $(this).find('.js-filter-button, .js-sorter-button').filter(':first').addClass('selected');
        });

        // Use imagesLoaded if it is available.
        if (typeof imagesLoaded !== 'undefined') {
          imagesLoaded($container.get(), this.isotopeRefresh);
        }

        // Config options that are not instance-specific.
        var presentation = $container.data('isotope');

        // Check for new hash values.
        if (presentation.urlFilters == 1) {
          window.onhashchange = this.isotopeHash;
          this.isotopeHash();
        }

        // Stamp elements.
        if (presentation.stamp) {
          $container.isotope().isotope('stamp', $(presentation.stamp));
        }

        // Filter button click.
        $('.js-isotope-options', context).delegate('.js-filter-button:not(.selected)', 'click', this.filterClick);

        // Sort button click.
        $('.js-isotope-options', context).delegate('.js-sorter-button:not(.selected)', 'click', this.sorterClick);
      }
    }
  }
}(jQuery));
