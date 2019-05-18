(function ($, Drupal, window, document) {
    "use strict";

    Drupal.behaviors.atinternet = {
        attach: function (context, settings) {
            $("body", context).once("atinternet").each(function () {

                function get_user_id (c_name) {
                    if (document.cookie.length > 0) {
                        var c_start = document.cookie.indexOf(c_name + "=");
                        if (c_start != -1) {
                            c_start = c_start + c_name.length + 1;
                            var c_end = document.cookie.indexOf(";", c_start);
                            if (c_end == -1) {
                                c_end = document.cookie.length;
                            }
                            return unescape(document.cookie.substring(c_start, c_end));
                        }
                    }
                    return "";
                }

                $(document).on("click", "[data-at-internet]", function () {
                    var $this = $(this);
                    return window.ATTag.click.send({
                        elem: $this,
                        type: $this.attr('data-at-internet-type'),
                        name: $this.attr('data-at-internet-name'),
                        level2: settings.atinternet.level2
                    });
                });

                if (window.ATInternet && settings && settings.atinternet) {
    	            if (window.addEventListener) {
    	                var ATTag = new window.ATInternet.Tracker.Tag();
    	                window.ATTag = ATTag;
    	                ATTag.page.set({
    	                    name: settings.atinternet.page_name,
                            chapter3: Array.isArray(settings.atinternet.breadcrumb) ? settings.atinternet.breadcrumb[0] ? settings.atinternet.breadcrumb[0] : '' : '',
                            chapter2: Array.isArray(settings.atinternet.breadcrumb) ? settings.atinternet.breadcrumb[1] ? settings.atinternet.breadcrumb[1] : '' : '',
                            chapter1: Array.isArray(settings.atinternet.breadcrumb) ? settings.atinternet.breadcrumb[2] ? settings.atinternet.breadcrumb[2] : '' : '',
    	                    level2: settings.atinternet.level2
    	                });

    	                ATTag.identifiedVisitor.set({
    	                    id: get_user_id('USERID'),
    	                });

    	                ATTag.dispatch();
    	            }
            	}
            });
        }
    };

})(jQuery, Drupal, window, document);
