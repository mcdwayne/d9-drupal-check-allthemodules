/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
/**
* @namespace
*/
Drupal.behaviors.exitpopupAccessData = {
attach: function (context, settings) {
    var widthDrupal, heightDrupal, htmlDrupal, cookieExpDrupal, delayDrupal,cssDrupal;
    widthDrupal = drupalSettings.drupal_exitpopup_width;
    heightDrupal = drupalSettings.drupal_exitpopup_height;
    htmlDrupal = drupalSettings.drupal_exitpopup_html.replace(/[\r\n]/g, '');
    cssDrupal = drupalSettings.drupal_exitpopup_css.replace(/[\r\n]/g, '');
    cookieExpDrupal = drupalSettings.drupal_exitpopup_cookie_exp;
    delayDrupal = drupalSettings.drupal_exitpopup_delay;

    bioEp.init({
        html: htmlDrupal,
        css: cssDrupal,
        width: widthDrupal,
        height: heightDrupal,
        delay: delayDrupal,
        cookieExp: cookieExpDrupal
    });
}
};
})(jQuery, Drupal, drupalSettings);
