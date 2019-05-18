var drupalchat_bundle = document.createElement("SCRIPT");
drupalchat_bundle.src = "//"+drupalSettings.drupalchat_external_cdn_host+"/js/iflychat-v2.min.js?app_id=" + drupalSettings.drupalchat_app_id;
drupalchat_bundle.async = "async";
document.body.appendChild(drupalchat_bundle);