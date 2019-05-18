/**
 * @file
 * The nsw_feedback_assist widget integration.
 */

"use strict";
(function (drupalSettings) {

    var widget = document.createElement('script');
    widget.src = 'https://www.onegov.nsw.gov.au/CDN/feedbackassist/feedbackassist.min.js';

    document.head.appendChild(widget);
    //initialise domain where API is hosted
    widget.onreadystatechange = function () {
        if (this.readyState === 'complete') {
            caBoootstrap.init(drupalSettings.data.nsw_feedback_assist_url);
        }
    };
    widget.onload = function () {
        caBoootstrap.init(drupalSettings.data.nsw_feedback_assist_url);
    };

})(drupalSettings);

