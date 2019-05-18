/**
 * @file
 * QyWeixin Jsapi interface helper.
 */
wx.config({
	debug: false,
	appId: drupalSettings.qyweixin.Jsapi.corpId,
	timestamp: drupalSettings.qyweixin.Jsapi.timestamp,
	nonceStr: drupalSettings.qyweixin.Jsapi.nonceStr,
	signature: drupalSettings.qyweixin.Jsapi.signature,
	jsApiList: [drupalSettings.qyweixin.Jsapi.jsApiList]
});
wx.ready(function () {
	'use strict';
	wx.hideOptionMenu();
});

