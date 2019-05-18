var selected_sharing_theme = jQuery('[name="interface"]');
var horizontal_image = jQuery('[name="horizontal_images"]:checked').val();
var vertical_image = jQuery('[name="vertical_images"]:checked').val();

window.onload = function () {
    jQuery("#edit-horizontal-images-0,#edit-horizontal-images-1, #edit-horizontal-images-10").click(function () {
        sharing_horizontal_show();
    });
    jQuery("#edit-horizontal-images-2,#edit-horizontal-images-3").click(function () {
        sharing_horizontal_show();
        sharing_simplehorizontal_show();
    });
    jQuery("#edit-horizontal-images-8,#edit-horizontal-images-9").click(function () {
        counter_horizontal_show();
    });
    jQuery("#edit-vertical-images-4,#edit-vertical-images-5").click(function () {
        sharing_vertical_show();
    });
    jQuery("#edit-vertical-images-6,#edit-vertical-images-7").click(function () {
        counter_vertical_show();
    });
    jQuery("#share_horizontal").click(function () {
        display_horizontal_widget();
    });
    jQuery("#share_veritical").click(function () {
        hidden_horizontal_widget();
    });
    jQuery("#share_advance").click(function () {
        display_advance_widget();
    });
    var sharing = ["Facebook","GooglePlus","Linkedin","Twitter","Pinterest","Email","Google","Digg","Reddit","Vkontakte","Tumblr","MySpace","Delicious","Print"];    
    sharingproviderlist(sharing);
    var counter = ["Facebook Like","Facebook Recommend","Facebook Send","Twitter Tweet","Pinterest Pin it","LinkedIn Share","StumbleUpon Badge","Reddit","Google+ +1","Google+ Share"];    
    counterproviderlist(counter);
    jQuery("#share_rearrange_providers, #share_vertical_rearrange_providers").sortable({
        revert: true
    });
    if (selected_sharing_theme)
        loginRadiusToggleShareTheme(selected_sharing_theme.val());
    jQuery(".share_rearrange, .socialvertical_share_rearrange").find("li").unwrap();
    jQuery("#share_veritical").click(function () {
        loginRadiusToggleShareTheme("vertical");
    });
    jQuery("#share_horizontal").click(function () {  
        loginRadiusToggleShareTheme("horizontal");
    });
    jQuery("#share_advance").click(function () {
        loginRadiusToggleShareTheme("advance");
    });
}
jQuery(document).ready(function() {
  //  var horizontal_image="0";
    horizontal_image = (horizontal_image == undefined ? 0 : horizontal_image);
    vertical_image = (vertical_image == undefined ? 4 : vertical_image);
    if (horizontal_image == 8 || horizontal_image == 9) {
        counter_horizontal_show();
    }
    else {
        sharing_horizontal_show();
        if (horizontal_image == 2 || horizontal_image == 3) {
            sharing_simplehorizontal_show();
        }
    }
    if (vertical_image == 6 || vertical_image == 7) {
        counter_vertical_show();
    }
    else {
        sharing_vertical_show();
    }
    var selected_theme = "";
    if (selected_theme == "vertical"){
        hidden_horizontal_widget();
    } else if(selected_theme == "advance"){
        display_advance_widget();
    }
    else {
        display_horizontal_widget();
    }
    
    showAndHidePopupWindow();
});

/*
 * Show and hide popup window ui
 */
