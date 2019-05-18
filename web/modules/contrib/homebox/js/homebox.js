(function ($, Drupal) {
    Drupal.homebox = {
        config: {}
    };

    Drupal.behaviors.homebox = {
        attach: function (context) {
            let $homebox = $('#homebox:not(.homebox-processed)', context).addClass('homebox-processed');

            if ($homebox.length > 0) {
                // Find all columns
                Drupal.homebox.$columns = $homebox.find('div.homebox-column');
                Drupal.homebox.$page = $homebox;

                // Try to find the button to save homebox state.
                Drupal.homebox.$pageSave = $('.homebox-save-form');

                // Equilize columns height
                Drupal.homebox.equalizeColumnsHeights();

                // Make columns sortable
                Drupal.homebox.$columns.sortable({
                    items: '.homebox-portlet.homebox-draggable',
                    handle: '.portlet-header',
                    connectWith: Drupal.homebox.$columns,
                    placeholder: 'homebox-placeholder',
                    forcePlaceholderSize: true,
                    over: function () {
                        Drupal.homebox.equalizeColumnsHeights();
                    },
                    stop: function () {
                        Drupal.homebox.equalizeColumnsHeights();
                        Drupal.homebox.pageChanged();
                    }
                });

                // Populate hidden form element with block order and values.
                Drupal.homebox.$pageSave.mousedown(function () {
                    let blocks = {}, regionIndex, i = 0;
                    Drupal.homebox.$columns.each(function () {
                        // Determine region out of column-id.
                        regionIndex = $(this).attr('id').replace(/region_/, '');
                        $(this).find('.homebox-portlet').each(function () {
                            let $this = $(this);
                            let $title = $(this).find('.portlet-title');

                            // Build blocks object
                            blocks[i++] = $.param({
                                id: $this.attr('id').replace(/^homebox-block-/, ''),
                                region: regionIndex,
                                status: $this.is(':visible') ? 1 : 0,
                                title: $title.text(),
                            });
                        });
                    });

                    $(this).siblings('[name=blocks]').attr('value', $.param(blocks));
                });
            }
        }
    };

    /**
     * Set all column heights equal
     */
    Drupal.homebox.equalizeColumnsHeights = function () {

    };

    Drupal.homebox.pageChanged = function () {
        Drupal.homebox.$pageSave.mousedown().click();
    };

    Drupal.behaviors.homeboxPortlet = {
        attach: function (context) {
            $('.homebox-portlet:not(.homebox-processed)', context).addClass('homebox-processed').each(function () {
                let $portlet = $(this),
                    $portletHeader = $portlet.find('.portlet-header');

                // Attach click event on close
                $portletHeader.find('.portlet-close').click(function () {
                    // Drupal.homebox.equalizeColumnsHeights();
                    $portlet.hide();
                    Drupal.homebox.pageChanged();
                });
            });
        }
    };

})(jQuery, Drupal);