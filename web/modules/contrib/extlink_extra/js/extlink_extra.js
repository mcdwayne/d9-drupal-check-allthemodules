(function ($, Drupal, drupalSettings) {

// Checks if a given element resides in default extra leaving container page.
    function isInExtraLeavingContainer(element) {
        return $(element).closest('div.extlink-extra-leaving').length > 0;
    }

    drupalSettings.extlink_extra.colorboxSettings = drupalSettings.extlink_extra.colorboxSettings || {
            href: drupalSettings.extlink_extra.extlink_alert_url + ' .extlink-extra-leaving',
            height: '50%',
            width: '50%',
            initialWidth: '50%',
            initialHeight: '50%',
            className: 'extlink-extra-leaving-colorbox',
            onComplete: function () { // Note - drupal colorbox module automatically attaches drupal behaviors to loaded content.
                // Allow our cancel link to close the colorbox.
                jQuery('div.extlink-extra-back-action a').click(function(e) {jQuery.colorbox.close(); return false;})
                extlink_extra_timer();
            },
            onClosed: extlink_stop_timer
        };

    Drupal.behaviors.extlink_extra = {
        // Function mostly duplicated from extlink.js.
        // Returns an array of DOM elements of all external links.
        extlinkAttach: function(context) {
            var settings = drupalSettings;

            if (!settings.data.hasOwnProperty('extlink')) {
                return;
            }

            // Strip the host name down, removing ports, subdomains, or www.
            var pattern = /^(([^\/:]+?\.)*)([^\.:]{4,})((\.[a-z]{1,4})*)(:[0-9]{1,5})?$/;
            var host = window.location.host.replace(pattern, '$3$4');
            var subdomain = window.location.host.replace(pattern, '$1');

            // Determine what subdomains are considered internal.
            var subdomains;
            if (settings.data.extlink.extSubdomains) {
                subdomains = "([^/]*\\.)?";
            }
            else if (subdomain == 'www.' || subdomain == '') {
                subdomains = "(www\\.)?";
            }
            else {
                subdomains = subdomain.replace(".", "\\.");
            }

            // Build regular expressions that define an internal link.
            var internal_link = new RegExp("^https?://" + subdomains + host, "i");

            // Extra internal link matching.
            var extInclude = false;
            if (settings.data.extlink.extInclude) {
                extInclude = new RegExp(settings.data.extlink.extInclude.replace(/\\/, '\\'), "i");
            }

            // Extra external link matching.
            var extExclude = false;
            if (settings.data.extlink.extExclude) {
                extExclude = new RegExp(settings.data.extlink.extExclude.replace(/\\/, '\\'), "i");
            }

            // Extra external link CSS selector exclusion.
            var extCssExclude = false;
            if (settings.data.extlink.extCssExclude) {
                extCssExclude = settings.data.extlink.extCssExclude;
            }

            // Extra external link CSS selector explicit.
            var extCssExplicit = false;
            if (settings.data.extlink.extCssExplicit) {
                extCssExplicit = settings.data.extlink.extCssExplicit;
            }

            // Find all links which are NOT internal and begin with http as opposed
            // to ftp://, javascript:, etc. other kinds of links.
            // When operating on the 'this' variable, the host has been appended to
            // all links by the browser, even local ones.
            // In jQuery 1.1 and higher, we'd use a filter method here, but it is not
            // available in jQuery 1.0 (Drupal 5 default).
            var external_links = new Array();
            var mailto_links = new Array();
            $("a, area", context).each(function(el) {
                try {
                    var url = this.href.toLowerCase();
                    if (url.indexOf('http') == 0
                        && (!url.match(internal_link) && !(extExclude && url.match(extExclude)))
                        || (extInclude && url.match(extInclude))
                        && !(extCssExclude && $(this).parents(extCssExclude).length > 0)
                        && !(extCssExplicit && $(this).parents(extCssExplicit).length < 1)) {

                        // Add a class of 'extlink' to all external links except those within
                        // the 'now leaving' area.
                        if (!isInExtraLeavingContainer(this)) {
                            $(this).addClass('extlink');
                        }

                        external_links.push(this);
                    }
                    // Do not include area tags with begin with mailto: (this prohibits
                    // icons from being added to image-maps).
                    else if (this.tagName != 'AREA'
                        && url.indexOf('mailto:') == 0
                        && !(extCssExclude && $(this).parents(extCssExclude).length > 0)
                        && !(extCssExplicit && $(this).parents(extCssExplicit).length < 1)) {
                        mailto_links.push(this);
                    }
                }
                    // IE7 throws errors often when dealing with irregular links, such as:
                    // <a href="node/10"></a> Empty tags.
                    // <a href="http://user:pass@example.com">example</a> User:pass syntax.
                catch (error) {
                    return false;
                }
            });
            return external_links;
        },

        // Our click handler for external links.
        clickReaction: function(e) {
            // Allow the default behavior for link if it's within the warning area.
            // This keeps us from firing an infinite loop of reactions.
            if (isInExtraLeavingContainer(this)) {
                return true;
            }

            var external_url = jQuery(this).attr('href');
            var back_url = window.location.href;
            var alerturl = drupalSettings.extlink_extra.extlink_alert_url;
            // Save the URL of the click reaction for use by extlink_extra_timer(). todo - Pass as a variable instead.
            drupalSettings.extlink_extra.externalUrl = external_url;

            // "Don't warn" pattern matching.
            var extlink_exclude_warning = false;
            if (drupalSettings.extlink_extra.extlink_exclude_warning) {
                extlink_exclude_warning = new RegExp(drupalSettings.extlink_extra.extlink_exclude_warning.replace(/\\/, '\\'));
            }
            // Don't do any warnings if the href matches the "don't warn" pattern.
            if (extlink_exclude_warning) {
                var url = external_url.toLowerCase();
                if (url.match(extlink_exclude_warning)) {
                    return true;
                }
            }

            // This is what extlink does by default (except
            if (drupalSettings.extlink_extra.extlink_alert_type == 'confirm') {
                var text = drupalSettings.data.extlink.extAlertText.value;
                text = text.replace(/\[extlink:external\-url\]/gi, external_url);
                text = text.replace(/\[extlink:back-url\]/gi, back_url);
                return confirm(text);
            }

            // Set cookies that the modal or page can read to determine the 'go to' and 'back' links.
            $.cookie("external_url", external_url, { path: '/' });
            $.cookie("back_url", back_url, { path: '/' });

            if (drupalSettings.extlink_extra.extlink_alert_type == 'colorbox') {
                jQuery.colorbox(drupalSettings.extlink_extra.colorboxSettings);
                return false;
            }

            if (drupalSettings.extlink_extra.extlink_alert_type == 'page') {
                // If we're here, alert text is on but pop-up is off; we should redirect to an intermediate confirm page.
                window.location = alerturl;
                return false;
            }
        },

        attach: function(context){
            // Build an array of external_links exactly like extlink does.
            var external_links = this.extlinkAttach(context);

            // Unbind the click handlers added by extlink and replace with our own
            // This whole section of code that does the finding, unbinding, and rebinding
            // could be made a lot less redundant and more efficient if this issue could be resolved: http://drupal.org/node/1715520
            $(external_links).unbind('click');
            if (drupalSettings.extlink_extra.extlink_alert_type == 'modal') {
                var backUrl = window.location.href;
                var alertUrl = drupalSettings.extlink_extra.extlink_alert_url;

                // Replace all external links with a Modal Dialog link.
                $(external_links, context).once('externalLinks').each(function () {
                    if (!isInExtraLeavingContainer(this)) {
                        var externalUrl = $(this).attr('href');
                        // Since we are using URLs as query parameters we need to encode them as a URI component.
                        // But we don't want to encode URLs that are already encoded so we decode them first.
                        var href = alertUrl + '?external_url=' + encodeURIComponent(decodeURI(externalUrl))
                            + '&back_url=' + encodeURIComponent(decodeURI(backUrl));
                        $(this).attr('href', href);
                        $(this).addClass('use-ajax');
                        $(this).attr('data-dialog-type', 'modal');

                        // Set the modal width if supplied.
                        var modalWidth = drupalSettings.extlink_extra.extlink_modal_width;
                        if (modalWidth) {
                            $(this).attr('data-dialog-options', '{"width":"' + modalWidth + '"}');
                        }

                        // Attach behaviors.
                        Drupal.attachBehaviors(context, drupalSettings);
                    }
                });
            }
            else {
                // Add our own click handler.
                $(external_links).not('.ext-override, .extlink-extra-leaving a').click(this.clickReaction);
            }

            $(document).ready(function() {
                if (drupalSettings.extlink_extra.extlink_url_override == 1) {
                    if (drupalSettings.extlink_extra.extlink_url_params.external_url) {
                        $.cookie("external_url", drupalSettings.extlink_extra.extlink_url_params.external_url, { path: '/' });
                    }
                    if (drupalSettings.extlink_extra.extlink_url_params.back_url) {
                        $.cookie("back_url", drupalSettings.extlink_extra.extlink_url_params.back_url, { path: '/' });
                    }
                }
            });

            // Dynamically replace hrefs of back and external links on page load. This
            // is to compensate for aggressive caching situations where the now-leaving
            // is returning cached results.
            if (drupalSettings.extlink_extra.extlink_cache_fix == 1) {
                if (jQuery('.extlink-extra-leaving').length > 0) {
                    // grab our cookies
                    var external_url = $.cookie("external_url");
                    var back_url = $.cookie("back_url");

                    // First, find any links within the .extlink-extra-leaving area that use our placeholder text and set their HREFs.
                    // Using jquery's attr function here (rather than text replace) is important because IE7 or (IE10+ in
                    // compatibility mode, possibly others) will have already turned link HREFs with a value of
                    // "external-url-placeholder" into a fully qualified link that has protocol and domain prepended, so we need to
                    // replace the whole thing.
                    $goLinks = jQuery('.extlink-extra-leaving a[href*=external-url-placeholder]').attr('href', external_url);
                    $backLinks = jQuery('.extlink-extra-leaving a[href*=back-url-placeholder]').attr('href', back_url);

                    // Respect the 'Open external links in a new window' in our modal/page with aggressive caching.  Use of the text
                    // placeholder means that extlink's attach function doesn't catch these.
                    if (drupalSettings.data.extlink.extTarget) {
                        // Apply the 'target' attribute to the 'go' links.
                        $goLinks.attr('target', drupalSettings.data.extlink.extTarget);
                    }

                    // Next find any other places within text or whatever that have the placeholder text.
                    var html = jQuery('.extlink-extra-leaving').html();
                    html = html.replace(/external-url-placeholder/gi, external_url);
                    html = html.replace(/back-url-placeholder/gi, back_url);
                    jQuery('.extlink-extra-leaving').html(html);
                }
            }

            // If the timer is configured, we'll call it for the intermediate page.
            if (drupalSettings.extlink_extra.extlink_alert_type == 'page') {
                if (jQuery('.extlink-extra-leaving').length > 0) {
                    extlink_extra_timer();
                }
            }

            // Apply 508 fix - extlink module makes empty <spans> to show the external link icon, screen readers
            // have trouble with this.
            if (drupalSettings.extlink_extra.extlink_508_fix == 1) {
                // Go through each <a> tag with an 'ext' class,
                $.each($("a.ext"), function(index, value) {
                    // find a <span> next to it with 'ext' class,
                    var nextSpan = $(this).next('span.ext');
                    if (nextSpan.length) {
                        // if found add the text 'External Link' to the empty <span> (or whatever is configured by the user)
                        nextSpan.html(drupalSettings.extlink_extra.extlink_508_text);

                        // and move the span inside the <a> tag (at the end).
                        $(this).append(nextSpan);
                    }
                });
            }
        }
    }

})(jQuery, Drupal, drupalSettings);

