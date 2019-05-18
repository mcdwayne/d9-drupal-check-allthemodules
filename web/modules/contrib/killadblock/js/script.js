/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
/**
* @namespace
*/
Drupal.behaviors.killadblockAccessData = {
    attach: function (context, settings) {

        var d_description, d_title, d_btntxt;

        d_description = drupalSettings.drupal_kadb_description;
        d_title = drupalSettings.drupal_kadb_title;
        d_btntxt = drupalSettings.drupal_kadb_btn_txt;

        function adBlockDetected() {
            var adb_banner = ' <div class="adkilloverlay"> <div class="adcontent"> <div class="kadbtitle">' + d_title + '</div><br><div class="kadbdescription">' + d_description + '</div> <div class="kadbbtn-text"><br> <button  class="adkill-btn" onClick="window.location.reload()">' + d_btntxt + '</button> </div> </div> </div>';
            $('body', context).once('body').append(adb_banner);
        }
        if (typeof blockAdBlock === 'undefined') {
            adBlockDetected();
        }
        else {
            blockAdBlock.onDetected(adBlockDetected);
            blockAdBlock.on(true, adBlockDetected);
        }

        blockAdBlock.setOption('checkOnLoad', true);

    }
};
})(jQuery, Drupal, drupalSettings);
