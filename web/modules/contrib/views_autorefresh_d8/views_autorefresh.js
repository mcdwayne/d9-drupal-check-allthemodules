(function ($, Drupal, drupalSettings) {
    // START jQuery

    Drupal.behaviors.views_autorefresh = {
        attach: function(context, settings) {
            for(var view_name in settings.views_autorefresh) {
                for(var view_display in settings.views_autorefresh[view_name]) {

                    var interval = settings.views_autorefresh[view_name][view_display];
                    var execution_setting = '.view-'+view_name.replace(new RegExp('_','g'),'-')+'.view-display-id-'+view_display;
                    if($(context).find(execution_setting).once(execution_setting).length > 0) {
                        // Delete timeOut before reset it
                        if(settings.views_autorefresh[view_name][view_display].timer) {
                            clearTimeout(settings.views_autorefresh[view_name][view_display].timer);
                        }
                        settings.views_autorefresh[view_name][view_display].timer = setInterval(
                            function() {
                                Drupal.behaviors.views_autorefresh.refresh(execution_setting)
                            }, interval
                        );
                    }
                }
            }
        },

        refresh: function(execution_setting) {
            $(execution_setting).trigger('RefreshView');
        }

    }


    // END jQuery
})(jQuery, Drupal, drupalSettings);
