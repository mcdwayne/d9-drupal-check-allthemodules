jQuery(document).ready(function() {
  
  jQuery("input[name=drupalchat_path_visibility]").change(function() {
    if ((jQuery("input[name=drupalchat_path_visibility]:checked").val() == '2') || (jQuery("input[name=drupalchat_path_visibility]:checked").val() == '3')) {
	  jQuery(".form-item-drupalchat-path-pages").show();
	}
	else {
	  jQuery(".form-item-drupalchat-path-pages").hide();
	}
  });
  jQuery("input[name=drupalchat_path_visibility]").change();
});