function showAndHidePopupWindow() { 
        var value = jQuery('input[name=opensocialshare_popup_window]:checked').val();            
            if (value != 1) {                
                  jQuery('.form-item.form-type-textfield.form-item-opensocialshare-popup-window-size-height,.form-item.form-type-textfield.form-item-opensocialshare-popup-window-size-width').hide();      
            } else {                  
                   jQuery('.form-item.form-type-textfield.form-item-opensocialshare-popup-window-size-height,.form-item.form-type-textfield.form-item-opensocialshare-popup-window-size-width').show();     
            }       
}
/*
 * Json is valid or not.
 */
 function openSocialShareCheckValidJson() {    
      jQuery('#add_custom_options').change(function(){
      var profile = jQuery('#add_custom_options').val();      
      var response = '';
      try
      {
          response = jQuery.parseJSON(profile);
          if(response != true && response != false){
              var validjson = JSON.stringify(response, null, '\t').replace(/</g, '&lt;');
              if(validjson != 'null'){
                  jQuery('#add_custom_options').val(validjson);
                  jQuery('#add_custom_options').css("border","1px solid green");
              }else{                  
                  jQuery('#add_custom_options').css("border","1px solid red");
              }
          }
          else{
              jQuery('#add_custom_options').css("border","1px solid green");
          }
      } catch (e)
      {
          jQuery('#add_custom_options').css("border","1px solid green");
      }
  });
}
/*
 * Sharing Theme selected.
 */
function loginRadiusToggleShareTheme(theme) {    
    var verticalDisplay = 'none';
    var horizontalDisplay = 'block';
    var share_horizontal = jQuery("#share_horizontal");
    var share_veritical = jQuery("#share_veritical");
    var share_advance = jQuery("#share_advance");
    var vertical_position = jQuery("[data-drupal-selector='edit-vertical-position']"); 
    if (theme == "vertical") { 
        verticalDisplay = 'block';        
        horizontalDisplay = 'none';
        share_horizontal.removeClass("active");
        share_advance.removeClass("active");
        share_veritical.addClass("active");
        vertical_position.removeClass("element-invisible");
    } else if(theme == "advance"){           
        share_veritical.removeClass("active");
        share_horizontal.removeClass("active");
        share_advance.addClass("active");
        vertical_position.addClass("element-invisible");        
    } else { 
        share_horizontal.addClass("active");
        share_veritical.removeClass("active");
        share_advance.removeClass("active");
        vertical_position.addClass("element-invisible");
    }
    jQuery('.form-item-horizontal-images').css("display", horizontalDisplay);
    jQuery('.form-item-vertical-images').css("display", verticalDisplay);   
}
/*
 * Get sharing provider list
 */
function sharingproviderlist(sharing) {    
    var div = jQuery('#share_providers');
    var div_vertical = jQuery('#vertical_share_providers');
    if (div && div_vertical) {
        for (var i = 0; i < sharing.length; i++) {
            var listItem = jQuery("<div class= 'form-item form-type-checkbox form-item-share-providers-" + sharing[i].toLowerCase() + "'><input type='checkbox' id='edit-share-providers-" + sharing[i].toLowerCase() + "' onChange='loginRadiusSharingLimit(this),loginRadiusRearrangeProviderList(this)' name='share_providers[" + sharing[i].toLowerCase() + "]' value='" + sharing[i].toLowerCase() + "' class='form-checkbox' /><label for='edit-simplified_social_share-show-providers-list-" + sharing[i].toLowerCase() + "' class='option'>" + sharing[i] + "</label></div>");
            div.append(listItem);
            var listItem = jQuery("<div class= 'form-item form-type-checkbox form-item-vertical-share-providers-" + sharing[i].toLowerCase() + "'><input type='checkbox' id='edit-vertical-share-providers-" + sharing[i].toLowerCase() + "' onChange='loginRadiusverticalSharingLimit(this),loginRadiusverticalRearrangeProviderList(this)' name='vertical_share_providers[" + sharing[i].toLowerCase() + "]' value='" + sharing[i].toLowerCase() + "' class='form-checkbox' /><label for='edit-simplified_social_share-vertical-show-providers-list-" + sharing[i].toLowerCase() + "' class='option'>" + sharing[i] + "</label></div>");
            div_vertical.append(listItem);
        }
        jQuery('input[name^="share_rearrange"]').each(function () {
            var elem = jQuery(this);
            if(jQuery('li#'+elem.attr('class')).parent().is( "div" )){
                jQuery('li#'+elem.attr('class')).unwrap();
            }
            if (!elem.checked) {
                jQuery('#edit-share-providers-' + elem.val()).attr('checked', 'checked');
            }
        });
        jQuery('input[name^="vertical_share_rearrange"]').each(function () {
            var elem = jQuery(this);
            if(jQuery('li#'+elem.attr('class')).parent().is( "div" )){
                jQuery('li#'+elem.attr('class')).unwrap();
            }
            if (!elem.checked) {
                jQuery('#edit-vertical-share-providers-' + elem.val()).attr('checked', 'checked');
            }
        });
    }
}
/*
 * Show sharing Rearrange Providers.
 */
