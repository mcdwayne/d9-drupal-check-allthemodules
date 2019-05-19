/**
 * @file
 * Attaches behaviors for the Vuukle module.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.vuukleSettings = {
    attach: function () {
      var VUUKLE_CUSTOM_TEXT = '{ "rating_text": '+ drupalSettings.vuukle.rating_text +', "comment_text_0": '+ drupalSettings.vuukle.comment_text_0 +', "comment_text_1": '+ drupalSettings.vuukle.comment_text_1 +', "comment_text_multi": '+ drupalSettings.vuukle.comment_text_multi +', "stories_title": '+ drupalSettings.vuukle.stories_title +' }';
      var UNIQUE_ARTICLE_ID = drupalSettings.vuukle.node_id;
      var SECTION_TAGS = "";// TO DO
      var ARTICLE_TITLE = drupalSettings.vuukle.node_title;
      var GA_CODE = drupalSettings.vuukle.ga_code;
      var VUUKLE_API_KEY = drupalSettings.vuukle.vuukle_api_key;
      var TRANSLITERATE_LANGUAGE_CODE = 'en';//TO DO Node language
      var VUUKLE_COL_CODE = drupalSettings.vuukle.vuukle_col_code;
      var ARTICLE_AUTHORS = {};
      create_vuukle_platform(VUUKLE_API_KEY, UNIQUE_ARTICLE_ID, "0", SECTION_TAGS, ARTICLE_TITLE, TRANSLITERATE_LANGUAGE_CODE, "1", "", GA_CODE, VUUKLE_COL_CODE, ARTICLE_AUTHORS);
    }
  };

})(jQuery);
