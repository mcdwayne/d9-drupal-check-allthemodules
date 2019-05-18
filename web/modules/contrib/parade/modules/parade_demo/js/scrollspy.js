(function (exports) {
'use strict';

/*
 * jQuery ScrollSpy Plugin
 * Author: @sxalexander, softwarespot
 * Licensed under the MIT license
 */
(function jQueryScrollspy(window, $) {
    // Plugin Logic

    $.fn.extend({
        scrollspy: function scrollspy(options, action) {
            // If the options parameter is a string, then assume it's an 'action', therefore swap the parameters around
            if (_isString(options)) {
                var tempOptions = action;

                // Set the action as the option parameter
                action = options;

                // Set to be the reference action pointed to
                options = tempOptions;
            }

            // override the default options with those passed to the plugin
            options = $.extend({}, _defaults, options);

            // sanitize the following option with the default value if the predicate fails
            _sanitizeOption(options, _defaults, 'container', _isObject);

            // cache the jQuery object
            var $container = $(options.container);

            // check if it's a valid jQuery selector
            if ($container.length === 0) {
                return this;
            }

            // sanitize the following option with the default value if the predicate fails
            _sanitizeOption(options, _defaults, 'namespace', _isString);

            // check if the action is set to DESTROY/destroy
            if (_isString(action) && action.toUpperCase() === 'DESTROY') {
                $container.off('scroll.' + options.namespace);
                return this;
            }

            // sanitize the following options with the default values if the predicates fails
            _sanitizeOption(options, _defaults, 'buffer', $.isNumeric);
            _sanitizeOption(options, _defaults, 'max', $.isNumeric);
            _sanitizeOption(options, _defaults, 'min', $.isNumeric);

            // callbacks
            _sanitizeOption(options, _defaults, 'onEnter', $.isFunction);
            _sanitizeOption(options, _defaults, 'onLeave', $.isFunction);
            _sanitizeOption(options, _defaults, 'onLeaveTop', $.isFunction);
            _sanitizeOption(options, _defaults, 'onLeaveBottom', $.isFunction);
            _sanitizeOption(options, _defaults, 'onTick', $.isFunction);

            if ($.isFunction(options.max)) {
                options.max = options.max();
            }

            if ($.isFunction(options.min)) {
                options.min = options.min();
            }

            // check if the mode is set to VERTICAL/vertical
            var isVertical = window.String(options.mode).toUpperCase() === 'VERTICAL';

            return this.each(function each() {
                // cache this
                var _this = this;

                // cache the jQuery object
                var $element = $(_this);

                // count the number of times a container is entered
                var enters = 0;

                // determine if the scroll is with inside the container
                var inside = false;

                // count the number of times a container is left
                var leaves = 0;

                // create a scroll listener for the container
                $container.on('scroll.' + options.namespace, function onScroll() {
                    // cache the jQuery object
                    var $this = $(this);

                    // create a position object literal
                    var position = {
                        top: $this.scrollTop(),
                        left: $this.scrollLeft(),
                    };

                    var containerHeight = $container.height();

                    var max = options.max;

                    var min = options.min;

                    var xAndY = isVertical ? position.top + options.buffer : position.left + options.buffer;

                    if (max === 0) {
                        // get the maximum value based on either the height or the outer width
                        max = isVertical ? containerHeight : $container.outerWidth() + $element.outerWidth();
                    }

                    // if we have reached the minimum bound, though are below the max
                    if (xAndY >= min && xAndY <= max) {
                        // trigger the 'scrollEnter' event
                        if (!inside) {
                            inside = true;
                            enters++;

                            // trigger the 'scrollEnter' event
                            $element.trigger('scrollEnter', {
                                position: position,
                            });

                            // call the 'onEnter' function
                            if (options.onEnter !== null) {
                                options.onEnter(_this, position);
                            }
                        }

                        // trigger the 'scrollTick' event
                        $element.trigger('scrollTick', {
                            position: position,
                            inside: inside,
                            enters: enters,
                            leaves: leaves,
                        });

                        // call the 'onTick' function
                        if (options.onTick !== null) {
                            options.onTick(_this, position, inside, enters, leaves);
                        }
                    } else {
                        if (inside) {
                            inside = false;
                            leaves++;

                            // trigger the 'scrollLeave' event
                            $element.trigger('scrollLeave', {
                                position: position,
                                leaves: leaves,
                            });

                            // call the 'onLeave' function
                            if (options.onLeave !== null) {
                                options.onLeave(_this, position);
                            }

                            if (xAndY <= min) {
                                // trigger the 'scrollLeaveTop' event
                                $element.trigger('scrollLeaveTop', {
                                    position: position,
                                    leaves: leaves,
                                });

                                // call the 'onLeaveTop' function
                                if (options.onLeaveTop !== null) {
                                    options.onLeaveTop(_this, position);
                                }
                            } else if (xAndY >= max) {
                                // trigger the 'scrollLeaveBottom' event
                                $element.trigger('scrollLeaveBottom', {
                                    position: position,
                                    leaves: leaves,
                                });

                                // call the 'onLeaveBottom' function
                                if (options.onLeaveBottom !== null) {
                                    options.onLeaveBottom(_this, position);
                                }
                            }
                        } else {
                            // Idea taken from: http://stackoverflow.com/questions/5353934/check-if-element-is-visible-on-screen
                            var containerScrollTop = $container.scrollTop();

                            // Get the element height
                            var elementHeight = $element.height();

                            // Get the element offset
                            var elementOffsetTop = $element.offset().top;

                            if ((elementOffsetTop < (containerHeight + containerScrollTop)) && (elementOffsetTop > (containerScrollTop - elementHeight))) {
                                // trigger the 'scrollView' event
                                $element.trigger('scrollView', {
                                    position: position,
                                });

                                // call the 'onView' function
                                if (options.onView !== null) {
                                    options.onView(_this, position);
                                }
                            }
                        }
                    }
                });
            });
        },
    });

    // Fields (Private)

    // Defaults

    // default options
    var _defaults = {
        // the offset to be applied to the left and top positions of the container
        buffer: 0,

        // the element to apply the 'scrolling' event to (default window)
        container: window,

        // the maximum value of the X or Y coordinate, depending on mode the selected
        max: 0,

        // the maximum value of the X or Y coordinate, depending on mode the selected
        min: 0,

        // whether to listen to the X (horizontal) or Y (vertical) scrolling
        mode: 'vertical',

        // namespace to append to the 'scroll' event
        namespace: 'scrollspy',

        // call the following callback function every time the user enters the min / max zone
        onEnter: null,

        // call the following callback function every time the user leaves the min / max zone
        onLeave: null,

        // call the following callback function every time the user leaves the top zone
        onLeaveTop: null,

        // call the following callback function every time the user leaves the bottom zone
        onLeaveBottom: null,

        // call the following callback function on each scroll event within the min and max parameters
        onTick: null,

        // call the following callback function on each scroll event when the element is inside the viewable view port
        onView: null,
    };

    // Methods (Private)

    // check if a value is an object datatype
    function _isObject(value) {
        return $.type(value) === 'object';
    }

    // check if a value is a string datatype with a length greater than zero when whitespace is stripped
    function _isString(value) {
        return $.type(value) === 'string' && $.trim(value).length > 0;
    }

    // check if an option is correctly formatted using a predicate; otherwise, return the default value
    function _sanitizeOption(options, defaults, property, predicate) {
        // set the property to the default value if the predicate returned false
        if (!predicate(options[property])) {
            options[property] = defaults[property];
        }
    }
}(window, window.jQuery));

/**
 * @file
 * Initialize ScrollSpy scripts
 */

(function ($) {

    var targets = $('.campaign-menu, .parade-campaign-page .site-branding__logo, #hamburger');

    targets.scrollspy({
        min: 490,
        max: 50000,
        onEnter: function() {
            targets.removeClass('not-fixed');
            targets.addClass('fixed');
        },
        onLeave: function() {
            targets.removeClass('fixed');
            targets.addClass('not-fixed');
        }
    });

    $(document).ready(function () { targets.addClass('not-fixed'); targets.trigger('scroll.scrollspy'); /* It has no effect if scroll pos less than min value. */});

})(jQuery);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL3ZlbmRvci9qcXVlcnktc2Nyb2xsc3B5LmpzIiwiRDovZGV2ZGVza3RvcC90Y3MubG9jL3dlYi90aGVtZXMvdGlldG9fYWRtaW4vc3JjL3NjcmlwdHMvc2Nyb2xsc3B5LmpzIl0sInNvdXJjZXNDb250ZW50IjpbIi8qXG4gKiBqUXVlcnkgU2Nyb2xsU3B5IFBsdWdpblxuICogQXV0aG9yOiBAc3hhbGV4YW5kZXIsIHNvZnR3YXJlc3BvdFxuICogTGljZW5zZWQgdW5kZXIgdGhlIE1JVCBsaWNlbnNlXG4gKi9cbihmdW5jdGlvbiBqUXVlcnlTY3JvbGxzcHkod2luZG93LCAkKSB7XG4gICAgLy8gUGx1Z2luIExvZ2ljXG5cbiAgICAkLmZuLmV4dGVuZCh7XG4gICAgICAgIHNjcm9sbHNweTogZnVuY3Rpb24gc2Nyb2xsc3B5KG9wdGlvbnMsIGFjdGlvbikge1xuICAgICAgICAgICAgLy8gSWYgdGhlIG9wdGlvbnMgcGFyYW1ldGVyIGlzIGEgc3RyaW5nLCB0aGVuIGFzc3VtZSBpdCdzIGFuICdhY3Rpb24nLCB0aGVyZWZvcmUgc3dhcCB0aGUgcGFyYW1ldGVycyBhcm91bmRcbiAgICAgICAgICAgIGlmIChfaXNTdHJpbmcob3B0aW9ucykpIHtcbiAgICAgICAgICAgICAgICB2YXIgdGVtcE9wdGlvbnMgPSBhY3Rpb247XG5cbiAgICAgICAgICAgICAgICAvLyBTZXQgdGhlIGFjdGlvbiBhcyB0aGUgb3B0aW9uIHBhcmFtZXRlclxuICAgICAgICAgICAgICAgIGFjdGlvbiA9IG9wdGlvbnM7XG5cbiAgICAgICAgICAgICAgICAvLyBTZXQgdG8gYmUgdGhlIHJlZmVyZW5jZSBhY3Rpb24gcG9pbnRlZCB0b1xuICAgICAgICAgICAgICAgIG9wdGlvbnMgPSB0ZW1wT3B0aW9ucztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gb3ZlcnJpZGUgdGhlIGRlZmF1bHQgb3B0aW9ucyB3aXRoIHRob3NlIHBhc3NlZCB0byB0aGUgcGx1Z2luXG4gICAgICAgICAgICBvcHRpb25zID0gJC5leHRlbmQoe30sIF9kZWZhdWx0cywgb3B0aW9ucyk7XG5cbiAgICAgICAgICAgIC8vIHNhbml0aXplIHRoZSBmb2xsb3dpbmcgb3B0aW9uIHdpdGggdGhlIGRlZmF1bHQgdmFsdWUgaWYgdGhlIHByZWRpY2F0ZSBmYWlsc1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ2NvbnRhaW5lcicsIF9pc09iamVjdCk7XG5cbiAgICAgICAgICAgIC8vIGNhY2hlIHRoZSBqUXVlcnkgb2JqZWN0XG4gICAgICAgICAgICB2YXIgJGNvbnRhaW5lciA9ICQob3B0aW9ucy5jb250YWluZXIpO1xuXG4gICAgICAgICAgICAvLyBjaGVjayBpZiBpdCdzIGEgdmFsaWQgalF1ZXJ5IHNlbGVjdG9yXG4gICAgICAgICAgICBpZiAoJGNvbnRhaW5lci5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gc2FuaXRpemUgdGhlIGZvbGxvd2luZyBvcHRpb24gd2l0aCB0aGUgZGVmYXVsdCB2YWx1ZSBpZiB0aGUgcHJlZGljYXRlIGZhaWxzXG4gICAgICAgICAgICBfc2FuaXRpemVPcHRpb24ob3B0aW9ucywgX2RlZmF1bHRzLCAnbmFtZXNwYWNlJywgX2lzU3RyaW5nKTtcblxuICAgICAgICAgICAgLy8gY2hlY2sgaWYgdGhlIGFjdGlvbiBpcyBzZXQgdG8gREVTVFJPWS9kZXN0cm95XG4gICAgICAgICAgICBpZiAoX2lzU3RyaW5nKGFjdGlvbikgJiYgYWN0aW9uLnRvVXBwZXJDYXNlKCkgPT09ICdERVNUUk9ZJykge1xuICAgICAgICAgICAgICAgICRjb250YWluZXIub2ZmKCdzY3JvbGwuJyArIG9wdGlvbnMubmFtZXNwYWNlKTtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gc2FuaXRpemUgdGhlIGZvbGxvd2luZyBvcHRpb25zIHdpdGggdGhlIGRlZmF1bHQgdmFsdWVzIGlmIHRoZSBwcmVkaWNhdGVzIGZhaWxzXG4gICAgICAgICAgICBfc2FuaXRpemVPcHRpb24ob3B0aW9ucywgX2RlZmF1bHRzLCAnYnVmZmVyJywgJC5pc051bWVyaWMpO1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ21heCcsICQuaXNOdW1lcmljKTtcbiAgICAgICAgICAgIF9zYW5pdGl6ZU9wdGlvbihvcHRpb25zLCBfZGVmYXVsdHMsICdtaW4nLCAkLmlzTnVtZXJpYyk7XG5cbiAgICAgICAgICAgIC8vIGNhbGxiYWNrc1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ29uRW50ZXInLCAkLmlzRnVuY3Rpb24pO1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ29uTGVhdmUnLCAkLmlzRnVuY3Rpb24pO1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ29uTGVhdmVUb3AnLCAkLmlzRnVuY3Rpb24pO1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ29uTGVhdmVCb3R0b20nLCAkLmlzRnVuY3Rpb24pO1xuICAgICAgICAgICAgX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIF9kZWZhdWx0cywgJ29uVGljaycsICQuaXNGdW5jdGlvbik7XG5cbiAgICAgICAgICAgIGlmICgkLmlzRnVuY3Rpb24ob3B0aW9ucy5tYXgpKSB7XG4gICAgICAgICAgICAgICAgb3B0aW9ucy5tYXggPSBvcHRpb25zLm1heCgpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAoJC5pc0Z1bmN0aW9uKG9wdGlvbnMubWluKSkge1xuICAgICAgICAgICAgICAgIG9wdGlvbnMubWluID0gb3B0aW9ucy5taW4oKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gY2hlY2sgaWYgdGhlIG1vZGUgaXMgc2V0IHRvIFZFUlRJQ0FML3ZlcnRpY2FsXG4gICAgICAgICAgICB2YXIgaXNWZXJ0aWNhbCA9IHdpbmRvdy5TdHJpbmcob3B0aW9ucy5tb2RlKS50b1VwcGVyQ2FzZSgpID09PSAnVkVSVElDQUwnO1xuXG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIGVhY2goKSB7XG4gICAgICAgICAgICAgICAgLy8gY2FjaGUgdGhpc1xuICAgICAgICAgICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG5cbiAgICAgICAgICAgICAgICAvLyBjYWNoZSB0aGUgalF1ZXJ5IG9iamVjdFxuICAgICAgICAgICAgICAgIHZhciAkZWxlbWVudCA9ICQoX3RoaXMpO1xuXG4gICAgICAgICAgICAgICAgLy8gY291bnQgdGhlIG51bWJlciBvZiB0aW1lcyBhIGNvbnRhaW5lciBpcyBlbnRlcmVkXG4gICAgICAgICAgICAgICAgdmFyIGVudGVycyA9IDA7XG5cbiAgICAgICAgICAgICAgICAvLyBkZXRlcm1pbmUgaWYgdGhlIHNjcm9sbCBpcyB3aXRoIGluc2lkZSB0aGUgY29udGFpbmVyXG4gICAgICAgICAgICAgICAgdmFyIGluc2lkZSA9IGZhbHNlO1xuXG4gICAgICAgICAgICAgICAgLy8gY291bnQgdGhlIG51bWJlciBvZiB0aW1lcyBhIGNvbnRhaW5lciBpcyBsZWZ0XG4gICAgICAgICAgICAgICAgdmFyIGxlYXZlcyA9IDA7XG5cbiAgICAgICAgICAgICAgICAvLyBjcmVhdGUgYSBzY3JvbGwgbGlzdGVuZXIgZm9yIHRoZSBjb250YWluZXJcbiAgICAgICAgICAgICAgICAkY29udGFpbmVyLm9uKCdzY3JvbGwuJyArIG9wdGlvbnMubmFtZXNwYWNlLCBmdW5jdGlvbiBvblNjcm9sbCgpIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gY2FjaGUgdGhlIGpRdWVyeSBvYmplY3RcbiAgICAgICAgICAgICAgICAgICAgdmFyICR0aGlzID0gJCh0aGlzKTtcblxuICAgICAgICAgICAgICAgICAgICAvLyBjcmVhdGUgYSBwb3NpdGlvbiBvYmplY3QgbGl0ZXJhbFxuICAgICAgICAgICAgICAgICAgICB2YXIgcG9zaXRpb24gPSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0b3A6ICR0aGlzLnNjcm9sbFRvcCgpLFxuICAgICAgICAgICAgICAgICAgICAgICAgbGVmdDogJHRoaXMuc2Nyb2xsTGVmdCgpLFxuICAgICAgICAgICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAgICAgICAgIHZhciBjb250YWluZXJIZWlnaHQgPSAkY29udGFpbmVyLmhlaWdodCgpO1xuXG4gICAgICAgICAgICAgICAgICAgIHZhciBtYXggPSBvcHRpb25zLm1heDtcblxuICAgICAgICAgICAgICAgICAgICB2YXIgbWluID0gb3B0aW9ucy5taW47XG5cbiAgICAgICAgICAgICAgICAgICAgdmFyIHhBbmRZID0gaXNWZXJ0aWNhbCA/IHBvc2l0aW9uLnRvcCArIG9wdGlvbnMuYnVmZmVyIDogcG9zaXRpb24ubGVmdCArIG9wdGlvbnMuYnVmZmVyO1xuXG4gICAgICAgICAgICAgICAgICAgIGlmIChtYXggPT09IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGdldCB0aGUgbWF4aW11bSB2YWx1ZSBiYXNlZCBvbiBlaXRoZXIgdGhlIGhlaWdodCBvciB0aGUgb3V0ZXIgd2lkdGhcbiAgICAgICAgICAgICAgICAgICAgICAgIG1heCA9IGlzVmVydGljYWwgPyBjb250YWluZXJIZWlnaHQgOiAkY29udGFpbmVyLm91dGVyV2lkdGgoKSArICRlbGVtZW50Lm91dGVyV2lkdGgoKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIC8vIGlmIHdlIGhhdmUgcmVhY2hlZCB0aGUgbWluaW11bSBib3VuZCwgdGhvdWdoIGFyZSBiZWxvdyB0aGUgbWF4XG4gICAgICAgICAgICAgICAgICAgIGlmICh4QW5kWSA+PSBtaW4gJiYgeEFuZFkgPD0gbWF4KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyB0cmlnZ2VyIHRoZSAnc2Nyb2xsRW50ZXInIGV2ZW50XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIWluc2lkZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGluc2lkZSA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZW50ZXJzKys7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyB0cmlnZ2VyIHRoZSAnc2Nyb2xsRW50ZXInIGV2ZW50XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGVsZW1lbnQudHJpZ2dlcignc2Nyb2xsRW50ZXInLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvc2l0aW9uOiBwb3NpdGlvbixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGNhbGwgdGhlICdvbkVudGVyJyBmdW5jdGlvblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChvcHRpb25zLm9uRW50ZXIgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3B0aW9ucy5vbkVudGVyKF90aGlzLCBwb3NpdGlvbik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyB0cmlnZ2VyIHRoZSAnc2Nyb2xsVGljaycgZXZlbnRcbiAgICAgICAgICAgICAgICAgICAgICAgICRlbGVtZW50LnRyaWdnZXIoJ3Njcm9sbFRpY2snLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcG9zaXRpb246IHBvc2l0aW9uLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGluc2lkZTogaW5zaWRlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGVudGVyczogZW50ZXJzLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxlYXZlczogbGVhdmVzLFxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGNhbGwgdGhlICdvblRpY2snIGZ1bmN0aW9uXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAob3B0aW9ucy5vblRpY2sgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBvcHRpb25zLm9uVGljayhfdGhpcywgcG9zaXRpb24sIGluc2lkZSwgZW50ZXJzLCBsZWF2ZXMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGluc2lkZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGluc2lkZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxlYXZlcysrO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gdHJpZ2dlciB0aGUgJ3Njcm9sbExlYXZlJyBldmVudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRlbGVtZW50LnRyaWdnZXIoJ3Njcm9sbExlYXZlJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwb3NpdGlvbjogcG9zaXRpb24sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxlYXZlczogbGVhdmVzLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gY2FsbCB0aGUgJ29uTGVhdmUnIGZ1bmN0aW9uXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG9wdGlvbnMub25MZWF2ZSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvcHRpb25zLm9uTGVhdmUoX3RoaXMsIHBvc2l0aW9uKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoeEFuZFkgPD0gbWluKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIHRyaWdnZXIgdGhlICdzY3JvbGxMZWF2ZVRvcCcgZXZlbnRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJGVsZW1lbnQudHJpZ2dlcignc2Nyb2xsTGVhdmVUb3AnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwb3NpdGlvbjogcG9zaXRpb24sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsZWF2ZXM6IGxlYXZlcyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gY2FsbCB0aGUgJ29uTGVhdmVUb3AnIGZ1bmN0aW9uXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChvcHRpb25zLm9uTGVhdmVUb3AgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9wdGlvbnMub25MZWF2ZVRvcChfdGhpcywgcG9zaXRpb24pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIGlmICh4QW5kWSA+PSBtYXgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gdHJpZ2dlciB0aGUgJ3Njcm9sbExlYXZlQm90dG9tJyBldmVudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZWxlbWVudC50cmlnZ2VyKCdzY3JvbGxMZWF2ZUJvdHRvbScsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvc2l0aW9uOiBwb3NpdGlvbixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxlYXZlczogbGVhdmVzLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBjYWxsIHRoZSAnb25MZWF2ZUJvdHRvbScgZnVuY3Rpb25cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG9wdGlvbnMub25MZWF2ZUJvdHRvbSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3B0aW9ucy5vbkxlYXZlQm90dG9tKF90aGlzLCBwb3NpdGlvbik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIElkZWEgdGFrZW4gZnJvbTogaHR0cDovL3N0YWNrb3ZlcmZsb3cuY29tL3F1ZXN0aW9ucy81MzUzOTM0L2NoZWNrLWlmLWVsZW1lbnQtaXMtdmlzaWJsZS1vbi1zY3JlZW5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgY29udGFpbmVyU2Nyb2xsVG9wID0gJGNvbnRhaW5lci5zY3JvbGxUb3AoKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIEdldCB0aGUgZWxlbWVudCBoZWlnaHRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgZWxlbWVudEhlaWdodCA9ICRlbGVtZW50LmhlaWdodCgpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gR2V0IHRoZSBlbGVtZW50IG9mZnNldFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBlbGVtZW50T2Zmc2V0VG9wID0gJGVsZW1lbnQub2Zmc2V0KCkudG9wO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKChlbGVtZW50T2Zmc2V0VG9wIDwgKGNvbnRhaW5lckhlaWdodCArIGNvbnRhaW5lclNjcm9sbFRvcCkpICYmIChlbGVtZW50T2Zmc2V0VG9wID4gKGNvbnRhaW5lclNjcm9sbFRvcCAtIGVsZW1lbnRIZWlnaHQpKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyB0cmlnZ2VyIHRoZSAnc2Nyb2xsVmlldycgZXZlbnRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJGVsZW1lbnQudHJpZ2dlcignc2Nyb2xsVmlldycsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvc2l0aW9uOiBwb3NpdGlvbixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gY2FsbCB0aGUgJ29uVmlldycgZnVuY3Rpb25cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG9wdGlvbnMub25WaWV3ICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvcHRpb25zLm9uVmlldyhfdGhpcywgcG9zaXRpb24pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSxcbiAgICB9KTtcblxuICAgIC8vIEZpZWxkcyAoUHJpdmF0ZSlcblxuICAgIC8vIERlZmF1bHRzXG5cbiAgICAvLyBkZWZhdWx0IG9wdGlvbnNcbiAgICB2YXIgX2RlZmF1bHRzID0ge1xuICAgICAgICAvLyB0aGUgb2Zmc2V0IHRvIGJlIGFwcGxpZWQgdG8gdGhlIGxlZnQgYW5kIHRvcCBwb3NpdGlvbnMgb2YgdGhlIGNvbnRhaW5lclxuICAgICAgICBidWZmZXI6IDAsXG5cbiAgICAgICAgLy8gdGhlIGVsZW1lbnQgdG8gYXBwbHkgdGhlICdzY3JvbGxpbmcnIGV2ZW50IHRvIChkZWZhdWx0IHdpbmRvdylcbiAgICAgICAgY29udGFpbmVyOiB3aW5kb3csXG5cbiAgICAgICAgLy8gdGhlIG1heGltdW0gdmFsdWUgb2YgdGhlIFggb3IgWSBjb29yZGluYXRlLCBkZXBlbmRpbmcgb24gbW9kZSB0aGUgc2VsZWN0ZWRcbiAgICAgICAgbWF4OiAwLFxuXG4gICAgICAgIC8vIHRoZSBtYXhpbXVtIHZhbHVlIG9mIHRoZSBYIG9yIFkgY29vcmRpbmF0ZSwgZGVwZW5kaW5nIG9uIG1vZGUgdGhlIHNlbGVjdGVkXG4gICAgICAgIG1pbjogMCxcblxuICAgICAgICAvLyB3aGV0aGVyIHRvIGxpc3RlbiB0byB0aGUgWCAoaG9yaXpvbnRhbCkgb3IgWSAodmVydGljYWwpIHNjcm9sbGluZ1xuICAgICAgICBtb2RlOiAndmVydGljYWwnLFxuXG4gICAgICAgIC8vIG5hbWVzcGFjZSB0byBhcHBlbmQgdG8gdGhlICdzY3JvbGwnIGV2ZW50XG4gICAgICAgIG5hbWVzcGFjZTogJ3Njcm9sbHNweScsXG5cbiAgICAgICAgLy8gY2FsbCB0aGUgZm9sbG93aW5nIGNhbGxiYWNrIGZ1bmN0aW9uIGV2ZXJ5IHRpbWUgdGhlIHVzZXIgZW50ZXJzIHRoZSBtaW4gLyBtYXggem9uZVxuICAgICAgICBvbkVudGVyOiBudWxsLFxuXG4gICAgICAgIC8vIGNhbGwgdGhlIGZvbGxvd2luZyBjYWxsYmFjayBmdW5jdGlvbiBldmVyeSB0aW1lIHRoZSB1c2VyIGxlYXZlcyB0aGUgbWluIC8gbWF4IHpvbmVcbiAgICAgICAgb25MZWF2ZTogbnVsbCxcblxuICAgICAgICAvLyBjYWxsIHRoZSBmb2xsb3dpbmcgY2FsbGJhY2sgZnVuY3Rpb24gZXZlcnkgdGltZSB0aGUgdXNlciBsZWF2ZXMgdGhlIHRvcCB6b25lXG4gICAgICAgIG9uTGVhdmVUb3A6IG51bGwsXG5cbiAgICAgICAgLy8gY2FsbCB0aGUgZm9sbG93aW5nIGNhbGxiYWNrIGZ1bmN0aW9uIGV2ZXJ5IHRpbWUgdGhlIHVzZXIgbGVhdmVzIHRoZSBib3R0b20gem9uZVxuICAgICAgICBvbkxlYXZlQm90dG9tOiBudWxsLFxuXG4gICAgICAgIC8vIGNhbGwgdGhlIGZvbGxvd2luZyBjYWxsYmFjayBmdW5jdGlvbiBvbiBlYWNoIHNjcm9sbCBldmVudCB3aXRoaW4gdGhlIG1pbiBhbmQgbWF4IHBhcmFtZXRlcnNcbiAgICAgICAgb25UaWNrOiBudWxsLFxuXG4gICAgICAgIC8vIGNhbGwgdGhlIGZvbGxvd2luZyBjYWxsYmFjayBmdW5jdGlvbiBvbiBlYWNoIHNjcm9sbCBldmVudCB3aGVuIHRoZSBlbGVtZW50IGlzIGluc2lkZSB0aGUgdmlld2FibGUgdmlldyBwb3J0XG4gICAgICAgIG9uVmlldzogbnVsbCxcbiAgICB9O1xuXG4gICAgLy8gTWV0aG9kcyAoUHJpdmF0ZSlcblxuICAgIC8vIGNoZWNrIGlmIGEgdmFsdWUgaXMgYW4gb2JqZWN0IGRhdGF0eXBlXG4gICAgZnVuY3Rpb24gX2lzT2JqZWN0KHZhbHVlKSB7XG4gICAgICAgIHJldHVybiAkLnR5cGUodmFsdWUpID09PSAnb2JqZWN0JztcbiAgICB9XG5cbiAgICAvLyBjaGVjayBpZiBhIHZhbHVlIGlzIGEgc3RyaW5nIGRhdGF0eXBlIHdpdGggYSBsZW5ndGggZ3JlYXRlciB0aGFuIHplcm8gd2hlbiB3aGl0ZXNwYWNlIGlzIHN0cmlwcGVkXG4gICAgZnVuY3Rpb24gX2lzU3RyaW5nKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiAkLnR5cGUodmFsdWUpID09PSAnc3RyaW5nJyAmJiAkLnRyaW0odmFsdWUpLmxlbmd0aCA+IDA7XG4gICAgfVxuXG4gICAgLy8gY2hlY2sgaWYgYW4gb3B0aW9uIGlzIGNvcnJlY3RseSBmb3JtYXR0ZWQgdXNpbmcgYSBwcmVkaWNhdGU7IG90aGVyd2lzZSwgcmV0dXJuIHRoZSBkZWZhdWx0IHZhbHVlXG4gICAgZnVuY3Rpb24gX3Nhbml0aXplT3B0aW9uKG9wdGlvbnMsIGRlZmF1bHRzLCBwcm9wZXJ0eSwgcHJlZGljYXRlKSB7XG4gICAgICAgIC8vIHNldCB0aGUgcHJvcGVydHkgdG8gdGhlIGRlZmF1bHQgdmFsdWUgaWYgdGhlIHByZWRpY2F0ZSByZXR1cm5lZCBmYWxzZVxuICAgICAgICBpZiAoIXByZWRpY2F0ZShvcHRpb25zW3Byb3BlcnR5XSkpIHtcbiAgICAgICAgICAgIG9wdGlvbnNbcHJvcGVydHldID0gZGVmYXVsdHNbcHJvcGVydHldO1xuICAgICAgICB9XG4gICAgfVxufSh3aW5kb3csIHdpbmRvdy5qUXVlcnkpKTtcbiIsIi8qKlxuICogQGZpbGVcbiAqIEluaXRpYWxpemUgU2Nyb2xsU3B5IHNjcmlwdHNcbiAqL1xuXG5pbXBvcnQgJy4uLy4uL3ZlbmRvci9qcXVlcnktc2Nyb2xsc3B5J1xuXG4oJCA9PiB7XG5cbiAgICBsZXQgdGFyZ2V0cyA9ICQoJy5maWVsZC0tbmFtZS1maWVsZC1tZW51LCAudGlldG8tY2FtcGFpZ24tcGFnZSA+IC5sb2dvLCAjaGFtYnVyZ2VyJylcblxuICAgIHRhcmdldHMuc2Nyb2xsc3B5KHtcbiAgICAgICAgbWluOiA0OTAsXG4gICAgICAgIG1heDogNTAwMDAsXG4gICAgICAgIG9uRW50ZXI6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgdGFyZ2V0cy5yZW1vdmVDbGFzcygnbm90LWZpeGVkJylcbiAgICAgICAgICAgIHRhcmdldHMuYWRkQ2xhc3MoJ2ZpeGVkJylcbiAgICAgICAgfSxcbiAgICAgICAgb25MZWF2ZTogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICB0YXJnZXRzLnJlbW92ZUNsYXNzKCdmaXhlZCcpXG4gICAgICAgICAgICB0YXJnZXRzLmFkZENsYXNzKCdub3QtZml4ZWQnKVxuICAgICAgICB9XG4gICAgfSlcblxuICAgICQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHsgdGFyZ2V0cy50cmlnZ2VyKCdzY3JvbGwuc2Nyb2xsc3B5JykgfSlcblxufSkoalF1ZXJ5KVxuIl0sIm5hbWVzIjpbImxldCJdLCJtYXBwaW5ncyI6Ijs7O0FBQUE7Ozs7O0FBS0EsQ0FBQyxTQUFTLGVBQWUsQ0FBQyxNQUFNLEVBQUUsQ0FBQyxFQUFFOzs7SUFHakMsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxNQUFNLENBQUM7UUFDUixTQUFTLEVBQUUsU0FBUyxTQUFTLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRTs7WUFFM0MsSUFBSSxTQUFTLENBQUMsT0FBTyxDQUFDLEVBQUU7Z0JBQ3BCLElBQUksV0FBVyxHQUFHLE1BQU0sQ0FBQzs7O2dCQUd6QixNQUFNLEdBQUcsT0FBTyxDQUFDOzs7Z0JBR2pCLE9BQU8sR0FBRyxXQUFXLENBQUM7YUFDekI7OztZQUdELE9BQU8sR0FBRyxDQUFDLENBQUMsTUFBTSxDQUFDLEVBQUUsRUFBRSxTQUFTLEVBQUUsT0FBTyxDQUFDLENBQUM7OztZQUczQyxlQUFlLENBQUMsT0FBTyxFQUFFLFNBQVMsRUFBRSxXQUFXLEVBQUUsU0FBUyxDQUFDLENBQUM7OztZQUc1RCxJQUFJLFVBQVUsR0FBRyxDQUFDLENBQUMsT0FBTyxDQUFDLFNBQVMsQ0FBQyxDQUFDOzs7WUFHdEMsSUFBSSxVQUFVLENBQUMsTUFBTSxLQUFLLENBQUMsRUFBRTtnQkFDekIsT0FBTyxJQUFJLENBQUM7YUFDZjs7O1lBR0QsZUFBZSxDQUFDLE9BQU8sRUFBRSxTQUFTLEVBQUUsV0FBVyxFQUFFLFNBQVMsQ0FBQyxDQUFDOzs7WUFHNUQsSUFBSSxTQUFTLENBQUMsTUFBTSxDQUFDLElBQUksTUFBTSxDQUFDLFdBQVcsRUFBRSxLQUFLLFNBQVMsRUFBRTtnQkFDekQsVUFBVSxDQUFDLEdBQUcsQ0FBQyxTQUFTLEdBQUcsT0FBTyxDQUFDLFNBQVMsQ0FBQyxDQUFDO2dCQUM5QyxPQUFPLElBQUksQ0FBQzthQUNmOzs7WUFHRCxlQUFl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
