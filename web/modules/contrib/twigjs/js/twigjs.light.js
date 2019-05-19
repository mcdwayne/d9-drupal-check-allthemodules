;(function(window) {
  'use strict';
  var _ = window._;

  var templateSettings = {
    interpolate: /\{\{(.+?)\}\}/g
  };

  var cache = {};

  window.TwigLight = {
    twig: function(data) {
      var id = data.id;
      if (cache[id]) {
        return cache[id];
      }
      cache[id] = {
        render: _.template(data.data, templateSettings)
      };
      return cache[id];
    }
  }
})(window);