function loginRadiusRearrangeProviderList(elem) {
    var ul = jQuery('#share_rearrange_providers');

    if (elem.checked) {
        var provider = jQuery("<li id='edit-osshare-iconsprite32" + elem.value + "' title='" + elem.value + "' class='share-provider " + elem.value + " flat square size-32 horizontal'><input data-drupal-selector='edit-share-rearrange-" + elem.value + "' type='hidden' value='" + elem.value + "' name='share_rearrange["+elem.value+"]' ></li>");
        ul.append(provider);
    } else {
        if (jQuery('#edit-osshare-iconsprite32' + elem.value)) {
            jQuery('#edit-osshare-iconsprite32' + elem.value).remove();
        }
    }
}

/*
 * vertical Sharing Rearrange counter
 */
function loginRadiusverticalRearrangeProviderList(elem) {
    var ul = jQuery('#share_vertical_rearrange_providers');

    if (elem.checked) {
        var provider = jQuery("<li id='edit-osshare-iconsprite32_vertical" + elem.value + "' title='" + elem.value + "' class='share-provider " + elem.value + " flat square size-32 horizontal'><input type='hidden' value='" + elem.value + "' name='vertical_share_rearrange["+elem.value+"]' id='input-osshare-vertical-" + elem.value + "'></li>");
        ul.append(provider);
    } else {
        if (jQuery('#edit-osshare-iconsprite32_vertical' + elem.value)) {
            jQuery('#edit-osshare-iconsprite32_vertical' + elem.value).remove();
        }
    }
}
/*
 * Check limit for horizontal Open Social sharing.
 */
function loginRadiusSharingLimit(elem) {
    var checkCount = jQuery('input[name^="share_rearrange"]').length;
    if (elem.checked) {
        // count checked providers
        checkCount++;
        if (checkCount >= 10) {
            elem.checked = false;
            jQuery("#loginRadiusSharingLimit").show('slow');
            setTimeout(function () {
                jQuery("#loginRadiusSharingLimit").hide('slow');
            }, 2000);
            return;
        }
    }
}
/*
 * check limit for vertical Open Social sharing.
 */
function loginRadiusverticalSharingLimit(elem) {
    var checkCount = jQuery('input[name^="vertical_share_rearrange"]').length;
    if (elem.checked) {
        // count checked providers
        checkCount++;
        if (checkCount >= 10) {
            elem.checked = false;
            jQuery("#loginRadiusSharingLimit_vertical").show('slow');
            setTimeout(function () {
                jQuery("#loginRadiusSharingLimit_vertical").hide('slow');
            }, 2000);
            return;
        }
    }
}
/*
 * Show Provider List for horizontal Open Social counter.
 */
