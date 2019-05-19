/**
 * @file
 * Attaches wechat share event listener to a web page.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.trackingWechatMenuShare = {
    attach: function () {
      // Make sure this behavior is processed only if wx is defined.
      if (typeof wx === 'undefined') {
        return;
      }
      wx.config({
        debug: drupalSettings.wechat_share_advance.debug_mode,
        appId: drupalSettings.wechat_share_advance.sign_package.appid,
        timestamp: drupalSettings.wechat_share_advance.sign_package.timestamp,
        nonceStr: drupalSettings.wechat_share_advance.sign_package.noncestr,
        signature: drupalSettings.wechat_share_advance.sign_package.signature,
        jsApiList: [
          'onMenuShareTimeline',
          'onMenuShareAppMessage'
        ]
      });
      wx.ready(function () {
        var shareData = {
          title: document.head.querySelector("[property='og:title']").content,
          desc: document.head.querySelector("[property='og:description']").content,
          link: '',
          imgUrl: document.head.querySelector("[property='og:image:secure_url']").content
        };
        wx.onMenuShareAppMessage(shareData);
        wx.onMenuShareTimeline(shareData);
      });
      wx.error(function (res) {
        console.log(res.errMsg);
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
