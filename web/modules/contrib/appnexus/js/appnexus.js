var apntag = apntag || {};
apntag.anq = apntag.anq || [];

(function() {
  var d = document, e = d.createElement('script'), p = d.getElementsByTagName('head')[0];
  e.type = 'text/javascript';  e.async = true;
  e.src = '//acdn.adnxs.com/ast/ast.js';
  p.insertBefore(e, p.firstChild);
})();

(function ($, drupalSettings) {

  "use strict";

  apntag.anq.push(function() {

    if ("undefined" !== typeof drupalSettings.appnexus && "undefined" !== typeof drupalSettings.appnexus.opts) {
      apntag.setPageOpts(drupalSettings.appnexus.opts);
    }

    if ("undefined" !== typeof drupalSettings.appnexus && "undefined" !== typeof drupalSettings.appnexus.tags) {
      for (var targetId in drupalSettings.appnexus.tags) {
        apntag.defineTag({
          tagId: drupalSettings.appnexus.tags[targetId].tagId,
          sizeMapping: drupalSettings.appnexus.tags[targetId].sizeMapping,
          targetId: targetId
        });
      }
      apntag.loadTags();
    }

  });

  apntag.anq.push(function() {

    if ("undefined" !== typeof drupalSettings.appnexus && "undefined" !== typeof drupalSettings.appnexus.tags) {
      for (var targetId in drupalSettings.appnexus.tags) {
        apntag.showTag(targetId);
      }
    }

  });

})(jQuery, drupalSettings);