function counterproviderlist(counter) {
    var div = jQuery('#counter_providers');
    var div_vertical = jQuery('#vertical_counter_providers');
    if (div && div_vertical) {
        for (var i = 0; i < counter.length; i++) {
            var value = counter[i].split(' ').join('');
            value = value.replace("++", "plusplus");
            value = value.replace("+", "plus");
            var listItem = jQuery("<div class= 'form-item form-type-checkbox form-item-counter-providers-" + counter[i] + "'><input type='checkbox' id='edit-counter-providers-" + value + "' onChange='loginRadiuscounterProviderList(this)' name='counter_providers[]' value='" + counter[i] + "' class='form-checkbox' /><label for='edit-counter-providers-" + value + "' class='option'>" + counter[i] + "</label></div>");
            div.append(listItem);
            var listItem = jQuery("<div class= 'form-item form-type-checkbox form-item-vertical-counter-providers" + counter[i] + "'><input type='checkbox' id='edit-vertical-counter-providers-" + value + "' onChange='loginRadiuscounterverticalProviderList(this)' name='vertical_counter_providers[]' value='" + counter[i] + "' class='form-checkbox' /><label for='edit-vertical-counter-providers-" + value + "' class='option'>" + counter[i] + "</label></div>");
            div_vertical.append(listItem);
        }
        jQuery('input[name^="counter_rearrange"]').each(function () {
            var elem = jQuery(this);
            if (!elem.checked) {
                var value = elem.val().split(' ').join('');
                value = value.replace("++", "plusplus");
                value = value.replace("+", "plus");
                jQuery('#edit-counter-providers-' + value).attr('checked', 'checked');
            }
        });
        jQuery('input[name^="vertical_counter_rearrange"]').each(function () {
            var elem = jQuery(this);
            if (!elem.checked) {
                var value = elem.val().split(' ').join('');
                value = value.replace("++", "plusplus");
                value = value.replace("+", "plus");
                jQuery('#edit-vertical-counter-providers-' + value).attr('checked', 'checked');
            }
        });
    }
}
/*
 * Show Counter Providers selected.
 */
function loginRadiuscounterProviderList(elem) {
    var ul = jQuery('#socialcounter_show_providers_list');
    var raw = elem.value;
    var value = elem.value.split(' ').join('');
    value = value.replace("++", "plusplus");
    value = value.replace("+", "plus");
    if (elem.checked) {
        var provider = jQuery("<input type='hidden' value='" + raw + "' name='counter_rearrange[]' id='input-oscounter-" + elem.value + "'>");
        ul.append(provider);
    } else {
        jQuery('#input-oscounter-' + value).remove();
        jQuery('#edit-' + value).remove();
    }
}
/*
 * Provider list selcted in vertical counter.
 */
function loginRadiuscounterverticalProviderList(elem) {
    var ul = jQuery('#socialcounter_vertical_show_providers_list');
    var raw = elem.value;
    var value = elem.value.split(' ').join('');
    value = value.replace("++", "plusplus");
    value = value.replace("+", "plus");
    if (elem.checked) {
        var provider = jQuery("<input type='hidden' value='" + raw + "' name='vertical_counter_rearrange[]' id='input-oscounter-vertical-" + value + "'>");
        ul.append(provider);
    } else {
        jQuery('#input-oscounter-vertical-' + value).remove();
        jQuery('#edit-osshare-vertical-' + value).remove();
    }
}
/*
 * show Sharing Horizontal
 */
function sharing_horizontal_show() {
    toggle_sharing_counter(true);
}
/*
 * show Counter Horizontal .
 */
function counter_horizontal_show() {
    toggle_sharing_counter(false);
}
/*
 * Show simple sharing widget.
 */
function sharing_simplehorizontal_show() {
    toggle_sharing_counter(true, true);
}
/*
 * Toggle shairing and counter fields.
 */
function toggle_sharing_counter(is_simplified_social_share, is_social_counter) {
    var simple_sharing = is_simplified_social_share ? "addClass" : "removeClass";
    var simple_counter = is_simplified_social_share ? "removeClass" : "addClass";
    if (is_social_counter) {
        simple_counter = "addClass";
    }
    jQuery("#share_providers, #rearrange_sharing_text, #share_rearrange_providers")[simple_counter]("element-invisible");
    jQuery("#counter_providers")[simple_sharing]("element-invisible");
}
/*
 * Show sharing vertical.
 */
function sharing_vertical_show() {
    toggle_sharing_vertical_show(true);
}
/*
 * show Counter Vertical.
 */
function counter_vertical_show() {
    toggle_sharing_vertical_show(false);
}
/*
 * Toggle Vertical sharing widget.
 */
