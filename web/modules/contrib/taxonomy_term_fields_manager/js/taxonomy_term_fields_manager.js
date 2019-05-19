/*  @file */

jQuery(document).ready(function($) {
	var maxAllowed = 3;

	jQuery('input.form-checkbox').on('change', function(evt) {
	  var cnt = $("input.form-checkbox:checked").length;
	  if (cnt > maxAllowed)
      {
         jQuery(this).prop("checked", "");
         var text_alert = Drupal.t('Select maximum @maxAllowed term fields!', {'@maxAllowed': maxAllowed});
         alert(text_alert);
     }

	});
});
