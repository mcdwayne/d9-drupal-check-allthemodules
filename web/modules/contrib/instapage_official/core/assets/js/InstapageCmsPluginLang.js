/* globals InstapageDictionary */
var InstapageCmsPluginLang = function InstapageCmsPluginLang() {
  var self = this;

  self.lang = InstapageDictionary;

  self.get = function get(format) {
    var result = self.lang[arguments[0]];

    for (var i = 1; i < arguments.length; i++) {
      result = result.replace(/%s/, arguments[i]);
    }

    return result;
  };
};

var iLang = new InstapageCmsPluginLang();
window.iLang = iLang;
