/**
 * @file
 * Load javascript, css and library assets on demand.
 */
(function($, Drupal, drupalSettings){
  var summonerCallbacks = {};

  drupalSettings.summonerState = drupalSettings.summonerState || {};

  Drupal.summonerAttachBehavior = function (libraries) {
    Drupal.behaviors['summonerLoad-' + libraries] = {
      attach: function () {
        if (summonerCallbacks[libraries]) {
          $.each(summonerCallbacks[libraries], function(index, callback) {
            callback();
          });
        }
        Drupal.behaviors['summonerLoad-' + libraries] = null;
      }
    };
  };

  Drupal.summon = function (libraries, callback) {
    libraries = $.isArray(libraries) ? libraries : [libraries];
    var toLoad = [];
    $.each(libraries, function (index, lib){
      if (!drupalSettings.summonerState[lib]) {
        toLoad.push(lib);
      }
    });
    if (toLoad.length > 0) {
      toLoad.sort();
      var libs = toLoad.join(',');
      if (!summonerCallbacks[libs]) {
        summonerCallbacks[libs] = [];
        var url = Drupal.url('summoner/load/' + libs.replace('/', '::'));
        var element = $('body');
        var ajax = new Drupal.ajax(url, element, { url: url });
        ajax.beforeSerialize(ajax.element, ajax.options);
        $.ajax(ajax.options);
      }
      summonerCallbacks[libs].push(callback);
    }
    else {
      callback();
    }
  };

}(jQuery, Drupal, drupalSettings));