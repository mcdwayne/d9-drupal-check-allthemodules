/**
 * @file
 * Hidey Help Javascript.
 */

'use strict';

var HideyHelp = function ($, el) {
  var $hh = $(el);
  if ($hh.length) {
    var html = $.trim($hh.html());
    if (html.length) {
      var hc = 'hidey-help';
      var $s1 = $('<span/>').attr({class: 'hidey-hide'}).text(Drupal.t('Hide Help')).hide();
      var $s2 = $('<span/>').attr({class: 'hidey-show'}).text(Drupal.t('Show Help'));
      var $a = $('<a/>').attr({class: hc, href: '#'}).append($s1, $s2).click(function () {
        if ($hh.is(':visible')) {
          $hh.hide();
          $s1.hide();
          $s2.show();
        }
        else {
          $hh.show();
          $s1.show();
          $s2.hide();
        }
        return false;
      });
      var $tabs = $('ul.tabs.primary');
      if ($tabs.length) {
        if ($tabs.find('a span.tab:first').length) {
          $s1.add($s2).addClass('tab');
        }
        $a.removeClass(hc);
        $('<li/>').attr({class: hc}).append($a).appendTo($tabs);
      }
      else {
        $a.insertBefore($hh);
      }
      $hh.hide();
    }
    else {
      $hh.remove();
    }
  }
};

Drupal.behaviors.hidey_help = {
  attach: function (d, s) {
    HideyHelp(jQuery, 'div.region-help:first');
  }
};
