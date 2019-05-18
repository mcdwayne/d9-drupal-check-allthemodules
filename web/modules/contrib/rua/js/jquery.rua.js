/*!
 * jQuery Remove Uppercase Accents for Drupal 7
 * https://www.drupal.org/project/rua
 *
 * Automatically removes accented characters (currently Greek) from elements
 * having their text content uppercase transformed through CSS.
 *
 * It WILL NOT target fieldset and elements capitalized inside fieldsets!
 */

(function($) {

  $.extend($.expr[":"], {
    uppercase: function(elem) {
      var attr = $(elem).css("text-transform");
      return typeof attr !== "undefined" && attr === "uppercase";
    },
    smallcaps: function(elem) {
      var attr = $(elem).css("font-variant");
      return typeof attr !== "undefined" && attr === "small-caps";
    }
  });

  $.extend({
    removeAcc: function(elem) {
      var text = elem.tagName.toLowerCase() === "input" ? elem.value : elem.innerHTML;

      text = text.replace(/Ά/g, "Α")
                 .replace(/ά/g, "α")
                 .replace(/Έ/g, "Ε")
                 .replace(/έ/g, "ε")
                 .replace(/Ή/g, "Η")
                 .replace(/ή/g, "η")
                 .replace(/Ί/g, "Ι")
                 .replace(/ί/g, "ι")
                 .replace(/ΐ/g, "ϊ")
                 .replace(/Ό/g, "Ο")
                 .replace(/ό/g, "ο")
                 .replace(/Ύ/g, "Υ")
                 .replace(/ύ/g, "υ")
                 .replace(/ΰ/g, "ϋ")
                 .replace(/Ώ/g, "Ω")
                 .replace(/ώ/g, "ω")
                 .replace(/ς/g, "Σ");

      if (elem.tagName.toLowerCase() === "input") {
        elem.value = text;
      } else {
        elem.innerHTML = text;
      }
    }
  });

  $.fn.extend({
    removeAcc: function() {
      return this.each(function() {
        $.removeAcc(this);
      });
    }
  });

  // Shorthand for `$(document).ready()`
  $(function() {

    $(":uppercase").not(".fieldset-legend").removeAcc();
    $(document).ajaxComplete(function() {
      $(":uppercase").not(".fieldset-legend").removeAcc();
    });

    $(":smallcaps").not(".fieldset-legend").removeAcc();
    $(document).ajaxComplete(function() {
      $(":smallcaps").not(".fieldset-legend").removeAcc();
    });

  });

})(jQuery);
