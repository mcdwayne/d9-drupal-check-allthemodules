/**
 * @file
 * Supporting JS for the menu parent item form element.
 */

(function($) {
    Drupal.behaviors.menu_select = {
        setUpMenuSelect: function (mKey) {
            // Remove active and expanded styling from all links.
            $('.js-menu-select-menu-link').removeClass('active');
            $('.expanded').removeClass('expanded');

            // Set up links and children.
            $('.js-menu-select-menu-link').each(function() {

                // Determine if this link has any children and add a class.
                var link = $(this);
                if (link.next().hasClass('item-list')) {
                    link.addClass('menu-select-menu-link--has-children')
                }

                // Add some markup to enable additional styling.
                link.html(link.html() + '<span></span>');

                // Set up the position preview.
                $('.js-menu-select-parent-position-preview').html(Drupal.t('Select a menu item.'));
            })

            // And now build based on the content's current menu parent.
            Drupal.behaviors.menu_select.updateChosenOne(mKey);
        },
        updateChosenOne: function (mKey) {
            // Find the chosen link.
            var theChosenOne = $('.js-menu-select-menu-link[data-mkey="' + mKey + '"]');

            // Get this link's parents.
            var parents = theChosenOne.parents('li');

            // Update the select box with the chosen link.
            $('#edit-menu-menu-parent').val(mKey);

            // Add active style to the chosen link.
            theChosenOne.addClass('active');

            // Update the position preview.
            Drupal.behaviors.menu_select.updatePreview(theChosenOne);

            // Run through and expand parents.
            for (var i = parents.length - 1; i >= 0; i--) {
                $(parents[i]).find('> a, > .item-list').addClass('expanded');
            }
        },
        updatePreview: function (link) {
            var parents = link.parents('li')
            var preview = $('.js-menu-select-parent-position-preview');
            var previewVal = '';
            preview.html('');

            // By running back up through the clicked item's parents.
            for (var i = parents.length - 1; i >= 0; i--) {
                var previewVal = previewVal + $(parents[i]).children('a').html() + ' > '
                preview.html(previewVal + '<strong>' + Drupal.t('New item') + '</strong>');
            }
        },
        attach: function(context, settings) {
            // Perform initial setup on page load.
            $('#edit-menu-menu-parent').once().each(function(){
                Drupal.behaviors.menu_select.setUpMenuSelect($(this).val());
            });

            // Selecting a menu link.
            $('.js-menu-select-menu-link').once().on('click', function(e) {

                // Prevent the link from changing the page.
                e.preventDefault();

                // Remove active style form all links.
                $('.js-menu-select-menu-link').removeClass('active');

                // Get the clicked link item.
                var link = $(this);

                // Update the select box.
                $('#edit-menu-menu-parent').val(link.data('mkey'));

                // Update the position preview.
                Drupal.behaviors.menu_select.updatePreview(link);

                // Add active style to clicked link.
                // Expand the link if need be.
                link.toggleClass('active');
                if (link.next().hasClass('item-list')) {
                    link.toggleClass('expanded');
                    link.next().toggleClass('expanded');
                }
            });

            $('.js-menu-select-parent-menu-item-search').once().on('autocompleteclose', (function(e){
                // Remove active and expanded styling from all links.
                $('.js-menu-select-menu-link').removeClass('active');
                $('.expanded').removeClass('expanded');

                // The menu link key.
                var mKey = $(this).val();

                // Clear the field once we have the key.
                $(this).val('');

                // Update the menu with chosen link.
                Drupal.behaviors.menu_select.updateChosenOne(mKey);
            }));
        }
    }

})(jQuery);
