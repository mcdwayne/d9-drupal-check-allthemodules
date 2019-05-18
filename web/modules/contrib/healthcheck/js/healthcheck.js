(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.healthcheck = {
    attach: function (context, settings) {

      // Handle categories
      $('.report-category h2', context).on('click', function() {
        var thisCategory = $(this).parent();
        var isOpen = thisCategory.hasClass('expanded');

        if (!isOpen) {
          thisCategory.addClass('expanded');
        }

        thisCategory.find('.report-category__findings').slideToggle(200, function() {
          if(isOpen) {
            thisCategory.removeClass('expanded');
          }
        });
      });

      /**
       * Filter results
       */
      $('.healthcheck-filter-list a', context).click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        var tag = $(this).data('filter-tag');

        // reset active filter status
        $('.healthcheck-filter-list a').removeClass('filter-link-active');
        $(this).addClass('filter-link-active');

        filter_results_by_tag(tag);

      });

      function filter_results_by_tag(tag) {
        var category = $('.report-category', context);

        // iterate through categories; update visibility and count
        category.each(function() {
          var findings = $(this).find('.report-category__findings .finding');
          var categoryTitle = $(this).find('h2 .report-category__count');
          var total = 0;

          // iterate findings, show if the tag matches, hide if not; update count
          findings.each(function() {
            var $this = $(this);

            if (tag === 'clear') {
              $this.show();
              total++;
            } else if ($this.hasClass(tag)) {
              $this.show();
              total++;
            } else {
              $this.hide();
            }
          });

          // update the category count
          categoryTitle.text(total);
        });
      }

      /**
       * Expand all / collapse all link
       */
      $('.expand-collapse-all', context).click(function() {
        var $link = $(this);
        var $categories = $('.report-category__findings', context);
        var isCollapsed = $link.hasClass('collapsed') ? true : false;

        if (isCollapsed) {
          $categories.show();
          $link.removeClass('collapsed').text('Collapse All');
        } else {
          $categories.hide();
          $link.addClass('collapsed').text('Expand All');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
