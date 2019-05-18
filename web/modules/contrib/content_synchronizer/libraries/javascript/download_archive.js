(function (drupalSettings, $) {
    "use strict";
    Drupal.behaviors.content_synchronizer_download_archive = {
        attach: function (context) {

            var param = 'cs_archive=';
            if (window.location.hash.indexOf(param) > -1) {
                var path = decodeURIComponent(window.location.hash.split(param)[1]);
                var location = drupalSettings.path.baseUrl+"admin/content_synchronizer/download_archive?" + param + path;
                var $iframe = $('<iframe src="' + location + '"></iframe>');

                $iframe.hide();

                $('body').append($iframe);
                window.location.hash = '';
            }
        }
    };
})(drupalSettings, jQuery);