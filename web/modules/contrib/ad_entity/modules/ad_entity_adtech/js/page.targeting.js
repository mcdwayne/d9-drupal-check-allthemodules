/**
 * @file
 * JS implementation for adding page targeting.
 */

(function (adEntity, settings, window) {

  adEntity.adtechAddPageTargeting = function (settings) {
    var page_targeting;
    var key;
    var delay;

    if (this.adtechPageTargetingAdded === true) {
      return;
    }
    if (settings.hasOwnProperty('adtech_page_targeting')) {
      this.adtechPageTargetingAdded = false;
      if (typeof window.atf_lib !== 'undefined') {
        this.adtechLoadingAttempts = true;
        page_targeting = settings['adtech_page_targeting'];
        for (key in page_targeting) {
          if (page_targeting.hasOwnProperty(key)) {
            window.atf_lib.add_page_targeting(key, page_targeting[key]);
          }
        }
        this.adtechPageTargetingAdded = true;
      }
      else {
        if (typeof this.adtechLoadingAttempts === 'undefined') {
          this.adtechLoadingAttempts = 0;
          this.adtechLoadingUnit = 'page_targeting';
        }
        if (this.adtechLoadingAttempts === false) {
          // Failed to load the library entirely, abort.
          return;
        }
        if (typeof this.adtechLoadingAttempts === 'number') {
          if (this.adtechLoadingAttempts < 100) {
            this.adtechLoadingAttempts++;
            delay = 10 * this.adtechLoadingAttempts;
            window.setTimeout(this.adtechAddPageTargeting.bind(this), delay, settings);
          }
          else {
            this.adtechLoadingAttempts = false;
          }
        }
      }
    }
  };

  adEntity.adtechAddPageTargeting(settings);

}(window.adEntity, drupalSettings, window));
