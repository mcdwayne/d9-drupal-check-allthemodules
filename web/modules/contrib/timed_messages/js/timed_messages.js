'use strict';

/*global Drupal, jQuery */
/**
 * @file
 *   enhances the messages with extra js
 * @version 7.x-1.x
 */

(function ($, undefined) {
    // jq plugin
    $.fn.timedMessage = function (options) {
        return this.each(function () {
            var settings = $.extend(
                {
                    // classes
                    pluginClass:      'timed-message',
                    outerWrapClass:   'tm-outer-wrap',
                    innerWrapClass:   'tm-inner-wrap',
                    showClass:        'tm-showlink',
                    processWrapClass: 'tm-progress-wrap',
                    processBarClass:  'tm-progress-bar',
                    inProgressClass:  'tm-in-progress',
                    openClass:        'tm-is-open',
                    closeClass:       'tm-is-closed',
                    dateClass:        'tm-date',

                    // text
                    showTxt:          Drupal.t('toggle hidden message display'),
                    // text by type
                    errorTxt:         Drupal.t('toggle hidden error message display'),
                    warningTxt:       Drupal.t('toggle hidden warning message display'),
                    statusTxt:        Drupal.t('toggle hidden status message display'),

                    // elements
                    $outerWrap:       $('<div>'),
                    $innerWrap:       $('<div>'),
                    $toggleLink:      $('<a>'),
                    $linkIcon:        $('<span class="icon">'),
                    $progressBar:     $('<div>'),
                    $progressWrap:    $('<div>'),
                    $date:            $('<span>'),

                    // stuff
                    collapseStatus:   true,
                    progressDuration: 7500,
                    type:             'status',
                    hide:             true,
                    animated:         true,

                    // date & momentjs
                    created:          undefined,
                    showDate:         false,
                    locale:          'en',
                    intervalDate:     10000
                }, options),
                $this = $(this),
                dSettings = drupalSettings.timed_messages;


            // detect the message-type by its class
            function setConfigByType () {
                var types = ['status', 'warning', 'error'];

                for (var i = 0, len = types.length; i < len; i += 1) {
                    var type = types[i];// TODO,
                        //krumoHide = $this.find('.krumo-root').length <= 0 || drupalSettings.hide_with_krumo;

                    if ($this.hasClass(dSettings[type + '_class'])) {
                        //settings.status = dSettings[type + '_class'];
                        settings.progressDuration = dSettings[type + '_time'];
                        settings.hide = dSettings[type + '_hide'];
                        settings.hideTxt = settings[type + 'Txt'];
                    }
                }
            }


            //// set & update the date of the message
            //function setDate () {
            //    var s = settings;
            //
            //    function dateTxt () {
            //        s.$date.text(s.created.fromNow());
            //    }
            //
            //    // set interval to update the time
            //    setInterval(dateTxt, s.intervalDate);
            //
            //    // initial kick
            //    dateTxt();
            //}


            // create the additional markup (buttons and stuff)
            function createMarkup () {
                var s = settings,
                    showTxt = s.showTxt;

                // 1st wrap message, to be able to position the links absolute
                //TODO fix s.$outerWrap ****here**** (s.$outerWrap doesn't contain the DOM element after wrapping)
                $this.wrap(s.$outerWrap.addClass(s.outerWrapClass));
                $this.wrapInner(s.$innerWrap.addClass(s.innerWrapClass));
                s.$innerWrap = $this.find('.' + s.innerWrapClass);

                // 2nd create the toggle-link
                s.$toggleLink
                    .text(showTxt)
                    .attr('title', showTxt)
                    // adding plugin-link class and message-type class
                    .addClass([s.showClass, s.type].join(' '))
                    .insertAfter($this)
                    .prepend(s.$linkIcon);

                //TODO
                //// 3rd (optional) show date
                //if (s.showDate === true) {
                //    s.$date
                //        .attr('title', s.created.calendar())
                //        .addClass(s.dateClass)
                //        .appendTo($this);
                //    setDate();
                //}

                // 4th create the progress
                s.$progressBar
                    .addClass(s.processBarClass)
                    .appendTo(s.$progressWrap);
                s.$progressWrap
                    .addClass(s.processWrapClass)
                    .appendTo(s.$innerWrap);
            }


            // set the message-icon
            function initStyles () {
                // get bg-img from message to reuse it on the button
                //var background = $this.css('background-image');
                //$this.css('background-image', 'none');

                // set background-image to button if present
                //if (background != 'none') {
                //    settings.$toggleLink.css('background-image', background);
                //}
            }


            // start the progress animation
            function initProgress () {
                //start progress animation, and if wanted show link and  hide the message when finished
                if (settings.hide) {
                    $this.addClass(settings.inProgressClass);

                    settings.$progressBar.animate(
                        {width: '100%'},
                        parseInt(settings.progressDuration),
                        'linear',
                        function () {
                            toggleMessage(false);
                        }
                    );
                }
            }


            // ... and stop it
            function stopProgress () {
                $this.removeClass(settings.inProgressClass)
                    .unbind('hover');
                settings.$progressBar.stop(true).hide();
            }


            // binding event handlers
            function setListener () {
                var $progressBar = settings.$progressBar;

                // toggle the message
                settings.$toggleLink.click(function (e) {
                    e.preventDefault();
                    toggleMessage();
                });

                //TODO
                //// pause the progress on hover
                //$this.hover(
                //    function () {
                //        //$progressBar.pause();
                //    },
                //    function () {
                //        //$progressBar.resume();
                //    }
                //)
            }


            // the toggle-function (where the magic happens)
            function toggleMessage (status) {
                var s = settings;

                // use given status or settings
                status = (typeof status != 'undefined') ? status : !s.collapseStatus;

                //TODO fix s.$outerWrap ****here**** and remove .parent()
                $this.parent()
                    .toggleClass(s.openClass, status)
                    .toggleClass(s.closeClass, !status);

                // using js to open/close the message
                if (settings.animated) {
                    // show message
                    if (status) {
                        s.$innerWrap.slideDown();
                    }
                    // ... or hide it (and stop the timer)
                    else {
                        s.$innerWrap.slideUp('slow', function () {
                            stopProgress();
                        });
                    }
                }
                else if (status === false) {
                    stopProgress();
                }

                // update status
                s.collapseStatus = status;
            }


            // init
            (function _init () {
                var s = settings;

                // apply branding
                $this.addClass(s.pluginClass);

                //TODO
                //// check for moment - required for showing the date/time
                //if (typeof moment !== 'undefined') {
                //    s.showDate = true;
                //    s.created = moment().locale(s.locale);
                //}

                setConfigByType();
                createMarkup();
                //initStyles();
                setListener();
                initProgress();
            })();
        });
    };


    Drupal.behaviors.timedMessages = {
        attach: function (context, settings) {
            var msg = '.' + drupalSettings.timed_messages.message_class;
            $(msg, context).once('drupal-timed-messages').each(function(){
                $(this).timedMessage();
            });
        }
    };
})(jQuery);