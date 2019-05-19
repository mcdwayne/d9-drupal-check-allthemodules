/**
 * @file
 * Javascript behaviors for Christian Aid Crafty Clicks.
 */
var cp_obj = '';
(function($) {
Drupal.behaviors.webform_craftyclicks = {
  attach: function (context, settings) {
    $(document).ready(function () {
      // Crafty Clicks set up.
      // @see: https://craftyclicks.co.uk/tutorials/postcode-lookup-in-15-minutes
      cp_obj = CraftyPostcodeCreate();
      cp_obj.set("access_token", $("input[name='crafty_token']").val());
      cp_obj.set("result_elem_id", "crafty_postcode_result_display");
      cp_obj.set("form", $(".webform-submission-form").attr("id"));
      cp_obj.set("elem_company", $("input[name*='[company]']").attr("name"));
      cp_obj.set("elem_street1", $("input[name*='[address1]']").attr("name"));
      cp_obj.set("elem_street2", $("input[name*='[address2]']").attr("name"));
      cp_obj.set("elem_street3", $("input[name*='[address3]']").attr("name"));
      cp_obj.set("elem_town", $("input[name*='[town]']").attr("name"));
      cp_obj.set("elem_postcode", $("input[name*='[postcode]']").attr("name"));
      cp_obj.set("town_uppercase", 0);
      cp_obj.set("res_autoselect", 0);
      cp_obj.set("busy_img_url", "/core/misc/throbber-active.gif");
    });
  }
};
})(jQuery);
