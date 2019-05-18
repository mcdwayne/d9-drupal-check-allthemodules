/**
 * @file
 * Fullcalendar Tooltip plugin JavaScript file.
 *
 * Custom tooltip plugin for FullCalendar.
 * More options see:
 * http://qtip2.com/options.
 */

(function ($) {
    $.fn.fullCalendarTooltip = function (title, description) {
        if (typeof this.qtip === 'function') {
             this.qtip({
                    content: {
                      text: description,
                      title: title,
                  },
                  position: {
                      my: 'bottom center',  // Position my bottom center...
                      at: 'top center', // at the top center of...
                  },
                  style: {
                      classes: 'qtip-bootstrap qtip-shadow'
                  }
             });
        }
        return this;
    };
}
)(jQuery);
