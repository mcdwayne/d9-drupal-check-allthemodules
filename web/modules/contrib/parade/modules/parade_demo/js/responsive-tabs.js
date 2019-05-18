(function (exports) {
'use strict';

(function ( $, window, undefined ) {

    /** Default settings */
    var defaults = {
        active: null,
        event: 'click',
        disabled: [],
        collapsible: 'accordion',
        startCollapsed: false,
        rotate: false,
        setHash: false,
        animation: 'default',
        animationQueue: false,
        duration: 500,
        fluidHeight: true,
        scrollToAccordion: false,
        scrollToAccordionOnLoad: true,
        scrollToAccordionOffset: 0,
        accordionTabElement: '<div></div>',
        click: function(){},
        activate: function(){},
        deactivate: function(){},
        load: function(){},
        activateState: function(){},
        classes: {
            stateDefault: 'r-tabs-state-default',
            stateActive: 'r-tabs-state-active',
            stateDisabled: 'r-tabs-state-disabled',
            stateExcluded: 'r-tabs-state-excluded',
            container: 'r-tabs',
            ul: 'r-tabs-nav',
            tab: 'r-tabs-tab',
            anchor: 'r-tabs-anchor',
            panel: 'r-tabs-panel',
            accordionTitle: 'r-tabs-accordion-title'
        }
    };

    /**
     * Responsive Tabs
     * @constructor
     * @param {object} element - The HTML element the validator should be bound to
     * @param {object} options - An option map
     */
    function ResponsiveTabs(element, options) {
        this.element = element; // Selected DOM element
        this.$element = $(element); // Selected jQuery element

        this.tabs = []; // Create tabs array
        this.state = ''; // Define the plugin state (tabs/accordion)
        this.rotateInterval = 0; // Define rotate interval
        this.$queue = $({});

        // Extend the defaults with the passed options
        this.options = $.extend( {}, defaults, options);

        this.init();
    }


    /**
     * This function initializes the tab plugin
     */
    ResponsiveTabs.prototype.init = function () {
        var _this = this;

        // Load all the elements
        this.tabs = this._loadElements();
        this._loadClasses();
        this._loadEvents();

        // Window resize bind to check state
        $(window).on('resize', function(e) {
            _this._setState(e);
            _this._equaliseHeights();
        });

        // Hashchange event
        $(window).on('hashchange', function(e) {
            var tabRef = _this._getTabRefBySelector(window.location.hash);
            var oTab = _this._getTab(tabRef);

            // Check if a tab is found that matches the hash
            if(tabRef >= 0 && !oTab._ignoreHashChange && !oTab.disabled) {
                // If so, open the tab and auto close the current one
                _this._openTab(e, _this._getTab(tabRef), true);
            }
        });

        // Start rotate event if rotate option is defined
        if(this.options.rotate !== false) {
            this.startRotation();
        }

        // Set fluid height
        if(this.options.fluidHeight !== true)  {
            _this._equaliseHeights();
        }

        // --------------------
        // Define plugin events
        //

        // Activate: this event is called when a tab is selected
        this.$element.bind('tabs-click', function(e, oTab) {
            _this.options.click.call(this, e, oTab);
        });

        // Activate: this event is called when a tab is selected
        this.$element.bind('tabs-activate', function(e, oTab) {
            _this.options.activate.call(this, e, oTab);
        });
        // Deactivate: this event is called when a tab is closed
        this.$element.bind('tabs-deactivate', function(e, oTab) {
            _this.options.deactivate.call(this, e, oTab);
        });
        // Activate State: this event is called when the plugin switches states
        this.$element.bind('tabs-activate-state', function(e, state) {
            _this.options.activateState.call(this, e, state);
        });

        // Load: this event is called when the plugin has been loaded
        this.$element.bind('tabs-load', function(e) {
            var startTab;

            _this._setState(e); // Set state

            // Check if the panel should be collaped on load
            if(_this.options.startCollapsed !== true && !(_this.options.startCollapsed === 'accordion' && _this.state === 'accordion')) {

                startTab = _this._getStartTab();

                // Open the initial tab
                _this._openTab(e, startTab); // Open first tab

                // Call the callback function
                _this.options.load.call(this, e, startTab); // Call the load callback
            }
        });
        // Trigger loaded event
        this.$element.trigger('tabs-load');
    };

    //
    // PRIVATE FUNCTIONS
    //

    /**
     * This function loads the tab elements and stores them in an array
     * @returns {Array} Array of tab elements
     */
    ResponsiveTabs.prototype._loadElements = function() {
        var _this = this;
        var $ul = this.$element.children('ul:first');
        var tabs = [];
        var id = 0;

        // Add the classes to the basic html elements
        this.$element.addClass(_this.options.classes.container); // Tab container
        $ul.addClass(_this.options.classes.ul); // List container

        // Get tab buttons and store their data in an array
        $('li', $ul).each(function() {
            var $tab = $(this);
            var isExcluded = $tab.hasClass(_this.options.classes.stateExcluded);
            var $anchor, $panel, $accordionTab, $accordionAnchor, panelSelector;

            // Check if the tab should be excluded
            if(!isExcluded) {

                $anchor = $('a', $tab);
                panelSelector = $anchor.attr('href');
                $panel = $(panelSelector);
                $accordionTab = $(_this.options.accordionTabElement).insertBefore($panel);
                $accordionAnchor = $('<a></a>').attr('href', panelSelector).html($anchor.html()).appendTo($accordionTab);

                var oTab = {
                    _ignoreHashChange: false,
                    id: id,
                    disabled: ($.inArray(id, _this.options.disabled) !== -1),
                    tab: $(this),
                    anchor: $('a', $tab),
                    panel: $panel,
                    selector: panelSelector,
                    accordionTab: $accordionTab,
                    accordionAnchor: $accordionAnchor,
                    active: false
                };

                // 1up the ID
                id++;
                // Add to tab array
                tabs.push(oTab);
            }
        });
        return tabs;
    };


    /**
     * This function adds classes to the tab elements based on the options
     */
    ResponsiveTabs.prototype._loadClasses = function() {
        var this$1 = this;

        for (var i=0; i<this.tabs.length; i++) {
            this$1.tabs[i].tab.addClass(this$1.options.classes.stateDefault).addClass(this$1.options.classes.tab);
            this$1.tabs[i].anchor.addClass(this$1.options.classes.anchor);
            this$1.tabs[i].panel.addClass(this$1.options.classes.stateDefault).addClass(this$1.options.classes.panel);
            this$1.tabs[i].accordionTab.addClass(this$1.options.classes.accordionTitle);
            this$1.tabs[i].accordionAnchor.addClass(this$1.options.classes.anchor);
            if(this$1.tabs[i].disabled) {
                this$1.tabs[i].tab.removeClass(this$1.options.classes.stateDefault).addClass(this$1.options.classes.stateDisabled);
                this$1.tabs[i].accordionTab.removeClass(this$1.options.classes.stateDefault).addClass(this$1.options.classes.stateDisabled);
           }
        }
    };

    /**
     * This function adds events to the tab elements
     */
    ResponsiveTabs.prototype._loadEvents = function() {
        var this$1 = this;

        var _this = this;

        // Define activate event on a tab element
        var fActivate = function(e) {
            var current = _this._getCurrentTab(); // Fetch current tab
            var activatedTab = e.data.tab;

            e.preventDefault();

            // Trigger click event for whenever a tab is clicked/touched even if the tab is disabled
            activatedTab.tab.trigger('tabs-click', activatedTab);

            // Make sure this tab isn't disabled
            if(!activatedTab.disabled) {

                // Check if hash has to be set in the URL location
                if(_this.options.setHash) {
                    // Set the hash using the history api if available to tackle Chromes repaint bug on hash change
                    if(history.pushState) {
                        history.pushState(null, null, window.location.origin + window.location.pathname + window.location.search + activatedTab.selector);
                    } else {
                        // Otherwise fallback to the hash update for sites that don't support the history api
                        window.location.hash = activatedTab.selector;
                    }
                }

                e.data.tab._ignoreHashChange = true;

                // Check if the activated tab isnt the current one or if its collapsible. If not, do nothing
                if(current !== activatedTab || _this._isCollapisble()) {
                    // The activated tab is either another tab of the current one. If it's the current tab it is collapsible
                    // Either way, the current tab can be closed
                    _this._closeTab(e, current);

                    // Check if the activated tab isnt the current one or if it isnt collapsible
                    if(current !== activatedTab || !_this._isCollapisble()) {
                        _this._openTab(e, activatedTab, false, true);
                    }
                }
            }
        };

        // Loop tabs
        for (var i=0; i<this.tabs.length; i++) {
            // Add activate function to the tab and accordion selection element
            this$1.tabs[i].anchor.on(_this.options.event, {tab: _this.tabs[i]}, fActivate);
            this$1.tabs[i].accordionAnchor.on(_this.options.event, {tab: _this.tabs[i]}, fActivate);
        }
    };

    /**
     * This function gets the tab that should be opened at start
     * @returns {Object} Tab object
     */
    ResponsiveTabs.prototype._getStartTab = function() {
        var tabRef = this._getTabRefBySelector(window.location.hash);
        var startTab;

        // Check if the page has a hash set that is linked to a tab
        if(tabRef >= 0 && !this._getTab(tabRef).disabled) {
            // If so, set the current tab to the linked tab
            startTab = this._getTab(tabRef);
        } else if(this.options.active > 0 && !this._getTab(this.options.active).disabled) {
            startTab = this._getTab(this.options.active);
        } else {
            // If not, just get the first one
            startTab = this._getTab(0);
        }

        return startTab;
    };

    /**
     * This function sets the current state of the plugin
     * @param {Event} e - The event that triggers the state change
     */
    ResponsiveTabs.prototype._setState = function(e) {
        var $ul = $('ul:first', this.$element);
        var oldState = this.state;
        var startCollapsedIsState = (typeof this.options.startCollapsed === 'string');
        var startTab;

        // The state is based on the visibility of the tabs list
        if($ul.is(':visible')){
            // Tab list is visible, so the state is 'tabs'
            this.state = 'tabs';
        } else {
            // Tab list is invisible, so the state is 'accordion'
            this.state = 'accordion';
        }

        // If the new state is different from the old state
        if(this.state !== oldState) {
            // If so, the state activate trigger must be called
            this.$element.trigger('tabs-activate-state', {oldState: oldState, newState: this.state});

            // Check if the state switch should open a tab
            if(oldState && startCollapsedIsState && this.options.startCollapsed !== this.state && this._getCurrentTab() === undefined) {
                // Get initial tab
                startTab = this._getStartTab(e);
                // Open the initial tab
                this._openTab(e, startTab); // Open first tab
            }
        }
    };

    /**
     * This function opens a tab
     * @param {Event} e - The event that triggers the tab opening
     * @param {Object} oTab - The tab object that should be opened
     * @param {Boolean} closeCurrent - Defines if the current tab should be closed
     * @param {Boolean} stopRotation - Defines if the tab rotation loop should be stopped
     */
    ResponsiveTabs.prototype._openTab = function(e, oTab, closeCurrent, stopRotation) {
        var _this = this;
        var scrollOffset;

        // Check if the current tab has to be closed
        if(closeCurrent) {
            this._closeTab(e, this._getCurrentTab());
        }

        // Check if the rotation has to be stopped when activated
        if(stopRotation && this.rotateInterval > 0) {
            this.stopRotation();
        }

        // Set this tab to active
        oTab.active = true;
        // Set active classes to the tab button and accordion tab button
        oTab.tab.removeClass(_this.options.classes.stateDefault).addClass(_this.options.classes.stateActive);
        oTab.accordionTab.removeClass(_this.options.classes.stateDefault).addClass(_this.options.classes.stateActive);

        // Run panel transiton
        _this._doTransition(oTab.panel, _this.options.animation, 'open', function() {
            var scrollOnLoad = (e.type !== 'tabs-load' || _this.options.scrollToAccordionOnLoad);

            // When finished, set active class to the panel
            oTab.panel.removeClass(_this.options.classes.stateDefault).addClass(_this.options.classes.stateActive);

            // And if enabled and state is accordion, scroll to the accordion tab
            if(_this.getState() === 'accordion' && _this.options.scrollToAccordion && (!_this._isInView(oTab.accordionTab) || _this.options.animation !== 'default') && scrollOnLoad) {

                // Add offset element's height to scroll position
                scrollOffset = oTab.accordionTab.offset().top - _this.options.scrollToAccordionOffset;

                // Check if the animation option is enabled, and if the duration isn't 0
                if(_this.options.animation !== 'default' && _this.options.duration > 0) {
                    // If so, set scrollTop with animate and use the 'animation' duration
                    $('html, body').animate({
                        scrollTop: scrollOffset
                    }, _this.options.duration);
                } else {
                    //  If not, just set scrollTop
                    $('html, body').scrollTop(scrollOffset);
                }
            }
        });

        this.$element.trigger('tabs-activate', oTab);
    };

    /**
     * This function closes a tab
     * @param {Event} e - The event that is triggered when a tab is closed
     * @param {Object} oTab - The tab object that should be closed
     */
    ResponsiveTabs.prototype._closeTab = function(e, oTab) {
        var _this = this;
        var doQueueOnState = typeof _this.options.animationQueue === 'string';
        var doQueue;

        if(oTab !== undefined) {
            if(doQueueOnState && _this.getState() === _this.options.animationQueue) {
                doQueue = true;
            } else if(doQueueOnState) {
                doQueue = false;
            } else {
                doQueue = _this.options.animationQueue;
            }

            // Deactivate tab
            oTab.active = false;
            // Set default class to the tab button
            oTab.tab.removeClass(_this.options.classes.stateActive).addClass(_this.options.classes.stateDefault);

            // Run panel transition
            _this._doTransition(oTab.panel, _this.options.animation, 'close', function() {
                // Set default class to the accordion tab button and tab panel
                oTab.accordionTab.removeClass(_this.options.classes.stateActive).addClass(_this.options.classes.stateDefault);
                oTab.panel.removeClass(_this.options.classes.stateActive).addClass(_this.options.classes.stateDefault);
            }, !doQueue);

            this.$element.trigger('tabs-deactivate', oTab);
        }
    };

    /**
     * This function runs an effect on a panel
     * @param {Element} panel - The HTML element of the tab panel
     * @param {String} method - The transition method reference
     * @param {String} state - The state (open/closed) that the panel should transition to
     * @param {Function} callback - The callback function that is called after the transition
     * @param {Boolean} dequeue - Defines if the event queue should be dequeued after the transition
     */
    ResponsiveTabs.prototype._doTransition = function(panel, method, state, callback, dequeue) {
        var effect;
        var _this = this;

        // Get effect based on method
        switch(method) {
            case 'slide':
                effect = (state === 'open') ? 'slideDown' : 'slideUp';
                break;
            case 'fade':
                effect = (state === 'open') ? 'fadeIn' : 'fadeOut';
                break;
            default:
                effect = (state === 'open') ? 'show' : 'hide';
                // When default is used, set the duration to 0
                _this.options.duration = 0;
                break;
        }

        // Add the transition to a custom queue
        this.$queue.queue('responsive-tabs',function(next){
            // Run the transition on the panel
            panel[effect]({
                duration: _this.options.duration,
                complete: function() {
                    // Call the callback function
                    callback.call(panel, method, state);
                    // Run the next function in the queue
                    next();
                }
            });
        });

        // When the panel is openend, dequeue everything so the animation starts
        if(state === 'open' || dequeue) {
            this.$queue.dequeue('responsive-tabs');
        }

    };

    /**
     * This function returns the collapsibility of the tab in this state
     * @returns {Boolean} The collapsibility of the tab
     */
    ResponsiveTabs.prototype._isCollapisble = function() {
        return (typeof this.options.collapsible === 'boolean' && this.options.collapsible) || (typeof this.options.collapsible === 'string' && this.options.collapsible === this.getState());
    };

    /**
     * This function returns a tab by numeric reference
     * @param {Integer} numRef - Numeric tab reference
     * @returns {Object} Tab object
     */
    ResponsiveTabs.prototype._getTab = function(numRef) {
        return this.tabs[numRef];
    };

    /**
     * This function returns the numeric tab reference based on a hash selector
     * @param {String} selector - Hash selector
     * @returns {Integer} Numeric tab reference
     */
    ResponsiveTabs.prototype._getTabRefBySelector = function(selector) {
        var this$1 = this;

        // Loop all tabs
        for (var i=0; i<this.tabs.length; i++) {
            // Check if the hash selector is equal to the tab selector
            if(this$1.tabs[i].selector === selector) {
                return i;
            }
        }
        // If none is found return a negative index
        return -1;
    };

    /**
     * This function returns the current tab element
     * @returns {Object} Current tab element
     */
    ResponsiveTabs.prototype._getCurrentTab = function() {
        return this._getTab(this._getCurrentTabRef());
    };

    /**
     * This function returns the next tab's numeric reference
     * @param {Integer} currentTabRef - Current numeric tab reference
     * @returns {Integer} Numeric tab reference
     */
    ResponsiveTabs.prototype._getNextTabRef = function(currentTabRef) {
        var tabRef = (currentTabRef || this._getCurrentTabRef());
        var nextTabRef = (tabRef === this.tabs.length - 1) ? 0 : tabRef + 1;
        return (this._getTab(nextTabRef).disabled) ? this._getNextTabRef(nextTabRef) : nextTabRef;
    };

    /**
     * This function returns the previous tab's numeric reference
     * @returns {Integer} Numeric tab reference
     */
    ResponsiveTabs.prototype._getPreviousTabRef = function() {
        return (this._getCurrentTabRef() === 0) ? this.tabs.length - 1 : this._getCurrentTabRef() - 1;
    };

    /**
     * This function returns the current tab's numeric reference
     * @returns {Integer} Numeric tab reference
     */
    ResponsiveTabs.prototype._getCurrentTabRef = function() {
        var this$1 = this;

        // Loop all tabs
        for (var i=0; i<this.tabs.length; i++) {
            // If this tab is active, return it
            if(this$1.tabs[i].active) {
                return i;
            }
        }
        // No tabs have been found, return negative index
        return -1;
    };

    /**
     * This function gets the tallest tab and applied the height to all tabs
     */
    ResponsiveTabs.prototype._equaliseHeights = function() {
        var maxHeight = 0;

        $.each($.map(this.tabs, function(tab) {
            maxHeight = Math.max(maxHeight, tab.panel.css('minHeight', '').height());
            return tab.panel;
        }), function() {
            this.css('minHeight', maxHeight);
        });
    };

    //
    // HELPER FUNCTIONS
    //

    ResponsiveTabs.prototype._isInView = function($element) {
        var docViewTop = $(window).scrollTop(),
            docViewBottom = docViewTop + $(window).height(),
            elemTop = $element.offset().top,
            elemBottom = elemTop + $element.height();
        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    };

    //
    // PUBLIC FUNCTIONS
    //

    /**
     * This function activates a tab
     * @param {Integer} tabRef - Numeric tab reference
     * @param {Boolean} stopRotation - Defines if the tab rotation should stop after activation
     */
    ResponsiveTabs.prototype.activate = function(tabRef, stopRotation) {
        var e = jQuery.Event('tabs-activate');
        var oTab = this._getTab(tabRef);
        if(!oTab.disabled) {
            this._openTab(e, oTab, true, stopRotation || true);
        }
    };

    /**
     * This function deactivates a tab
     * @param {Integer} tabRef - Numeric tab reference
     */
    ResponsiveTabs.prototype.deactivate = function(tabRef) {
        var e = jQuery.Event('tabs-dectivate');
        var oTab = this._getTab(tabRef);
        if(!oTab.disabled) {
            this._closeTab(e, oTab);
        }
    };

    /**
     * This function enables a tab
     * @param {Integer} tabRef - Numeric tab reference
     */
    ResponsiveTabs.prototype.enable = function(tabRef) {
        var oTab = this._getTab(tabRef);
        if(oTab){
            oTab.disabled = false;
            oTab.tab.addClass(this.options.classes.stateDefault).removeClass(this.options.classes.stateDisabled);
            oTab.accordionTab.addClass(this.options.classes.stateDefault).removeClass(this.options.classes.stateDisabled);
        }
    };

    /**
     * This function disable a tab
     * @param {Integer} tabRef - Numeric tab reference
     */
    ResponsiveTabs.prototype.disable = function(tabRef) {
        var oTab = this._getTab(tabRef);
        if(oTab){
            oTab.disabled = true;
            oTab.tab.removeClass(this.options.classes.stateDefault).addClass(this.options.classes.stateDisabled);
            oTab.accordionTab.removeClass(this.options.classes.stateDefault).addClass(this.options.classes.stateDisabled);
        }
    };

    /**
     * This function gets the current state of the plugin
     * @returns {String} State of the plugin
     */
    ResponsiveTabs.prototype.getState = function() {
        return this.state;
    };

    /**
     * This function starts the rotation of the tabs
     * @param {Integer} speed - The speed of the rotation
     */
    ResponsiveTabs.prototype.startRotation = function(speed) {
        var _this = this;
        // Make sure not all tabs are disabled
        if(this.tabs.length > this.options.disabled.length) {
            this.rotateInterval = setInterval(function(){
                var e = jQuery.Event('rotate');
                _this._openTab(e, _this._getTab(_this._getNextTabRef()), true);
            }, speed || (($.isNumeric(_this.options.rotate)) ? _this.options.rotate : 4000) );
        } else {
            throw new Error("Rotation is not possible if all tabs are disabled");
        }
    };

    /**
     * This function stops the rotation of the tabs
     */
    ResponsiveTabs.prototype.stopRotation = function() {
        window.clearInterval(this.rotateInterval);
        this.rotateInterval = 0;
    };

    /**
     * This function can be used to get/set options
     * @return {any} Option value
     */
    ResponsiveTabs.prototype.option = function(key, value) {
        if(value) {
            this.options[key] = value;
        }
        return this.options[key];
    };

    /** jQuery wrapper */
    $.fn.responsiveTabs = function ( options ) {
        var args = arguments;
        var instance;

        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if (!$.data(this, 'responsivetabs')) {
                    $.data(this, 'responsivetabs', new ResponsiveTabs( this, options ));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            instance = $.data(this[0], 'responsivetabs');

            // Allow instances to be destroyed via the 'destroy' method
            if (options === 'destroy') {
                // TODO: destroy instance classes, etc
                $.data(this, 'responsivetabs', null);
            }

            if (instance instanceof ResponsiveTabs && typeof instance[options] === 'function') {
                return instance[options].apply( instance, Array.prototype.slice.call( args, 1 ) );
            } else {
                return this;
            }
        }
    };

}(jQuery, window));

/**
 * @file
 * Initialize Responsive Tabs scripts.
 */

(function ($) {

	$('.r-tabs-container').responsiveTabs({
	  startCollapsed: false,
	  animation: 'slide',
	  duration: 200
	});

})(jQuery);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL25vZGVfbW9kdWxlcy9yZXNwb25zaXZlLXRhYnMvanMvanF1ZXJ5LnJlc3BvbnNpdmVUYWJzLmpzIiwiRDovZGV2ZGVza3RvcC90Y3MubG9jL3dlYi90aGVtZXMvdGlldG9fYWRtaW4vc3JjL3NjcmlwdHMvcmVzcG9uc2l2ZS10YWJzLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIjsoZnVuY3Rpb24gKCAkLCB3aW5kb3csIHVuZGVmaW5lZCApIHtcblxuICAgIC8qKiBEZWZhdWx0IHNldHRpbmdzICovXG4gICAgdmFyIGRlZmF1bHRzID0ge1xuICAgICAgICBhY3RpdmU6IG51bGwsXG4gICAgICAgIGV2ZW50OiAnY2xpY2snLFxuICAgICAgICBkaXNhYmxlZDogW10sXG4gICAgICAgIGNvbGxhcHNpYmxlOiAnYWNjb3JkaW9uJyxcbiAgICAgICAgc3RhcnRDb2xsYXBzZWQ6IGZhbHNlLFxuICAgICAgICByb3RhdGU6IGZhbHNlLFxuICAgICAgICBzZXRIYXNoOiBmYWxzZSxcbiAgICAgICAgYW5pbWF0aW9uOiAnZGVmYXVsdCcsXG4gICAgICAgIGFuaW1hdGlvblF1ZXVlOiBmYWxzZSxcbiAgICAgICAgZHVyYXRpb246IDUwMCxcbiAgICAgICAgZmx1aWRIZWlnaHQ6IHRydWUsXG4gICAgICAgIHNjcm9sbFRvQWNjb3JkaW9uOiBmYWxzZSxcbiAgICAgICAgc2Nyb2xsVG9BY2NvcmRpb25PbkxvYWQ6IHRydWUsXG4gICAgICAgIHNjcm9sbFRvQWNjb3JkaW9uT2Zmc2V0OiAwLFxuICAgICAgICBhY2NvcmRpb25UYWJFbGVtZW50OiAnPGRpdj48L2Rpdj4nLFxuICAgICAgICBjbGljazogZnVuY3Rpb24oKXt9LFxuICAgICAgICBhY3RpdmF0ZTogZnVuY3Rpb24oKXt9LFxuICAgICAgICBkZWFjdGl2YXRlOiBmdW5jdGlvbigpe30sXG4gICAgICAgIGxvYWQ6IGZ1bmN0aW9uKCl7fSxcbiAgICAgICAgYWN0aXZhdGVTdGF0ZTogZnVuY3Rpb24oKXt9LFxuICAgICAgICBjbGFzc2VzOiB7XG4gICAgICAgICAgICBzdGF0ZURlZmF1bHQ6ICdyLXRhYnMtc3RhdGUtZGVmYXVsdCcsXG4gICAgICAgICAgICBzdGF0ZUFjdGl2ZTogJ3ItdGFicy1zdGF0ZS1hY3RpdmUnLFxuICAgICAgICAgICAgc3RhdGVEaXNhYmxlZDogJ3ItdGFicy1zdGF0ZS1kaXNhYmxlZCcsXG4gICAgICAgICAgICBzdGF0ZUV4Y2x1ZGVkOiAnci10YWJzLXN0YXRlLWV4Y2x1ZGVkJyxcbiAgICAgICAgICAgIGNvbnRhaW5lcjogJ3ItdGFicycsXG4gICAgICAgICAgICB1bDogJ3ItdGFicy1uYXYnLFxuICAgICAgICAgICAgdGFiOiAnci10YWJzLXRhYicsXG4gICAgICAgICAgICBhbmNob3I6ICdyLXRhYnMtYW5jaG9yJyxcbiAgICAgICAgICAgIHBhbmVsOiAnci10YWJzLXBhbmVsJyxcbiAgICAgICAgICAgIGFjY29yZGlvblRpdGxlOiAnci10YWJzLWFjY29yZGlvbi10aXRsZSdcbiAgICAgICAgfVxuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBSZXNwb25zaXZlIFRhYnNcbiAgICAgKiBAY29uc3RydWN0b3JcbiAgICAgKiBAcGFyYW0ge29iamVjdH0gZWxlbWVudCAtIFRoZSBIVE1MIGVsZW1lbnQgdGhlIHZhbGlkYXRvciBzaG91bGQgYmUgYm91bmQgdG9cbiAgICAgKiBAcGFyYW0ge29iamVjdH0gb3B0aW9ucyAtIEFuIG9wdGlvbiBtYXBcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBSZXNwb25zaXZlVGFicyhlbGVtZW50LCBvcHRpb25zKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7IC8vIFNlbGVjdGVkIERPTSBlbGVtZW50XG4gICAgICAgIHRoaXMuJGVsZW1lbnQgPSAkKGVsZW1lbnQpOyAvLyBTZWxlY3RlZCBqUXVlcnkgZWxlbWVudFxuXG4gICAgICAgIHRoaXMudGFicyA9IFtdOyAvLyBDcmVhdGUgdGFicyBhcnJheVxuICAgICAgICB0aGlzLnN0YXRlID0gJyc7IC8vIERlZmluZSB0aGUgcGx1Z2luIHN0YXRlICh0YWJzL2FjY29yZGlvbilcbiAgICAgICAgdGhpcy5yb3RhdGVJbnRlcnZhbCA9IDA7IC8vIERlZmluZSByb3RhdGUgaW50ZXJ2YWxcbiAgICAgICAgdGhpcy4kcXVldWUgPSAkKHt9KTtcblxuICAgICAgICAvLyBFeHRlbmQgdGhlIGRlZmF1bHRzIHdpdGggdGhlIHBhc3NlZCBvcHRpb25zXG4gICAgICAgIHRoaXMub3B0aW9ucyA9ICQuZXh0ZW5kKCB7fSwgZGVmYXVsdHMsIG9wdGlvbnMpO1xuXG4gICAgICAgIHRoaXMuaW5pdCgpO1xuICAgIH1cblxuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBpbml0aWFsaXplcyB0aGUgdGFiIHBsdWdpblxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5pbml0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuXG4gICAgICAgIC8vIExvYWQgYWxsIHRoZSBlbGVtZW50c1xuICAgICAgICB0aGlzLnRhYnMgPSB0aGlzLl9sb2FkRWxlbWVudHMoKTtcbiAgICAgICAgdGhpcy5fbG9hZENsYXNzZXMoKTtcbiAgICAgICAgdGhpcy5fbG9hZEV2ZW50cygpO1xuXG4gICAgICAgIC8vIFdpbmRvdyByZXNpemUgYmluZCB0byBjaGVjayBzdGF0ZVxuICAgICAgICAkKHdpbmRvdykub24oJ3Jlc2l6ZScsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgIF90aGlzLl9zZXRTdGF0ZShlKTtcbiAgICAgICAgICAgIF90aGlzLl9lcXVhbGlzZUhlaWdodHMoKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gSGFzaGNoYW5nZSBldmVudFxuICAgICAgICAkKHdpbmRvdykub24oJ2hhc2hjaGFuZ2UnLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICB2YXIgdGFiUmVmID0gX3RoaXMuX2dldFRhYlJlZkJ5U2VsZWN0b3Iod2luZG93LmxvY2F0aW9uLmhhc2gpO1xuICAgICAgICAgICAgdmFyIG9UYWIgPSBfdGhpcy5fZ2V0VGFiKHRhYlJlZik7XG5cbiAgICAgICAgICAgIC8vIENoZWNrIGlmIGEgdGFiIGlzIGZvdW5kIHRoYXQgbWF0Y2hlcyB0aGUgaGFzaFxuICAgICAgICAgICAgaWYodGFiUmVmID49IDAgJiYgIW9UYWIuX2lnbm9yZUhhc2hDaGFuZ2UgJiYgIW9UYWIuZGlzYWJsZWQpIHtcbiAgICAgICAgICAgICAgICAvLyBJZiBzbywgb3BlbiB0aGUgdGFiIGFuZCBhdXRvIGNsb3NlIHRoZSBjdXJyZW50IG9uZVxuICAgICAgICAgICAgICAgIF90aGlzLl9vcGVuVGFiKGUsIF90aGlzLl9nZXRUYWIodGFiUmVmKSwgdHJ1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIFN0YXJ0IHJvdGF0ZSBldmVudCBpZiByb3RhdGUgb3B0aW9uIGlzIGRlZmluZWRcbiAgICAgICAgaWYodGhpcy5vcHRpb25zLnJvdGF0ZSAhPT0gZmFsc2UpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRSb3RhdGlvbigpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gU2V0IGZsdWlkIGhlaWdodFxuICAgICAgICBpZih0aGlzLm9wdGlvbnMuZmx1aWRIZWlnaHQgIT09IHRydWUpICB7XG4gICAgICAgICAgICBfdGhpcy5fZXF1YWxpc2VIZWlnaHRzKCk7XG4gICAgICAgIH1cblxuICAgICAgICAvLyAtLS0tLS0tLS0tLS0tLS0tLS0tLVxuICAgICAgICAvLyBEZWZpbmUgcGx1Z2luIGV2ZW50c1xuICAgICAgICAvL1xuXG4gICAgICAgIC8vIEFjdGl2YXRlOiB0aGlzIGV2ZW50IGlzIGNhbGxlZCB3aGVuIGEgdGFiIGlzIHNlbGVjdGVkXG4gICAgICAgIHRoaXMuJGVsZW1lbnQuYmluZCgndGFicy1jbGljaycsIGZ1bmN0aW9uKGUsIG9UYWIpIHtcbiAgICAgICAgICAgIF90aGlzLm9wdGlvbnMuY2xpY2suY2FsbCh0aGlzLCBlLCBvVGFiKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gQWN0aXZhdGU6IHRoaXMgZXZlbnQgaXMgY2FsbGVkIHdoZW4gYSB0YWIgaXMgc2VsZWN0ZWRcbiAgICAgICAgdGhpcy4kZWxlbWVudC5iaW5kKCd0YWJzLWFjdGl2YXRlJywgZnVuY3Rpb24oZSwgb1RhYikge1xuICAgICAgICAgICAgX3RoaXMub3B0aW9ucy5hY3RpdmF0ZS5jYWxsKHRoaXMsIGUsIG9UYWIpO1xuICAgICAgICB9KTtcbiAgICAgICAgLy8gRGVhY3RpdmF0ZTogdGhpcyBldmVudCBpcyBjYWxsZWQgd2hlbiBhIHRhYiBpcyBjbG9zZWRcbiAgICAgICAgdGhpcy4kZWxlbWVudC5iaW5kKCd0YWJzLWRlYWN0aXZhdGUnLCBmdW5jdGlvbihlLCBvVGFiKSB7XG4gICAgICAgICAgICBfdGhpcy5vcHRpb25zLmRlYWN0aXZhdGUuY2FsbCh0aGlzLCBlLCBvVGFiKTtcbiAgICAgICAgfSk7XG4gICAgICAgIC8vIEFjdGl2YXRlIFN0YXRlOiB0aGlzIGV2ZW50IGlzIGNhbGxlZCB3aGVuIHRoZSBwbHVnaW4gc3dpdGNoZXMgc3RhdGVzXG4gICAgICAgIHRoaXMuJGVsZW1lbnQuYmluZCgndGFicy1hY3RpdmF0ZS1zdGF0ZScsIGZ1bmN0aW9uKGUsIHN0YXRlKSB7XG4gICAgICAgICAgICBfdGhpcy5vcHRpb25zLmFjdGl2YXRlU3RhdGUuY2FsbCh0aGlzLCBlLCBzdGF0ZSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIExvYWQ6IHRoaXMgZXZlbnQgaXMgY2FsbGVkIHdoZW4gdGhlIHBsdWdpbiBoYXMgYmVlbiBsb2FkZWRcbiAgICAgICAgdGhpcy4kZWxlbWVudC5iaW5kKCd0YWJzLWxvYWQnLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICB2YXIgc3RhcnRUYWI7XG5cbiAgICAgICAgICAgIF90aGlzLl9zZXRTdGF0ZShlKTsgLy8gU2V0IHN0YXRlXG5cbiAgICAgICAgICAgIC8vIENoZWNrIGlmIHRoZSBwYW5lbCBzaG91bGQgYmUgY29sbGFwZWQgb24gbG9hZFxuICAgICAgICAgICAgaWYoX3RoaXMub3B0aW9ucy5zdGFydENvbGxhcHNlZCAhPT0gdHJ1ZSAmJiAhKF90aGlzLm9wdGlvbnMuc3RhcnRDb2xsYXBzZWQgPT09ICdhY2NvcmRpb24nICYmIF90aGlzLnN0YXRlID09PSAnYWNjb3JkaW9uJykpIHtcblxuICAgICAgICAgICAgICAgIHN0YXJ0VGFiID0gX3RoaXMuX2dldFN0YXJ0VGFiKCk7XG5cbiAgICAgICAgICAgICAgICAvLyBPcGVuIHRoZSBpbml0aWFsIHRhYlxuICAgICAgICAgICAgICAgIF90aGlzLl9vcGVuVGFiKGUsIHN0YXJ0VGFiKTsgLy8gT3BlbiBmaXJzdCB0YWJcblxuICAgICAgICAgICAgICAgIC8vIENhbGwgdGhlIGNhbGxiYWNrIGZ1bmN0aW9uXG4gICAgICAgICAgICAgICAgX3RoaXMub3B0aW9ucy5sb2FkLmNhbGwodGhpcywgZSwgc3RhcnRUYWIpOyAvLyBDYWxsIHRoZSBsb2FkIGNhbGxiYWNrXG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICAvLyBUcmlnZ2VyIGxvYWRlZCBldmVudFxuICAgICAgICB0aGlzLiRlbGVtZW50LnRyaWdnZXIoJ3RhYnMtbG9hZCcpO1xuICAgIH07XG5cbiAgICAvL1xuICAgIC8vIFBSSVZBVEUgRlVOQ1RJT05TXG4gICAgLy9cblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gbG9hZHMgdGhlIHRhYiBlbGVtZW50cyBhbmQgc3RvcmVzIHRoZW0gaW4gYW4gYXJyYXlcbiAgICAgKiBAcmV0dXJucyB7QXJyYXl9IEFycmF5IG9mIHRhYiBlbGVtZW50c1xuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fbG9hZEVsZW1lbnRzID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciAkdWwgPSB0aGlzLiRlbGVtZW50LmNoaWxkcmVuKCd1bDpmaXJzdCcpO1xuICAgICAgICB2YXIgdGFicyA9IFtdO1xuICAgICAgICB2YXIgaWQgPSAwO1xuXG4gICAgICAgIC8vIEFkZCB0aGUgY2xhc3NlcyB0byB0aGUgYmFzaWMgaHRtbCBlbGVtZW50c1xuICAgICAgICB0aGlzLiRlbGVtZW50LmFkZENsYXNzKF90aGlzLm9wdGlvbnMuY2xhc3Nlcy5jb250YWluZXIpOyAvLyBUYWIgY29udGFpbmVyXG4gICAgICAgICR1bC5hZGRDbGFzcyhfdGhpcy5vcHRpb25zLmNsYXNzZXMudWwpOyAvLyBMaXN0IGNvbnRhaW5lclxuXG4gICAgICAgIC8vIEdldCB0YWIgYnV0dG9ucyBhbmQgc3RvcmUgdGhlaXIgZGF0YSBpbiBhbiBhcnJheVxuICAgICAgICAkKCdsaScsICR1bCkuZWFjaChmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIHZhciAkdGFiID0gJCh0aGlzKTtcbiAgICAgICAgICAgIHZhciBpc0V4Y2x1ZGVkID0gJHRhYi5oYXNDbGFzcyhfdGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVFeGNsdWRlZCk7XG4gICAgICAgICAgICB2YXIgJGFuY2hvciwgJHBhbmVsLCAkYWNjb3JkaW9uVGFiLCAkYWNjb3JkaW9uQW5jaG9yLCBwYW5lbFNlbGVjdG9yO1xuXG4gICAgICAgICAgICAvLyBDaGVjayBpZiB0aGUgdGFiIHNob3VsZCBiZSBleGNsdWRlZFxuICAgICAgICAgICAgaWYoIWlzRXhjbHVkZWQpIHtcblxuICAgICAgICAgICAgICAgICRhbmNob3IgPSAkKCdhJywgJHRhYik7XG4gICAgICAgICAgICAgICAgcGFuZWxTZWxlY3RvciA9ICRhbmNob3IuYXR0cignaHJlZicpO1xuICAgICAgICAgICAgICAgICRwYW5lbCA9ICQocGFuZWxTZWxlY3Rvcik7XG4gICAgICAgICAgICAgICAgJGFjY29yZGlvblRhYiA9ICQoX3RoaXMub3B0aW9ucy5hY2NvcmRpb25UYWJFbGVtZW50KS5pbnNlcnRCZWZvcmUoJHBhbmVsKTtcbiAgICAgICAgICAgICAgICAkYWNjb3JkaW9uQW5jaG9yID0gJCgnPGE+PC9hPicpLmF0dHIoJ2hyZWYnLCBwYW5lbFNlbGVjdG9yKS5odG1sKCRhbmNob3IuaHRtbCgpKS5hcHBlbmRUbygkYWNjb3JkaW9uVGFiKTtcblxuICAgICAgICAgICAgICAgIHZhciBvVGFiID0ge1xuICAgICAgICAgICAgICAgICAgICBfaWdub3JlSGFzaENoYW5nZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgIGlkOiBpZCxcbiAgICAgICAgICAgICAgICAgICAgZGlzYWJsZWQ6ICgkLmluQXJyYXkoaWQsIF90aGlzLm9wdGlvbnMuZGlzYWJsZWQpICE9PSAtMSksXG4gICAgICAgICAgICAgICAgICAgIHRhYjogJCh0aGlzKSxcbiAgICAgICAgICAgICAgICAgICAgYW5jaG9yOiAkKCdhJywgJHRhYiksXG4gICAgICAgICAgICAgICAgICAgIHBhbmVsOiAkcGFuZWwsXG4gICAgICAgICAgICAgICAgICAgIHNlbGVjdG9yOiBwYW5lbFNlbGVjdG9yLFxuICAgICAgICAgICAgICAgICAgICBhY2NvcmRpb25UYWI6ICRhY2NvcmRpb25UYWIsXG4gICAgICAgICAgICAgICAgICAgIGFjY29yZGlvbkFuY2hvcjogJGFjY29yZGlvbkFuY2hvcixcbiAgICAgICAgICAgICAgICAgICAgYWN0aXZlOiBmYWxzZVxuICAgICAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICAgICAvLyAxdXAgdGhlIElEXG4gICAgICAgICAgICAgICAgaWQrKztcbiAgICAgICAgICAgICAgICAvLyBBZGQgdG8gdGFiIGFycmF5XG4gICAgICAgICAgICAgICAgdGFicy5wdXNoKG9UYWIpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIHRhYnM7XG4gICAgfTtcblxuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBhZGRzIGNsYXNzZXMgdG8gdGhlIHRhYiBlbGVtZW50cyBiYXNlZCBvbiB0aGUgb3B0aW9uc1xuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fbG9hZENsYXNzZXMgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgZm9yICh2YXIgaT0wOyBpPHRoaXMudGFicy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgdGhpcy50YWJzW2ldLnRhYi5hZGRDbGFzcyh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZURlZmF1bHQpLmFkZENsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLnRhYik7XG4gICAgICAgICAgICB0aGlzLnRhYnNbaV0uYW5jaG9yLmFkZENsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLmFuY2hvcik7XG4gICAgICAgICAgICB0aGlzLnRhYnNbaV0ucGFuZWwuYWRkQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KS5hZGRDbGFzcyh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5wYW5lbCk7XG4gICAgICAgICAgICB0aGlzLnRhYnNbaV0uYWNjb3JkaW9uVGFiLmFkZENsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLmFjY29yZGlvblRpdGxlKTtcbiAgICAgICAgICAgIHRoaXMudGFic1tpXS5hY2NvcmRpb25BbmNob3IuYWRkQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuYW5jaG9yKTtcbiAgICAgICAgICAgIGlmKHRoaXMudGFic1tpXS5kaXNhYmxlZCkge1xuICAgICAgICAgICAgICAgIHRoaXMudGFic1tpXS50YWIucmVtb3ZlQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KS5hZGRDbGFzcyh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZURpc2FibGVkKTtcbiAgICAgICAgICAgICAgICB0aGlzLnRhYnNbaV0uYWNjb3JkaW9uVGFiLnJlbW92ZUNsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCkuYWRkQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEaXNhYmxlZCk7XG4gICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBUaGlzIGZ1bmN0aW9uIGFkZHMgZXZlbnRzIHRvIHRoZSB0YWIgZWxlbWVudHNcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuX2xvYWRFdmVudHMgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcblxuICAgICAgICAvLyBEZWZpbmUgYWN0aXZhdGUgZXZlbnQgb24gYSB0YWIgZWxlbWVudFxuICAgICAgICB2YXIgZkFjdGl2YXRlID0gZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgdmFyIGN1cnJlbnQgPSBfdGhpcy5fZ2V0Q3VycmVudFRhYigpOyAvLyBGZXRjaCBjdXJyZW50IHRhYlxuICAgICAgICAgICAgdmFyIGFjdGl2YXRlZFRhYiA9IGUuZGF0YS50YWI7XG5cbiAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgICAgICAgLy8gVHJpZ2dlciBjbGljayBldmVudCBmb3Igd2hlbmV2ZXIgYSB0YWIgaXMgY2xpY2tlZC90b3VjaGVkIGV2ZW4gaWYgdGhlIHRhYiBpcyBkaXNhYmxlZFxuICAgICAgICAgICAgYWN0aXZhdGVkVGFiLnRhYi50cmlnZ2VyKCd0YWJzLWNsaWNrJywgYWN0aXZhdGVkVGFiKTtcblxuICAgICAgICAgICAgLy8gTWFrZSBzdXJlIHRoaXMgdGFiIGlzbid0IGRpc2FibGVkXG4gICAgICAgICAgICBpZighYWN0aXZhdGVkVGFiLmRpc2FibGVkKSB7XG5cbiAgICAgICAgICAgICAgICAvLyBDaGVjayBpZiBoYXNoIGhhcyB0byBiZSBzZXQgaW4gdGhlIFVSTCBsb2NhdGlvblxuICAgICAgICAgICAgICAgIGlmKF90aGlzLm9wdGlvbnMuc2V0SGFzaCkge1xuICAgICAgICAgICAgICAgICAgICAvLyBTZXQgdGhlIGhhc2ggdXNpbmcgdGhlIGhpc3RvcnkgYXBpIGlmIGF2YWlsYWJsZSB0byB0YWNrbGUgQ2hyb21lcyByZXBhaW50IGJ1ZyBvbiBoYXNoIGNoYW5nZVxuICAgICAgICAgICAgICAgICAgICBpZihoaXN0b3J5LnB1c2hTdGF0ZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaGlzdG9yeS5wdXNoU3RhdGUobnVsbCwgbnVsbCwgd2luZG93LmxvY2F0aW9uLm9yaWdpbiArIHdpbmRvdy5sb2NhdGlvbi5wYXRobmFtZSArIHdpbmRvdy5sb2NhdGlvbi5zZWFyY2ggKyBhY3RpdmF0ZWRUYWIuc2VsZWN0b3IpO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gT3RoZXJ3aXNlIGZhbGxiYWNrIHRvIHRoZSBoYXNoIHVwZGF0ZSBmb3Igc2l0ZXMgdGhhdCBkb24ndCBzdXBwb3J0IHRoZSBoaXN0b3J5IGFwaVxuICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhhc2ggPSBhY3RpdmF0ZWRUYWIuc2VsZWN0b3I7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBlLmRhdGEudGFiLl9pZ25vcmVIYXNoQ2hhbmdlID0gdHJ1ZTtcblxuICAgICAgICAgICAgICAgIC8vIENoZWNrIGlmIHRoZSBhY3RpdmF0ZWQgdGFiIGlzbnQgdGhlIGN1cnJlbnQgb25lIG9yIGlmIGl0cyBjb2xsYXBzaWJsZS4gSWYgbm90LCBkbyBub3RoaW5nXG4gICAgICAgICAgICAgICAgaWYoY3VycmVudCAhPT0gYWN0aXZhdGVkVGFiIHx8IF90aGlzLl9pc0NvbGxhcGlzYmxlKCkpIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gVGhlIGFjdGl2YXRlZCB0YWIgaXMgZWl0aGVyIGFub3RoZXIgdGFiIG9mIHRoZSBjdXJyZW50IG9uZS4gSWYgaXQncyB0aGUgY3VycmVudCB0YWIgaXQgaXMgY29sbGFwc2libGVcbiAgICAgICAgICAgICAgICAgICAgLy8gRWl0aGVyIHdheSwgdGhlIGN1cnJlbnQgdGFiIGNhbiBiZSBjbG9zZWRcbiAgICAgICAgICAgICAgICAgICAgX3RoaXMuX2Nsb3NlVGFiKGUsIGN1cnJlbnQpO1xuXG4gICAgICAgICAgICAgICAgICAgIC8vIENoZWNrIGlmIHRoZSBhY3RpdmF0ZWQgdGFiIGlzbnQgdGhlIGN1cnJlbnQgb25lIG9yIGlmIGl0IGlzbnQgY29sbGFwc2libGVcbiAgICAgICAgICAgICAgICAgICAgaWYoY3VycmVudCAhPT0gYWN0aXZhdGVkVGFiIHx8ICFfdGhpcy5faXNDb2xsYXBpc2JsZSgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBfdGhpcy5fb3BlblRhYihlLCBhY3RpdmF0ZWRUYWIsIGZhbHNlLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcblxuICAgICAgICAvLyBMb29wIHRhYnNcbiAgICAgICAgZm9yICh2YXIgaT0wOyBpPHRoaXMudGFicy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgLy8gQWRkIGFjdGl2YXRlIGZ1bmN0aW9uIHRvIHRoZSB0YWIgYW5kIGFjY29yZGlvbiBzZWxlY3Rpb24gZWxlbWVudFxuICAgICAgICAgICAgdGhpcy50YWJzW2ldLmFuY2hvci5vbihfdGhpcy5vcHRpb25zLmV2ZW50LCB7dGFiOiBfdGhpcy50YWJzW2ldfSwgZkFjdGl2YXRlKTtcbiAgICAgICAgICAgIHRoaXMudGFic1tpXS5hY2NvcmRpb25BbmNob3Iub24oX3RoaXMub3B0aW9ucy5ldmVudCwge3RhYjogX3RoaXMudGFic1tpXX0sIGZBY3RpdmF0ZSk7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBnZXRzIHRoZSB0YWIgdGhhdCBzaG91bGQgYmUgb3BlbmVkIGF0IHN0YXJ0XG4gICAgICogQHJldHVybnMge09iamVjdH0gVGFiIG9iamVjdFxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZ2V0U3RhcnRUYWIgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgdmFyIHRhYlJlZiA9IHRoaXMuX2dldFRhYlJlZkJ5U2VsZWN0b3Iod2luZG93LmxvY2F0aW9uLmhhc2gpO1xuICAgICAgICB2YXIgc3RhcnRUYWI7XG5cbiAgICAgICAgLy8gQ2hlY2sgaWYgdGhlIHBhZ2UgaGFzIGEgaGFzaCBzZXQgdGhhdCBpcyBsaW5rZWQgdG8gYSB0YWJcbiAgICAgICAgaWYodGFiUmVmID49IDAgJiYgIXRoaXMuX2dldFRhYih0YWJSZWYpLmRpc2FibGVkKSB7XG4gICAgICAgICAgICAvLyBJZiBzbywgc2V0IHRoZSBjdXJyZW50IHRhYiB0byB0aGUgbGlua2VkIHRhYlxuICAgICAgICAgICAgc3RhcnRUYWIgPSB0aGlzLl9nZXRUYWIodGFiUmVmKTtcbiAgICAgICAgfSBlbHNlIGlmKHRoaXMub3B0aW9ucy5hY3RpdmUgPiAwICYmICF0aGlzLl9nZXRUYWIodGhpcy5vcHRpb25zLmFjdGl2ZSkuZGlzYWJsZWQpIHtcbiAgICAgICAgICAgIHN0YXJ0VGFiID0gdGhpcy5fZ2V0VGFiKHRoaXMub3B0aW9ucy5hY3RpdmUpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy8gSWYgbm90LCBqdXN0IGdldCB0aGUgZmlyc3Qgb25lXG4gICAgICAgICAgICBzdGFydFRhYiA9IHRoaXMuX2dldFRhYigwKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBzdGFydFRhYjtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBzZXRzIHRoZSBjdXJyZW50IHN0YXRlIG9mIHRoZSBwbHVnaW5cbiAgICAgKiBAcGFyYW0ge0V2ZW50fSBlIC0gVGhlIGV2ZW50IHRoYXQgdHJpZ2dlcnMgdGhlIHN0YXRlIGNoYW5nZVxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fc2V0U3RhdGUgPSBmdW5jdGlvbihlKSB7XG4gICAgICAgIHZhciAkdWwgPSAkKCd1bDpmaXJzdCcsIHRoaXMuJGVsZW1lbnQpO1xuICAgICAgICB2YXIgb2xkU3RhdGUgPSB0aGlzLnN0YXRlO1xuICAgICAgICB2YXIgc3RhcnRDb2xsYXBzZWRJc1N0YXRlID0gKHR5cGVvZiB0aGlzLm9wdGlvbnMuc3RhcnRDb2xsYXBzZWQgPT09ICdzdHJpbmcnKTtcbiAgICAgICAgdmFyIHN0YXJ0VGFiO1xuXG4gICAgICAgIC8vIFRoZSBzdGF0ZSBpcyBiYXNlZCBvbiB0aGUgdmlzaWJpbGl0eSBvZiB0aGUgdGFicyBsaXN0XG4gICAgICAgIGlmKCR1bC5pcygnOnZpc2libGUnKSl7XG4gICAgICAgICAgICAvLyBUYWIgbGlzdCBpcyB2aXNpYmxlLCBzbyB0aGUgc3RhdGUgaXMgJ3RhYnMnXG4gICAgICAgICAgICB0aGlzLnN0YXRlID0gJ3RhYnMnO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy8gVGFiIGxpc3QgaXMgaW52aXNpYmxlLCBzbyB0aGUgc3RhdGUgaXMgJ2FjY29yZGlvbidcbiAgICAgICAgICAgIHRoaXMuc3RhdGUgPSAnYWNjb3JkaW9uJztcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIElmIHRoZSBuZXcgc3RhdGUgaXMgZGlmZmVyZW50IGZyb20gdGhlIG9sZCBzdGF0ZVxuICAgICAgICBpZih0aGlzLnN0YXRlICE9PSBvbGRTdGF0ZSkge1xuICAgICAgICAgICAgLy8gSWYgc28sIHRoZSBzdGF0ZSBhY3RpdmF0ZSB0cmlnZ2VyIG11c3QgYmUgY2FsbGVkXG4gICAgICAgICAgICB0aGlzLiRlbGVtZW50LnRyaWdnZXIoJ3RhYnMtYWN0aXZhdGUtc3RhdGUnLCB7b2xkU3RhdGU6IG9sZFN0YXRlLCBuZXdTdGF0ZTogdGhpcy5zdGF0ZX0pO1xuXG4gICAgICAgICAgICAvLyBDaGVjayBpZiB0aGUgc3RhdGUgc3dpdGNoIHNob3VsZCBvcGVuIGEgdGFiXG4gICAgICAgICAgICBpZihvbGRTdGF0ZSAmJiBzdGFydENvbGxhcHNlZElzU3RhdGUgJiYgdGhpcy5vcHRpb25zLnN0YXJ0Q29sbGFwc2VkICE9PSB0aGlzLnN0YXRlICYmIHRoaXMuX2dldEN1cnJlbnRUYWIoKSA9PT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICAgICAgLy8gR2V0IGluaXRpYWwgdGFiXG4gICAgICAgICAgICAgICAgc3RhcnRUYWIgPSB0aGlzLl9nZXRTdGFydFRhYihlKTtcbiAgICAgICAgICAgICAgICAvLyBPcGVuIHRoZSBpbml0aWFsIHRhYlxuICAgICAgICAgICAgICAgIHRoaXMuX29wZW5UYWIoZSwgc3RhcnRUYWIpOyAvLyBPcGVuIGZpcnN0IHRhYlxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gb3BlbnMgYSB0YWJcbiAgICAgKiBAcGFyYW0ge0V2ZW50fSBlIC0gVGhlIGV2ZW50IHRoYXQgdHJpZ2dlcnMgdGhlIHRhYiBvcGVuaW5nXG4gICAgICogQHBhcmFtIHtPYmplY3R9IG9UYWIgLSBUaGUgdGFiIG9iamVjdCB0aGF0IHNob3VsZCBiZSBvcGVuZWRcbiAgICAgKiBAcGFyYW0ge0Jvb2xlYW59IGNsb3NlQ3VycmVudCAtIERlZmluZXMgaWYgdGhlIGN1cnJlbnQgdGFiIHNob3VsZCBiZSBjbG9zZWRcbiAgICAgKiBAcGFyYW0ge0Jvb2xlYW59IHN0b3BSb3RhdGlvbiAtIERlZmluZXMgaWYgdGhlIHRhYiByb3RhdGlvbiBsb29wIHNob3VsZCBiZSBzdG9wcGVkXG4gICAgICovXG4gICAgUmVzcG9uc2l2ZVRhYnMucHJvdG90eXBlLl9vcGVuVGFiID0gZnVuY3Rpb24oZSwgb1RhYiwgY2xvc2VDdXJyZW50LCBzdG9wUm90YXRpb24pIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHNjcm9sbE9mZnNldDtcblxuICAgICAgICAvLyBDaGVjayBpZiB0aGUgY3VycmVudCB0YWIgaGFzIHRvIGJlIGNsb3NlZFxuICAgICAgICBpZihjbG9zZUN1cnJlbnQpIHtcbiAgICAgICAgICAgIHRoaXMuX2Nsb3NlVGFiKGUsIHRoaXMuX2dldEN1cnJlbnRUYWIoKSk7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDaGVjayBpZiB0aGUgcm90YXRpb24gaGFzIHRvIGJlIHN0b3BwZWQgd2hlbiBhY3RpdmF0ZWRcbiAgICAgICAgaWYoc3RvcFJvdGF0aW9uICYmIHRoaXMucm90YXRlSW50ZXJ2YWwgPiAwKSB7XG4gICAgICAgICAgICB0aGlzLnN0b3BSb3RhdGlvbigpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gU2V0IHRoaXMgdGFiIHRvIGFjdGl2ZVxuICAgICAgICBvVGFiLmFjdGl2ZSA9IHRydWU7XG4gICAgICAgIC8vIFNldCBhY3RpdmUgY2xhc3NlcyB0byB0aGUgdGFiIGJ1dHRvbiBhbmQgYWNjb3JkaW9uIHRhYiBidXR0b25cbiAgICAgICAgb1RhYi50YWIucmVtb3ZlQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCkuYWRkQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlQWN0aXZlKTtcbiAgICAgICAgb1RhYi5hY2NvcmRpb25UYWIucmVtb3ZlQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCkuYWRkQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlQWN0aXZlKTtcblxuICAgICAgICAvLyBSdW4gcGFuZWwgdHJhbnNpdG9uXG4gICAgICAgIF90aGlzLl9kb1RyYW5zaXRpb24ob1RhYi5wYW5lbCwgX3RoaXMub3B0aW9ucy5hbmltYXRpb24sICdvcGVuJywgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICB2YXIgc2Nyb2xsT25Mb2FkID0gKGUudHlwZSAhPT0gJ3RhYnMtbG9hZCcgfHwgX3RoaXMub3B0aW9ucy5zY3JvbGxUb0FjY29yZGlvbk9uTG9hZCk7XG5cbiAgICAgICAgICAgIC8vIFdoZW4gZmluaXNoZWQsIHNldCBhY3RpdmUgY2xhc3MgdG8gdGhlIHBhbmVsXG4gICAgICAgICAgICBvVGFiLnBhbmVsLnJlbW92ZUNsYXNzKF90aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZURlZmF1bHQpLmFkZENsYXNzKF90aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZUFjdGl2ZSk7XG5cbiAgICAgICAgICAgIC8vIEFuZCBpZiBlbmFibGVkIGFuZCBzdGF0ZSBpcyBhY2NvcmRpb24sIHNjcm9sbCB0byB0aGUgYWNjb3JkaW9uIHRhYlxuICAgICAgICAgICAgaWYoX3RoaXMuZ2V0U3RhdGUoKSA9PT0gJ2FjY29yZGlvbicgJiYgX3RoaXMub3B0aW9ucy5zY3JvbGxUb0FjY29yZGlvbiAmJiAoIV90aGlzLl9pc0luVmlldyhvVGFiLmFjY29yZGlvblRhYikgfHwgX3RoaXMub3B0aW9ucy5hbmltYXRpb24gIT09ICdkZWZhdWx0JykgJiYgc2Nyb2xsT25Mb2FkKSB7XG5cbiAgICAgICAgICAgICAgICAvLyBBZGQgb2Zmc2V0IGVsZW1lbnQncyBoZWlnaHQgdG8gc2Nyb2xsIHBvc2l0aW9uXG4gICAgICAgICAgICAgICAgc2Nyb2xsT2Zmc2V0ID0gb1RhYi5hY2NvcmRpb25UYWIub2Zmc2V0KCkudG9wIC0gX3RoaXMub3B0aW9ucy5zY3JvbGxUb0FjY29yZGlvbk9mZnNldDtcblxuICAgICAgICAgICAgICAgIC8vIENoZWNrIGlmIHRoZSBhbmltYXRpb24gb3B0aW9uIGlzIGVuYWJsZWQsIGFuZCBpZiB0aGUgZHVyYXRpb24gaXNuJ3QgMFxuICAgICAgICAgICAgICAgIGlmKF90aGlzLm9wdGlvbnMuYW5pbWF0aW9uICE9PSAnZGVmYXVsdCcgJiYgX3RoaXMub3B0aW9ucy5kdXJhdGlvbiA+IDApIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gSWYgc28sIHNldCBzY3JvbGxUb3Agd2l0aCBhbmltYXRlIGFuZCB1c2UgdGhlICdhbmltYXRpb24nIGR1cmF0aW9uXG4gICAgICAgICAgICAgICAgICAgICQoJ2h0bWwsIGJvZHknKS5hbmltYXRlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNjcm9sbFRvcDogc2Nyb2xsT2Zmc2V0XG4gICAgICAgICAgICAgICAgICAgIH0sIF90aGlzLm9wdGlvbnMuZHVyYXRpb24pO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIC8vICBJZiBub3QsIGp1c3Qgc2V0IHNjcm9sbFRvcFxuICAgICAgICAgICAgICAgICAgICAkKCdodG1sLCBib2R5Jykuc2Nyb2xsVG9wKHNjcm9sbE9mZnNldCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICB0aGlzLiRlbGVtZW50LnRyaWdnZXIoJ3RhYnMtYWN0aXZhdGUnLCBvVGFiKTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBjbG9zZXMgYSB0YWJcbiAgICAgKiBAcGFyYW0ge0V2ZW50fSBlIC0gVGhlIGV2ZW50IHRoYXQgaXMgdHJpZ2dlcmVkIHdoZW4gYSB0YWIgaXMgY2xvc2VkXG4gICAgICogQHBhcmFtIHtPYmplY3R9IG9UYWIgLSBUaGUgdGFiIG9iamVjdCB0aGF0IHNob3VsZCBiZSBjbG9zZWRcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuX2Nsb3NlVGFiID0gZnVuY3Rpb24oZSwgb1RhYikge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgZG9RdWV1ZU9uU3RhdGUgPSB0eXBlb2YgX3RoaXMub3B0aW9ucy5hbmltYXRpb25RdWV1ZSA9PT0gJ3N0cmluZyc7XG4gICAgICAgIHZhciBkb1F1ZXVlO1xuXG4gICAgICAgIGlmKG9UYWIgIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgaWYoZG9RdWV1ZU9uU3RhdGUgJiYgX3RoaXMuZ2V0U3RhdGUoKSA9PT0gX3RoaXMub3B0aW9ucy5hbmltYXRpb25RdWV1ZSkge1xuICAgICAgICAgICAgICAgIGRvUXVldWUgPSB0cnVlO1xuICAgICAgICAgICAgfSBlbHNlIGlmKGRvUXVldWVPblN0YXRlKSB7XG4gICAgICAgICAgICAgICAgZG9RdWV1ZSA9IGZhbHNlO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBkb1F1ZXVlID0gX3RoaXMub3B0aW9ucy5hbmltYXRpb25RdWV1ZTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gRGVhY3RpdmF0ZSB0YWJcbiAgICAgICAgICAgIG9UYWIuYWN0aXZlID0gZmFsc2U7XG4gICAgICAgICAgICAvLyBTZXQgZGVmYXVsdCBjbGFzcyB0byB0aGUgdGFiIGJ1dHRvblxuICAgICAgICAgICAgb1RhYi50YWIucmVtb3ZlQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlQWN0aXZlKS5hZGRDbGFzcyhfdGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KTtcblxuICAgICAgICAgICAgLy8gUnVuIHBhbmVsIHRyYW5zaXRpb25cbiAgICAgICAgICAgIF90aGlzLl9kb1RyYW5zaXRpb24ob1RhYi5wYW5lbCwgX3RoaXMub3B0aW9ucy5hbmltYXRpb24sICdjbG9zZScsIGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgIC8vIFNldCBkZWZhdWx0IGNsYXNzIHRvIHRoZSBhY2NvcmRpb24gdGFiIGJ1dHRvbiBhbmQgdGFiIHBhbmVsXG4gICAgICAgICAgICAgICAgb1RhYi5hY2NvcmRpb25UYWIucmVtb3ZlQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlQWN0aXZlKS5hZGRDbGFzcyhfdGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KTtcbiAgICAgICAgICAgICAgICBvVGFiLnBhbmVsLnJlbW92ZUNsYXNzKF90aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZUFjdGl2ZSkuYWRkQ2xhc3MoX3RoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCk7XG4gICAgICAgICAgICB9LCAhZG9RdWV1ZSk7XG5cbiAgICAgICAgICAgIHRoaXMuJGVsZW1lbnQudHJpZ2dlcigndGFicy1kZWFjdGl2YXRlJywgb1RhYik7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBydW5zIGFuIGVmZmVjdCBvbiBhIHBhbmVsXG4gICAgICogQHBhcmFtIHtFbGVtZW50fSBwYW5lbCAtIFRoZSBIVE1MIGVsZW1lbnQgb2YgdGhlIHRhYiBwYW5lbFxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSBtZXRob2QgLSBUaGUgdHJhbnNpdGlvbiBtZXRob2QgcmVmZXJlbmNlXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IHN0YXRlIC0gVGhlIHN0YXRlIChvcGVuL2Nsb3NlZCkgdGhhdCB0aGUgcGFuZWwgc2hvdWxkIHRyYW5zaXRpb24gdG9cbiAgICAgKiBAcGFyYW0ge0Z1bmN0aW9ufSBjYWxsYmFjayAtIFRoZSBjYWxsYmFjayBmdW5jdGlvbiB0aGF0IGlzIGNhbGxlZCBhZnRlciB0aGUgdHJhbnNpdGlvblxuICAgICAqIEBwYXJhbSB7Qm9vbGVhbn0gZGVxdWV1ZSAtIERlZmluZXMgaWYgdGhlIGV2ZW50IHF1ZXVlIHNob3VsZCBiZSBkZXF1ZXVlZCBhZnRlciB0aGUgdHJhbnNpdGlvblxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZG9UcmFuc2l0aW9uID0gZnVuY3Rpb24ocGFuZWwsIG1ldGhvZCwgc3RhdGUsIGNhbGxiYWNrLCBkZXF1ZXVlKSB7XG4gICAgICAgIHZhciBlZmZlY3Q7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG5cbiAgICAgICAgLy8gR2V0IGVmZmVjdCBiYXNlZCBvbiBtZXRob2RcbiAgICAgICAgc3dpdGNoKG1ldGhvZCkge1xuICAgICAgICAgICAgY2FzZSAnc2xpZGUnOlxuICAgICAgICAgICAgICAgIGVmZmVjdCA9IChzdGF0ZSA9PT0gJ29wZW4nKSA/ICdzbGlkZURvd24nIDogJ3NsaWRlVXAnO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAnZmFkZSc6XG4gICAgICAgICAgICAgICAgZWZmZWN0ID0gKHN0YXRlID09PSAnb3BlbicpID8gJ2ZhZGVJbicgOiAnZmFkZU91dCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgIGVmZmVjdCA9IChzdGF0ZSA9PT0gJ29wZW4nKSA/ICdzaG93JyA6ICdoaWRlJztcbiAgICAgICAgICAgICAgICAvLyBXaGVuIGRlZmF1bHQgaXMgdXNlZCwgc2V0IHRoZSBkdXJhdGlvbiB0byAwXG4gICAgICAgICAgICAgICAgX3RoaXMub3B0aW9ucy5kdXJhdGlvbiA9IDA7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBBZGQgdGhlIHRyYW5zaXRpb24gdG8gYSBjdXN0b20gcXVldWVcbiAgICAgICAgdGhpcy4kcXVldWUucXVldWUoJ3Jlc3BvbnNpdmUtdGFicycsZnVuY3Rpb24obmV4dCl7XG4gICAgICAgICAgICAvLyBSdW4gdGhlIHRyYW5zaXRpb24gb24gdGhlIHBhbmVsXG4gICAgICAgICAgICBwYW5lbFtlZmZlY3RdKHtcbiAgICAgICAgICAgICAgICBkdXJhdGlvbjogX3RoaXMub3B0aW9ucy5kdXJhdGlvbixcbiAgICAgICAgICAgICAgICBjb21wbGV0ZTogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIENhbGwgdGhlIGNhbGxiYWNrIGZ1bmN0aW9uXG4gICAgICAgICAgICAgICAgICAgIGNhbGxiYWNrLmNhbGwocGFuZWwsIG1ldGhvZCwgc3RhdGUpO1xuICAgICAgICAgICAgICAgICAgICAvLyBSdW4gdGhlIG5leHQgZnVuY3Rpb24gaW4gdGhlIHF1ZXVlXG4gICAgICAgICAgICAgICAgICAgIG5leHQoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gV2hlbiB0aGUgcGFuZWwgaXMgb3BlbmVuZCwgZGVxdWV1ZSBldmVyeXRoaW5nIHNvIHRoZSBhbmltYXRpb24gc3RhcnRzXG4gICAgICAgIGlmKHN0YXRlID09PSAnb3BlbicgfHwgZGVxdWV1ZSkge1xuICAgICAgICAgICAgdGhpcy4kcXVldWUuZGVxdWV1ZSgncmVzcG9uc2l2ZS10YWJzJyk7XG4gICAgICAgIH1cblxuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBUaGlzIGZ1bmN0aW9uIHJldHVybnMgdGhlIGNvbGxhcHNpYmlsaXR5IG9mIHRoZSB0YWIgaW4gdGhpcyBzdGF0ZVxuICAgICAqIEByZXR1cm5zIHtCb29sZWFufSBUaGUgY29sbGFwc2liaWxpdHkgb2YgdGhlIHRhYlxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5faXNDb2xsYXBpc2JsZSA9IGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gKHR5cGVvZiB0aGlzLm9wdGlvbnMuY29sbGFwc2libGUgPT09ICdib29sZWFuJyAmJiB0aGlzLm9wdGlvbnMuY29sbGFwc2libGUpIHx8ICh0eXBlb2YgdGhpcy5vcHRpb25zLmNvbGxhcHNpYmxlID09PSAnc3RyaW5nJyAmJiB0aGlzLm9wdGlvbnMuY29sbGFwc2libGUgPT09IHRoaXMuZ2V0U3RhdGUoKSk7XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gcmV0dXJucyBhIHRhYiBieSBudW1lcmljIHJlZmVyZW5jZVxuICAgICAqIEBwYXJhbSB7SW50ZWdlcn0gbnVtUmVmIC0gTnVtZXJpYyB0YWIgcmVmZXJlbmNlXG4gICAgICogQHJldHVybnMge09iamVjdH0gVGFiIG9iamVjdFxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZ2V0VGFiID0gZnVuY3Rpb24obnVtUmVmKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnRhYnNbbnVtUmVmXTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiByZXR1cm5zIHRoZSBudW1lcmljIHRhYiByZWZlcmVuY2UgYmFzZWQgb24gYSBoYXNoIHNlbGVjdG9yXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IHNlbGVjdG9yIC0gSGFzaCBzZWxlY3RvclxuICAgICAqIEByZXR1cm5zIHtJbnRlZ2VyfSBOdW1lcmljIHRhYiByZWZlcmVuY2VcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuX2dldFRhYlJlZkJ5U2VsZWN0b3IgPSBmdW5jdGlvbihzZWxlY3Rvcikge1xuICAgICAgICAvLyBMb29wIGFsbCB0YWJzXG4gICAgICAgIGZvciAodmFyIGk9MDsgaTx0aGlzLnRhYnMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgIC8vIENoZWNrIGlmIHRoZSBoYXNoIHNlbGVjdG9yIGlzIGVxdWFsIHRvIHRoZSB0YWIgc2VsZWN0b3JcbiAgICAgICAgICAgIGlmKHRoaXMudGFic1tpXS5zZWxlY3RvciA9PT0gc2VsZWN0b3IpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gaTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICAvLyBJZiBub25lIGlzIGZvdW5kIHJldHVybiBhIG5lZ2F0aXZlIGluZGV4XG4gICAgICAgIHJldHVybiAtMTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiByZXR1cm5zIHRoZSBjdXJyZW50IHRhYiBlbGVtZW50XG4gICAgICogQHJldHVybnMge09iamVjdH0gQ3VycmVudCB0YWIgZWxlbWVudFxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZ2V0Q3VycmVudFRhYiA9IGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5fZ2V0VGFiKHRoaXMuX2dldEN1cnJlbnRUYWJSZWYoKSk7XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gcmV0dXJucyB0aGUgbmV4dCB0YWIncyBudW1lcmljIHJlZmVyZW5jZVxuICAgICAqIEBwYXJhbSB7SW50ZWdlcn0gY3VycmVudFRhYlJlZiAtIEN1cnJlbnQgbnVtZXJpYyB0YWIgcmVmZXJlbmNlXG4gICAgICogQHJldHVybnMge0ludGVnZXJ9IE51bWVyaWMgdGFiIHJlZmVyZW5jZVxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZ2V0TmV4dFRhYlJlZiA9IGZ1bmN0aW9uKGN1cnJlbnRUYWJSZWYpIHtcbiAgICAgICAgdmFyIHRhYlJlZiA9IChjdXJyZW50VGFiUmVmIHx8IHRoaXMuX2dldEN1cnJlbnRUYWJSZWYoKSk7XG4gICAgICAgIHZhciBuZXh0VGFiUmVmID0gKHRhYlJlZiA9PT0gdGhpcy50YWJzLmxlbmd0aCAtIDEpID8gMCA6IHRhYlJlZiArIDE7XG4gICAgICAgIHJldHVybiAodGhpcy5fZ2V0VGFiKG5leHRUYWJSZWYpLmRpc2FibGVkKSA/IHRoaXMuX2dldE5leHRUYWJSZWYobmV4dFRhYlJlZikgOiBuZXh0VGFiUmVmO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBUaGlzIGZ1bmN0aW9uIHJldHVybnMgdGhlIHByZXZpb3VzIHRhYidzIG51bWVyaWMgcmVmZXJlbmNlXG4gICAgICogQHJldHVybnMge0ludGVnZXJ9IE51bWVyaWMgdGFiIHJlZmVyZW5jZVxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5fZ2V0UHJldmlvdXNUYWJSZWYgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgcmV0dXJuICh0aGlzLl9nZXRDdXJyZW50VGFiUmVmKCkgPT09IDApID8gdGhpcy50YWJzLmxlbmd0aCAtIDEgOiB0aGlzLl9nZXRDdXJyZW50VGFiUmVmKCkgLSAxO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBUaGlzIGZ1bmN0aW9uIHJldHVybnMgdGhlIGN1cnJlbnQgdGFiJ3MgbnVtZXJpYyByZWZlcmVuY2VcbiAgICAgKiBAcmV0dXJucyB7SW50ZWdlcn0gTnVtZXJpYyB0YWIgcmVmZXJlbmNlXG4gICAgICovXG4gICAgUmVzcG9uc2l2ZVRhYnMucHJvdG90eXBlLl9nZXRDdXJyZW50VGFiUmVmID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIC8vIExvb3AgYWxsIHRhYnNcbiAgICAgICAgZm9yICh2YXIgaT0wOyBpPHRoaXMudGFicy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgLy8gSWYgdGhpcyB0YWIgaXMgYWN0aXZlLCByZXR1cm4gaXRcbiAgICAgICAgICAgIGlmKHRoaXMudGFic1tpXS5hY3RpdmUpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gaTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICAvLyBObyB0YWJzIGhhdmUgYmVlbiBmb3VuZCwgcmV0dXJuIG5lZ2F0aXZlIGluZGV4XG4gICAgICAgIHJldHVybiAtMTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBnZXRzIHRoZSB0YWxsZXN0IHRhYiBhbmQgYXBwbGllZCB0aGUgaGVpZ2h0IHRvIGFsbCB0YWJzXG4gICAgICovXG4gICAgUmVzcG9uc2l2ZVRhYnMucHJvdG90eXBlLl9lcXVhbGlzZUhlaWdodHMgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgdmFyIG1heEhlaWdodCA9IDA7XG5cbiAgICAgICAgJC5lYWNoKCQubWFwKHRoaXMudGFicywgZnVuY3Rpb24odGFiKSB7XG4gICAgICAgICAgICBtYXhIZWlnaHQgPSBNYXRoLm1heChtYXhIZWlnaHQsIHRhYi5wYW5lbC5jc3MoJ21pbkhlaWdodCcsICcnKS5oZWlnaHQoKSk7XG4gICAgICAgICAgICByZXR1cm4gdGFiLnBhbmVsO1xuICAgICAgICB9KSwgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICB0aGlzLmNzcygnbWluSGVpZ2h0JywgbWF4SGVpZ2h0KTtcbiAgICAgICAgfSk7XG4gICAgfTtcblxuICAgIC8vXG4gICAgLy8gSEVMUEVSIEZVTkNUSU9OU1xuICAgIC8vXG5cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuX2lzSW5WaWV3ID0gZnVuY3Rpb24oJGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIGRvY1ZpZXdUb3AgPSAkKHdpbmRvdykuc2Nyb2xsVG9wKCksXG4gICAgICAgICAgICBkb2NWaWV3Qm90dG9tID0gZG9jVmlld1RvcCArICQod2luZG93KS5oZWlnaHQoKSxcbiAgICAgICAgICAgIGVsZW1Ub3AgPSAkZWxlbWVudC5vZmZzZXQoKS50b3AsXG4gICAgICAgICAgICBlbGVtQm90dG9tID0gZWxlbVRvcCArICRlbGVtZW50LmhlaWdodCgpO1xuICAgICAgICByZXR1cm4gKChlbGVtQm90dG9tIDw9IGRvY1ZpZXdCb3R0b20pICYmIChlbGVtVG9wID49IGRvY1ZpZXdUb3ApKTtcbiAgICB9O1xuXG4gICAgLy9cbiAgICAvLyBQVUJMSUMgRlVOQ1RJT05TXG4gICAgLy9cblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gYWN0aXZhdGVzIGEgdGFiXG4gICAgICogQHBhcmFtIHtJbnRlZ2VyfSB0YWJSZWYgLSBOdW1lcmljIHRhYiByZWZlcmVuY2VcbiAgICAgKiBAcGFyYW0ge0Jvb2xlYW59IHN0b3BSb3RhdGlvbiAtIERlZmluZXMgaWYgdGhlIHRhYiByb3RhdGlvbiBzaG91bGQgc3RvcCBhZnRlciBhY3RpdmF0aW9uXG4gICAgICovXG4gICAgUmVzcG9uc2l2ZVRhYnMucHJvdG90eXBlLmFjdGl2YXRlID0gZnVuY3Rpb24odGFiUmVmLCBzdG9wUm90YXRpb24pIHtcbiAgICAgICAgdmFyIGUgPSBqUXVlcnkuRXZlbnQoJ3RhYnMtYWN0aXZhdGUnKTtcbiAgICAgICAgdmFyIG9UYWIgPSB0aGlzLl9nZXRUYWIodGFiUmVmKTtcbiAgICAgICAgaWYoIW9UYWIuZGlzYWJsZWQpIHtcbiAgICAgICAgICAgIHRoaXMuX29wZW5UYWIoZSwgb1RhYiwgdHJ1ZSwgc3RvcFJvdGF0aW9uIHx8IHRydWUpO1xuICAgICAgICB9XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIFRoaXMgZnVuY3Rpb24gZGVhY3RpdmF0ZXMgYSB0YWJcbiAgICAgKiBAcGFyYW0ge0ludGVnZXJ9IHRhYlJlZiAtIE51bWVyaWMgdGFiIHJlZmVyZW5jZVxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5kZWFjdGl2YXRlID0gZnVuY3Rpb24odGFiUmVmKSB7XG4gICAgICAgIHZhciBlID0galF1ZXJ5LkV2ZW50KCd0YWJzLWRlY3RpdmF0ZScpO1xuICAgICAgICB2YXIgb1RhYiA9IHRoaXMuX2dldFRhYih0YWJSZWYpO1xuICAgICAgICBpZighb1RhYi5kaXNhYmxlZCkge1xuICAgICAgICAgICAgdGhpcy5fY2xvc2VUYWIoZSwgb1RhYik7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBlbmFibGVzIGEgdGFiXG4gICAgICogQHBhcmFtIHtJbnRlZ2VyfSB0YWJSZWYgLSBOdW1lcmljIHRhYiByZWZlcmVuY2VcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuZW5hYmxlID0gZnVuY3Rpb24odGFiUmVmKSB7XG4gICAgICAgIHZhciBvVGFiID0gdGhpcy5fZ2V0VGFiKHRhYlJlZik7XG4gICAgICAgIGlmKG9UYWIpe1xuICAgICAgICAgICAgb1RhYi5kaXNhYmxlZCA9IGZhbHNlO1xuICAgICAgICAgICAgb1RhYi50YWIuYWRkQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KS5yZW1vdmVDbGFzcyh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZURpc2FibGVkKTtcbiAgICAgICAgICAgIG9UYWIuYWNjb3JkaW9uVGFiLmFkZENsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCkucmVtb3ZlQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEaXNhYmxlZCk7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBkaXNhYmxlIGEgdGFiXG4gICAgICogQHBhcmFtIHtJbnRlZ2VyfSB0YWJSZWYgLSBOdW1lcmljIHRhYiByZWZlcmVuY2VcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuZGlzYWJsZSA9IGZ1bmN0aW9uKHRhYlJlZikge1xuICAgICAgICB2YXIgb1RhYiA9IHRoaXMuX2dldFRhYih0YWJSZWYpO1xuICAgICAgICBpZihvVGFiKXtcbiAgICAgICAgICAgIG9UYWIuZGlzYWJsZWQgPSB0cnVlO1xuICAgICAgICAgICAgb1RhYi50YWIucmVtb3ZlQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEZWZhdWx0KS5hZGRDbGFzcyh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5zdGF0ZURpc2FibGVkKTtcbiAgICAgICAgICAgIG9UYWIuYWNjb3JkaW9uVGFiLnJlbW92ZUNsYXNzKHRoaXMub3B0aW9ucy5jbGFzc2VzLnN0YXRlRGVmYXVsdCkuYWRkQ2xhc3ModGhpcy5vcHRpb25zLmNsYXNzZXMuc3RhdGVEaXNhYmxlZCk7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBnZXRzIHRoZSBjdXJyZW50IHN0YXRlIG9mIHRoZSBwbHVnaW5cbiAgICAgKiBAcmV0dXJucyB7U3RyaW5nfSBTdGF0ZSBvZiB0aGUgcGx1Z2luXG4gICAgICovXG4gICAgUmVzcG9uc2l2ZVRhYnMucHJvdG90eXBlLmdldFN0YXRlID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnN0YXRlO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBUaGlzIGZ1bmN0aW9uIHN0YXJ0cyB0aGUgcm90YXRpb24gb2YgdGhlIHRhYnNcbiAgICAgKiBAcGFyYW0ge0ludGVnZXJ9IHNwZWVkIC0gVGhlIHNwZWVkIG9mIHRoZSByb3RhdGlvblxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5zdGFydFJvdGF0aW9uID0gZnVuY3Rpb24oc3BlZWQpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgLy8gTWFrZSBzdXJlIG5vdCBhbGwgdGFicyBhcmUgZGlzYWJsZWRcbiAgICAgICAgaWYodGhpcy50YWJzLmxlbmd0aCA+IHRoaXMub3B0aW9ucy5kaXNhYmxlZC5sZW5ndGgpIHtcbiAgICAgICAgICAgIHRoaXMucm90YXRlSW50ZXJ2YWwgPSBzZXRJbnRlcnZhbChmdW5jdGlvbigpe1xuICAgICAgICAgICAgICAgIHZhciBlID0galF1ZXJ5LkV2ZW50KCdyb3RhdGUnKTtcbiAgICAgICAgICAgICAgICBfdGhpcy5fb3BlblRhYihlLCBfdGhpcy5fZ2V0VGFiKF90aGlzLl9nZXROZXh0VGFiUmVmKCkpLCB0cnVlKTtcbiAgICAgICAgICAgIH0sIHNwZWVkIHx8ICgoJC5pc051bWVyaWMoX3RoaXMub3B0aW9ucy5yb3RhdGUpKSA/IF90aGlzLm9wdGlvbnMucm90YXRlIDogNDAwMCkgKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihcIlJvdGF0aW9uIGlzIG5vdCBwb3NzaWJsZSBpZiBhbGwgdGFicyBhcmUgZGlzYWJsZWRcIik7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBzdG9wcyB0aGUgcm90YXRpb24gb2YgdGhlIHRhYnNcbiAgICAgKi9cbiAgICBSZXNwb25zaXZlVGFicy5wcm90b3R5cGUuc3RvcFJvdGF0aW9uID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIHdpbmRvdy5jbGVhckludGVydmFsKHRoaXMucm90YXRlSW50ZXJ2YWwpO1xuICAgICAgICB0aGlzLnJvdGF0ZUludGVydmFsID0gMDtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogVGhpcyBmdW5jdGlvbiBjYW4gYmUgdXNlZCB0byBnZXQvc2V0IG9wdGlvbnNcbiAgICAgKiBAcmV0dXJuIHthbnl9IE9wdGlvbiB2YWx1ZVxuICAgICAqL1xuICAgIFJlc3BvbnNpdmVUYWJzLnByb3RvdHlwZS5vcHRpb24gPSBmdW5jdGlvbihrZXksIHZhbHVlKSB7XG4gICAgICAgIGlmKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLm9wdGlvbnNba2V5XSA9IHZhbHVlO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB0aGlzLm9wdGlvbnNba2V5XTtcbiAgICB9O1xuXG4gICAgLyoqIGpRdWVyeSB3cmFwcGVyICovXG4gICAgJC5mbi5yZXNwb25zaXZlVGFicyA9IGZ1bmN0aW9uICggb3B0aW9ucyApIHtcbiAgICAgICAgdmFyIGFyZ3MgPSBhcmd1bWVudHM7XG4gICAgICAgIHZhciBpbnN0YW5jZTtcblxuICAgICAgICBpZiAob3B0aW9ucyA9PT0gdW5kZWZpbmVkIHx8IHR5cGVvZiBvcHRpb25zID09PSAnb2JqZWN0Jykge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgaWYgKCEkLmRhdGEodGhpcywgJ3Jlc3BvbnNpdmV0YWJzJykpIHtcbiAgICAgICAgICAgICAgICAgICAgJC5kYXRhKHRoaXMsICdyZXNwb25zaXZldGFicycsIG5ldyBSZXNwb25zaXZlVGFicyggdGhpcywgb3B0aW9ucyApKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSBlbHNlIGlmICh0eXBlb2Ygb3B0aW9ucyA9PT0gJ3N0cmluZycgJiYgb3B0aW9uc1swXSAhPT0gJ18nICYmIG9wdGlvbnMgIT09ICdpbml0Jykge1xuICAgICAgICAgICAgaW5zdGFuY2UgPSAkLmRhdGEodGhpc1swXSwgJ3Jlc3BvbnNpdmV0YWJzJyk7XG5cbiAgICAgICAgICAgIC8vIEFsbG93IGluc3RhbmNlcyB0byBiZSBkZXN0cm95ZWQgdmlhIHRoZSAnZGVzdHJveScgbWV0aG9kXG4gICAgICAgICAgICBpZiAob3B0aW9ucyA9PT0gJ2Rlc3Ryb3knKSB7XG4gICAgICAgICAgICAgICAgLy8gVE9ETzogZGVzdHJveSBpbnN0YW5jZSBjbGFzc2VzLCBldGNcbiAgICAgICAgICAgICAgICAkLmRhdGEodGhpcywgJ3Jlc3BvbnNpdmV0YWJzJywgbnVsbCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmIChpbnN0YW5jZSBpbnN0YW5jZW9mIFJlc3BvbnNpdmVUYWJzICYmIHR5cGVvZiBpbnN0YW5jZVtvcHRpb25zXSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIHJldHVybiBpbnN0YW5jZVtvcHRpb25zXS5hcHBseSggaW5zdGFuY2UsIEFycmF5LnByb3RvdHlwZS5zbGljZS5jYWxsKCBhcmdzLCAxICkgKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuXG59KGpRdWVyeSwgd2luZG93KSk7XG4iLCIvKipcbiAqIEBmaWxlXG4gKiBJbml0aWFsaXplIFJlc3BvbnNpdmUgVGFicyBzY3JpcHRzLlxuICovXG5cbmltcG9ydCAncmVzcG9uc2l2ZS10YWJzJ1xuXG4oJCA9PiB7XG5cblx0JCgnLnItdGFicy1jb250YWluZXInKS5yZXNwb25zaXZlVGFicyh7XG5cdCAgc3RhcnRDb2xsYXBzZWQ6IGZhbHNlLFxuXHQgIGFuaW1hdGlvbjogJ3NsaWRlJyxcblx0ICBkdXJhdGlvbjogMjAwXG5cdH0pXG5cbn0pKGpRdWVyeSkiXSwibmFtZXMiOlsidGhpcyJdLCJtYXBwaW5ncyI6Ijs7O0FBQUMsQ0FBQyxXQUFXLENBQUMsRUFBRSxNQUFNLEVBQUUsU0FBUyxHQUFHOzs7SUFHaEMsSUFBSSxRQUFRLEdBQUc7UUFDWCxNQUFNLEVBQUUsSUFBSTtRQUNaLEtBQUssRUFBRSxPQUFPO1FBQ2QsUUFBUSxFQUFFLEVBQUU7UUFDWixXQUFXLEVBQUUsV0FBVztRQUN4QixjQUFjLEVBQUUsS0FBSztRQUNyQixNQUFNLEVBQUUsS0FBSztRQUNiLE9BQU8sRUFBRSxLQUFLO1FBQ2QsU0FBUyxFQUFFLFNBQVM7UUFDcEIsY0FBYyxFQUFFLEtBQUs7UUFDckIsUUFBUSxFQUFFLEdBQUc7UUFDYixXQUFXLEVBQUUsSUFBSTtRQUNqQixpQkFBaUIsRUFBRSxL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