function toggle_sharing_vertical_show(is_simplified_social_share) {
    var simple_vertical_sharing = is_simplified_social_share ? "addClass" : "removeClass";
    var simple_vertical_counter = is_simplified_social_share ? "removeClass" : "addClass";
    jQuery("#vertical_counter_providers")[simple_vertical_sharing]("element-invisible");
    jQuery("#vertical_share_providers, #rearrange_sharing_text_vertical, #share_vertical_rearrange_providers")[simple_vertical_counter]("element-invisible");
}
/*
 * Toggle horizontal sharing widget.
 */
function toggle_horizontal_widget(is_horizontal) {
    var horizontal_sharing = is_horizontal ? "addClass" : "removeClass";
    var vertical_sharing = is_horizontal ? "removeClass" : "addClass";
    var advance_sharing =  "addClass";
    jQuery("#share_show_horizontal_widget, #edit-horizontal-images, .form-item-show-exceptpages, [data-drupal-selector='edit-show-pages'], #horizontal_sharing_show, [data-drupal-selector='edit-enable-horizontal'],  .form-item-label")[horizontal_sharing]("element-invisible");
    jQuery("#share_show_veritcal_widget, #edit-vertical-images, [data-drupal-selector='edit-vertical-show-pages'], .form-item-vertical-show-exceptpages, [data-drupal-selector='edit-enable-vertical']")[vertical_sharing]("element-invisible");
    jQuery("#popup_window_size, .form-item-opensocialshare-email-message, .form-item-opensocialshare-email-subject, [data-drupal-selector='edit-opensocialshare-is-email-content-read-only'], [data-drupal-selector='edit-opensocialshare-is-shorten-url'], .form-item-opensocialshare-facebook-app-id, [data-drupal-selector='edit-opensocialshare-is-total-share'], [data-drupal-selector='edit-opensocialshare-is-open-single-window'], .form-item-opensocialshare-popup-window-size-height, .form-item-opensocialshare-popup-window-size-width, .form-item-opensocialshare-twitter-mention, .form-item-opensocialshare-twitter-hash-tags, [data-drupal-selector='edit-opensocialshare-is-custom-shortner'], [data-drupal-selector='edit-opensocialshare-is-mobile-friendly'], .form-item-opensocialshare-custom-options")[advance_sharing]("element-invisible");
    
}
/*
 * Show only vertical widget options.socialloginandsimplified_social_share
 */
function hidden_horizontal_widget() {
    toggle_horizontal_widget(true);
}
/*
 * Show only Horizontal widget options.
 */
function display_horizontal_widget() {
    toggle_horizontal_widget(false);
}
/*
 * Show only advance widget options.
 */
function display_advance_widget() {  
    var horizontal_sharing =  "addClass";   
    var vertical_sharing =  "addClass";
    var advance_sharing =  "removeClass";
    jQuery("#share_show_horizontal_widget, #edit-horizontal-images, .form-item-show-exceptpages, [data-drupal-selector='edit-show-pages'], #horizontal_sharing_show, [data-drupal-selector='edit-enable-horizontal'],  .form-item-label")[horizontal_sharing]("element-invisible");
    jQuery("#share_show_veritcal_widget, #edit-vertical-images, [data-drupal-selector='edit-vertical-show-pages'], .form-item-vertical-show-exceptpages, [data-drupal-selector='edit-enable-vertical']")[vertical_sharing]("element-invisible");
    jQuery("#popup_window_size, .form-item-opensocialshare-email-message, .form-item-opensocialshare-email-subject, [data-drupal-selector='edit-opensocialshare-is-email-content-read-only'], [data-drupal-selector='edit-opensocialshare-is-shorten-url'], .form-item-opensocialshare-facebook-app-id, [data-drupal-selector='edit-opensocialshare-is-total-share'], [data-drupal-selector='edit-opensocialshare-is-open-single-window'], .form-item-opensocialshare-popup-window-size-height, .form-item-opensocialshare-popup-window-size-width, .form-item-opensocialshare-twitter-mention, .form-item-opensocialshare-twitter-hash-tags, [data-drupal-selector='edit-opensocialshare-is-custom-shortner'], [data-drupal-selector='edit-opensocialshare-is-mobile-friendly'], .form-item-opensocialshare-custom-options")[advance_sharing]("element-invisible");
    
}