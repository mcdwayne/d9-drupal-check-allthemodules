(function ($) {
  /**
   * Initialization
   */
  Drupal.behaviors.drowl_trademark = {
    /**
     * Run Drupal module JS initialization.
     *
     * @param context
     * @param settings
     */
    attach: function (context, settings) {
      /*
       * jQuery replaceText - v1.1 - 11/21/2009
       * http://benalman.com/projects/jquery-replacetext-plugin/
       *
       * Copyright (c) 2009 "Cowboy" Ben Alman
       * Dual licensed under the MIT and GPL licenses.
       * http://benalman.com/about/license/
       */
      (function ($) {
        $.fn.replaceText = function (b, a, c) {
          return this.each(function () {
            var f = this.firstChild, g, e, d = [];
            if (f) {
              do {
                if (f.nodeType === 3) {
                  g = f.nodeValue;
                  e = g.replace(b, a);
                  if (e !== g) {
                    if (!c && /</.test(e)) {
                      $(f).before(e);
                      d.push(f);
                    } else {
                      f.nodeValue = e;
                    }
                  }
                }
              } while (f = f.nextSibling);
            }
            d.length && $(d).remove();
          });
        };
      })(jQuery);

      // #webksde#JP20141015: Do not handle email addresses and elements with container class ".no-replacetext".
      // #webksde#JP20150813: once ergänzt - ACHTUNG - setzt sehr viele classes, sodass es hier zu Performance-Problemen kommen kann. Bei Problemen wieder entfernen!
      // WICHTIG: Die <sup - Erkennung "(?!\<sup)" des Folgewortes funktioniert hier nicht, da der Regex nur auf einzelne Texte, nicht aber auf folgende HTML Elemente angewandt wird!
      var replacepattern = settings.drowl_trademark.replacepattern;
      var filter = settings.drowl_trademark.filter || '.no-drowl-trademark,a[itemprop=email],.spamspan > *';
      var regexp = new RegExp('\\b(' + replacepattern + ')(?!\\<sup)\\b', "gi");
      jQuery('body *', context).not(filter).once('drowl-trademark').replaceText(regexp, '$1<sup>®</sup>', false);
    }
  };
})(jQuery);
