/**
 * @file
 * Consent: Load OIL.js when needed.
 */

(function (c, dom, settings) {

  var oiljs;
  if (settings.consent && settings.consent.oil_src && !c.userOptedIn()) {
    oiljs = dom.createElement('script');
    oiljs.src = settings.consent.oil_src;
    oiljs.async = true;
    dom.body.appendChild(oiljs);
  }

}(window.Consent, window.document, window.drupalSettings));
