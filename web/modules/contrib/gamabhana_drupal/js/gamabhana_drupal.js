function switchmode(internal, obj) {
  if (obj != null) {
    try {
      GphMasterSwapLang();
    }
    catch (e) {
    }
  }
  else if (internal) {
    try {
      GphMasterSwapLang();
    }
    catch (e) {
    }
  }
}

function setprilanguage(ilang) {
  try {
    GphMasterPriLangUpdate(ilang);
  }
  catch (e) {
  }
  return;
}

/*
 * Function to get the element ids form drupal settings and creating
 * gamabhanaPhoneticHandler oject
 */
var filter_ids_js = [];
(function($) {
  Drupal.behaviors.backgroundAnimation = {
    attach : function(context, settings) {
      filter_ids_js = settings.field_ids;
      default_lang = settings.default_lang;
      secondery_lang = settings.secondery_lang;
      languages = settings.languages;
      all_lang = settings.all_lang;

      var css = '#gamabhana-lang-switch-block {padding:5px;text-align:left;z-index:99;line-height:1em;position:fixed;width:150px;bottom:0px;left:10px;background:#EDF5FA;border:solid 1px #336699;}';
      css = css + '#gamabhana-lang-switch-block select {width:140px}';

      var styleElement = document.createElement("style");
      styleElement.type = "text/css";
      if (styleElement.styleSheet) {
        styleElement.styleSheet.cssText = css;
      }
      else {
        styleElement.appendChild(document.createTextNode(css));
      }
      document.getElementsByTagName("head")[0].appendChild(styleElement);
      var div = document.createElement("div");
      var body = document.querySelector('body');
      var node = document.createElement('div');
      node.setAttribute('class', 'gamabhana-lang-switch-block');
      node.setAttribute('style', 'display:none');
      node.setAttribute('id', 'gamabhana-lang-switch-block');
      if (all_lang) {
        var buttons = "<select onChange='setprilanguage(this.value);'>";
        for (key in languages) {
          if (languages[key] == 'English') {
            buttons += "<option value='" + key + "' selected>" + languages[key] + "</option>";
          }
          else {
            buttons += "<option value='" + key + "'>" + languages[key] + "</option>";
          }
        }
        buttons += "</select>";
      }
      else {
        var buttons = "<select onChange='setprilanguage(this.value);'><option value='"
            + default_lang + "'>" + languages[default_lang] + "</option><option value='"
            + secondery_lang + "'>" + languages[secondery_lang] + "</option></select>";
      }

      node.innerHTML = buttons;
      body.appendChild(node);

      for (var i = 0; i < filter_ids_js.length; i++) {
        gph_id = '_gph_' + filter_ids_js[i].replace("-", "_");
        if ($('#' + filter_ids_js[i]).length > 0) {
          gph_id = new gamabhanaPhoneticHandler(filter_ids_js[i], default_lang, secondery_lang,
              '#gamabhana#');
        }
      }

    }
  };

  $(document).ready(
      function() {
        $('.gamabhan-enabled').click(
            function() {
              gamabhana_switch_block = document.getElementById("gamabhana-lang-switch-block");
              if (gamabhana_switch_block.style.display == 'block'
                  || gamabhana_switch_block.style.display == '') {
                document.getElementById("gamabhana-lang-switch-block").style.display = "none";
              }
              else {
                document.getElementById("gamabhana-lang-switch-block").style.display = "block";
              }
            });
      });
})(jQuery);