// Global var that will be our JS interval.
var extlink_int;

// Handles the automatic redirection timer
function extlink_extra_timer() {
    // Don't start the timer if the time is set to '0' or somehow null
    if (drupalSettings.extlink_extra.extlink_alert_timer == 0 || drupalSettings.extlink_extra.extlink_alert_timer == null) {
        return;
    }

    // Initialize the time, grab the container, and update the markup.
    var count = drupalSettings.extlink_extra.extlink_alert_timer;
    var container = jQuery('.automatic-redirect-countdown');
    extlink_update_countdown_markup(container, count);

    // Decrement the timer each second
    extlink_int = setInterval(function () {
        if (count >= 0) {
            extlink_update_countdown_markup(container, count);
            count--;
        }
        else {
            // Time's up! Stop the timer and redirect.
            extlink_stop_timer();
            if (typeof drupalSettings.extlink_extra.externalUrl != 'undefined') {
                window.location = drupalSettings.extlink_extra.externalUrl;
            }
            else {
                // If the user is using the 'intermediate page' method of warning, a click reaction won't have set
                // drupalSettings.extlink_extra.externalUrl and it will be null - so we should fall back on the cookie for
                // our destination.
                window.location = jQuery.cookie("external_url");
            }
        }
    }, 1000);
}

function extlink_stop_timer() {
    clearInterval(extlink_int);
}

function extlink_update_countdown_markup(container, count) {
    container.html('<span class="extlink-timer-text">Automatically redirecting in: </span><span class="extlink-count">'+count+'</span><span class="extlink-timer-text"> seconds.</span>');
}
