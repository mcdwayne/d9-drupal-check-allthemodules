/**
 * @file
 * Segmentio Tracking Code.
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.segmentio = {
        attach: function (context) {
            $('body', context).once('track-page-load').each(function () {
                // Create a queue, but don't obliterate an existing one!
                var analytics = window.analytics = window.analytics || [];

                // If the real analytics.js is already on the page return.
                if (analytics.initialize) return;

                // If the snippet was invoked already show an error.
                if (analytics.invoked) {
                    if (window.console && console.error) {
                        console.error('Segment snippet included twice.');
                    }
                    return;
                }

                // Invoked flag, to make sure the snippet
                // is never invoked twice.
                analytics.invoked = true;

                // A list of the methods in Analytics.js to stub.
                analytics.methods = [
                    'trackSubmit',
                    'trackClick',
                    'trackLink',
                    'trackForm',
                    'pageview',
                    'identify',
                    'reset',
                    'group',
                    'track',
                    'ready',
                    'alias',
                    'debug',
                    'page',
                    'once',
                    'off',
                    'on'
                ];

                // Define a factory to create stubs. These are placeholders
                // for methods in Analytics.js so that you never have to wait
                // for it to load to actually record data. The `method` is
                // stored as the first argument, so we can replay the data.
                analytics.factory = function (method) {
                    return function () {
                        var args = Array.prototype.slice.call(arguments);
                        args.unshift(method);
                        analytics.push(args);
                        return analytics;
                    };
                };

                // For each of our methods, generate a queueing stub.
                for (var i = 0; i < analytics.methods.length; i++) {
                    var key = analytics.methods[i];
                    analytics[key] = analytics.factory(key);
                }

                // Define a method to load Analytics.js from our CDN,
                // and that will be sure to only ever load it once.
                analytics.load = function (key) {
                // Create an async script element based on your key.
                    var script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.async = true;
                    script.src = ('https:' === document.location.protocol
                            ? 'https://' : 'http://')
                        + 'cdn.segment.com/analytics.js/v1/'
                        + key + '/analytics.min.js';

                // Insert our script next to the first script element.
                    var first = document.getElementsByTagName('script')[0];
                    first.parentNode.insertBefore(script, first);
                };

                // Add a version to keep track of what's in the wild.
                analytics.SNIPPET_VERSION = '4.0.0';

                // Load Analytics.js with your key, which will automatically
                // load the tools you've enabled for your account. Boosh!
                analytics.load(drupalSettings.segmentio.segmentio.write_key);

                var segmentVars = drupalSettings.segmentio.segmentio.variables;
                // Identify users.
                if (segmentVars.identify) {
                    var identifyOptions = [];
                    if (segmentVars.identify.userId) {
                        identifyOptions.push(segmentVars.identify.userId);
                    }
                    if (segmentVars.identify.traits) {
                        identifyOptions.push(segmentVars.identify.traits);
                    }
                    if (segmentVars.identify.options) {
                        if (!segmentVars.identify.traits) {
                            identifyOptions.push({});
                        }
                        identifyOptions.push(segmentVars.identify.options);
                    }
                    if (segmentVars.identify.callback) {
                        identifyOptions.push(segmentVars.identify.callback);
                    }
                    analytics.identify.apply(null, identifyOptions);
                }

                // Associated identified users with group.
                if (segmentVars.group) {
                    var groupOptions = [];
                    if (segmentVars.group.groupId) {
                        groupOptions.push(segmentVars.group.groupId);
                    }
                    if (groupOptions.group.traits) {
                        groupOptions.push(segmentVars.group.traits);
                    }
                    if (groupOptions.group.options) {
                        if (!segmentVars.group.traits) {
                            groupOptions.push({});
                        }
                        groupOptions.push(segmentVars.group.options);
                    }
                    if (segmentVars.group.callback) {
                        groupOptions.push(segmentVars.group.callback);
                    }
                    analytics.group.apply(null, groupOptions);
                }

                // Alias two identities.
                if (segmentVars.alias) {
                    var aliasOptions = [];
                    if (segmentVars.alias.userId) {
                        aliasOptions.push(segmentVars.alias.userId);
                    }
                    if (segmentVars.alias.previousId) {
                        aliasOptions.push(segmentVars.alias.previousId);
                    }
                    if (segmentVars.alias.options) {
                        aliasOptions.push(segmentVars.alias.options);
                    }
                    if (segmentVars.alias.callback) {
                        aliasOptions.push(segmentVars.alias.callback);
                    }
                    analytics.alias.apply(null, aliasOptions);
                }

                // Track events.
                if (segmentVars.track) {
                    for (var j = 0; j < segmentVars.track.length; j++) {
                        var trackOptions = [];
                        if (segmentVars.track[j].event) {
                            trackOptions.push(segmentVars.track[j].event);
                        }
                        if (segmentVars.track[j].properties) {
                            trackOptions.push(segmentVars.track[j].properties);
                        }
                        if (segmentVars.track[j].options) {
                            if (!segmentVars.track[j].properties) {
                                trackOptions.push({});
                            }
                            trackOptions.push(segmentVars.track[j].options);
                        }
                        if (segmentVars.track.callback) {
                            trackOptions.push(segmentVars.track[j].callback);
                        }
                        analytics.track.apply(null, trackOptions);
                    }
                }

                // Record page view.
                var pageOptions = [];
                if (segmentVars.page) {
                    if (segmentVars.page.category) {
                        pageOptions.push(segmentVars.page.category);
                    }
                    if (segmentVars.page.name) {
                        pageOptions.push(segmentVars.page.name);
                    }
                    if (segmentVars.page.properties) {
                        pageOptions.push(segmentVars.page.properties);
                    }
                    if (segmentVars.page.options) {
                        if (!segmentVars.page.properties) {
                            pageOptions.push({});
                        }
                        pageOptions.push(segmentVars.page.options);
                    }
                    if (segmentVars.page.callback) {
                        pageOptions.push(segmentVars.page.callback);
                    }
                }
                analytics.page.apply(null, pageOptions);
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
