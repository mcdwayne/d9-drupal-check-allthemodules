/**
 * @file
 * Initializes Google Publisher Tag (GPT) service provider.
 */

(function (window) {

  window.googletag = window.googletag || {};
  window.googletag.cmd = window.googletag.cmd || [];

  window.googletag.cmd.push(function () {
    var gpt = window.googletag;
    var usePersonalization = window.adEntity.usePersonalization;
    gpt.pubads().enableSingleRequest(true);
    gpt.pubads().disableInitialLoad();
    gpt.pubads().collapseEmptyDivs();
    if ((typeof usePersonalization === 'function') && (usePersonalization() === true)) {
      gpt.pubads().setRequestNonPersonalizedAds(0);
    }
    else {
      gpt.pubads().setRequestNonPersonalizedAds(1);
    }
    gpt.enableServices();
  });

}(window));
